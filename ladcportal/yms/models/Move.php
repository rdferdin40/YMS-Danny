<?php
/**
 * Move Model
 */

require_once __DIR__ . '/../config/db.php';

class Move {

    /**
     * Get move by ID
     * @param int $id
     * @return array|false
     */
    public static function findById($id) {
        $sql = "SELECT m.*, t.trailer_number, u.full_name as performed_by_name
                FROM moves m
                LEFT JOIN trailers t ON m.trailer_id = t.id
                LEFT JOIN users u ON m.performed_by_user_id = u.id
                WHERE m.id = ?
                LIMIT 1";
        return fetchOne($sql, [$id]);
    }

    /**
     * Get all moves with filters
     * @param array $filters
     * @return array
     */
    public static function getAll($filters = []) {
        $sql = "SELECT m.*, t.trailer_number, u.full_name as performed_by_name
                FROM moves m
                LEFT JOIN trailers t ON m.trailer_id = t.id
                LEFT JOIN users u ON m.performed_by_user_id = u.id
                WHERE 1=1";

        $params = [];

        if (isset($filters['trailer_id'])) {
            $sql .= " AND m.trailer_id = ?";
            $params[] = $filters['trailer_id'];
        }

        if (isset($filters['move_type'])) {
            $sql .= " AND m.move_type = ?";
            $params[] = $filters['move_type'];
        }

        if (isset($filters['performed_by_user_id'])) {
            $sql .= " AND m.performed_by_user_id = ?";
            $params[] = $filters['performed_by_user_id'];
        }

        if (isset($filters['date_from'])) {
            $sql .= " AND DATE(m.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $sql .= " AND DATE(m.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (isset($filters['trailer_number'])) {
            $sql .= " AND t.trailer_number LIKE ?";
            $params[] = '%' . $filters['trailer_number'] . '%';
        }

        $sql .= " ORDER BY m.created_at DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
        }

        return fetchAll($sql, $params);
    }

    /**
     * Create new move
     * @param array $data
     * @return int Move ID
     */
    public static function create($data) {
        $sql = "INSERT INTO moves (
                    move_id, trailer_id, from_location_type, from_yard_area, from_dock_door,
                    to_location_type, to_yard_area, to_dock_door, move_type,
                    spotter_name, performed_by_user_id, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        query($sql, [
            $data['move_id'],
            $data['trailer_id'],
            $data['from_location_type'],
            $data['from_yard_area'] ?? null,
            $data['from_dock_door'] ?? null,
            $data['to_location_type'],
            $data['to_yard_area'] ?? null,
            $data['to_dock_door'] ?? null,
            $data['move_type'],
            $data['spotter_name'] ?? null,
            $data['performed_by_user_id'],
            $data['notes'] ?? null
        ]);

        return lastInsertId();
    }

    /**
     * Get moves for a trailer
     * @param int $trailerId
     * @return array
     */
    public static function getByTrailer($trailerId) {
        return self::getAll(['trailer_id' => $trailerId]);
    }

    /**
     * Get moves by date range
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public static function getByDateRange($dateFrom, $dateTo) {
        return self::getAll([
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
    }

    /**
     * Get moves in last 24 hours
     * @return array
     */
    public static function getDaily() {
        $sql = "SELECT m.*, t.trailer_number, u.full_name as performed_by_name
                FROM moves m
                LEFT JOIN trailers t ON m.trailer_id = t.id
                LEFT JOIN users u ON m.performed_by_user_id = u.id
                WHERE m.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY m.created_at DESC";

        return fetchAll($sql);
    }

    /**
     * Get move statistics by user
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public static function getStatsByUser($dateFrom = null, $dateTo = null) {
        $sql = "SELECT u.full_name, u.role, COUNT(m.id) as move_count
                FROM moves m
                LEFT JOIN users u ON m.performed_by_user_id = u.id
                WHERE 1=1";

        $params = [];

        if ($dateFrom) {
            $sql .= " AND DATE(m.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND DATE(m.created_at) <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY m.performed_by_user_id
                  ORDER BY move_count DESC";

        return fetchAll($sql, $params);
    }

    /**
     * Get check-in/out statistics by guard and gate
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public static function getGuardActivity($dateFrom = null, $dateTo = null) {
        $sql = "SELECT u.full_name, DATE(m.created_at) as activity_date,
                COUNT(CASE WHEN m.move_type = 'CHECK_IN' THEN 1 END) as checkins,
                COUNT(CASE WHEN m.move_type = 'CHECK_OUT' THEN 1 END) as checkouts,
                COUNT(m.id) as total_activities
                FROM moves m
                LEFT JOIN users u ON m.performed_by_user_id = u.id
                WHERE u.role = 'GUARD'
                AND m.move_type IN ('CHECK_IN', 'CHECK_OUT')";

        $params = [];

        if ($dateFrom) {
            $sql .= " AND DATE(m.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND DATE(m.created_at) <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY m.performed_by_user_id, DATE(m.created_at)
                  ORDER BY activity_date DESC, total_activities DESC";

        return fetchAll($sql, $params);
    }

    /**
     * Get move count
     * @param array $filters
     * @return int
     */
    public static function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM moves m WHERE 1=1";
        $params = [];

        if (isset($filters['date_from'])) {
            $sql .= " AND DATE(m.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $sql .= " AND DATE(m.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $result = fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
}
