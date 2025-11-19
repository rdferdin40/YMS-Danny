<?php
/**
 * Trailer Model
 */

require_once __DIR__ . '/../config/db.php';

class Trailer {

    /**
     * Get trailer by ID
     * @param int $id
     * @return array|false
     */
    public static function findById($id) {
        $sql = "SELECT t.*, u.full_name as created_by_name
                FROM trailers t
                LEFT JOIN users u ON t.created_by_user_id = u.id
                WHERE t.id = ?
                LIMIT 1";
        return fetchOne($sql, [$id]);
    }

    /**
     * Get trailer by trailer number
     * @param string $trailerNumber
     * @return array|false
     */
    public static function findByNumber($trailerNumber) {
        $sql = "SELECT * FROM trailers WHERE trailer_number = ? ORDER BY id DESC LIMIT 1";
        return fetchOne($sql, [$trailerNumber]);
    }

    /**
     * Get all trailers with filters
     * @param array $filters
     * @return array
     */
    public static function getAll($filters = []) {
        $sql = "SELECT t.*, u.full_name as created_by_name
                FROM trailers t
                LEFT JOIN users u ON t.created_by_user_id = u.id
                WHERE 1=1";

        $params = [];

        if (isset($filters['location_type'])) {
            $sql .= " AND t.current_location_type = ?";
            $params[] = $filters['location_type'];
        }

        if (isset($filters['yard_area'])) {
            $sql .= " AND t.yard_area = ?";
            $params[] = $filters['yard_area'];
        }

        if (isset($filters['dock_door'])) {
            $sql .= " AND t.dock_door = ?";
            $params[] = $filters['dock_door'];
        }

        if (isset($filters['gate'])) {
            $sql .= " AND t.gate = ?";
            $params[] = $filters['gate'];
        }

        if (isset($filters['load_status'])) {
            $sql .= " AND t.load_status = ?";
            $params[] = $filters['load_status'];
        }

        if (isset($filters['search'])) {
            $sql .= " AND (t.trailer_number LIKE ? OR t.carrier LIKE ? OR t.reference_number LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY t.time_in DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
        }

        return fetchAll($sql, $params);
    }

    /**
     * Create new trailer check-in
     * @param array $data
     * @return int Trailer ID
     */
    public static function create($data) {
        $sql = "INSERT INTO trailers (
                    trailer_number, carrier, trailer_type, seal_number, reference_number,
                    live_or_drop, tractor_number, route, plant, load_status, reason_load_status,
                    yard_area, dock_door, date_in, time_in, created_by_user_id,
                    current_location_type, comments, load_id_or_contents, weight, gate, last_moved_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        query($sql, [
            $data['trailer_number'],
            $data['carrier'] ?? null,
            $data['trailer_type'] ?? null,
            $data['seal_number'] ?? null,
            $data['reference_number'] ?? null,
            $data['live_or_drop'] ?? null,
            $data['tractor_number'] ?? null,
            $data['route'] ?? null,
            $data['plant'] ?? null,
            $data['load_status'],
            $data['reason_load_status'] ?? null,
            $data['yard_area'] ?? null,
            $data['dock_door'] ?? null,
            $data['date_in'] ?? date('Y-m-d'),
            $data['time_in'] ?? date('Y-m-d H:i:s'),
            $data['created_by_user_id'],
            $data['current_location_type'] ?? 'YARD',
            $data['comments'] ?? null,
            $data['load_id_or_contents'] ?? null,
            $data['weight'] ?? null,
            $data['gate'] ?? null
        ]);

        return lastInsertId();
    }

    /**
     * Update trailer
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        $setParts = [];
        $params = [];

        $allowedFields = [
            'trailer_number', 'carrier', 'trailer_type', 'seal_number', 'reference_number',
            'live_or_drop', 'tractor_number', 'route', 'plant', 'load_status', 'reason_load_status',
            'yard_area', 'dock_door', 'last_move_type', 'last_move_description', 'time_out',
            'last_spotter_name', 'last_guard_user_id', 'last_supervisor_user_id',
            'current_location_type', 'comments', 'load_id_or_contents', 'weight', 'gate'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $setParts[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($setParts)) {
            return false;
        }

        // Update last_moved_at
        $setParts[] = "last_moved_at = NOW()";

        $params[] = $id;
        $sql = "UPDATE trailers SET " . implode(', ', $setParts) . " WHERE id = ?";
        query($sql, $params);

        return true;
    }

    /**
     * Check out trailer (mark as departed)
     * @param int $id
     * @return bool
     */
    public static function checkOut($id) {
        $sql = "UPDATE trailers SET
                current_location_type = 'DEPARTED',
                time_out = NOW(),
                yard_area = NULL,
                dock_door = NULL,
                last_moved_at = NOW()
                WHERE id = ?";

        query($sql, [$id]);
        return true;
    }

    /**
     * Assign to yard
     * @param int $id
     * @param string $yardArea
     * @return bool
     */
    public static function assignToYard($id, $yardArea) {
        $sql = "UPDATE trailers SET
                yard_area = ?,
                dock_door = NULL,
                current_location_type = 'YARD',
                last_moved_at = NOW()
                WHERE id = ?";

        query($sql, [$yardArea, $id]);
        return true;
    }

    /**
     * Assign to dock door
     * @param int $id
     * @param int $dockDoor
     * @return bool
     */
    public static function assignToDock($id, $dockDoor) {
        $sql = "UPDATE trailers SET
                dock_door = ?,
                yard_area = NULL,
                current_location_type = 'DOCK',
                last_moved_at = NOW()
                WHERE id = ?";

        query($sql, [$dockDoor, $id]);
        return true;
    }

    /**
     * Get trailers in yard
     * @param string $yardArea EAST_YARD or WEST_YARD
     * @return array
     */
    public static function getByYard($yardArea) {
        return self::getAll([
            'location_type' => 'YARD',
            'yard_area' => $yardArea
        ]);
    }

    /**
     * Get trailer at specific dock door
     * @param int $dockDoor
     * @return array|false
     */
    public static function getByDockDoor($dockDoor) {
        $sql = "SELECT * FROM trailers
                WHERE dock_door = ?
                AND current_location_type = 'DOCK'
                LIMIT 1";
        return fetchOne($sql, [$dockDoor]);
    }

    /**
     * Get all dock door statuses (1-52)
     * @return array
     */
    public static function getDockDoorStatuses() {
        $sql = "SELECT id, dock_door, trailer_number, load_status, time_in, trailer_type
                FROM trailers
                WHERE dock_door IS NOT NULL
                AND current_location_type = 'DOCK'";

        $occupied = fetchAll($sql);
        $doorMap = [];

        foreach ($occupied as $trailer) {
            $doorMap[$trailer['dock_door']] = $trailer;
        }

        return $doorMap;
    }

    /**
     * Get active trailers count
     * @return int
     */
    public static function getActiveCount() {
        $sql = "SELECT COUNT(*) as count FROM trailers WHERE current_location_type != 'DEPARTED'";
        $result = fetchOne($sql);
        return $result['count'] ?? 0;
    }

    /**
     * Get trailers by dwell time
     * @return array
     */
    public static function getByDwellTime() {
        $sql = "SELECT *,
                TIMESTAMPDIFF(HOUR, time_in, NOW()) as dwell_hours
                FROM trailers
                WHERE current_location_type != 'DEPARTED'
                ORDER BY dwell_hours DESC";

        return fetchAll($sql);
    }

    /**
     * Get departed trailers
     * @param int $days Number of days to look back
     * @return array
     */
    public static function getDeparted($days = 7) {
        $sql = "SELECT t.*, u.full_name as created_by_name
                FROM trailers t
                LEFT JOIN users u ON t.created_by_user_id = u.id
                WHERE t.current_location_type = 'DEPARTED'
                AND t.time_out >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY t.time_out DESC";

        return fetchAll($sql, [$days]);
    }

    /**
     * Get waiting list for a specific yard
     * Ordered by: Priority (HIGH → NORMAL → LOW), then oldest first
     * @param string $yardArea 'EAST_YARD' or 'WEST_YARD'
     * @return array
     */
    public static function getWaitingList($yardArea) {
        $sql = "SELECT
                    id,
                    trailer_number,
                    carrier,
                    load_status,
                    time_in,
                    priority,
                    waiting_notes,
                    TIMESTAMPDIFF(MINUTE, time_in, NOW()) as wait_minutes,
                    TIMESTAMPDIFF(HOUR, time_in, NOW()) as wait_hours
                FROM trailers
                WHERE yard_area = ?
                AND current_location_type = 'YARD'
                AND dock_door IS NULL
                ORDER BY
                    FIELD(priority, 'HIGH', 'NORMAL', 'LOW'),
                    time_in ASC";

        return fetchAll($sql, [$yardArea]);
    }

    /**
     * Update trailer priority
     * @param int $id
     * @param string $priority 'LOW', 'NORMAL', or 'HIGH'
     * @return bool
     */
    public static function updatePriority($id, $priority) {
        if (!in_array($priority, ['LOW', 'NORMAL', 'HIGH'])) {
            return false;
        }

        $sql = "UPDATE trailers SET priority = ? WHERE id = ?";
        query($sql, [$priority, $id]);
        return true;
    }

    /**
     * Update waiting notes
     * @param int $id
     * @param string $notes
     * @return bool
     */
    public static function updateWaitingNotes($id, $notes) {
        $sql = "UPDATE trailers SET waiting_notes = ? WHERE id = ?";
        query($sql, [$notes, $id]);
        return true;
    }

    /**
     * Get waiting list statistics
     * @param string $yardArea
     * @return array
     */
    public static function getWaitingListStats($yardArea) {
        $sql = "SELECT
                    COUNT(*) as total_waiting,
                    SUM(CASE WHEN priority = 'HIGH' THEN 1 ELSE 0 END) as high_priority_count,
                    SUM(CASE WHEN priority = 'NORMAL' THEN 1 ELSE 0 END) as normal_priority_count,
                    SUM(CASE WHEN priority = 'LOW' THEN 1 ELSE 0 END) as low_priority_count,
                    SUM(CASE WHEN load_status = 'LOADED' THEN 1 ELSE 0 END) as loaded_count,
                    SUM(CASE WHEN load_status = 'EMPTY' THEN 1 ELSE 0 END) as empty_count,
                    AVG(TIMESTAMPDIFF(MINUTE, time_in, NOW())) as avg_wait_minutes,
                    MAX(TIMESTAMPDIFF(MINUTE, time_in, NOW())) as max_wait_minutes
                FROM trailers
                WHERE yard_area = ?
                AND current_location_type = 'YARD'
                AND dock_door IS NULL";

        return fetchOne($sql, [$yardArea]) ?? [];
    }
}
