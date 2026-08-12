<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

// Si ya hay cohorte guardada, ir directo al inicio
$saved = getSavedCohorte();
if ($saved && !isset($_GET['cambiar']) && DB::getCohorte($saved)) {
    header('Location: /inicio.php');
    exit;
}

$campusList = DB::getCampusList();
$programasList = DB::getProgramasList();

$pageTitle = 'Seleccionar horario';
$subtitle = 'Universidad de Antioquia · Sedes regionales';
$currentPage = '';
include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom:20px">
<h2 style="font-size:18px;font-weight:700;color:var(--text-primary);margin-bottom:4px">Facultad de Educación</h2>
<p style="font-size:14px;color:var(--text-secondary);margin-bottom:12px">Consulta Programación Programas Regiones, Madre Tierra y Primaria</p>
<p style="font-size:13px;color:var(--text-tertiary)">Selecciona tu campus, programa y cohorte para ver el horario.</p>
</div>

<form method="GET" action="/inicio.php">
<div class="card-group-title">Campus</div>
<input type="hidden" name="campus" id="campusVal" value="">
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:20px">
<?php foreach ($campusList as $c): ?>
<button type="button" class="campus-btn" data-campus="<?=$c['id']?>" style="padding:14px 12px;border:2px solid var(--separator);border-radius:12px;background:var(--bg-secondary);color:var(--text-primary);font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .2s;text-align:center">
<svg style="display:block;margin:0 auto 6px;width:24px;height:24px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
<?=htmlspecialchars($c['nombre'])?>
</button>
<?php endforeach; ?>
</div>

<div class="card-group-title">Programa</div>
<select class="filter-select" name="programa" id="selPrograma" style="margin-bottom:16px;width:100%" required disabled>
<option value="">Seleccionar programa...</option>
</select>

<div class="card-group-title">Cohorte</div>
<select class="filter-select" name="c" id="selCohorte" style="margin-bottom:16px;width:100%" required disabled>
<option value="">Seleccionar cohorte...</option>
</select>

<button type="submit" class="btn btn-green btn-full" id="btnGo" disabled style="opacity:0.5">Ver horario</button>
</form>

<script>
const data = <?= json_encode(['campus'=>$campusList, 'cohortes'=>DB::query('SELECT id, campus_id, programa_id, cohorte FROM cohortes ORDER BY cohorte DESC')]) ?>;
const progMap = <?= json_encode(array_column($programasList, 'nombre', 'id')) ?>;

const campusVal=document.getElementById('campusVal');
const selP=document.getElementById('selPrograma'),selCo=document.getElementById('selCohorte'),btn=document.getElementById('btnGo');

// Campus buttons
document.querySelectorAll('.campus-btn').forEach(b=>{
b.addEventListener('click',function(){
document.querySelectorAll('.campus-btn').forEach(x=>{x.style.borderColor='var(--separator)';x.style.background='var(--bg-secondary)';x.style.color='var(--text-primary)';});
this.style.borderColor='var(--accent)';this.style.background='var(--accent-light)';this.style.color='var(--accent)';
campusVal.value=this.dataset.campus;
selP.innerHTML='<option value="">Seleccionar programa...</option>';
selCo.innerHTML='<option value="">Seleccionar cohorte...</option>';
selP.disabled=false;selCo.disabled=true;btn.disabled=true;btn.style.opacity='0.5';
const progs=new Set();
data.cohortes.filter(c=>c.campus_id===campusVal.value).forEach(c=>progs.add(c.programa_id));
progs.forEach(pid=>{if(progMap[pid])selP.innerHTML+='<option value="'+pid+'">'+progMap[pid]+'</option>';});
});
});

selP.addEventListener('change',()=>{
selCo.innerHTML='<option value="">Seleccionar cohorte...</option>';
selCo.disabled=!selP.value;btn.disabled=true;btn.style.opacity='0.5';
if(!selP.value)return;
data.cohortes.filter(c=>c.campus_id===campusVal.value&&c.programa_id===selP.value).forEach(c=>{
selCo.innerHTML+='<option value="'+c.id+'">Cohorte '+c.cohorte+'</option>';});
});

selCo.addEventListener('change',()=>{btn.disabled=!selCo.value;btn.style.opacity=selCo.value?'1':'0.5';});
</script>

<?php // Sin tab bar en el selector — no hay cohorte seleccionada ?>
</main>
<script src="/js/app.js"></script>
</body>
</html>

