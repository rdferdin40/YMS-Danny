-- Migration: Allow NULL entity_id in audit_log table
-- Purpose: Failed login attempts and other events don't have an entity_id
-- Date: 2025-11-18

ALTER TABLE audit_log
MODIFY COLUMN entity_id INT NULL COMMENT 'NULL for events without specific entity (e.g., failed logins)';
