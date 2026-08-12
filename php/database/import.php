<?php
/**
 * import.php - Importa data.json a SQLite
 * Ejecutar: php database/import.php
 * 
 * @author Luis Cabezas - Inteligencia.com.co
 */

$jsonPath = __DIR__ . '/../../data/data.json';
$dbPath = __DIR__ . '/horario.db';

// Eliminar DB existente
if (file_exists($dbPath)) unlink($dbPath);

$db = new SQLite3($dbPath);
$db->exec('PRAGMA journal_mode=WAL');

// Crear tablas
$db->exec('
CREATE TABLE campus (
    id TEXT PRIMARY KEY,
    nombre TEXT NOT NULL
);

CREATE TABLE programas (
    id TEXT PRIMARY KEY,
    nombre TEXT NOT NULL
);

CREATE TABLE cohortes (
    id TEXT PRIMARY KEY,
    campus_id TEXT NOT NULL,
    programa_id TEXT NOT NULL,
    cohorte TEXT NOT NULL,
    FOREIGN KEY (campus_id) REFERENCES campus(id),
    FOREIGN KEY (programa_id) REFERENCES programas(id)
);

CREATE TABLE docentes (
    id TEXT NOT NULL,
    cohorte_id TEXT NOT NULL,
    nombre TEXT NOT NULL,
    PRIMARY KEY (id, cohorte_id),
    FOREIGN KEY (cohorte_id) REFERENCES cohortes(id)
);

CREATE TABLE materias (
    id TEXT NOT NULL,
    cohorte_id TEXT NOT NULL,
    codigo TEXT,
    nombre TEXT NOT NULL,
    docente_id TEXT,
    resumen_horarios TEXT,
    icon TEXT DEFAULT "bookOpen",
    color TEXT DEFAULT "#007AFF",
    cupo INTEGER,
    PRIMARY KEY (id, cohorte_id),
    FOREIGN KEY (cohorte_id) REFERENCES cohortes(id)
);

CREATE TABLE sesiones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cohorte_id TEXT NOT NULL,
    materia_id TEXT NOT NULL,
    fecha TEXT NOT NULL,
    modalidad TEXT NOT NULL DEFAULT "Pre",
    FOREIGN KEY (cohorte_id) REFERENCES cohortes(id)
);

CREATE INDEX idx_sesiones_cohorte ON sesiones(cohorte_id);
CREATE INDEX idx_sesiones_materia ON sesiones(materia_id);
CREATE INDEX idx_sesiones_fecha ON sesiones(fecha);
');

// Cargar JSON
$json = json_decode(file_get_contents($jsonPath), true);

// Insertar campus
$stmt = $db->prepare('INSERT INTO campus (id, nombre) VALUES (:id, :nombre)');
foreach ($json['campus'] as $c) {
    $stmt->bindValue(':id', $c['id']);
    $stmt->bindValue(':nombre', $c['nombre']);
    $stmt->execute(); $stmt->reset();
}

// Insertar programas
$stmt = $db->prepare('INSERT INTO programas (id, nombre) VALUES (:id, :nombre)');
foreach ($json['programas'] as $p) {
    $stmt->bindValue(':id', $p['id']);
    $stmt->bindValue(':nombre', $p['nombre']);
    $stmt->execute(); $stmt->reset();
}

// Insertar cohortes con sus datos
$stmtC = $db->prepare('INSERT INTO cohortes (id, campus_id, programa_id, cohorte) VALUES (:id, :campus_id, :programa_id, :cohorte)');
$stmtD = $db->prepare('INSERT OR IGNORE INTO docentes (id, cohorte_id, nombre) VALUES (:id, :cohorte_id, :nombre)');
$stmtM = $db->prepare('INSERT OR IGNORE INTO materias (id, cohorte_id, codigo, nombre, docente_id, resumen_horarios, icon, color, cupo) VALUES (:id, :cohorte_id, :codigo, :nombre, :docente_id, :resumen_horarios, :icon, :color, :cupo)');
$stmtS = $db->prepare('INSERT INTO sesiones (cohorte_id, materia_id, fecha, modalidad) VALUES (:cohorte_id, :materia_id, :fecha, :modalidad)');

$db->exec('BEGIN TRANSACTION');

foreach ($json['cohortes'] as $cohort) {
    $stmtC->bindValue(':id', $cohort['id']);
    $stmtC->bindValue(':campus_id', $cohort['campus_id']);
    $stmtC->bindValue(':programa_id', $cohort['programa_id']);
    $stmtC->bindValue(':cohorte', $cohort['cohorte']);
    $stmtC->execute(); $stmtC->reset();

    foreach ($cohort['docentes'] as $doc) {
        $stmtD->bindValue(':id', $doc['id']);
        $stmtD->bindValue(':cohorte_id', $cohort['id']);
        $stmtD->bindValue(':nombre', $doc['nombre']);
        $stmtD->execute(); $stmtD->reset();
    }

    foreach ($cohort['materias'] as $mat) {
        $stmtM->bindValue(':id', $mat['id']);
        $stmtM->bindValue(':cohorte_id', $cohort['id']);
        $stmtM->bindValue(':codigo', $mat['codigo'] ?? '');
        $stmtM->bindValue(':nombre', $mat['nombre']);
        $stmtM->bindValue(':docente_id', $mat['docente_id'] ?? '');
        $stmtM->bindValue(':resumen_horarios', $mat['resumen_horarios'] ?? '');
        $stmtM->bindValue(':icon', $mat['icon'] ?? 'bookOpen');
        $stmtM->bindValue(':color', $mat['color'] ?? '#007AFF');
        $stmtM->bindValue(':cupo', $mat['cupo'] ?? null);
        $stmtM->execute(); $stmtM->reset();
    }

    foreach ($cohort['sesiones'] as $ses) {
        $stmtS->bindValue(':cohorte_id', $cohort['id']);
        $stmtS->bindValue(':materia_id', $ses['materia_id']);
        $stmtS->bindValue(':fecha', $ses['fecha']);
        $stmtS->bindValue(':modalidad', $ses['modalidad']);
        $stmtS->execute(); $stmtS->reset();
    }
}

$db->exec('COMMIT');

// Stats
$totalC = $db->querySingle('SELECT COUNT(*) FROM cohortes');
$totalM = $db->querySingle('SELECT COUNT(*) FROM materias');
$totalS = $db->querySingle('SELECT COUNT(*) FROM sesiones');
$totalD = $db->querySingle('SELECT COUNT(*) FROM docentes');

echo "✅ Base de datos creada: $dbPath\n";
echo "   Campus: " . count($json['campus']) . "\n";
echo "   Programas: " . count($json['programas']) . "\n";
echo "   Cohortes: $totalC\n";
echo "   Docentes: $totalD\n";
echo "   Materias: $totalM\n";
echo "   Sesiones: $totalS\n";
