<?php
/**
 * filters.php - Componente de filtros reutilizable
 * Variables esperadas: $materias, $currentMateria, $currentModalidad, $baseUrl
 */
$currentMateria = $currentMateria ?? 'todas';
$currentModalidad = $currentModalidad ?? 'todas';
$baseUrl = $baseUrl ?? '';
?>
<form method="GET" class="filters" id="filterForm">
<input type="hidden" name="c" value="<?= htmlspecialchars($_GET['c'] ?? '') ?>">
<select class="filter-select" name="materia" onchange="this.form.submit()">
<option value="todas">Todas las materias</option>
<?php foreach ($materias as $m): ?>
<option value="<?=$m['id']?>" <?=$currentMateria===$m['id']?'selected':''?>><?=htmlspecialchars($m['nombre'])?></option>
<?php endforeach; ?>
</select>
<div class="filter-buttons">
<a href="<?=$baseUrl?>&modalidad=todas" class="filter-btn <?=$currentModalidad==='todas'?'active':''?>">Todas</a>
<a href="<?=$baseUrl?>&modalidad=Pre" class="filter-btn <?=$currentModalidad==='Pre'?'active pre-active':''?>">Pre</a>
<a href="<?=$baseUrl?>&modalidad=AMT" class="filter-btn <?=$currentModalidad==='AMT'?'active amt-active':''?>">AMT</a>
</div>
</form>
