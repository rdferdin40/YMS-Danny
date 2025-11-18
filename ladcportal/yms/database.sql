-- =====================================================
-- Penske Logistics - Laredo YMS Phase 1 Database Schema
-- =====================================================
-- Instructions:
-- 1. Open phpMyAdmin or MySQL command line
-- 2. Create database: CREATE DATABASE ladc_yms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 3. USE ladc_yms;
-- 4. Run this entire script
-- =====================================================

DROP DATABASE IF EXISTS ladc_yms;
CREATE DATABASE ladc_yms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ladc_yms;

-- =====================================================
-- Table: users
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('GUARD', 'XD_TRAFFIC_CLERK', 'FG_TRAFFIC_CLERK', 'SHIPPING_CLERK', 'SUPERVISOR', 'ADMIN') NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: trailers
-- =====================================================
CREATE TABLE trailers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trailer_number VARCHAR(20) NOT NULL,
    carrier VARCHAR(100) NULL,
    trailer_type VARCHAR(50) NULL COMMENT 'Dry Van, Reefer, etc.',
    seal_number VARCHAR(50) NULL,
    reference_number VARCHAR(100) NULL COMMENT 'BOL/ASN/Delivery reference',
    live_or_drop ENUM('LIVE', 'DROP') NULL,
    tractor_number VARCHAR(20) NULL,
    route ENUM('NORTHBOUND', 'SOUTHBOUND') NULL,
    plant VARCHAR(50) NULL,

    -- Status & Location
    load_status ENUM('EMPTY', 'LOADED', 'BOBTAIL', 'LIVE_LOAD') NOT NULL,
    reason_load_status VARCHAR(255) NULL,
    yard_area ENUM('EAST_YARD', 'WEST_YARD') NULL,
    dock_door INT NULL COMMENT 'Doors 1-52',
    last_move_type VARCHAR(50) NULL,
    last_move_description VARCHAR(255) NULL,

    -- Timestamps
    date_in DATE NOT NULL,
    time_in DATETIME NOT NULL,
    time_out DATETIME NULL,
    last_moved_at DATETIME NULL,

    -- People References
    last_spotter_name VARCHAR(100) NULL,
    last_guard_user_id INT NULL,
    last_supervisor_user_id INT NULL,
    created_by_user_id INT NOT NULL,

    -- Operational
    current_location_type ENUM('YARD', 'DOCK', 'DEPARTED') NOT NULL DEFAULT 'YARD',
    comments TEXT NULL,
    load_id_or_contents VARCHAR(255) NULL,
    weight DECIMAL(10,2) NULL,

    -- Gate (East/West)
    gate ENUM('East', 'West') NULL,

    FOREIGN KEY (last_guard_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (last_supervisor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id),

    INDEX idx_trailer_number (trailer_number),
    INDEX idx_current_location_type (current_location_type),
    INDEX idx_yard_area (yard_area),
    INDEX idx_dock_door (dock_door),
    INDEX idx_date_in (date_in),
    INDEX idx_time_in (time_in),
    INDEX idx_time_out (time_out),
    INDEX idx_gate (gate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: moves
-- =====================================================
CREATE TABLE moves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    move_id VARCHAR(30) UNIQUE NOT NULL COMMENT 'Format: MV-YYYY-MM-DD-00001',
    trailer_id INT NOT NULL,

    -- From Location
    from_location_type ENUM('YARD', 'DOCK', 'GATE', 'DEPARTED') NOT NULL,
    from_yard_area ENUM('EAST_YARD', 'WEST_YARD') NULL,
    from_dock_door INT NULL,

    -- To Location
    to_location_type ENUM('YARD', 'DOCK', 'GATE', 'DEPARTED') NOT NULL,
    to_yard_area ENUM('EAST_YARD', 'WEST_YARD') NULL,
    to_dock_door INT NULL,

    -- Move Details
    move_type VARCHAR(50) NOT NULL COMMENT 'YARD_TO_DOCK, DOCK_TO_YARD, YARD_TO_YARD, CHECK_IN, CHECK_OUT',
    spotter_name VARCHAR(100) NULL,
    performed_by_user_id INT NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (trailer_id) REFERENCES trailers(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by_user_id) REFERENCES users(id),

    INDEX idx_move_id (move_id),
    INDEX idx_trailer_id (trailer_id),
    INDEX idx_move_type (move_type),
    INDEX idx_created_at (created_at),
    INDEX idx_performed_by (performed_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: spotters
-- =====================================================
CREATE TABLE spotters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: carriers
-- =====================================================
CREATE TABLE carriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: load_reasons
-- =====================================================
CREATE TABLE load_reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: purposes
-- =====================================================
CREATE TABLE purposes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: audit_log
-- =====================================================
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'TRAILER_CREATED, MOVE_CREATED, TRAILER_UPDATED, etc.',
    entity_type VARCHAR(50) NOT NULL COMMENT 'TRAILER, MOVE, USER, etc.',
    entity_id INT NOT NULL,
    before_data TEXT NULL COMMENT 'JSON of previous state',
    after_data TEXT NULL COMMENT 'JSON of new state',
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Insert sample users (passwords are all 'password123')
INSERT INTO users (username, password_hash, full_name, role, active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'ADMIN', 1),
('guard1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Guard East', 'GUARD', 1),
('guard2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Guard West', 'GUARD', 1),
('xd_clerk1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Rodriguez', 'XD_TRAFFIC_CLERK', 1),
('fg_clerk1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Martinez', 'FG_TRAFFIC_CLERK', 1),
('ship_clerk1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Lopez', 'SHIPPING_CLERK', 1),
('supervisor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'SUPERVISOR', 1);

-- Insert sample spotters
INSERT INTO spotters (name, active) VALUES
('Miguel Hernandez', 1),
('Jose Garcia', 1),
('Luis Ramirez', 1),
('Pedro Sanchez', 1),
('Roberto Cruz', 1);

-- Insert sample carriers
INSERT INTO carriers (name, active) VALUES
('SCHNEIDER NATIONAL', 1),
('J.B. HUNT', 1),
('SWIFT TRANSPORTATION', 1),
('WERNER ENTERPRISES', 1),
('KNIGHT-SWIFT', 1),
('PENSKE LOGISTICS', 1),
('XPO LOGISTICS', 1),
('OLD DOMINION', 1),
('FEDEX FREIGHT', 1),
('UPS FREIGHT', 1),
('C.H. ROBINSON', 1),
('LANDSTAR', 1),
('ESTES EXPRESS', 1),
('SAIA MOTOR FREIGHT', 1),
('YRC FREIGHT', 1);

-- Insert sample load reasons
INSERT INTO load_reasons (name, active) VALUES
('Inbound Raw Materials', 1),
('Outbound Finished Goods', 1),
('Return to Vendor', 1),
('Cross-Dock Transfer', 1),
('Expedited Shipment', 1),
('Scheduled Pickup', 1),
('Emergency Delivery', 1),
('Backhaul', 1),
('LTL Consolidation', 1),
('Sample Shipment', 1);

-- Insert sample purposes
INSERT INTO purposes (name, active) VALUES
('Delivery', 1),
('Pickup', 1),
('Live Load', 1),
('Live Unload', 1),
('Drop and Hook', 1),
('Cross-Dock', 1),
('Storage', 1),
('Return', 1),
('Transfer', 1),
('Inspection', 1);

-- =====================================================
-- END OF SCHEMA
-- =====================================================
