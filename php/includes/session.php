<?php
/**
 * session.php - Persistencia de selección del usuario via cookies
 * IMPORTANTE: Debe incluirse ANTES de cualquier output HTML
 * 
 * @author Luis Cabezas - Inteligencia.com.co
 */

define('COOKIE_DURATION', 60 * 60 * 24 * 30); // 30 días

/**
 * Guarda la cohorte seleccionada en cookie (solo si no hay output)
 */
function saveCohorte(string $cohorteId): void {
    if (!headers_sent()) {
        setcookie('udea_cohorte', $cohorteId, time() + COOKIE_DURATION, '/');
    }
    $_COOKIE['udea_cohorte'] = $cohorteId;
}

/**
 * Obtiene la cohorte guardada
 */
function getSavedCohorte(): string {
    return $_COOKIE['udea_cohorte'] ?? '';
}

/**
 * Guarda filtros en cookies (solo si no hay output)
 */
function saveFilter(string $key, string $value): void {
    if (!headers_sent()) {
        setcookie('udea_' . $key, $value, time() + COOKIE_DURATION, '/');
    }
    $_COOKIE['udea_' . $key] = $value;
}

/**
 * Obtiene un filtro guardado
 */
function getSavedFilter(string $key, string $default = 'todas'): string {
    return $_COOKIE['udea_' . $key] ?? $default;
}

/**
 * Resuelve la cohorte actual: prioridad URL > cookie
 */
function resolveCohorte(): string {
    $fromUrl = $_GET['c'] ?? '';
    if ($fromUrl) {
        saveCohorte($fromUrl);
        return $fromUrl;
    }
    return getSavedCohorte();
}

/**
 * Resuelve un filtro: prioridad URL > cookie
 */
function resolveFilter(string $key, string $default = 'todas'): string {
    $fromUrl = $_GET[$key] ?? '';
    if ($fromUrl) {
        saveFilter($key, $fromUrl);
        return $fromUrl;
    }
    return getSavedFilter($key, $default);
}

/**
 * Limpia todas las cookies de sesión (logout)
 */
function clearSession(): void {
    $keys = ['udea_cohorte', 'udea_materia', 'udea_modalidad'];
    foreach ($keys as $k) {
        setcookie($k, '', time() - 3600, '/');
        unset($_COOKIE[$k]);
    }
}
