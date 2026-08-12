<?php
/**
 * logout.php - Limpia la sesión y redirige al selector
 */
require_once __DIR__ . '/includes/session.php';
clearSession();
header('Location: /?cambiar=1');
exit;

