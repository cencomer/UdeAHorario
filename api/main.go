/**
 * Horario UdeA - API REST
 * Backend en Go para servir datos del horario académico.
 * Protegido con API Key, CORS y Rate Limiting.
 *
 * @author Luis Cabezas - Inteligencia.com.co
 * @version 1.0.0
 */

package main

import (
	"encoding/json"
	"log"
	"net/http"
	"os"
	"sync"
	"time"
)

// ============================================================
// MODELOS
// ============================================================

type Programa struct {
	Nombre      string `json:"nombre"`
	Universidad string `json:"universidad"`
	Campus      string `json:"campus"`
	Semestre    string `json:"semestre"`
}

type Docente struct {
	ID     string `json:"id"`
	Nombre string `json:"nombre"`
}

type Materia struct {
	ID        string `json:"id"`
	Nombre    string `json:"nombre"`
	DocenteID string `json:"docente_id"`
	Dia       string `json:"dia"`
	Hora      string `json:"hora"`
	Icon      string `json:"icon"`
	Color     string `json:"color"`
}

type Sesion struct {
	MateriaID string `json:"materia_id"`
	Fecha     string `json:"fecha"`
	Modalidad string `json:"modalidad"`
}

type Database struct {
	Programa Programa  `json:"programa"`
	Docentes []Docente `json:"docentes"`
	Materias []Materia `json:"materias"`
	Sesiones []Sesion  `json:"sesiones"`
}

type ResumenResponse struct {
	TotalSesiones      int `json:"total_sesiones"`
	TotalPresenciales  int `json:"total_presenciales"`
	TotalVirtuales     int `json:"total_virtuales"`
	SesionesRestantes  int `json:"sesiones_restantes"`
	PresencialesPorIr  int `json:"presenciales_por_ir"`
	VirtualesPorIr     int `json:"virtuales_por_ir"`
}

// ============================================================
// RATE LIMITER
// ============================================================

type RateLimiter struct {
	mu       sync.Mutex
	requests map[string][]time.Time
	limit    int
	window   time.Duration
}

func NewRateLimiter(limit int, window time.Duration) *RateLimiter {
	return &RateLimiter{
		requests: make(map[string][]time.Time),
		limit:    limit,
		window:   window,
	}
}

func (rl *RateLimiter) Allow(ip string) bool {
	rl.mu.Lock()
	defer rl.mu.Unlock()

	now := time.Now()
	cutoff := now.Add(-rl.window)

	// Limpiar requests expirados
	valid := make([]time.Time, 0)
	for _, t := range rl.requests[ip] {
		if t.After(cutoff) {
			valid = append(valid, t)
		}
	}

	if len(valid) >= rl.limit {
		rl.requests[ip] = valid
		return false
	}

	rl.requests[ip] = append(valid, now)
	return true
}

// ============================================================
// SERVIDOR
// ============================================================

var db Database
var dbMu sync.RWMutex
var dataFilePath string

func main() {
	// Cargar datos
	dataFilePath = getEnv("DATA_PATH", "./data/data.json")
	if err := reloadData(); err != nil {
		log.Fatalf("Error cargando datos: %v", err)
	}
	log.Printf("Datos cargados: %d materias, %d sesiones", len(db.Materias), len(db.Sesiones))

	// Configuración
	apiKey := getEnv("API_KEY", "udea-horario-2026-key")
	adminKey := getEnv("ADMIN_KEY", "udea-admin-secret-2026")
	port := getEnv("PORT", "8080")
	allowedOrigins := getEnv("ALLOWED_ORIGINS", "https://ryp.inteligencia.com.co,http://localhost:8080,http://localhost:19006")

	// Rate limiter: 60 requests por minuto por IP
	limiter := NewRateLimiter(60, time.Minute)

	// Router
	mux := http.NewServeMux()

	// Middleware chain
	handler := corsMiddleware(allowedOrigins,
		apiKeyMiddleware(apiKey,
			rateLimitMiddleware(limiter, mux)))

	// Rutas públicas (requieren API key)
	mux.HandleFunc("GET /api/health", handleHealth)
	mux.HandleFunc("GET /api/programa", handlePrograma)
	mux.HandleFunc("GET /api/docentes", handleDocentes)
	mux.HandleFunc("GET /api/materias", handleMaterias)
	mux.HandleFunc("GET /api/sesiones", handleSesiones)
	mux.HandleFunc("GET /api/resumen", handleResumen)

	// Ruta admin (requiere ADMIN_KEY)
	mux.HandleFunc("POST /api/admin/reload", adminKeyMiddleware(adminKey, handleReload))

	log.Printf("API iniciada en :%s", port)
	log.Fatal(http.ListenAndServe(":"+port, handler))
}

// ============================================================
// HANDLERS
// ============================================================

func handleHealth(w http.ResponseWriter, r *http.Request) {
	jsonResponse(w, map[string]string{"status": "ok", "version": "1.0.0"})
}

func handlePrograma(w http.ResponseWriter, r *http.Request) {
	dbMu.RLock()
	defer dbMu.RUnlock()
	jsonResponse(w, db.Programa)
}

func handleDocentes(w http.ResponseWriter, r *http.Request) {
	dbMu.RLock()
	defer dbMu.RUnlock()
	jsonResponse(w, db.Docentes)
}

func handleMaterias(w http.ResponseWriter, r *http.Request) {
	dbMu.RLock()
	defer dbMu.RUnlock()
	jsonResponse(w, db.Materias)
}

func handleSesiones(w http.ResponseWriter, r *http.Request) {
	dbMu.RLock()
	defer dbMu.RUnlock()

	materia := r.URL.Query().Get("materia")
	modalidad := r.URL.Query().Get("modalidad")

	result := make([]Sesion, 0)
	for _, s := range db.Sesiones {
		if materia != "" && s.MateriaID != materia {
			continue
		}
		if modalidad != "" && s.Modalidad != modalidad {
			continue
		}
		result = append(result, s)
	}

	jsonResponse(w, result)
}

func handleResumen(w http.ResponseWriter, r *http.Request) {
	dbMu.RLock()
	defer dbMu.RUnlock()

	today := time.Now().Format("2006-01-02")

	var res ResumenResponse
	res.TotalSesiones = len(db.Sesiones)

	for _, s := range db.Sesiones {
		if s.Modalidad == "Pre" {
			res.TotalPresenciales++
		} else {
			res.TotalVirtuales++
		}
		if s.Fecha >= today {
			res.SesionesRestantes++
			if s.Modalidad == "Pre" {
				res.PresencialesPorIr++
			} else {
				res.VirtualesPorIr++
			}
		}
	}

	jsonResponse(w, res)
}

// ============================================================
// MIDDLEWARE
// ============================================================

func corsMiddleware(origins string, next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, X-API-Key")
		w.Header().Set("Access-Control-Max-Age", "86400")

		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusNoContent)
			return
		}
		next.ServeHTTP(w, r)
	})
}

func apiKeyMiddleware(key string, next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// Health check no requiere API key
		if r.URL.Path == "/api/health" {
			next.ServeHTTP(w, r)
			return
		}

		provided := r.Header.Get("X-API-Key")
		if provided == "" {
			provided = r.URL.Query().Get("api_key")
		}

		if provided != key {
			http.Error(w, `{"error":"unauthorized","message":"API key inválida o no proporcionada"}`, http.StatusUnauthorized)
			return
		}
		next.ServeHTTP(w, r)
	})
}

func rateLimitMiddleware(rl *RateLimiter, next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		ip := r.RemoteAddr
		if forwarded := r.Header.Get("X-Forwarded-For"); forwarded != "" {
			ip = forwarded
		}

		if !rl.Allow(ip) {
			http.Error(w, `{"error":"rate_limit","message":"Demasiadas peticiones. Intenta en un minuto."}`, http.StatusTooManyRequests)
			return
		}
		next.ServeHTTP(w, r)
	})
}

// ============================================================
// UTILIDADES
// ============================================================

func loadData(path string) error {
	file, err := os.ReadFile(path)
	if err != nil {
		return err
	}
	return json.Unmarshal(file, &db)
}

/**
 * reloadData re-lee el data.json del disco y actualiza la base de datos en memoria.
 * Thread-safe gracias al RWMutex.
 */
func reloadData() error {
	file, err := os.ReadFile(dataFilePath)
	if err != nil {
		return err
	}
	var newDB Database
	if err := json.Unmarshal(file, &newDB); err != nil {
		return err
	}
	dbMu.Lock()
	db = newDB
	dbMu.Unlock()
	return nil
}

/**
 * handleReload - Endpoint admin para recargar datos en caliente.
 * POST /api/admin/reload
 */
func handleReload(w http.ResponseWriter, r *http.Request) {
	if err := reloadData(); err != nil {
		http.Error(w, `{"error":"reload_failed","message":"`+err.Error()+`"}`, http.StatusInternalServerError)
		return
	}
	dbMu.RLock()
	count := len(db.Sesiones)
	dbMu.RUnlock()
	log.Printf("Datos recargados: %d sesiones", count)
	jsonResponse(w, map[string]interface{}{
		"status":   "ok",
		"message":  "Datos recargados exitosamente",
		"sesiones": count,
	})
}

/**
 * adminKeyMiddleware protege rutas admin con una clave separada.
 */
func adminKeyMiddleware(key string, next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		provided := r.Header.Get("X-Admin-Key")
		if provided != key {
			http.Error(w, `{"error":"forbidden","message":"Acceso admin denegado"}`, http.StatusForbidden)
			return
		}
		next(w, r)
	}
}

func jsonResponse(w http.ResponseWriter, data interface{}) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	json.NewEncoder(w).Encode(data)
}

func getEnv(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}
