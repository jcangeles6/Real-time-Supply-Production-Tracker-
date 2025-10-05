-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hotwheels
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `batches`
--

DROP TABLE IF EXISTS `batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('scheduled','in_progress','completed') DEFAULT 'scheduled',
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batches`
--

LOCK TABLES `batches` WRITE;
/*!40000 ALTER TABLE `batches` DISABLE KEYS */;
INSERT INTO `batches` VALUES (11,'Sugar',5,'completed','2025-10-05 09:18:59','2025-10-05 09:19:09'),(12,'Sugar',11,'completed','2025-10-05 12:23:06','2025-10-05 13:02:09');
/*!40000 ALTER TABLE `batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'kg',
  `supplier` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredients`
--

LOCK TABLES `ingredients` WRITE;
/*!40000 ALTER TABLE `ingredients` DISABLE KEYS */;
INSERT INTO `ingredients` VALUES (1,'Flour',20.00,'25kg','ABC Mills','2025-09-28 14:17:31'),(2,'Sugar',18.00,'25kg','Sweet Co.','2025-09-28 14:17:31'),(3,'Butter',45.00,'10kg','Dairy Best','2025-09-28 14:17:31'),(4,'Yeast',12.00,'5kg','BakePro','2025-09-28 14:17:31');
/*!40000 ALTER TABLE `ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `status` enum('available','low','out') DEFAULT 'available',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (2,'tite',5,'kg','low','2025-10-05 16:15:51','2025-10-05 17:01:10'),(4,'Butter',12,'kg','available','2025-09-28 18:29:52','2025-10-05 17:01:10'),(6,'Sugar',5,'kg','low','2025-10-05 08:40:47','2025-10-05 17:01:10'),(7,'Sugar',40,'kg','available','2025-10-05 19:19:21','2025-10-05 17:01:10'),(8,'Sugar',5,'kg','low','2025-10-05 09:11:05','2025-10-05 17:01:10'),(41,'Milk',25,'L','available','2025-10-05 15:23:49','2025-10-05 17:01:10'),(42,'Butter',12,'kg','available','2025-10-05 15:24:18','2025-10-05 17:01:10'),(43,'Butter',12,'kg','available','2025-10-05 15:25:42','2025-10-05 17:01:10'),(44,'Butter',12,'kg','available','2025-10-05 15:25:43','2025-10-05 17:01:10'),(45,'Butter',13,'kg','available','2025-10-05 15:26:38','2025-10-05 17:01:10'),(46,'Goat',13,'kg','available','2025-10-05 15:30:30','2025-10-05 17:01:10'),(48,'KAMBING',23,'kg','available','2025-10-05 16:13:52','2025-10-05 17:01:10'),(49,'KABAYO',44,'kg','available','2025-10-05 16:13:56','2025-10-05 17:01:10'),(50,'ISDA',23,'kg','available','2025-10-05 16:14:01','2025-10-05 17:01:10'),(51,'ETITS',23,'kg','available','2025-10-05 16:14:05','2025-10-05 17:01:10'),(52,'PUNO',23,'kg','available','2025-10-05 16:14:10','2025-10-05 17:01:10'),(53,'HOLLOWBLOCKS',23,'kg','available','2025-10-05 16:14:16','2025-10-05 17:01:10'),(54,'123',123,'kg','available','2025-10-05 16:23:55','2025-10-05 17:01:10'),(55,'Sugar',23,'kg','available','2025-10-05 16:41:48','2025-10-05 17:01:10'),(56,'Flour',12,'kg','available','2025-10-05 18:50:42','2025-10-05 17:01:10'),(58,'Flour',23,'kg','available','2025-10-05 19:19:28','2025-10-05 17:01:10'),(59,'Sugar',55,'kg','available','2025-10-05 16:47:01','2025-10-05 17:01:10'),(63,'Sugar',1,'kg','available','2025-10-05 17:08:05','2025-10-05 17:07:47');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_batches`
--

DROP TABLE IF EXISTS `production_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('Scheduled','In Progress','Completed') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_batches`
--

LOCK TABLES `production_batches` WRITE;
/*!40000 ALTER TABLE `production_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `production_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requests`
--

DROP TABLE IF EXISTS `requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ingredient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ingredient_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','completed') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requests`
--

LOCK TABLES `requests` WRITE;
/*!40000 ALTER TABLE `requests` DISABLE KEYS */;
INSERT INTO `requests` VALUES (1,0,0,'flour',30,NULL,'kg','','2025-09-28 13:49:02'),(2,0,0,'flour',123,NULL,'kg','','2025-09-28 16:51:51'),(3,0,0,'yeast',400,NULL,'kg','','2025-09-28 19:52:10'),(4,0,0,'flour',1,NULL,'kg','','2025-09-28 19:55:49'),(5,0,0,'yeast',14,NULL,'kg','','2025-09-28 19:58:36'),(6,0,0,'flour',15,NULL,'kg','','2025-09-28 20:00:40'),(7,0,0,'sugar',100,NULL,'kg','','2025-09-28 20:01:20'),(8,0,0,'sugar',15,NULL,'kg','','2025-09-28 20:03:59'),(9,0,0,'yeast',51,NULL,'kg','','2025-09-28 20:08:51'),(10,0,0,'yeast',51,NULL,'kg','','2025-09-28 20:11:17'),(11,0,0,'sugar',124,NULL,'kg','','2025-09-28 20:13:35'),(12,0,0,'sugar',54,NULL,'kg','','2025-09-28 20:17:30'),(13,0,0,'flour',15,NULL,'kg','pending','2025-10-01 17:08:57');
/*!40000 ALTER TABLE `requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `reset_requested` tinyint(1) DEFAULT 0,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'admin','administrator@gmail.com','$2y$10$Tc405XDWqFBsX1WYUHti1esUWm/8qbMYo7sZ4FFLKLoetqfmDZ2pa',NULL,1,0,NULL,NULL,0,NULL),(3,'jcangeles6','jcangeles6@gmail.com','$2y$10$0zqLhTIwC1RidG4EeUDsAOPAG0C8z06IjqAUZL5FjXQ/VNMQQjdZi',NULL,1,0,'ano nickname ko?','$2y$10$ghXoLBM3f6/KgeXIEhfrP.cByINiZVGWtOuDeRNfl6pMcnXqMfOVy',0,NULL),(5,'jcang22','jcang22@gmail.com','$2y$10$AoPoJbpmGNBimUZR815Vpu4pz1j03jYj0QLYMoJtLJ7fj.Dl423w6',NULL,0,0,'What is your first pet\'s name?','$2y$10$rexNf6YnuEhhHbuwCNHv.umtCXtXKC2EOp8dIldkU6nHsgjl9Ed2.',0,NULL),(6,'alex','alexjagonoy@gmail.com','$2y$10$jIhk9V.npnLu5M9v6t8FlefplkL6TL.p2Rs2bQ/JX7qnr8kac5.se',NULL,0,0,'What is your favorite color?','$2y$10$NrPNJJjvE4jdW6./U2gji.2icWbkeZ1p87k4UbqQw.Kghj3tsB0u6',0,NULL),(7,'pitoy','keanoivanpitoy@gmail.com','$2y$10$Gb.EdZ1HKB8fsHaViYwf2ehKvvPPXP4tJ4ZLIY0IuPJ0vdDetK4La',NULL,1,0,'What is your favorite color?','$2y$10$fkkqM0o/8aLWOxWb7rCgLuAjNUjDZ.XamXcxtiSByHxgXxEER20QW',0,NULL),(8,'ivan','peanutsfriedrice@gmail.com','$2y$10$onkSZ/DnycTVMcfxuteoCOlXjvhaQZpXdev6PxAGZZrIEJOVefvUm',NULL,0,0,'What is your favorite color?','$2y$10$tV5by1edNmDAqJ54JEMKZ..bw.sJb925x6iCAfVUHbzQRUhKVk75e',0,NULL);
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

-- Dump completed on 2025-10-06  3:55:06
