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
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? 2026);

$pageTitle = 'Calendario';
$subtitle = ($programa['nombre'] ?? '') . ' · Cohorte ' . $cohorte['cohorte'] . ' · ' . ($campus['nombre'] ?? '');
$currentPage = 'calendario';
$baseUrl = "?c=$cohorteId&materia=$currentMateria";

$MESES = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
$DIAS_SHORT = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

// Get sessions for this month
$sesiones = DB::getSesiones($cohorteId, $currentMateria, $currentModalidad !== 'todas' ? $currentModalidad : null);
$byDay = [];
foreach ($sesiones as $s) {
    $d = (int)date('j', strtotime($s['fecha']));
    $m = (int)date('n', strtotime($s['fecha']));
    $y = (int)date('Y', strtotime($s['fecha']));
    if ($m === $month && $y === $year) {
        $byDay[$d][] = $s;
    }
}

$firstDay = (int)date('w', mktime(0, 0, 0, $month, 1, $year));
$totalDays = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
$today = (int)date('j');
$todayMonth = (int)date('n');
$todayYear = (int)date('Y');

// Nav
$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/filters.php';
?>

<div class="cal-nav">
<a href="?c=<?=$cohorteId?>&materia=<?=$currentMateria?>&modalidad=<?=$currentModalidad?>&month=<?=$prevMonth?>&year=<?=$prevYear?>">◀</a>
<span class="month"><?= $MESES[str_pad($month, 2, '0', STR_PAD_LEFT)] ?> <?= $year ?></span>
<a href="?c=<?=$cohorteId?>&materia=<?=$currentMateria?>&modalidad=<?=$currentModalidad?>&month=<?=$nextMonth?>&year=<?=$nextYear?>">▶</a>
</div>

<div class="cal-header">
<?php foreach ($DIAS_SHORT as $d): ?><span><?=$d?></span><?php endforeach; ?>
</div>

<div class="cal-body">
<?php for ($i = 0; $i < $firstDay; $i++): ?><div class="cc empty"></div><?php endfor; ?>
<?php for ($day = 1; $day <= $totalDays; $day++):
    $isToday = ($day === $today && $month === $todayMonth && $year === $todayYear);
    $ss = $byDay[$day] ?? null;
    $hasPre = $ss ? count(array_filter($ss, fn($s) => $s['modalidad'] === 'Pre')) > 0 : false;
    $hasAmt = $ss ? count(array_filter($ss, fn($s) => $s['modalidad'] === 'AMT')) > 0 : false;
    $bc = '';
    if ($ss && !$isToday) { $bc = ($hasPre && $hasAmt) ? 'bm' : ($hasPre ? 'bp' : 'ba'); }
    $cls = trim(($isToday ? 'today ' : '') . ($ss ? 'hc ' : '') . $bc);
?>
<div class="cc <?=$cls?>" <?php if($ss): ?>data-day="<?=$day?>"<?php endif; ?>>
<?= $day ?>
<?php if ($ss): ?>
<div class="dots">
<?php foreach ($ss as $s): ?><span class="dot <?=$s['modalidad']==='Pre'?'pre':'amt'?>"></span><?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<?php endfor; ?>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
<div class="modal-sheet">
<div class="modal-handle"></div>
<div class="modal-hdr"><span class="mt" id="modalTitle"></span><button class="modal-close" id="modalClose">✕</button></div>
<div class="modal-body" id="modalBody"></div>
</div>
</div>

<?php
// Prepare day data as JSON for JS modal
$dayDataJson = [];
foreach ($byDay as $d => $items) {
    $dayDataJson[$d] = array_map(function($s) use ($DIAS_SHORT, $MESES, $month, $year) {
        return [
            'materia' => $s['materia_nombre'],
            'horario' => $s['resumen_horarios'] ?? '',
            'docente' => $s['docente_nombre'] ?? '',
            'modalidad' => $s['modalidad'],
        ];
    }, $items);
}
?>

<script>
const dayData = <?= json_encode($dayDataJson) ?>;
const monthName = "<?= $MESES[str_pad($month, 2, '0', STR_PAD_LEFT)] ?>";
const diasShort = <?= json_encode($DIAS_SHORT) ?>;
const year = <?= $year ?>;
const month = <?= $month ?>;

document.querySelectorAll('.cc[data-day]').forEach(cell=>{
cell.addEventListener('click',function(){
const day=parseInt(this.dataset.day);
const items=dayData[day];
if(!items||!items.length)return;

const dt=new Date(year,month-1,day);
const title=diasShort[dt.getDay()]+' '+day+' de '+monthName;
document.getElementById('modalTitle').textContent=title;

const icClock='<svg style="display:inline-block;width:11px;height:11px;vertical-align:middle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
const icUser='<svg style="display:inline-block;width:11px;height:11px;vertical-align:middle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';

let html='';
items.forEach(s=>{
html+=`<div class="si"><div class="si-info"><div class="t">${s.materia}</div><div class="s">${icClock} ${s.horario}</div><div class="s">${icUser} ${s.docente}</div></div><span class="badge ${s.modalidad==='Pre'?'pre':'amt'}">${s.modalidad==='Pre'?'Presencial':'Virtual'}</span></div>`;
});
document.getElementById('modalBody').innerHTML=html;
document.getElementById('modalOverlay').classList.add('open');
});
});

document.getElementById('modalClose').addEventListener('click',()=>{document.getElementById('modalOverlay').classList.remove('open');});
document.getElementById('modalOverlay').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
