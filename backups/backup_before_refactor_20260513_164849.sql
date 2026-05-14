-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: dilp_monitoring
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL COMMENT 'Province context of the activity',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user` (`user_id`),
  KEY `idx_activity_logs_table` (`table_name`,`record_id`),
  KEY `idx_activity_logs_province` (`province`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit trail for all system activities';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'login','users',1,'User logged in',NULL,'::1','2026-05-13 08:15:10');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `beneficiaries`
--

DROP TABLE IF EXISTS `beneficiaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beneficiaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `province` varchar(100) DEFAULT 'Negros Occidental',
  `contact_number` varchar(20) DEFAULT NULL,
  `project_name` varchar(255) NOT NULL,
  `type_of_worker` varchar(100) DEFAULT NULL,
  `type_of_beneficiaries` varchar(255) DEFAULT NULL COMMENT 'Types of beneficiaries (e.g., Farmers, Former PDL)',
  `amount_worth` decimal(15,2) NOT NULL,
  `noted_findings` text DEFAULT NULL,
  `date_complied_by_proponent` date DEFAULT NULL,
  `date_forwarded_to_ro6` date DEFAULT NULL,
  `rpmt_findings` text DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `date_forwarded_to_nofo` date DEFAULT NULL,
  `date_turnover` date DEFAULT NULL,
  `date_monitoring` date DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `source_of_funds` varchar(255) DEFAULT NULL COMMENT 'Funding source (e.g., GAA, Centrally Managed Fund)',
  `status` enum('pending','approved','implemented','monitored') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_beneficiaries_municipality` (`municipality`),
  KEY `idx_beneficiaries_barangay` (`barangay`),
  KEY `idx_beneficiaries_status` (`status`),
  KEY `idx_beneficiaries_date_approved` (`date_approved`),
  KEY `idx_beneficiaries_province` (`province`),
  KEY `idx_beneficiaries_province_status` (`province`,`status`),
  KEY `idx_beneficiaries_province_municipality` (`province`,`municipality`),
  CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `beneficiaries_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Individual beneficiary records';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `beneficiaries`
--

LOCK TABLES `beneficiaries` WRITE;
/*!40000 ALTER TABLE `beneficiaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `beneficiaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fieldwork_schedule`
--

DROP TABLE IF EXISTS `fieldwork_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fieldwork_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(500) DEFAULT NULL,
  `province` enum('Negros Occidental','Negros Oriental','Siquijor') DEFAULT NULL COMMENT 'Province assignment (NULL for cross-province roles)',
  `assigned_user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','ongoing','completed','missed') DEFAULT 'pending',
  `manual_override` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = status was manually set; skip auto-update until next natural transition',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fieldwork_status` (`status`),
  KEY `idx_fieldwork_start_date` (`start_date`),
  KEY `idx_fieldwork_end_date` (`end_date`),
  KEY `idx_fieldwork_assigned_user` (`assigned_user_id`),
  KEY `idx_fieldwork_created_by` (`created_by`),
  KEY `idx_fieldwork_province` (`province`),
  CONSTRAINT `fieldwork_schedule_ibfk_1` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fieldwork_schedule_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Fieldwork scheduling system';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fieldwork_schedule`
--

LOCK TABLES `fieldwork_schedule` WRITE;
/*!40000 ALTER TABLE `fieldwork_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `fieldwork_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `org_chart`
--

DROP TABLE IF EXISTS `org_chart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `org_chart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tier` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=Regional Dir, 1=Field Office Head, 2=DILEEP Focal, 3=Staff',
  `sort_order` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Order within tier (0-4)',
  `position_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Legacy sort field',
  `position_title` varchar(255) NOT NULL,
  `person_name` varchar(255) DEFAULT NULL COMMENT 'Name of person in this position',
  `province` varchar(100) DEFAULT NULL COMMENT 'Province assignment for position',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tier_sort` (`tier`,`sort_order`),
  KEY `idx_province` (`province`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Organizational chart structure with multi-person tier support';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `org_chart`
--

LOCK TABLES `org_chart` WRITE;
/*!40000 ALTER TABLE `org_chart` DISABLE KEYS */;
INSERT INTO `org_chart` VALUES (1,0,0,1,'Regional Director',NULL,NULL,'2026-05-13 08:15:04','2026-05-13 08:15:04'),(2,1,0,2,'Field Office Head - Negros Occidental',NULL,'Negros Occidental','2026-05-13 08:15:04','2026-05-13 08:15:04'),(3,1,1,3,'Field Office Head - Negros Oriental',NULL,'Negros Oriental','2026-05-13 08:15:04','2026-05-13 08:15:04'),(4,1,2,4,'Field Office Head - Siquijor',NULL,'Siquijor','2026-05-13 08:15:04','2026-05-13 08:15:04'),(5,2,0,5,'DILEEP Focal Person - Negros Occidental',NULL,'Negros Occidental','2026-05-13 08:15:04','2026-05-13 08:15:04'),(6,2,1,6,'DILEEP Focal Person - Negros Oriental',NULL,'Negros Oriental','2026-05-13 08:15:04','2026-05-13 08:15:04'),(7,2,2,7,'DILEEP Focal Person - Siquijor',NULL,'Siquijor','2026-05-13 08:15:04','2026-05-13 08:15:04');
/*!40000 ALTER TABLE `org_chart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proponent_associations`
--

DROP TABLE IF EXISTS `proponent_associations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proponent_associations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proponent_id` int(11) NOT NULL,
  `association_name` varchar(255) NOT NULL,
  `association_address` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proponent_id` (`proponent_id`),
  CONSTRAINT `proponent_associations_ibfk_1` FOREIGN KEY (`proponent_id`) REFERENCES `proponents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Association mappings for proponents';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proponent_associations`
--

LOCK TABLES `proponent_associations` WRITE;
/*!40000 ALTER TABLE `proponent_associations` DISABLE KEYS */;
/*!40000 ALTER TABLE `proponent_associations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proponent_returns`
--

DROP TABLE IF EXISTS `proponent_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proponent_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proponent_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `returned_by` (`returned_by`),
  KEY `idx_proponent_returns_proponent` (`proponent_id`),
  CONSTRAINT `proponent_returns_ibfk_1` FOREIGN KEY (`proponent_id`) REFERENCES `proponents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proponent_returns_ibfk_2` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Proponent return tracking';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proponent_returns`
--

LOCK TABLES `proponent_returns` WRITE;
/*!40000 ALTER TABLE `proponent_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `proponent_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proponents`
--

DROP TABLE IF EXISTS `proponents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proponents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proponent_type` enum('LGU-associated','Non-LGU-associated','By Administration','Others') NOT NULL,
  `date_received` date DEFAULT NULL,
  `noted_findings` text DEFAULT NULL,
  `control_number` varchar(50) DEFAULT NULL,
  `number_of_copies` int(11) DEFAULT NULL,
  `date_copies_received` date DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT 'Negros Occidental',
  `proponent_name` varchar(255) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `number_of_associations` int(11) DEFAULT NULL,
  `total_beneficiaries` int(11) NOT NULL,
  `beneficiary_full_name` varchar(255) DEFAULT NULL COMMENT 'Comma-separated list of beneficiary names',
  `male_beneficiaries` int(11) DEFAULT 0,
  `female_beneficiaries` int(11) DEFAULT 0,
  `type_of_beneficiaries` varchar(255) DEFAULT NULL COMMENT 'Types of beneficiaries (e.g., Farmers, Former PDL)',
  `type_of_workers` varchar(255) DEFAULT NULL COMMENT 'Worker classifications',
  `category` enum('Formation','Enhancement','Restoration') NOT NULL,
  `recipient_barangays` text DEFAULT NULL,
  `letter_of_intent_date` date DEFAULT NULL,
  `date_forwarded_to_ro6` date DEFAULT NULL,
  `rpmt_findings` text DEFAULT NULL,
  `date_complied_by_proponent` date DEFAULT NULL,
  `date_complied_by_proponent_nofo` date DEFAULT NULL,
  `date_forwarded_to_nofo` date DEFAULT NULL,
  `date_approved` date DEFAULT NULL,
  `date_check_release` date DEFAULT NULL,
  `check_number` varchar(50) DEFAULT NULL,
  `check_date_issued` date DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `or_date_issued` date DEFAULT NULL,
  `date_turnover` date DEFAULT NULL,
  `date_implemented` date DEFAULT NULL,
  `date_liquidated` date DEFAULT NULL,
  `liquidation_deadline` date DEFAULT NULL COMMENT 'Auto-calculated based on proponent type',
  `date_monitoring` date DEFAULT NULL,
  `source_of_funds` varchar(255) DEFAULT NULL COMMENT 'Funding source (e.g., GAA, TUPAD)',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','approved','implemented','liquidated','monitored') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `control_number` (`control_number`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_proponents_type` (`proponent_type`),
  KEY `idx_proponents_district` (`district`),
  KEY `idx_proponents_status` (`status`),
  KEY `idx_proponents_control_number` (`control_number`),
  KEY `idx_proponents_date_approved` (`date_approved`),
  KEY `idx_proponents_province` (`province`),
  KEY `idx_proponents_province_status` (`province`,`status`),
  KEY `idx_proponents_province_type` (`province`,`proponent_type`),
  CONSTRAINT `proponents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proponents_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Proponent and organization records';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proponents`
--

LOCK TABLES `proponents` WRITE;
/*!40000 ALTER TABLE `proponents` DISABLE KEYS */;
/*!40000 ALTER TABLE `proponents` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `calculate_liquidation_deadline` 
BEFORE INSERT ON `proponents` 
FOR EACH ROW 
BEGIN
    IF NEW.date_turnover IS NOT NULL THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `update_liquidation_deadline` 
BEFORE UPDATE ON `proponents` 
FOR EACH ROW 
BEGIN
    IF NEW.date_turnover IS NOT NULL AND (
        OLD.date_turnover IS NULL OR 
        NEW.date_turnover != OLD.date_turnover OR 
        NEW.proponent_type != OLD.proponent_type
    ) THEN
        IF NEW.proponent_type = 'LGU-associated' THEN
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 10 DAY);
        ELSE
            SET NEW.liquidation_deadline = DATE_ADD(NEW.date_turnover, INTERVAL 60 DAY);
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `province_access_audit`
--

DROP TABLE IF EXISTS `province_access_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `province_access_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'create, read, update, delete',
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `province_accessed` varchar(100) DEFAULT NULL,
  `allowed` tinyint(1) DEFAULT 1,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_province` (`province_accessed`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_allowed` (`allowed`),
  CONSTRAINT `fk_province_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit trail for province-based access control';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `province_access_audit`
--

LOCK TABLES `province_access_audit` WRITE;
/*!40000 ALTER TABLE `province_access_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `province_access_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provinces`
--

DROP TABLE IF EXISTS `provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provinces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL COMMENT 'Province code: NO, NOR, SIQ',
  `name` varchar(100) NOT NULL COMMENT 'Full province name',
  `region_code` varchar(10) DEFAULT NULL COMMENT 'Region code: VI, VII',
  `region_name` varchar(100) DEFAULT NULL COMMENT 'Region name for reference',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_code` (`code`),
  KEY `idx_name` (`name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Province reference table for Region VI coverage';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provinces`
--

LOCK TABLES `provinces` WRITE;
/*!40000 ALTER TABLE `provinces` DISABLE KEYS */;
INSERT INTO `provinces` VALUES (1,'NO','Negros Occidental','VI','Western Visayas',1,1,'2026-05-13 08:15:04','2026-05-13 08:15:04'),(2,'NOR','Negros Oriental','VII','Central Visayas',1,2,'2026-05-13 08:15:04','2026-05-13 08:15:04'),(3,'SIQ','Siquijor','VII','Central Visayas',1,3,'2026-05-13 08:15:04','2026-05-13 08:15:04');
/*!40000 ALTER TABLE `provinces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System configuration settings';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'maintenance_mode','0','2026-05-13 08:15:04','2026-05-13 08:15:04');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_provinces`
--

DROP TABLE IF EXISTS `user_provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_provinces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL,
  `role` enum('super_admin','admin','regional_director','encoder','user') DEFAULT 'user' COMMENT 'Role in this province',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'User default province',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_province` (`user_id`,`province_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_province` (`province_id`),
  KEY `idx_role` (`role`),
  CONSTRAINT `fk_user_provinces_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_provinces_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Maps users to provinces they can access';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_provinces`
--

LOCK TABLES `user_provinces` WRITE;
/*!40000 ALTER TABLE `user_provinces` DISABLE KEYS */;
INSERT INTO `user_provinces` VALUES (1,1,1,'super_admin',1,'2026-05-13 08:15:04','2026-05-13 08:15:04'),(2,1,2,'super_admin',0,'2026-05-13 08:15:04','2026-05-13 08:15:04'),(3,1,3,'super_admin',0,'2026-05-13 08:15:04','2026-05-13 08:15:04');
/*!40000 ALTER TABLE `user_provinces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','regional_director','encoder','user') NOT NULL DEFAULT 'user',
  `province` varchar(100) DEFAULT NULL COMMENT 'User assigned province (NULL = all provinces for super_admin)',
  `full_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_province` (`province`),
  KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='User accounts with role-based access control';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@dilp.gov.ph','$2y$10$a6B7wXCzG83VKX.lX/h/seGi7H40EqquOlKeKgU3ytp/W.fpuOTkm','super_admin',NULL,'System Administrator',1,'2026-05-13 08:15:04','2026-05-13 08:15:04');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-13 16:48:49
