<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$cohorteId = resolveCohorte();
$cohorte = $cohorteId ? DB::getCohorte($cohorteId) : null;
$campus = $cohorte ? DB::one('SELECT nombre FROM campus WHERE id = :id', [':id' => $cohorte['campus_id']]) : null;
$programa = $cohorte ? DB::one('SELECT nombre FROM programas WHERE id = :id', [':id' => $cohorte['programa_id']]) : null;
$resumen = $cohorte ? DB::getResumen($cohorteId) : null;

$pageTitle = 'Ajustes';
$subtitle = $cohorte ? ($programa['nombre'] ?? '') . ' · Cohorte ' . $cohorte['cohorte'] . ' · ' . ($campus['nombre'] ?? '') : '';
$currentPage = 'ajustes';
include __DIR__ . '/../includes/header.php';
?>

<div class="card-group-title">Apariencia</div>
<div class="card-group">
<div class="sr"><span>Modo oscuro</span><button class="btn btn-green" onclick="applyTheme(getTheme()==='dark'?'light':'dark');location.reload();" id="toggleDark">Cambiar</button></div>
</div>

<?php if ($cohorte): ?>
<div class="card-group-title">Cohorte activa</div>
<div class="card-group">
<div class="sr"><span>Campus</span><span style="color:var(--text-tertiary);font-size:14px"><?= htmlspecialchars($campus['nombre'] ?? '') ?></span></div>
<div class="sr"><span>Programa</span><span style="color:var(--text-tertiary);font-size:14px"><?= htmlspecialchars($programa['nombre'] ?? '') ?></span></div>
<div class="sr"><span>Cohorte</span><span style="color:var(--text-tertiary);font-size:14px"><?= $cohorte['cohorte'] ?></span></div>
<div class="sr"><span>Materias</span><span style="color:var(--text-tertiary);font-size:14px"><?= count(DB::getMaterias($cohorteId)) ?></span></div>
<div class="sr"><span>Sesiones</span><span style="color:var(--text-tertiary);font-size:14px"><?= $resumen['total'] ?></span></div>
<div class="sr"><span>Cambiar cohorte</span><a href="/?cambiar=1" class="btn btn-orange">Cambiar</a></div>
</div>
<?php endif; ?>

<div class="card-group-title">Compartir</div>
<div class="card-group">
<div class="sr"><span>Compartir app</span><button class="btn btn-green" onclick="if(navigator.share)navigator.share({title:'Horario UdeA',url:'https://ryp.inteligencia.com.co'});else{navigator.clipboard.writeText('https://ryp.inteligencia.com.co');this.textContent='¡Copiado!'}">Compartir</button></div>
</div>

<div class="card-group-title">Leyenda</div>
<div class="card-group">
<div class="sr"><span><?= icon('building', 16) ?> Presencial (Pre)</span><span class="badge pre">Pre</span></div>
<div class="sr"><span><?= icon('monitor', 16) ?> Virtual (AMT)</span><span class="badge amt">AMT</span></div>
</div>

<div class="card-group-title">App</div>
<div class="card-group">
<div class="sr"><span>Versión</span><span style="color:var(--text-tertiary);font-size:14px">2.0.0 PHP</span></div>
</div>

<div style="text-align:center;padding:32px 16px 16px;color:var(--text-tertiary);font-size:12px;line-height:1.8">
<div style="font-weight:600;color:var(--text-secondary)">Desarrollado por Luis Cabezas</div>
<div>Estudiante</div>
<div><a href="https://inteligencia.com.co" target="_blank" style="color:var(--accent);text-decoration:none;font-weight:500">Inteligencia.com.co</a></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
