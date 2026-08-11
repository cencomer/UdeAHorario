# Horario UdeA - Pedagogía en Ruralidad y Paz

Progressive Web App (PWA) para consultar el horario de clases del programa **Pedagogía en Ruralidad y Paz** de la Universidad de Antioquia, Campus Carmen de Viboral.

🌐 **Producción:** [ruralidadypaz.inteligencia.com.co](https://ruralidadypaz.inteligencia.com.co)

## Características

- 📱 PWA instalable en cualquier dispositivo (iOS, Android, Desktop)
- 🌙 Modo claro y oscuro (detecta preferencia del sistema)
- 📅 Vista de calendario mensual interactivo con modal de detalle
- 📋 Vista de agenda con accordion por mes
- 📖 Vista de materias con detalle por docente
- 🔍 Filtros por materia y modalidad (Presencial / Virtual)
- 💾 Estado persistido en localStorage (filtros, vista, mes)
- 🔌 Funciona 100% offline (Service Worker con cache-first)
- ♿ Accesible (ARIA roles, focus-visible, contraste)
- 🍎 UI siguiendo Apple Human Interface Guidelines

## Estructura del proyecto

```
├── index.html          # HTML + CSS (estructura y estilos)
├── manifest.json       # Configuración PWA
├── sw.js               # Service Worker (cache offline)
├── Dockerfile          # Contenedor Docker (nginx)
├── .dockerignore       # Exclusiones del build
├── icons/
│   └── logo-app.png    # Logo/ícono de la aplicación
├── js/
│   ├── app.js          # Iconos SVG inline (Lucide)
│   ├── data.js         # Cargador de datos + helpers + estado
│   └── views.js        # Renderizado de vistas + navegación
└── data/
    ├── data.json       # Base de datos de la app
    ├── Docentes.csv    # Fuente original (respaldo)
    ├── Horario.csv
    └── Sesiones.csv
```

## Despliegue

### Docker (recomendado)

```bash
docker build -t horario-udea .
docker run -d --name horario-udea -p 8080:80 horario-udea
```

Acceder en: `http://localhost:8080`

### Servidor estático

Copiar al servidor los siguientes archivos/carpetas:

```
index.html
manifest.json
sw.js
icons/
js/
data/data.json
```

Funciona con cualquier servidor HTTP (nginx, Apache, Netlify, GitHub Pages, etc).

### Hosting compartido

Subir por FTP los archivos anteriores a la raíz del dominio `ruralidadypaz.inteligencia.com.co`.

## Actualizar datos

Para un nuevo semestre, editar únicamente `data/data.json`:

```json
{
  "programa": { ... },
  "docentes": [ ... ],
  "materias": [ ... ],
  "sesiones": [ ... ]
}
```

No se requiere modificar ningún archivo de código.

## Stack tecnológico

- HTML5 + CSS3 (variables CSS, grid, flexbox)
- JavaScript vanilla (ES2017+, async/await)
- SVG inline para iconos (zero dependencies)
- Service Worker API (cache offline)
- Web App Manifest (PWA)
- Docker + nginx:alpine (producción)

## Autor

**Luis Cabezas**  
Estudiante - Pedagogía en Ruralidad y Paz  
Universidad de Antioquia  

🌐 [Inteligencia.com.co](https://inteligencia.com.co)

## Licencia

Uso académico. Todos los derechos reservados.
