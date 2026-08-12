<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$cohorteId = resolveCohorte();
if (!$cohorteId) { header('Location: /'); exit; }

$cohorte = DB::getCohorte($cohorteId);
if (!$cohorte) { header('Location: /'); exit; }

$campus = DB::one('SELECT nombre FROM campus WHERE id = :id', [':id' => $cohorte['campus_id']]);
$programa = DB::one('SELECT nombre FROM programas WHERE id = :id', [':id' => $cohorte['programa_id']]);

$materias = DB::getMaterias($cohorteId);
$currentMateria = resolveFilter('materia');
$currentModalidad = resolveFilter('modalidad');

$pageTitle = 'Materias';
$subtitle = ($programa['nombre'] ?? '') . ' · Cohorte ' . $cohorte['cohorte'] . ' · ' . ($campus['nombre'] ?? '');
$currentPage = 'materias';
$baseUrl = "?c=$cohorteId&materia=$currentMateria";

$MESES = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
$DIAS = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/filters.php';

$showMaterias = $currentMateria === 'todas' ? $materias : array_filter($materias, fn($m) => $m['id'] === $currentMateria);
?>

<?php if (empty($showMaterias)): ?>
<div class="empty-state"><p>Sin resultados</p></div>
<?php endif; ?>

<?php foreach ($showMaterias as $mat): ?>
<?php
$sesiones = DB::getSesiones($cohorteId, $mat['id'], $currentModalidad !== 'todas' ? $currentModalidad : null);
$preCnt = count(array_filter($sesiones, fn($s) => $s['modalidad'] === 'Pre'));
$amtCnt = count($sesiones) - $preCnt;
$byMonth = [];
foreach ($sesiones as $s) { $byMonth[substr($s['fecha'], 0, 7)][] = $s; }
?>
<div class="materia-card">
<div class="mh">
<div class="mi" style="background:<?=$mat['color']?>20;color:<?=$mat['color']?>"><?= icon($mat['icon'] ?? 'book-open', 20) ?></div>
<div>
<h3 style="font-size:15px;font-weight:600;line-height:1.3"><?= htmlspecialchars($mat['nombre']) ?></h3>
<p style="font-size:13px;color:var(--text-tertiary);margin-top:2px"><?= htmlspecialchars($mat['docente_nombre'] ?? '') ?></p>
</div>
</div>
<div class="mm">
<span class="mc2"><?= icon('clock', 12) ?> <?= htmlspecialchars($mat['resumen_horarios'] ?? '') ?></span>
<span class="mc2"><?= icon('building', 12) ?> <?= $preCnt ?> pre</span>
<span class="mc2"><?= icon('monitor', 12) ?> <?= $amtCnt ?> virt</span>
<?php if (!empty($mat['cupo'])): ?><span class="mc2"><?= icon('user', 12) ?> Cupo: <?= $mat['cupo'] ?></span><?php endif; ?>
</div>

<?php foreach ($byMonth as $mk => $items): ?>
<?php $parts = explode('-', $mk); $mn = $MESES[$parts[1]] ?? $parts[1]; ?>
<div class="accordion" style="margin-bottom:8px;box-shadow:none;background:var(--bg-tertiary)">
<button class="accordion-header" style="padding:10px 12px">
<div class="ah-left"><span class="ml" style="font-size:13px"><?=$mn?></span><span class="mc" style="font-size:11px"><?=count($items)?> sesiones</span></div>
<span class="accordion-chevron">▶</span>
</button>
<div class="accordion-body"><div class="accordion-body-inner" style="padding:10px 12px"><div style="display:flex;flex-wrap:wrap;gap:6px">
<?php foreach ($items as $s): ?>
<?php $dt = strtotime($s['fecha']); ?>
<span class="tc <?=$s['modalidad']==='Pre'?'pre':'amt'?>"><?=$s['modalidad']==='Pre'?icon('building',11):icon('monitor',11)?> <?=$DIAS[date('w',$dt)]?> <?=date('d',$dt)?></span>
<?php endforeach; ?>
</div></div></div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
