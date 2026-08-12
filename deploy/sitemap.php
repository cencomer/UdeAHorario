<?php
/**
 * sitemap.php - Genera sitemap XML dinámico desde la base de datos
 * @author Luis Cabezas - Inteligencia.com.co
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=utf-8');
$base = 'https://regudea.inteligencia.com.co';
$cohortes = DB::query('SELECT id FROM cohortes');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc><?=$base?>/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
<?php foreach ($cohortes as $c): ?>
<url><loc><?=$base?>/inicio.php?c=<?=$c['id']?></loc><changefreq>weekly</changefreq><priority>0.8</priority></url>
<url><loc><?=$base?>/calendario.php?c=<?=$c['id']?></loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
<url><loc><?=$base?>/materias.php?c=<?=$c['id']?></loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
<?php endforeach; ?>
</urlset>

