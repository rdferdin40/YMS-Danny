-- Migration: Add waiting list management fields
-- Purpose: Support priority queue and notes for dock assignment
-- Date: 2025-11-18

-- Add priority column
ALTER TABLE trailers
ADD COLUMN priority ENUM('LOW', 'NORMAL', 'HIGH') NOT NULL DEFAULT 'NORMAL' COMMENT 'Priority for dock assignment' AFTER weight;

-- Add waiting notes column
ALTER TABLE trailers
ADD COLUMN waiting_notes VARCHAR(255) NULL COMMENT 'Short notes for waiting list clerks' AFTER priority;

-- Add composite index for efficient waiting list queries
ALTER TABLE trailers
ADD INDEX idx_waiting_list (yard_area, priority, time_in);

-- Verify changes
DESCRIBE trailers;
