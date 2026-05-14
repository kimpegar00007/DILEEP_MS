-- Phase 4 Migration: Create organizational chart table
-- Date: May 11, 2026
-- Description: Creates org_chart table for managing DILEEP-NOCFO organizational structure

-- Create org_chart table
CREATE TABLE IF NOT EXISTS org_chart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    position_order INT NOT NULL UNIQUE COMMENT '1=Regional Director, 2=Field Office Head, 3=DILEEP Focal, 4=LDS/Office Staff/IT',
    position_title VARCHAR(255) NOT NULL COMMENT 'Position title (e.g., Regional Director)',
    person_name VARCHAR(255) DEFAULT NULL COMMENT 'Name of person in this position',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_position_order (position_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='DILEEP-NOCFO Organizational Chart';

-- Insert default 4 positions
INSERT INTO org_chart (position_order, position_title, person_name) VALUES
(1, 'Regional Director', NULL),
(2, 'Field Office Head', NULL),
(3, 'DILEEP Focal', NULL),
(4, 'LDS / Office Staff / IT', NULL)
ON DUPLICATE KEY UPDATE position_title = VALUES(position_title);
