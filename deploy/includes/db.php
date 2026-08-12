<?php
/**
 * db.php - Conexión SQLite y consultas
 * @author Luis Cabezas - Inteligencia.com.co
 */

class DB {
    private static ?SQLite3 $instance = null;

    public static function get(): SQLite3 {
        if (self::$instance === null) {
            $path = __DIR__ . '/../database/horario.db';
            self::$instance = new SQLite3($path, SQLITE3_OPEN_READONLY);
            self::$instance->exec('PRAGMA journal_mode=WAL');
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): array {
        $db = self::get();
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $result = $stmt->execute();
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function one(string $sql, array $params = []): ?array {
        $rows = self::query($sql, $params);
        return $rows[0] ?? null;
    }

    public static function getCampusList(): array {
        return self::query('SELECT * FROM campus ORDER BY nombre');
    }

    public static function getProgramasList(): array {
        return self::query('SELECT * FROM programas ORDER BY nombre');
    }

    public static function getProgramasByCampus(string $campusId): array {
        return self::query('
            SELECT DISTINCT p.* FROM programas p
            JOIN cohortes c ON c.programa_id = p.id
            WHERE c.campus_id = :campus
            ORDER BY p.nombre
        ', [':campus' => $campusId]);
    }

    public static function getCohortes(string $campusId, string $programaId): array {
        return self::query('
            SELECT * FROM cohortes
            WHERE campus_id = :campus AND programa_id = :programa
            ORDER BY cohorte DESC
        ', [':campus' => $campusId, ':programa' => $programaId]);
    }

    public static function getCohorte(string $id): ?array {
        return self::one('SELECT * FROM cohortes WHERE id = :id', [':id' => $id]);
    }

    public static function getMaterias(string $cohorteId): array {
        return self::query('
            SELECT m.*, d.nombre as docente_nombre
            FROM materias m
            LEFT JOIN docentes d ON d.id = m.docente_id AND d.cohorte_id = m.cohorte_id
            WHERE m.cohorte_id = :cid
            ORDER BY m.nombre
        ', [':cid' => $cohorteId]);
    }

    public static function getSesiones(string $cohorteId, ?string $materiaId = null, ?string $modalidad = null): array {
        $sql = 'SELECT s.*, m.nombre as materia_nombre, m.icon, m.color, m.resumen_horarios, d.nombre as docente_nombre
                FROM sesiones s
                JOIN materias m ON m.id = s.materia_id AND m.cohorte_id = s.cohorte_id
                LEFT JOIN docentes d ON d.id = m.docente_id AND d.cohorte_id = m.cohorte_id
                WHERE s.cohorte_id = :cid';
        $params = [':cid' => $cohorteId];

        if ($materiaId && $materiaId !== 'todas') {
            $sql .= ' AND s.materia_id = :mid';
            $params[':mid'] = $materiaId;
        }
        if ($modalidad && $modalidad !== 'todas') {
            $sql .= ' AND s.modalidad = :mod';
            $params[':mod'] = $modalidad;
        }
        $sql .= ' ORDER BY s.fecha';
        return self::query($sql, $params);
    }

    public static function getResumen(string $cohorteId): array {
        $today = date('Y-m-d');
        $total = self::one('SELECT COUNT(*) as c FROM sesiones WHERE cohorte_id = :cid', [':cid' => $cohorteId]);
        $pre = self::one('SELECT COUNT(*) as c FROM sesiones WHERE cohorte_id = :cid AND modalidad = "Pre"', [':cid' => $cohorteId]);
        $amt = self::one('SELECT COUNT(*) as c FROM sesiones WHERE cohorte_id = :cid AND modalidad = "AMT"', [':cid' => $cohorteId]);
        $restantes = self::one('SELECT COUNT(*) as c FROM sesiones WHERE cohorte_id = :cid AND fecha >= :today', [':cid' => $cohorteId, ':today' => $today]);
        $restPre = self::one('SELECT COUNT(*) as c FROM sesiones WHERE cohorte_id = :cid AND modalidad = "Pre" AND fecha >= :today', [':cid' => $cohorteId, ':today' => $today]);
        $restAmt = self::one('SELECT COUNT(*) as c FROM sesiones WHERE cohorte_id = :cid AND modalidad = "AMT" AND fecha >= :today', [':cid' => $cohorteId, ':today' => $today]);

        return [
            'total' => $total['c'],
            'presenciales' => $pre['c'],
            'virtuales' => $amt['c'],
            'restantes' => $restantes['c'],
            'restantes_pre' => $restPre['c'],
            'restantes_amt' => $restAmt['c'],
        ];
    }

    public static function getProximaClase(string $cohorteId): ?array {
        $today = date('Y-m-d');
        return self::one('
            SELECT s.*, m.nombre as materia_nombre, m.icon, m.resumen_horarios, d.nombre as docente_nombre
            FROM sesiones s
            JOIN materias m ON m.id = s.materia_id AND m.cohorte_id = s.cohorte_id
            LEFT JOIN docentes d ON d.id = m.docente_id AND d.cohorte_id = m.cohorte_id
            WHERE s.cohorte_id = :cid AND s.fecha >= :today
            ORDER BY s.fecha LIMIT 1
        ', [':cid' => $cohorteId, ':today' => $today]);
    }
}


