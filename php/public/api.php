<?php
/**
 * api.php - Endpoint JSON para apps (Expo, etc.)
 * Protegido con API Key. Misma base SQLite que las páginas web.
 * 
 * Endpoints:
 *   GET /api.php?action=health
 *   GET /api.php?action=campus
 *   GET /api.php?action=programas&campus=amalfi
 *   GET /api.php?action=cohortes&campus=amalfi&programa=ruralidad-y-paz
 *   GET /api.php?action=materias&c=ID_COHORTE
 *   GET /api.php?action=sesiones&c=ID_COHORTE[&materia=X&modalidad=Pre]
 *   GET /api.php?action=resumen&c=ID_COHORTE
 *   GET /api.php?action=proxima&c=ID_COHORTE
 * 
 * @author Luis Cabezas - Inteligencia.com.co
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/db.php';

// ─── Configuración ───────────────────────────────────────────────────────────
define('API_KEY', getenv('API_KEY') ?: 'udea-horario-2026-key');
define('ALLOWED_ORIGINS', getenv('ALLOWED_ORIGINS') ?: 'https://ryp.inteligencia.com.co,http://localhost:8081,http://localhost:19006');

// ─── CORS ────────────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Autenticación ───────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

// Health check no requiere key
if ($action !== 'health') {
    $key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
    if ($key !== API_KEY) {
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized', 'message' => 'API key inválida o no proporcionada']);
        exit;
    }
}

// ─── Rate Limiting (simple, basado en sesión de IP) ──────────────────────────
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ratefile = sys_get_temp_dir() . '/horario_rate_' . md5($ip);
$now = time();
$window = 60; // 1 minuto
$limit = 60;  // 60 requests

$requests = [];
if (file_exists($ratefile)) {
    $requests = json_decode(file_get_contents($ratefile), true) ?: [];
    $requests = array_filter($requests, fn($t) => $t > ($now - $window));
}

if (count($requests) >= $limit) {
    http_response_code(429);
    echo json_encode(['error' => 'rate_limit', 'message' => 'Demasiadas peticiones. Intenta en un minuto.']);
    exit;
}

$requests[] = $now;
file_put_contents($ratefile, json_encode($requests));

// ─── Router ──────────────────────────────────────────────────────────────────
switch ($action) {
    case 'health':
        respond(['status' => 'ok', 'version' => '1.0.0', 'engine' => 'php']);
        break;

    case 'campus':
        respond(DB::getCampusList());
        break;

    case 'programas':
        $campus = $_GET['campus'] ?? '';
        if ($campus) {
            respond(DB::getProgramasByCampus($campus));
        } else {
            respond(DB::getProgramasList());
        }
        break;

    case 'cohortes':
        $campus = $_GET['campus'] ?? '';
        $programa = $_GET['programa'] ?? '';
        if (!$campus || !$programa) {
            error(400, 'Parámetros campus y programa requeridos');
        }
        respond(DB::getCohortes($campus, $programa));
        break;

    case 'materias':
        $c = $_GET['c'] ?? '';
        if (!$c) error(400, 'Parámetro c (cohorte_id) requerido');
        respond(DB::getMaterias($c));
        break;

    case 'sesiones':
        $c = $_GET['c'] ?? '';
        if (!$c) error(400, 'Parámetro c (cohorte_id) requerido');
        $materia = $_GET['materia'] ?? null;
        $modalidad = $_GET['modalidad'] ?? null;
        respond(DB::getSesiones($c, $materia, $modalidad));
        break;

    case 'resumen':
        $c = $_GET['c'] ?? '';
        if (!$c) error(400, 'Parámetro c (cohorte_id) requerido');
        respond(DB::getResumen($c));
        break;

    case 'proxima':
        $c = $_GET['c'] ?? '';
        if (!$c) error(400, 'Parámetro c (cohorte_id) requerido');
        $proxima = DB::getProximaClase($c);
        respond($proxima ?: ['message' => 'No hay clases próximas']);
        break;

    default:
        error(400, 'Acción no válida. Acciones disponibles: health, campus, programas, cohortes, materias, sesiones, resumen, proxima');
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function respond($data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function error(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['error' => 'bad_request', 'message' => $message]);
    exit;
}
