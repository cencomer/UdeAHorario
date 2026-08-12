# Horario UdeA - Sedes Regionales

Sistema de consulta de horarios académicos para la Universidad de Antioquia, sedes regionales. Soporta múltiples campus, programas y cohortes.

🌐 **Producción:** [ryp.inteligencia.com.co](https://ryp.inteligencia.com.co)

## Arquitectura

```
├── php/          → Aplicación web (PHP + SQLite) - Para hosting compartido
├── api/          → API REST (Go) - Para app móvil Expo / servidores
├── data/         → Fuentes de datos (CSVs + JSON master)
└── scripts/      → Herramientas de procesamiento
```

## Aplicación Web (PHP)

Aplicación server-rendered con diseño Apple HIG. Compatible con cualquier hosting compartido con PHP 7.4+.

### Características
- Multi-campus, multi-programa, multi-cohorte
- Vista agenda con accordion por mes
- Calendario interactivo con modal de detalle
- Fichas de materias con sesiones agrupadas
- Filtros por materia y modalidad (Presencial / Virtual)
- Modo claro y oscuro
- Persistencia de selección via cookies
- Diseño responsive (móvil, tablet, desktop)
- Iconos SVG inline (sin CDN)
- API JSON integrada para apps móviles

### Deploy en Hosting Compartido

1. Sube el contenido de `php/public/` a la raíz de tu dominio
2. Sube `php/includes/` fuera del document root (o dentro, junto a public)
3. Sube `php/database/horario.db` 
4. Verifica que la ruta en `includes/db.php` apunte correctamente a `horario.db`

**Estructura en el servidor:**
```
public_html/             (o www/ o htdocs/)
├── index.php
├── inicio.php
├── calendario.php
├── materias.php
├── ajustes.php
├── api.php
├── logout.php
├── .htaccess
├── css/app.css
├── js/app.js
├── icons/logo-app.png
└── includes/            (mover aquí)
    ├── db.php
    ├── header.php
    ├── footer.php
    ├── tabbar.php
    ├── filters.php
    ├── icons.php
    └── session.php
database/
    └── horario.db
```

### Docker (desarrollo local)

```bash
# Levantar con volumen (cambios instantáneos)
docker run -d --name horario-php -p 8081:80 \
  -v $(pwd)/php:/app -w /app/public \
  php:8.3-cli php -S 0.0.0.0:80
```

## API REST (Go)

API independiente para ser consumida por la app Expo u otros clientes.

### Endpoints

| Método | Ruta | Auth | Descripción |
|--------|------|:---:|-------------|
| GET | /api/health | ❌ | Health check |
| GET | /api/programa | ✅ | Info del programa |
| GET | /api/docentes | ✅ | Lista de docentes |
| GET | /api/materias | ✅ | Lista de materias |
| GET | /api/sesiones | ✅ | Sesiones (filtrable) |
| GET | /api/resumen | ✅ | Estadísticas |
| POST | /api/admin/reload | 🔒 | Recargar datos en caliente |

### Docker

```bash
cd api
docker build -t horario-api .
docker run -d --name horario-api -p 9090:8080 \
  -e API_KEY=tu-clave-secreta \
  -e ADMIN_KEY=tu-clave-admin \
  horario-api
```

## Actualizar Datos

1. Editar los CSVs en `data/`
2. Ejecutar `python scripts/parse_csv.py` → genera `data/data.json`
3. Para PHP: ejecutar `php php/database/import.php` → actualiza `horario.db`
4. Para Go API: `POST /api/admin/reload` con header `X-Admin-Key`

## Stack

- **Frontend:** PHP 7.4+ server-rendered, CSS variables, JS vanilla
- **Base de datos:** SQLite (archivo embebido)
- **API:** Go 1.22 (binario estático)
- **Iconos:** SVG inline (Lucide)
- **Deploy:** Hosting compartido / Docker

## Autor

**Luis Cabezas**  
Estudiante - Pedagogía en Ruralidad y Paz  
Universidad de Antioquia  
🌐 [Inteligencia.com.co](https://inteligencia.com.co)
