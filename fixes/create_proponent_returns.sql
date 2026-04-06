-- Migration: Create proponent_returns table for tracking application return history
-- Date: 2026-04-06

CREATE TABLE IF NOT EXISTS `proponent_returns` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `proponent_id` INT NOT NULL,
  `return_date` DATE NOT NULL,
  `reason` TEXT,
  `returned_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`proponent_id`) REFERENCES `proponents`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`returned_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for faster lookups
CREATE INDEX idx_proponent_returns_proponent ON proponent_returns(proponent_id);
