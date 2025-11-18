<?php
/**
 * User Model
 */

require_once __DIR__ . '/../config/db.php';

class User {

    /**
     * Authenticate user by username and password
     * @param string $username
     * @param string $password
     * @return array|false User data or false if invalid
     */
    public static function authenticate($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1";
        $user = fetchOne($sql, [$username]);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }

    /**
     * Get user by ID
     * @param int $id
     * @return array|false
     */
    public static function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        return fetchOne($sql, [$id]);
    }

    /**
     * Get user by username
     * @param string $username
     * @return array|false
     */
    public static function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        return fetchOne($sql, [$username]);
    }

    /**
     * Get all users
     * @param bool $activeOnly
     * @return array
     */
    public static function getAll($activeOnly = true) {
        $sql = "SELECT * FROM users";
        if ($activeOnly) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY full_name ASC";
        return fetchAll($sql);
    }

    /**
     * Create new user
     * @param array $data
     * @return int User ID
     */
    public static function create($data) {
        $sql = "INSERT INTO users (username, password_hash, full_name, role, active, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";

        query($sql, [
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['full_name'],
            $data['role'],
            $data['active'] ?? 1
        ]);

        return lastInsertId();
    }

    /**
     * Update user
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        $setParts = [];
        $params = [];

        if (isset($data['full_name'])) {
            $setParts[] = "full_name = ?";
            $params[] = $data['full_name'];
        }
        if (isset($data['role'])) {
            $setParts[] = "role = ?";
            $params[] = $data['role'];
        }
        if (isset($data['active'])) {
            $setParts[] = "active = ?";
            $params[] = $data['active'];
        }
        if (isset($data['password'])) {
            $setParts[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($setParts)) {
            return false;
        }

        $setParts[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?";
        query($sql, $params);

        return true;
    }

    /**
     * Delete user (soft delete by setting active = 0)
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $sql = "UPDATE users SET active = 0, updated_at = NOW() WHERE id = ?";
        query($sql, [$id]);
        return true;
    }

    /**
     * Get users by role
     * @param string $role
     * @return array
     */
    public static function getByRole($role) {
        $sql = "SELECT * FROM users WHERE role = ? AND active = 1 ORDER BY full_name ASC";
        return fetchAll($sql, [$role]);
    }

    /**
     * Check if username exists
     * @param string $username
     * @param int|null $excludeId
     * @return bool
     */
    public static function usernameExists($username, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $params = [$username];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $user = fetchOne($sql, $params);
        return (bool)$user;
    }
}
