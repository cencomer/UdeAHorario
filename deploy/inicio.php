<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

$cohorteId = resolveCohorte();
if (!$cohorteId) { header('Location: /'); exit; }

$cohorte = DB::getCohorte($cohorteId);
if (!$cohorte) { header('Location: /'); exit; }

$campus = DB::one('SELECT nombre FROM campus WHERE id = :id', [':id' => $cohorte['campus_id']]);
$programa = DB::one('SELECT nombre FROM programas WHERE id = :id', [':id' => $cohorte['programa_id']]);

// Filtros (persisten en cookies)
$materias = DB::getMaterias($cohorteId);
$currentMateria = resolveFilter('materia');
$currentModalidad = resolveFilter('modalidad');

// Datos
$resumen = DB::getResumen($cohorteId);
$proxima = DB::getProximaClase($cohorteId);
$sesiones = DB::getSesiones($cohorteId, $currentMateria, $currentModalidad);

// Agrupar por mes
$byMonth = [];
foreach ($sesiones as $s) {
    $key = substr($s['fecha'], 0, 7); // YYYY-MM
    $byMonth[$key][] = $s;
}

$pageTitle = 'Inicio';
$subtitle = ($programa['nombre'] ?? '') . ' · Cohorte ' . $cohorte['cohorte'] . ' · ' . ($campus['nombre'] ?? '');
$currentPage = 'inicio';
$baseUrl = "?c=$cohorteId&materia=$currentMateria";

$MESES = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
$DIAS = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/filters.php';
?>

<!-- Header para impresión PDF -->
<div class="print-header">
<img src="/icons/logo-app.png" alt="Logo">
<h2><?= htmlspecialchars($programa['nombre'] ?? '') ?></h2>
<p>Cohorte <?= $cohorte['cohorte'] ?> · <?= htmlspecialchars($campus['nombre'] ?? '') ?> · Universidad de Antioquia</p>
<p>Semestre 2026-2 · Generado: <?= date('d/m/Y') ?></p>
</div>

<!-- Botón exportar PDF -->
<button class="btn-pdf" onclick="exportPDF()">
<?= icon('file-text', 14) ?> Exportar PDF
</button>

<?php if ($proxima): ?>
<div class="next-class">
<div class="lbl">Próxima clase</div>
<div class="cn"><?= htmlspecialchars($proxima['materia_nombre']) ?></div>
<div class="cm"><?= icon('calendar', 14) ?> <?= $DIAS[date('w', strtotime($proxima['fecha']))] ?> <?= date('d', strtotime($proxima['fecha'])) ?> de <?= $MESES[date('m', strtotime($proxima['fecha']))] ?> · <?= htmlspecialchars($proxima['resumen_horarios'] ?? '') ?></div>
<?php if ($proxima['docente_nombre']): ?><div class="cm"><?= icon('user', 14) ?> <?= htmlspecialchars($proxima['docente_nombre']) ?></div><?php endif; ?>
<span class="cb"><?= $proxima['modalidad'] === 'Pre' ? icon('building', 12) . ' Presencial' : icon('monitor', 12) . ' Virtual' ?></span>
</div>
<?php endif; ?>

<div class="stats-grid">
<div class="stat-box"><div class="value"><?= $resumen['restantes'] ?></div><div class="label">Sesiones restantes</div></div>
<div class="stat-box blue"><div class="value"><?= $resumen['total'] ?></div><div class="label">Total sesiones</div></div>
<div class="stat-box"><div class="value"><?= $resumen['restantes_pre'] ?></div><div class="label"><?= icon('building', 14) ?> Presenciales por ir</div></div>
<div class="stat-box blue"><div class="value"><?= $resumen['restantes_amt'] ?></div><div class="label"><?= icon('monitor', 14) ?> Virtuales por ir</div></div>
</div>

<?php
$pctPre = $resumen['total'] > 0 ? round($resumen['presenciales'] / $resumen['total'] * 100) : 0;
$pctAmt = 100 - $pctPre;
?>
<div style="background:var(--bg-secondary);border-radius:12px;padding:14px 16px;margin-bottom:20px;box-shadow:var(--card-shadow)">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
<span style="font-size:13px;font-weight:600;color:var(--text-secondary)">Distribución</span>
<span style="font-size:12px;color:var(--text-tertiary)"><?= $resumen['total'] ?> sesiones</span></div>
<div style="display:flex;height:8px;border-radius:4px;overflow:hidden;gap:2px">
<div style="flex:<?= $resumen['presenciales'] ?: 1 ?>;background:var(--accent);border-radius:4px"></div>
<div style="flex:<?= $resumen['virtuales'] ?: 1 ?>;background:var(--blue);border-radius:4px"></div></div>
<div style="display:flex;justify-content:space-between;margin-top:8px">
<span style="font-size:12px;color:var(--accent);font-weight:600"><?= icon('building', 12) ?> <?= $resumen['presenciales'] ?> Pre (<?= $pctPre ?>%)</span>
<span style="font-size:12px;color:var(--blue);font-weight:600"><?= icon('monitor', 12) ?> <?= $resumen['virtuales'] ?> AMT (<?= $pctAmt ?>%)</span></div></div>

<?php if (empty($byMonth)): ?>
<div class="empty-state"><p>Sin sesiones para este filtro</p></div>
<?php else: ?>
<?php $currentMonth = date('Y-m'); ?>
<?php foreach ($byMonth as $monthKey => $items): ?>
<?php
$parts = explode('-', $monthKey);
$monthName = $MESES[$parts[1]] ?? $parts[1];
$preCnt = count(array_filter($items, fn($s) => $s['modalidad'] === 'Pre'));
$amtCnt = count($items) - $preCnt;
$isOpen = $monthKey === $currentMonth;
?>
<div class="accordion <?= $isOpen ? 'open' : '' ?>">
<button class="accordion-header">
<div class="ah-left">
<span class="ml"><?= $monthName ?> <?= $parts[0] ?></span>
<span class="mc"><?= count($items) ?> · <?= $preCnt ?> pre · <?= $amtCnt ?> virt</span>
</div>
<span class="accordion-chevron">▶</span>
</button>
<div class="accordion-body"><div class="accordion-body-inner">
<?php foreach ($items as $s): ?>
<?php $dt = strtotime($s['fecha']); ?>
<div class="si">
<div class="si-date"><div class="n"><?= date('d', $dt) ?></div><div class="d"><?= $DIAS[date('w', $dt)] ?></div></div>
<div class="si-info">
<div class="t"><?= htmlspecialchars($s['materia_nombre']) ?></div>
<div class="s"><?= icon('clock', 11) ?> <?= htmlspecialchars($s['resumen_horarios'] ?? '') ?></div>
<div class="s"><?= icon('user', 11) ?> <?= htmlspecialchars($s['docente_nombre'] ?? '') ?></div>
</div>
<span class="badge <?= $s['modalidad'] === 'Pre' ? 'pre' : 'amt' ?>"><?= $s['modalidad'] ?></span>
</div>
<?php endforeach; ?>
</div></div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

