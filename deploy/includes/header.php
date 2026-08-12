<?php
/**
 * header.php - Componente header reutilizable
 * Incluye: PWA meta tags, SEO dinámico, Schema.org, Open Graph
 * 
 * Variables esperadas:
 *   $pageTitle     - Título de la página
 *   $subtitle      - Subtítulo en el nav
 *   $pageDesc      - Meta description (opcional, usa default)
 *   $currentPage   - Página activa para tab bar
 * 
 * @author Luis Cabezas - Inteligencia.com.co
 */
require_once __DIR__ . '/icons.php';
$pageTitle = $pageTitle ?? 'Horario';
$pageDesc = $pageDesc ?? 'Consulta el horario de clases de la Universidad de Antioquia, sedes regionales. Múltiples campus, programas y cohortes.';
$canonicalUrl = 'https://regudea.inteligencia.com.co';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover,user-scalable=no">

<!-- PWA -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Regiones UdeA">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#34A853" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1c1c1e" media="(prefers-color-scheme: dark)">
<meta name="application-name" content="Regiones UdeA">
<meta name="msapplication-TileColor" content="#34A853">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/icons/logo-app.png">
<link rel="icon" type="image/png" sizes="512x512" href="/icons/logo-app.png">

<!-- SEO -->
<title><?= htmlspecialchars($pageTitle) ?> | Horario UdeA - Universidad de Antioquia</title>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta name="keywords" content="horario, UdeA, Universidad de Antioquia, clases, semestre 2026, sedes regionales, Amalfi, Andes, Apartadó, Carmen de Viboral, Ruralidad y Paz, Ciencias Naturales, Educación Especial, Lengua Castellana">
<meta name="author" content="Luis Cabezas - Inteligencia.com.co">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= $canonicalUrl ?>">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?> | Horario UdeA">
<meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta property="og:url" content="<?= $canonicalUrl ?>">
<meta property="og:image" content="<?= $canonicalUrl ?>/icons/logo-app.png">
<meta property="og:image:width" content="512">
<meta property="og:image:height" content="512">
<meta property="og:locale" content="es_CO">
<meta property="og:site_name" content="Horario UdeA">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?> | Horario UdeA">
<meta name="twitter:description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta name="twitter:image" content="<?= $canonicalUrl ?>/icons/logo-app.png">

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "Horario UdeA",
  "url": "<?= $canonicalUrl ?>",
  "description": "<?= htmlspecialchars($pageDesc) ?>",
  "applicationCategory": "EducationalApplication",
  "operatingSystem": "All",
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "COP" },
  "author": {
    "@type": "Person",
    "name": "Luis Cabezas",
    "url": "https://inteligencia.com.co"
  },
  "provider": {
    "@type": "EducationalOrganization",
    "name": "Universidad de Antioquia",
    "url": "https://www.udea.edu.co"
  }
}
</script>

<!-- Estilos -->
<link rel="stylesheet" href="/css/app.css">

<!-- Tema: aplicar antes del render para evitar flash (FOUC) -->
<script>
(function(){var t=localStorage.getItem('theme')||'auto';if(t!=='auto')document.documentElement.setAttribute('data-theme',t);})();
</script>
</head>
<body>
<nav class="nav-bar" role="navigation" aria-label="Barra de navegación">
<div style="display:flex;align-items:center;gap:12px;padding:10px 16px 10px">
<img src="/icons/logo-app.png" alt="Horario UdeA" style="width:64px;height:64px;border-radius:50%;object-fit:cover;flex-shrink:0" loading="eager">
<div style="flex:1">
<div style="display:flex;align-items:center;justify-content:space-between">
<h1 class="nav-title"><?= htmlspecialchars($pageTitle) ?></h1>
<button class="nav-btn" id="themeBtn" aria-label="Cambiar tema de color"></button>
</div>
<p class="nav-subtitle"><?= htmlspecialchars($subtitle ?? 'Universidad de Antioquia · Sedes regionales') ?></p>
</div>
</div>
</nav>
<main class="content" role="main">

