<?php
/**
 * header.php - Componente header reutilizable
 */
require_once __DIR__ . '/icons.php';
$pageTitle = $pageTitle ?? 'Inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover,user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Horario UdeA">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1c1c1e" media="(prefers-color-scheme: dark)">
<meta name="description" content="Horario de clases - Universidad de Antioquia - Sedes regionales">
<meta property="og:title" content="Horario UdeA">
<meta property="og:description" content="Consulta tu horario de clases. Universidad de Antioquia.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://ryp.inteligencia.com.co/">
<meta property="og:image" content="https://ryp.inteligencia.com.co/icons/logo-app.png">
<link rel="canonical" href="https://ryp.inteligencia.com.co/">
<link rel="icon" type="image/png" href="/icons/logo-app.png">
<link rel="apple-touch-icon" href="/icons/logo-app.png">
<link rel="manifest" href="/manifest.json">
<link rel="stylesheet" href="/css/app.css">
<title><?= htmlspecialchars($pageTitle) ?> - Horario UdeA</title>
<script>
// Aplica tema antes del render para evitar parpadeo (FOUC)
(function(){var t=localStorage.getItem('theme')||'auto';if(t!=='auto')document.documentElement.setAttribute('data-theme',t);})();
</script>
</head>
<body>
<nav class="nav-bar">
<div style="display:flex;align-items:center;gap:12px;padding:10px 16px 10px">
<img src="/icons/logo-app.png" alt="Logo" style="width:64px;height:64px;border-radius:50%;object-fit:cover;flex-shrink:0">
<div style="flex:1">
<div style="display:flex;align-items:center;justify-content:space-between">
<div class="nav-title"><?= htmlspecialchars($pageTitle) ?></div>
<button class="nav-btn" id="themeBtn" aria-label="Cambiar tema"></button>
</div>
<div class="nav-subtitle"><?= htmlspecialchars($subtitle ?? 'Selecciona tu horario') ?></div>
</div>
</div>
</nav>
<main class="content">
