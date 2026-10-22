-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: textilecycle
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_brand_id_created_at_index` (`brand_id`,`created_at`),
  CONSTRAINT `activity_logs_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `article_reactions`
--

DROP TABLE IF EXISTS `article_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `article_reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `type` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_reactions_article_id_session_id_unique` (`article_id`,`session_id`),
  CONSTRAINT `article_reactions_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `article_reactions`
--

LOCK TABLES `article_reactions` WRITE;
/*!40000 ALTER TABLE `article_reactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `article_reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `articles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `excerpt` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `views_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articles_slug_unique` (`slug`),
  KEY `articles_category_id_foreign` (`category_id`),
  KEY `articles_author_id_foreign` (`author_id`),
  CONSTRAINT `articles_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `articles_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articles`
--

LOCK TABLES `articles` WRITE;
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
INSERT INTO `articles` VALUES (1,1,1,'Donner une seconde vie à sa garde-robe','donner-une-seconde-vie-a-sa-garde-robe','Les bonnes pratiques pour trier, donner et revaloriser ses vêtements avant de les jeter.','Avant de jeter un vêtement, plusieurs options existent : le don, la revente, l\'échange entre particuliers ou le dépôt en point de collecte.\n\nCet article détaille les critères pour choisir la meilleure filière selon l\'état du vêtement et vous aide à réduire votre impact environnemental tout en donnant une seconde chance à vos pièces.',NULL,'2026-10-12 08:59:55',0,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,2,1,'Comment fonctionne le recyclage des fibres textiles','comment-fonctionne-le-recyclage-des-fibres-textiles','Du tri au fil régénéré : le parcours d’un textile recyclé expliqué étape par étape.','Le recyclage textile repose sur plusieurs étapes : collecte, tri par matière, effilochage puis transformation en nouvelle fibre.\n\nCertaines matières comme le coton ou le polyester se recyclent plus facilement que les mélanges, ce qui explique pourquoi la composition d\'un vêtement influence directement sa recyclabilité.',NULL,'2026-09-27 08:59:55',0,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,3,1,'L’empreinte carbone d’un vêtement, de la fibre au dressing','lempreinte-carbone-dun-vetement-de-la-fibre-au-dressing','Un tour d’horizon des étapes qui pèsent le plus dans le bilan carbone d’un textile.','La production de fibres, la teinture, le transport et l\'entretien représentent les principales sources d\'émissions d\'un vêtement.\n\nComprendre ces étapes permet d\'identifier les leviers les plus efficaces pour réduire son impact, aussi bien côté marque que côté consommateur.',NULL,'2026-10-17 08:59:55',0,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(4,4,1,'5 réparations simples à réaliser soi-même','5-reparations-simples-a-realiser-soi-meme','Boutons, ourlets, fermetures éclair : les gestes de base pour éviter de jeter trop vite.','Avec quelques outils de base (fil, aiguille, pince), il est possible de réparer soi-même la majorité des petites dégradations courantes.\n\nCet article présente cinq réparations accessibles aux débutants, avant de faire appel à un atelier de réparation professionnel pour les cas plus complexes.',NULL,'2026-10-20 08:59:55',0,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(5,1,1,'Organiser un vide-dressing solidaire','organiser-un-vide-dressing-solidaire','Les étapes clés pour transformer un tri de dressing en action solidaire et écoresponsable.','Un vide-dressing solidaire permet de redistribuer des vêtements encore en bon état à des associations locales.\n\nCe guide pratique couvre la préparation, la communication et le suivi d\'un événement de ce type, à l\'échelle d\'un quartier ou d\'une entreprise.',NULL,NULL,0,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_name` varchar(255) NOT NULL,
  `framework` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `readiness_score` tinyint(3) unsigned DEFAULT NULL,
  `assessment_date` date NOT NULL,
  `next_review_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `label_id` bigint(20) unsigned DEFAULT NULL,
  `score` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `submitted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessments_user_id_foreign` (`user_id`),
  KEY `assessments_label_id_foreign` (`label_id`),
  CONSTRAINT `assessments_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assessments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessments`
--

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
INSERT INTO `assessments` VALUES (1,'EcoFashion','ISO 14001','in_progress',81,'2026-09-07','2027-04-22','Objectif eau annuel et analyse des fournisseurs à compléter.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,0,NULL),(2,'Circular Studio','Textile Circularity','completed',92,'2026-08-03','2027-10-22','Excellente réparabilité et forte part de matières recyclées.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,0,NULL),(3,'North Loom','Responsible Wool Standard','in_progress',87,'2026-09-27','2027-04-22','Traçabilité de la laine élevée, contrôle documentaire en cours.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,0,NULL),(4,'BlueStep','ISO 14001','draft',69,'2026-10-10','2027-02-22','Formaliser les objectifs énergie et les résultats de fin de vie.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,0,NULL),(5,'Verde Atelier','Animal-Free Readiness','completed',89,'2026-08-28','2027-10-22','Composition sans matière animale vérifiée; analyse polymères disponible.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brand_applications`
--

DROP TABLE IF EXISTS `brand_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brand_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `questionnaire_version` varchar(255) NOT NULL DEFAULT '2026-10-v1',
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `analysis_status` varchar(255) NOT NULL DEFAULT 'pending',
  `analysis_model` varchar(255) DEFAULT NULL,
  `recommendations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recommendations`)),
  `analysis_summary` text DEFAULT NULL,
  `analysed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `approved_label_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approved_label_snapshot`)),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brand_applications_brand_id_unique` (`brand_id`),
  KEY `brand_applications_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `brand_applications_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `brand_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brand_applications`
--

LOCK TABLES `brand_applications` WRITE;
/*!40000 ALTER TABLE `brand_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `brand_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brand_label`
--

DROP TABLE IF EXISTS `brand_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brand_label` (
  `brand_id` bigint(20) unsigned NOT NULL,
  `label_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`brand_id`,`label_id`),
  KEY `brand_label_label_id_foreign` (`label_id`),
  CONSTRAINT `brand_label_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `brand_label_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brand_label`
--

LOCK TABLES `brand_label` WRITE;
/*!40000 ALTER TABLE `brand_label` DISABLE KEYS */;
/*!40000 ALTER TABLE `brand_label` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brand_members`
--

DROP TABLE IF EXISTS `brand_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brand_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'invited',
  `invitation_token_hash` varchar(64) DEFAULT NULL,
  `invited_at` timestamp NULL DEFAULT NULL,
  `joined_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brand_members_brand_id_email_unique` (`brand_id`,`email`),
  UNIQUE KEY `brand_members_user_id_unique` (`user_id`),
  CONSTRAINT `brand_members_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `brand_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brand_members`
--

LOCK TABLES `brand_members` WRITE;
/*!40000 ALTER TABLE `brand_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `brand_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brand_notifications`
--

DROP TABLE IF EXISTS `brand_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brand_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `brand_notifications_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `brand_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brand_notifications`
--

LOCK TABLES `brand_notifications` WRITE;
/*!40000 ALTER TABLE `brand_notifications` DISABLE KEYS */;
INSERT INTO `brand_notifications` VALUES (1,5,'purchase_order_created','Nouvelle commande de EcoFashion','PO-261022-HQ3SZ : 500 kg de Coton recyclé et 200 kg de Polyester recyclé, livraison souhaitée le 26/11/2026. À accepter, refuser ou contre-proposer.','http://127.0.0.1:8000/espace-fournisseur/commandes/4',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(2,6,'purchase_order_created','Nouvelle commande de EcoFashion','PO-261022-5M24R : 150 kg de Laine mérinos, livraison souhaitée le 12/11/2026. À accepter, refuser ou contre-proposer.','http://127.0.0.1:8000/espace-fournisseur/commandes/5',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(3,5,'purchase_order_created','Nouvelle commande de EcoFashion','PO-261022-UHITX : 400 kg de Coton recyclé et 150 kg de Polyester recyclé, livraison souhaitée le 26/10/2026. À accepter, refuser ou contre-proposer.','http://127.0.0.1:8000/espace-fournisseur/commandes/6',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(4,6,'purchase_order_created','Nouvelle commande de EcoFashion','PO-261022-JGBUX : 60 kg de Laine mérinos, livraison souhaitée le 17/10/2026. À accepter, refuser ou contre-proposer.','http://127.0.0.1:8000/espace-fournisseur/commandes/7',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(5,4,'impact_report_validated','Rapport d’impact validé','Winter Jacket 02 : score 63/100 (classe C).','http://127.0.0.1:8000/brand/impact/1',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(6,4,'impact_report_validated','Rapport d’impact validé','Ocean Runner : score 86/100 (classe A).','http://127.0.0.1:8000/brand/impact/3',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(7,1,'repair_consumer_decision','Réparation acceptée','REP-261022-OMRMA · décision du consommateur.','http://127.0.0.1:8000/admin/repair-orders/1',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(8,2,'repair_consumer_decision','Réparation acceptée','REP-261022-OMRMA · décision du consommateur.','http://127.0.0.1:8000/admin/repair-orders/1',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(9,4,'resale_offer_received','Nouvelle offre sur « Winter Jacket 02 — reconditionné »','Consommateur Démo propose 80,00  (prix affiché 89,00).','http://127.0.0.1:8000/brand/marketplace',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(10,1,'repair_consumer_decision','Réparation acceptée','REP-261022-SE3HG · décision du consommateur.','http://127.0.0.1:8000/admin/repair-orders/2',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(11,2,'repair_consumer_decision','Réparation acceptée','REP-261022-SE3HG · décision du consommateur.','http://127.0.0.1:8000/admin/repair-orders/2',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `brand_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sector` varchar(255) DEFAULT NULL,
  `founded_year` smallint(5) unsigned DEFAULT NULL,
  `employee_range` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `commitments` text DEFAULT NULL,
  `target_markets` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `onboarding_status` varchar(255) NOT NULL DEFAULT 'approved',
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_user_id_unique` (`user_id`),
  UNIQUE KEY `brands_slug_unique` (`slug`),
  CONSTRAINT `brands_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,4,'EcoFashion',NULL,'ecofashion',NULL,'Marque de démonstration engagée dans la traçabilité et la circularité textile.',NULL,NULL,NULL,'https://example.com',NULL,1,'2026-10-22 06:59:52','2026-10-22 06:59:52','Tunisie',NULL,NULL,'Améliorer la traçabilité des matières, la réparabilité et la collecte en fin de vie.',NULL,'brand@textilecycle.test',NULL,'approved');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('textilecycle-cache-5c785c036466adea360111aa28563bfd556b5fba','i:1;',1792660429),('textilecycle-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1792660429;',1792660429),('textilecycle-cache-902ba3cda1883801594b6e1b452790cc53948fda','i:2;',1792660225),('textilecycle-cache-902ba3cda1883801594b6e1b452790cc53948fda:timer','i:1792660225;',1792660225),('textilecycle-cache-fe5dbbcea5ce7e2988b8c69bcfdfde8904aabc1f','i:1;',1792660466),('textilecycle-cache-fe5dbbcea5ce7e2988b8c69bcfdfde8904aabc1f:timer','i:1792660466;',1792660466);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carbon_footprints`
--

DROP TABLE IF EXISTS `carbon_footprints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carbon_footprints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_impact_report_id` bigint(20) unsigned NOT NULL,
  `stage` varchar(255) NOT NULL,
  `kg_co2e` decimal(12,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `carbon_footprints_product_impact_report_id_foreign` (`product_impact_report_id`),
  CONSTRAINT `carbon_footprints_product_impact_report_id_foreign` FOREIGN KEY (`product_impact_report_id`) REFERENCES `product_impact_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carbon_footprints`
--

LOCK TABLES `carbon_footprints` WRITE;
/*!40000 ALTER TABLE `carbon_footprints` DISABLE KEYS */;
INSERT INTO `carbon_footprints` VALUES (1,1,'materials',9.660,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(2,1,'manufacturing',4.000,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(3,1,'transport',0.800,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(4,1,'end_of_life',0.200,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(5,2,'materials',0.375,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(6,2,'manufacturing',1.000,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(7,2,'transport',0.200,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(8,2,'end_of_life',0.050,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(9,3,'materials',1.733,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(10,3,'manufacturing',3.600,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(11,3,'transport',0.720,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(12,3,'end_of_life',0.180,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(13,4,'materials',25.431,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(14,4,'manufacturing',5.600,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(15,4,'transport',1.120,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(16,4,'end_of_life',0.280,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(17,5,'materials',3.392,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(18,5,'manufacturing',3.200,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(19,5,'transport',0.640,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(20,5,'end_of_life',0.160,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(21,6,'materials',1.653,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(22,6,'manufacturing',2.400,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(23,6,'transport',0.480,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(24,6,'end_of_life',0.120,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(25,7,'materials',0.924,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(26,7,'manufacturing',1.400,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(27,7,'transport',0.280,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(28,7,'end_of_life',0.070,'2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `carbon_footprints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carbon_targets`
--

DROP TABLE IF EXISTS `carbon_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carbon_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `season` varchar(40) DEFAULT NULL,
  `budget_kg` decimal(12,2) DEFAULT NULL,
  `max_unit_kg` decimal(8,3) DEFAULT NULL,
  `planned_units` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`planned_units`)),
  `deadline` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `carbon_targets_brand_id_foreign` (`brand_id`),
  KEY `carbon_targets_created_by_foreign` (`created_by`),
  CONSTRAINT `carbon_targets_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `carbon_targets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carbon_targets`
--

LOCK TABLES `carbon_targets` WRITE;
/*!40000 ALTER TABLE `carbon_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `carbon_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Réemploi','reemploi','Conseils pour prolonger la vie de vos vêtements.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,'Recyclage textile','recyclage-textile','Comprendre les filières de recyclage des matières.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,'Impact environnemental','impact-environnemental','Décryptage des enjeux écologiques de la mode.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(4,'Réparation','reparation','Astuces et retours d’expérience sur la réparation textile.','2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificate_alerts`
--

DROP TABLE IF EXISTS `certificate_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_origin_certificate_id` bigint(20) unsigned NOT NULL,
  `threshold` smallint(5) unsigned NOT NULL,
  `sent_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cert_alerts_threshold_unique` (`material_origin_certificate_id`,`threshold`),
  CONSTRAINT `certificate_alerts_material_origin_certificate_id_foreign` FOREIGN KEY (`material_origin_certificate_id`) REFERENCES `material_origin_certificates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificate_alerts`
--

LOCK TABLES `certificate_alerts` WRITE;
/*!40000 ALTER TABLE `certificate_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificate_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_categories`
--

DROP TABLE IF EXISTS `chatbot_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chatbot_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chatbot_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_categories`
--

LOCK TABLES `chatbot_categories` WRITE;
/*!40000 ALTER TABLE `chatbot_categories` DISABLE KEYS */;
INSERT INTO `chatbot_categories` VALUES (1,'Compte & général','compte-general','Inscription, tarifs et fonctionnement général de la plateforme.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,'Smart Scan & passeport','smart-scan-passeport','Scanner un produit et consulter son passeport textile.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,'Retours & collecte','retours-collecte','Déposer un vêtement usagé et trouver un point de collecte.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(4,'Réparation & seconde vie','reparation-seconde-vie','Faire réparer, revendre ou échanger un produit.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(5,'Recyclage & impact','recyclage-impact','Comprendre le recyclage des matières et l’impact environnemental.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(6,'Marques, labels & fournisseurs','marques-labels-fournisseurs','Inscription des marques, labels écologiques et traçabilité.','2026-10-22 06:59:55','2026-10-22 06:59:55'),(7,'Récompenses & communauté','recompenses-communaute','Points fidélité, réclamations et blog de sensibilisation.','2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `chatbot_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_query_logs`
--

DROP TABLE IF EXISTS `chatbot_query_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chatbot_query_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question_text` varchar(500) NOT NULL,
  `matched_question_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chatbot_query_logs_matched_question_id_foreign` (`matched_question_id`),
  CONSTRAINT `chatbot_query_logs_matched_question_id_foreign` FOREIGN KEY (`matched_question_id`) REFERENCES `chatbot_questions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_query_logs`
--

LOCK TABLES `chatbot_query_logs` WRITE;
/*!40000 ALTER TABLE `chatbot_query_logs` DISABLE KEYS */;
INSERT INTO `chatbot_query_logs` VALUES (1,'password',NULL,'9597997e-6684-4df0-b48a-6498942178e5','2026-10-22 06:55:32','2026-10-22 06:55:32'),(2,'Comment réinitialiser mon mot de passe ?',2,'9597997e-6684-4df0-b48a-6498942178e5','2026-10-22 07:05:40','2026-10-22 07:05:40');
/*!40000 ALTER TABLE `chatbot_query_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_questions`
--

DROP TABLE IF EXISTS `chatbot_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chatbot_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chatbot_category_id` bigint(20) unsigned NOT NULL,
  `question` varchar(255) NOT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `answer` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chatbot_questions_chatbot_category_id_foreign` (`chatbot_category_id`),
  CONSTRAINT `chatbot_questions_chatbot_category_id_foreign` FOREIGN KEY (`chatbot_category_id`) REFERENCES `chatbot_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_questions`
--

LOCK TABLES `chatbot_questions` WRITE;
/*!40000 ALTER TABLE `chatbot_questions` DISABLE KEYS */;
INSERT INTO `chatbot_questions` VALUES (1,1,'Comment créer un compte sur TexTileCycle ?','inscription, créer compte, s\'inscrire','Cliquez sur « Créer un compte » en haut de la page d\'accueil, renseignez votre nom, votre e-mail et un mot de passe. Un e-mail de confirmation vous sera envoyé.',1,0,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,1,'Comment réinitialiser mon mot de passe ?','mot de passe oublié, réinitialiser, reset password','Depuis la page de connexion, cliquez sur « Mot de passe oublié » et suivez les instructions reçues par e-mail pour en créer un nouveau.',1,1,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,1,'TexTileCycle est-il gratuit ?','gratuit, prix, tarif, payant','Le service est entièrement gratuit pour les particuliers (scan, retours, réparation, récompenses). Les marques disposent d\'un espace professionnel avec inscription validée par notre équipe.',1,2,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(4,1,'Quels rôles existent sur la plateforme ?','rôle, consommateur, marque, collecteur, admin','Quatre profils existent : consommateur (scan, retours, récompenses), marque (passeports produits, roadmaps), collecteur (gestion des points de collecte) et administrateur (pilotage global).',1,3,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(5,2,'Comment scanner un produit ?','scanner, scan, qr code, étiquette','Utilisez le Smart Scan depuis votre espace : prenez en photo l\'étiquette, le ticket ou le QR code du produit. Notre moteur de détection identifie automatiquement la matière et la marque.',1,4,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(6,2,'Qu\'est-ce qu\'un passeport produit ?','passeport, passeport textile, historique produit','Le passeport produit regroupe la composition matière, l\'origine, les instructions d\'entretien et l\'historique de traçabilité fournisseur d\'un vêtement, accessible via son Smart Scan.',1,5,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(7,2,'Comment savoir si un vêtement est recyclable ?','recyclable, recyclabilité, label écologique','Après un scan, la fiche produit affiche un score de circularité ainsi que les labels écologiques associés qui indiquent sa recyclabilité.',1,6,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(8,2,'Le scan fonctionne-t-il sans connexion caméra ?','caméra indisponible, saisie manuelle, scanner sans caméra','Si votre navigateur ne permet pas d\'accéder à la caméra, vous pouvez saisir manuellement le code ou les informations du produit dans le formulaire de scan.',1,7,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(9,3,'Comment retourner un vêtement usagé ?','retour, don, vêtement usagé, déclarer un retour','Depuis votre espace, déclarez un retour en indiquant le produit, son poids approximatif et le point de collecte choisi. Vous recevez ensuite une référence de suivi.',1,8,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(10,3,'Où se trouve le point de collecte le plus proche ?','point de collecte, localisation, carte','La page des points de collecte affiche les emplacements disponibles ; vous pouvez activer la géolocalisation pour trouver le point le plus proche de vous.',1,9,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(11,3,'Qui récupère mes vêtements déposés ?','collecteur, qui récupère, ramassage','Les collecteurs partenaires, rattachés à chaque programme de récupération, gèrent la collecte et le suivi des retours déposés en point de collecte.',1,10,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(12,3,'Que deviennent mes vêtements après le retour ?','statut retour, suivi, traitement','Chaque retour passe par les étapes déclaré, reçu, tri, traité. Vous pouvez suivre ce statut à tout moment depuis votre historique de retours.',1,11,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(13,3,'Puis-je annuler un retour déclaré ?','annuler retour, modifier retour','Un retour peut être modifié tant qu\'il est au statut « déclaré ». Contactez le point de collecte concerné pour toute annulation après ce stade.',1,12,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(14,4,'Puis-je faire réparer un produit ?','réparation, réparer, atelier','Oui, déposez une demande de réparation en précisant le produit et le défaut constaté. Vous pourrez choisir un atelier partenaire ou un centre de réparation proche de vous.',1,13,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(15,4,'Combien coûte une réparation ?','prix réparation, coût, devis','Le tarif dépend de l\'atelier et du type de réparation. Un devis personnalisé vous est communiqué après l\'examen de votre demande.',1,14,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(16,4,'Qu\'est-ce que la Marketplace de seconde main ?','marketplace, revente, seconde main, occasion','La Marketplace permet de proposer à la revente des vêtements en bon état, dans une logique de prolongation de leur durée de vie.',1,15,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(17,4,'Comment fonctionne la reprise d\'un vêtement (trade-in) ?','reprise, trade-in, échange','La reprise permet d\'échanger un vêtement usagé contre des points de récompense ou un avantage auprès des marques partenaires participantes.',1,16,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(18,4,'Quelle différence entre un atelier partenaire et un centre de réparation ?','atelier partenaire, centre de réparation, différence','Les centres de réparation sont des structures internes référencées sur la plateforme, tandis que les ateliers partenaires sont des professionnels indépendants affiliés à TexTileCycle.',1,17,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(19,5,'Comment sont traitées les matières collectées ?','traitement matière, filière recyclage','Chaque retour traité génère un résultat de traitement détaillant les pourcentages recyclés, réemployés et perdus, selon la filière adaptée à la matière.',1,18,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(20,5,'Qu\'est-ce que l\'impact environnemental affiché sur mon profil ?','impact environnemental, rapport esg, bilan carbone','Nos rapports d\'impact calculent, par catégorie et par matière, les bénéfices environnementaux (eau économisée, CO2 évité) générés par vos retours et réparations.',1,19,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(21,5,'Comment identifier la fibre d\'un vêtement ?','identifier fibre, reconnaître matière, composition','L\'outil de reconnaissance de matière analyse votre vêtement et propose une estimation de sa composition textile dominante.',1,20,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(22,5,'Qu\'est-ce que le greenwashing et comment le détecter ?','greenwashing, allégation écologique, analyse impact','Le greenwashing désigne des allégations écologiques exagérées ou trompeuses. Notre analyseur d\'allégations vertes évalue la cohérence des discours environnementaux des marques.',1,21,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(23,5,'Toutes les matières sont-elles recyclables de la même façon ?','matière recyclable, mélange fibre, coton polyester','Non, les matières pures comme le coton ou le polyester se recyclent plus facilement que les mélanges de fibres, ce qui influence le taux de recyclabilité d\'un vêtement.',1,22,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(24,6,'Comment une marque s\'inscrit-elle sur la plateforme ?','inscription marque, candidature marque','Une marque dépose une demande d\'inscription avec ses informations ; notre équipe l\'examine avant validation et activation de l\'espace marque.',1,23,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(25,6,'Que signifient les labels écologiques affichés sur un produit ?','label écologique, certification, signification label','Les labels écologiques certifient des engagements précis (origine des matières, absence de substances nocives, conditions de production) vérifiés par notre référentiel textile.',1,24,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(26,6,'Comment la traçabilité des lots de matière est-elle vérifiée ?','traçabilité, lot, fournisseur, conformité','Chaque lot fourni par un fournisseur est associé à des preuves de conformité, contrôlées par notre équipe via les dossiers de traçabilité des lots.',1,25,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(27,6,'Comment une marque propose-t-elle un nouveau passeport produit ?','proposition passeport, validation produit','Les marques soumettent une proposition de passeport produit depuis leur espace ; elle est examinée et validée par notre équipe avant publication.',1,26,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(28,7,'Comment gagner des points de récompense ?','points, récompense, fidélité, gagner points','Vous gagnez des points à chaque retour traité, réparation effectuée ou participation à un programme de récupération, échangeables contre des récompenses du catalogue.',1,27,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(29,7,'Comment déposer une réclamation ?','réclamation, litige, problème commande','Depuis votre espace consommateur, ouvrez une réclamation en décrivant votre problème ; notre équipe vous répond directement sur le dossier.',1,28,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(30,7,'Où lire les articles de sensibilisation ?','blog, articles, sensibilisation, conseils','Notre blog propose des articles sur le réemploi, le recyclage, l\'impact environnemental et la réparation, accessibles depuis le menu « Blog ».',1,29,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(31,7,'Puis-je exporter mes données personnelles ?','export données, rgpd, télécharger mes données','Oui, un export de vos données personnelles est disponible depuis votre espace consommateur, conformément à la réglementation RGPD.',1,30,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `chatbot_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection_points`
--

DROP TABLE IF EXISTS `collection_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collection_points` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recovery_program_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `opening_hours` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `operator_user_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collection_points_recovery_program_id_foreign` (`recovery_program_id`),
  KEY `collection_points_operator_user_id_foreign` (`operator_user_id`),
  CONSTRAINT `collection_points_operator_user_id_foreign` FOREIGN KEY (`operator_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `collection_points_recovery_program_id_foreign` FOREIGN KEY (`recovery_program_id`) REFERENCES `recovery_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection_points`
--

LOCK TABLES `collection_points` WRITE;
/*!40000 ALTER TABLE `collection_points` DISABLE KEYS */;
INSERT INTO `collection_points` VALUES (1,1,'EcoFashion Lac 2','Rue de la Bourse, Les Berges du Lac 2','Tunis',36.8487000,10.2685000,'Lun–Sam · 09:00–19:00',1,'2026-10-22 06:59:50','2026-10-22 06:59:50',NULL),(2,1,'EcoFashion El Manar','Centre commercial El Manar','Tunis',36.8398000,10.1581000,'Lun–Dim · 10:00–20:00',1,'2026-10-22 06:59:50','2026-10-22 06:59:50',NULL),(3,2,'Atelier Seconde Vie Ariana','Avenue Hédi Nouira, Ennasr 2','Ariana',36.8665000,10.1647000,'Mar–Sam · 09:30–18:30',1,'2026-10-22 06:59:50','2026-10-22 06:59:50',NULL),(4,3,'Maison du Réemploi Sousse','Avenue de la République','Sousse',35.8256000,10.6369000,'Lun–Ven · 08:30–17:00',1,'2026-10-22 06:59:50','2026-10-22 06:59:50',NULL),(5,3,'Hub Solidaire Sfax','Route de Tunis km 3','Sfax',34.7538000,10.7460000,'Lun–Sam · 09:00–18:00',1,'2026-10-22 06:59:50','2026-10-22 06:59:50',NULL),(6,4,'BlueStep Marsa Cube','La Marsa Cube','La Marsa',36.8869000,10.3266000,'Lun–Dim · 10:00–20:00',1,'2026-10-22 06:59:50','2026-10-22 06:59:50',NULL),(7,4,'BlueStep Nabeul','Avenue Habib Bourguiba','Nabeul',36.4513000,10.7352000,'Lun–Sam · 09:00–19:00',1,'2026-10-22 06:59:50','2026-10-22 06:59:50',NULL);
/*!40000 ALTER TABLE `collection_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_comment_mentions`
--

DROP TABLE IF EXISTS `community_comment_mentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_comment_mentions` (
  `community_comment_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`community_comment_id`,`user_id`),
  KEY `community_comment_mentions_user_id_foreign` (`user_id`),
  CONSTRAINT `community_comment_mentions_community_comment_id_foreign` FOREIGN KEY (`community_comment_id`) REFERENCES `community_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_comment_mentions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_comment_mentions`
--

LOCK TABLES `community_comment_mentions` WRITE;
/*!40000 ALTER TABLE `community_comment_mentions` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_comment_mentions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_comments`
--

DROP TABLE IF EXISTS `community_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_post_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_comments_user_id_foreign` (`user_id`),
  KEY `community_comments_community_post_id_created_at_index` (`community_post_id`,`created_at`),
  CONSTRAINT `community_comments_community_post_id_foreign` FOREIGN KEY (`community_post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_comments`
--

LOCK TABLES `community_comments` WRITE;
/*!40000 ALTER TABLE `community_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_post_likes`
--

DROP TABLE IF EXISTS `community_post_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_post_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_post_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_post_likes_community_post_id_user_id_unique` (`community_post_id`,`user_id`),
  KEY `community_post_likes_user_id_foreign` (`user_id`),
  CONSTRAINT `community_post_likes_community_post_id_foreign` FOREIGN KEY (`community_post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_post_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_post_likes`
--

LOCK TABLES `community_post_likes` WRITE;
/*!40000 ALTER TABLE `community_post_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_post_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_post_mentions`
--

DROP TABLE IF EXISTS `community_post_mentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_post_mentions` (
  `community_post_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`community_post_id`,`user_id`),
  KEY `community_post_mentions_user_id_foreign` (`user_id`),
  CONSTRAINT `community_post_mentions_community_post_id_foreign` FOREIGN KEY (`community_post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_post_mentions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_post_mentions`
--

LOCK TABLES `community_post_mentions` WRITE;
/*!40000 ALTER TABLE `community_post_mentions` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_post_mentions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_posts`
--

DROP TABLE IF EXISTS `community_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `repost_of_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_repost_user_unique` (`user_id`,`repost_of_id`),
  KEY `community_posts_repost_of_id_foreign` (`repost_of_id`),
  KEY `community_posts_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `community_posts_repost_of_id_foreign` FOREIGN KEY (`repost_of_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_posts`
--

LOCK TABLES `community_posts` WRITE;
/*!40000 ALTER TABLE `community_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumer_claims`
--

DROP TABLE IF EXISTS `consumer_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consumer_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_return_id` bigint(20) unsigned DEFAULT NULL,
  `repair_order_id` bigint(20) unsigned DEFAULT NULL,
  `trade_in_request_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `priority` varchar(255) NOT NULL DEFAULT 'normal',
  `admin_response` text DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consumer_claims_reference_unique` (`reference`),
  KEY `consumer_claims_user_id_foreign` (`user_id`),
  KEY `consumer_claims_product_return_id_foreign` (`product_return_id`),
  KEY `consumer_claims_repair_order_id_foreign` (`repair_order_id`),
  KEY `consumer_claims_trade_in_request_id_foreign` (`trade_in_request_id`),
  KEY `consumer_claims_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `consumer_claims_product_return_id_foreign` FOREIGN KEY (`product_return_id`) REFERENCES `product_returns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumer_claims_repair_order_id_foreign` FOREIGN KEY (`repair_order_id`) REFERENCES `repair_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumer_claims_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumer_claims_trade_in_request_id_foreign` FOREIGN KEY (`trade_in_request_id`) REFERENCES `trade_in_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumer_claims_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumer_claims`
--

LOCK TABLES `consumer_claims` WRITE;
/*!40000 ALTER TABLE `consumer_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `consumer_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumer_notifications`
--

DROP TABLE IF EXISTS `consumer_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consumer_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consumer_notifications_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `consumer_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumer_notifications`
--

LOCK TABLES `consumer_notifications` WRITE;
/*!40000 ALTER TABLE `consumer_notifications` DISABLE KEYS */;
INSERT INTO `consumer_notifications` VALUES (1,3,'demo_return_completed','Retour valorisé','Le retour RET-2026-0002 est terminé et les points sont validés.','http://127.0.0.1:8000/product-returns/2',NULL,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(2,3,'demo_return_completed','Retour valorisé','Le retour RET-2026-0003 est terminé et les points sont validés.','http://127.0.0.1:8000/product-returns/3',NULL,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(3,3,'demo_return_received','Retour en cours','Le retour RET-2026-0005 est actuellement au statut « Reçu ».','http://127.0.0.1:8000/product-returns/5',NULL,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(4,3,'demo_return_declared','Retour en cours','Le retour RET-2026-0006 est actuellement au statut « Déclaré ».','http://127.0.0.1:8000/product-returns/6',NULL,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(5,2,'repair_diagnosed','Diagnostic de votre article','REP-261022-OMRMA : Reconditionner et revendre.','http://127.0.0.1:8000/discover/repairs',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(6,2,'second_life','Votre article a une seconde vie','Winter Jacket 02 — reconditionné est en vente sur le marketplace : +60 points.','http://127.0.0.1:8000/discover/marketplace/1',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(7,3,'repair_diagnosed','Diagnostic de votre article','REP-261022-SE3HG : Réparer et rendre.','http://127.0.0.1:8000/discover/repairs',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(8,3,'trade_in_evaluated','Offre de reprise disponible','Pull en laine : 35 points proposés.','http://127.0.0.1:8000/discover/trade-ins',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `consumer_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detections`
--

DROP TABLE IF EXISTS `detections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scan_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `detected_brand` varchar(255) DEFAULT NULL,
  `detected_name` varchar(255) NOT NULL,
  `detected_category` varchar(255) DEFAULT NULL,
  `detected_composition` text DEFAULT NULL,
  `confidence` decimal(5,2) DEFAULT NULL,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detections_scan_id_foreign` (`scan_id`),
  KEY `detections_product_id_foreign` (`product_id`),
  CONSTRAINT `detections_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `detections_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detections`
--

LOCK TABLES `detections` WRITE;
/*!40000 ALTER TABLE `detections` DISABLE KEYS */;
INSERT INTO `detections` VALUES (1,1,1,'EcoFashion','Winter Jacket 02','Veste','60 % polyester recyclé, 40 % laine mérinos',94.50,1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(2,2,2,'Circular Studio','ReLoop Shirt','Chemise','70 % coton recyclé, 30 % lin européen',96.20,1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(3,3,3,'BlueStep','Ocean Runner','Chaussures','65 % polyester recyclé, 35 % caoutchouc recyclé',98.10,1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(4,4,5,'Verde Atelier','Cactus City Bag','Sac','80 % alternative cuir de cactus, 20 % coton recyclé',99.00,1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(5,5,6,'Terra Denim','Hemp Denim 2030','Pantalon','55 % chanvre, 45 % coton biologique',93.80,1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(6,6,8,'Heritage Walk','Classic Leather Boots','Chaussures','75 % cuir bovin, 25 % caoutchouc recyclé',91.40,1,'2026-10-22 06:59:51','2026-10-22 06:59:51');
/*!40000 ALTER TABLE `detections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evidence_comments`
--

DROP TABLE IF EXISTS `evidence_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evidence_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evidence_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evidence_comments_evidence_id_foreign` (`evidence_id`),
  KEY `evidence_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `evidence_comments_evidence_id_foreign` FOREIGN KEY (`evidence_id`) REFERENCES `evidences` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evidence_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evidence_comments`
--

LOCK TABLES `evidence_comments` WRITE;
/*!40000 ALTER TABLE `evidence_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `evidence_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evidences`
--

DROP TABLE IF EXISTS `evidences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evidences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `expires_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `label_step_id` bigint(20) unsigned DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `declaration` text DEFAULT NULL,
  `admin_comment` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `supersedes_id` bigint(20) unsigned DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `evidences_assessment_id_foreign` (`assessment_id`),
  KEY `evidences_label_step_id_foreign` (`label_step_id`),
  KEY `evidences_reviewed_by_foreign` (`reviewed_by`),
  KEY `evidences_supersedes_id_foreign` (`supersedes_id`),
  CONSTRAINT `evidences_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evidences_label_step_id_foreign` FOREIGN KEY (`label_step_id`) REFERENCES `label_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evidences_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evidences_supersedes_id_foreign` FOREIGN KEY (`supersedes_id`) REFERENCES `evidences` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evidences`
--

LOCK TABLES `evidences` WRITE;
/*!40000 ALTER TABLE `evidences` DISABLE KEYS */;
INSERT INTO `evidences` VALUES (1,1,'Rapport annuel de collecte 2026','report',NULL,'verified','2027-10-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(2,1,'Registre des consommations d’eau','dataset',NULL,'pending','2027-06-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(3,2,'Passeport matières ReLoop','traceability',NULL,'verified','2028-04-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(4,2,'Rapport de réparabilité','audit',NULL,'verified','2027-08-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(5,3,'Certificat fournisseur laine','certificate',NULL,'verified','2027-07-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(6,3,'Audit bien-être animal','audit',NULL,'pending','2027-03-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(7,4,'Bilan Footwear Loop','report',NULL,'pending','2027-05-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(8,4,'Traçabilité caoutchouc','traceability',NULL,'pending','2027-04-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(9,5,'Déclaration de composition','certificate',NULL,'verified','2028-01-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),(10,5,'Analyse environnementale cactus','report',NULL,'verified','2027-09-22','Donnée de démonstration TexTileCycle.','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `evidences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `impact_categories`
--

DROP TABLE IF EXISTS `impact_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `impact_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `weight` decimal(4,2) NOT NULL DEFAULT 0.25,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `impact_categories_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `impact_categories`
--

LOCK TABLES `impact_categories` WRITE;
/*!40000 ALTER TABLE `impact_categories` DISABLE KEYS */;
INSERT INTO `impact_categories` VALUES (1,'co2','Empreinte carbone','kg CO₂e',0.40,'Gaz à effet de serre émis sur le cycle de vie.','2026-10-22 06:59:52','2026-10-22 06:59:52'),(2,'water','Consommation d’eau','L',0.25,'Eau consommée par la culture, la teinture et la fabrication.','2026-10-22 06:59:52','2026-10-22 06:59:52'),(3,'energy','Énergie','kWh',0.20,'Énergie primaire nécessaire à la transformation.','2026-10-22 06:59:52','2026-10-22 06:59:52'),(4,'waste','Déchets','kg',0.15,'Chutes de production et fin de vie non valorisée.','2026-10-22 06:59:52','2026-10-22 06:59:52');
/*!40000 ALTER TABLE `impact_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `impact_metrics`
--

DROP TABLE IF EXISTS `impact_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `impact_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_id` bigint(20) unsigned NOT NULL,
  `impact_category_id` bigint(20) unsigned NOT NULL,
  `value_per_kg` decimal(12,4) NOT NULL,
  `source` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `impact_metrics_material_id_impact_category_id_unique` (`material_id`,`impact_category_id`),
  KEY `impact_metrics_impact_category_id_foreign` (`impact_category_id`),
  CONSTRAINT `impact_metrics_impact_category_id_foreign` FOREIGN KEY (`impact_category_id`) REFERENCES `impact_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `impact_metrics_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `impact_metrics`
--

LOCK TABLES `impact_metrics` WRITE;
/*!40000 ALTER TABLE `impact_metrics` DISABLE KEYS */;
INSERT INTO `impact_metrics` VALUES (1,20,1,3.8000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(2,20,2,2500.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(3,20,3,35.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(4,20,4,0.1200,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(5,19,1,1.2000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(6,19,2,150.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(7,19,3,12.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(8,19,4,0.0400,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(9,22,1,2.1000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(10,22,2,40.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(11,22,3,30.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(12,22,4,0.0500,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(13,21,1,21.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(14,21,2,1500.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(15,21,3,60.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(16,21,4,0.2000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(17,23,1,2.2000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(18,23,2,300.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(19,23,3,20.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(20,23,4,0.0800,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(21,24,1,1.9000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(22,24,2,400.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(23,24,3,18.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(24,24,4,0.0700,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(25,25,1,2.8000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(26,25,2,450.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(27,25,3,45.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(28,25,4,0.0600,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(29,26,1,17.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(30,26,2,15000.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(31,26,3,70.0000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52'),(32,26,4,0.4000,'Valeur indicative (démo)','2026-10-22 06:59:52','2026-10-22 06:59:52');
/*!40000 ALTER TABLE `impact_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `label_roadmap_product`
--

DROP TABLE IF EXISTS `label_roadmap_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `label_roadmap_product` (
  `label_roadmap_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  PRIMARY KEY (`label_roadmap_id`,`product_id`),
  KEY `label_roadmap_product_product_id_foreign` (`product_id`),
  CONSTRAINT `label_roadmap_product_label_roadmap_id_foreign` FOREIGN KEY (`label_roadmap_id`) REFERENCES `label_roadmaps` (`id`) ON DELETE CASCADE,
  CONSTRAINT `label_roadmap_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `label_roadmap_product`
--

LOCK TABLES `label_roadmap_product` WRITE;
/*!40000 ALTER TABLE `label_roadmap_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `label_roadmap_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `label_roadmaps`
--

DROP TABLE IF EXISTS `label_roadmaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `label_roadmaps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `brand_member_id` bigint(20) unsigned DEFAULT NULL,
  `assignment_priority` varchar(255) NOT NULL DEFAULT 'normal',
  `assignment_comment` text DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `label_id` bigint(20) unsigned NOT NULL,
  `product_label_recommendation_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `scope_description` text NOT NULL,
  `baseline` text NOT NULL,
  `objective` text NOT NULL,
  `starts_on` date NOT NULL,
  `target_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `scope_version` int(10) unsigned NOT NULL DEFAULT 1,
  `template_version` varchar(255) NOT NULL DEFAULT 'preparation-2026-v1',
  `label_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`label_snapshot`)),
  `specification` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specification`)),
  `impact_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`impact_summary`)),
  `ai_assessment_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_assessment_snapshot`)),
  `certification_estimate` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certification_estimate`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `label_roadmaps_brand_id_foreign` (`brand_id`),
  KEY `label_roadmaps_label_id_foreign` (`label_id`),
  KEY `label_roadmaps_product_label_recommendation_id_foreign` (`product_label_recommendation_id`),
  KEY `label_roadmaps_brand_member_id_foreign` (`brand_member_id`),
  CONSTRAINT `label_roadmaps_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `label_roadmaps_brand_member_id_foreign` FOREIGN KEY (`brand_member_id`) REFERENCES `brand_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `label_roadmaps_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`),
  CONSTRAINT `label_roadmaps_product_label_recommendation_id_foreign` FOREIGN KEY (`product_label_recommendation_id`) REFERENCES `product_label_recommendations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `label_roadmaps`
--

LOCK TABLES `label_roadmaps` WRITE;
/*!40000 ALTER TABLE `label_roadmaps` DISABLE KEYS */;
/*!40000 ALTER TABLE `label_roadmaps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `label_steps`
--

DROP TABLE IF EXISTS `label_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `label_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `weight` tinyint(3) unsigned NOT NULL DEFAULT 25,
  PRIMARY KEY (`id`),
  KEY `label_steps_label_id_foreign` (`label_id`),
  CONSTRAINT `label_steps_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `label_steps`
--

LOCK TABLES `label_steps` WRITE;
/*!40000 ALTER TABLE `label_steps` DISABLE KEYS */;
INSERT INTO `label_steps` VALUES (1,1,'Définir la gamme et vérifier le référentiel officiel en vigueur',NULL,1,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(2,1,'Documenter la composition et les fournisseurs',NULL,2,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(3,1,'Identifier les écarts et réunir les tests et justificatifs',NULL,3,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(4,1,'Préparer la revue auprès de l’organisme officiel',NULL,4,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(5,2,'Définir la gamme et vérifier le référentiel officiel en vigueur',NULL,1,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(6,2,'Documenter la composition et les fournisseurs',NULL,2,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(7,2,'Identifier les écarts et réunir les tests et justificatifs',NULL,3,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(8,2,'Préparer la revue auprès de l’organisme officiel',NULL,4,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(9,3,'Définir la gamme et vérifier le référentiel officiel en vigueur',NULL,1,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(10,3,'Documenter la composition et les fournisseurs',NULL,2,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(11,3,'Identifier les écarts et réunir les tests et justificatifs',NULL,3,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(12,3,'Préparer la revue auprès de l’organisme officiel',NULL,4,'2026-10-22 06:23:27','2026-10-22 06:23:27',25),(13,4,'Périmètre et engagement','Définir les produits, sites et responsabilités concernés.',1,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(14,4,'Traçabilité documentaire','Réunir fournisseurs, factures, compositions et chaîne de contrôle.',2,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(15,4,'Pratiques et mesures','Documenter les procédures et indicateurs correspondant au référentiel.',3,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(16,4,'Revue des preuves','Vérifier la cohérence et la validité des pièces avant soumission.',4,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(17,5,'Périmètre et engagement','Définir les produits, sites et responsabilités concernés.',1,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(18,5,'Traçabilité documentaire','Réunir fournisseurs, factures, compositions et chaîne de contrôle.',2,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(19,5,'Pratiques et mesures','Documenter les procédures et indicateurs correspondant au référentiel.',3,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(20,5,'Revue des preuves','Vérifier la cohérence et la validité des pièces avant soumission.',4,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(21,6,'Périmètre et engagement','Définir les produits, sites et responsabilités concernés.',1,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(22,6,'Traçabilité documentaire','Réunir fournisseurs, factures, compositions et chaîne de contrôle.',2,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(23,6,'Pratiques et mesures','Documenter les procédures et indicateurs correspondant au référentiel.',3,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(24,6,'Revue des preuves','Vérifier la cohérence et la validité des pièces avant soumission.',4,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(25,7,'Périmètre et engagement','Définir les produits, sites et responsabilités concernés.',1,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(26,7,'Traçabilité documentaire','Réunir fournisseurs, factures, compositions et chaîne de contrôle.',2,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(27,7,'Pratiques et mesures','Documenter les procédures et indicateurs correspondant au référentiel.',3,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(28,7,'Revue des preuves','Vérifier la cohérence et la validité des pièces avant soumission.',4,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(29,8,'Périmètre et engagement','Définir les produits, sites et responsabilités concernés.',1,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(30,8,'Traçabilité documentaire','Réunir fournisseurs, factures, compositions et chaîne de contrôle.',2,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(31,8,'Pratiques et mesures','Documenter les procédures et indicateurs correspondant au référentiel.',3,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(32,8,'Revue des preuves','Vérifier la cohérence et la validité des pièces avant soumission.',4,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(33,9,'Périmètre et engagement','Définir les produits, sites et responsabilités concernés.',1,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(34,9,'Traçabilité documentaire','Réunir fournisseurs, factures, compositions et chaîne de contrôle.',2,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(35,9,'Pratiques et mesures','Documenter les procédures et indicateurs correspondant au référentiel.',3,'2026-10-22 06:59:52','2026-10-22 06:59:52',25),(36,9,'Revue des preuves','Vérifier la cohérence et la validité des pièces avant soumission.',4,'2026-10-22 06:59:52','2026-10-22 06:59:52',25);
/*!40000 ALTER TABLE `label_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `labels`
--

DROP TABLE IF EXISTS `labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `labels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `pillar` enum('environment','animals','social','circularity') NOT NULL,
  `description` text NOT NULL,
  `official_url` varchar(255) DEFAULT NULL,
  `cost_hint` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `labels_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labels`
--

LOCK TABLES `labels` WRITE;
/*!40000 ALTER TABLE `labels` DISABLE KEYS */;
INSERT INTO `labels` VALUES (1,'Recycled Claim Standard (RCS)','recycled-claim-standard-rcs','Textile Exchange','circularity','Démarche relative au contenu recyclé et à sa chaîne de contrôle. La recyclabilité seule ne prouve pas l’éligibilité.','https://www.textileexchange.org/recycled-claim-global-recycled-standard/','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:23:27','2026-10-22 06:23:27'),(2,'EU Ecolabel — Textiles','eu-ecolabel-textiles','Commission européenne','environment','Démarche couvrant notamment les fibres, substances, procédés de fabrication et la durabilité des produits textiles.','https://green-forum.ec.europa.eu/green-business/eu-ecolabel/product-groups-and-criteria/clothing-and-textiles_en','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:23:27','2026-10-22 06:23:27'),(3,'Vegan Trademark','vegan-trademark','The Vegan Society','animals','Démarche pour des produits sans composants animaux et sans tests sur animaux réalisés par la marque, pour elle ou sous son contrôle. Tous les ingrédients et auxiliaires doivent être vérifiés.','https://www.vegansociety.com/trademark/vegan-trademark-standards','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:23:27','2026-10-22 06:23:27'),(4,'Global Organic Textile Standard (GOTS)','global-organic-textile-standard-gots','Global Standard gGmbH','environment','Référentiel externe relatif aux fibres biologiques et à des critères environnementaux et sociaux.','https://global-standard.org/','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(5,'OEKO-TEX STANDARD 100','oeko-tex-standard-100','OEKO-TEX','environment','Référentiel externe portant sur le contrôle de substances potentiellement nocives.','https://www.oeko-tex.com/','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(6,'Global Recycled Standard (GRS)','global-recycled-standard-grs','Textile Exchange','circularity','Référentiel externe relatif au contenu recyclé et à la chaîne de contrôle.','https://textileexchange.org/','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(7,'Responsible Wool Standard (RWS)','responsible-wool-standard-rws','Textile Exchange','animals','Référentiel externe relatif au bien-être animal et à la traçabilité de la laine.','https://textileexchange.org/','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(8,'Fairtrade Textile Standard','fairtrade-textile-standard','Fairtrade International','social','Référentiel externe portant notamment sur les conditions de travail de la filière textile.','https://www.fairtrade.net/','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(9,'Cradle to Cradle Certified','cradle-to-cradle-certified','Cradle to Cradle Products Innovation Institute','circularity','Référentiel externe d’évaluation de la circularité et de la conception des produits.','https://c2ccertified.org/','Tarification communiquée par l’organisme officiel',1,'2026-10-22 06:59:52','2026-10-22 06:59:52');
/*!40000 ALTER TABLE `labels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketplace_listings`
--

DROP TABLE IF EXISTS `marketplace_listings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketplace_listings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `repair_order_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `try_on_category` varchar(20) NOT NULL DEFAULT 'top',
  `condition_grade` char(1) NOT NULL DEFAULT 'B',
  `price` decimal(10,2) NOT NULL,
  `points_price` int(10) unsigned DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'TND',
  `status` varchar(255) NOT NULL DEFAULT 'published',
  `published_at` timestamp NULL DEFAULT NULL,
  `sold_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `marketplace_listings_product_id_foreign` (`product_id`),
  KEY `marketplace_listings_brand_id_foreign` (`brand_id`),
  KEY `marketplace_listings_repair_order_id_foreign` (`repair_order_id`),
  CONSTRAINT `marketplace_listings_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketplace_listings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketplace_listings_repair_order_id_foreign` FOREIGN KEY (`repair_order_id`) REFERENCES `repair_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_listings`
--

LOCK TABLES `marketplace_listings` WRITE;
/*!40000 ALTER TABLE `marketplace_listings` DISABLE KEYS */;
INSERT INTO `marketplace_listings` VALUES (1,1,1,1,'Winter Jacket 02 — reconditionné','Comme neuf après passage en atelier partenaire.',NULL,'top','A',89.00,650,'TND','published','2026-10-22 06:59:55',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,4,1,NULL,'Heritage Wool Coat — retour boutique réparé','Invendu légèrement abîmé, remis à neuf par la marque.',NULL,'top','A',240.00,1200,'TND','published','2026-10-22 06:59:55',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,4,1,NULL,'Heritage Wool Coat — reconditionné','Aut velit voluptatibus minima delectus sed vel et et eum omnis.',NULL,'dress','B',68.08,600,'TND','published','2026-10-22 06:59:55',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(4,3,1,NULL,'Ocean Runner — reconditionné','Quae ab ipsam est quia neque qui quia eum rerum laboriosam et ut veritatis voluptas.',NULL,'bottom','C',116.58,450,'TND','published','2026-10-22 06:59:55',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `marketplace_listings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketplace_orders`
--

DROP TABLE IF EXISTS `marketplace_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketplace_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) NOT NULL,
  `marketplace_listing_id` bigint(20) unsigned NOT NULL,
  `resale_offer_id` bigint(20) unsigned DEFAULT NULL,
  `buyer_id` bigint(20) unsigned NOT NULL,
  `collection_point_id` bigint(20) unsigned DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `points_spent` int(10) unsigned NOT NULL DEFAULT 0,
  `currency` varchar(3) NOT NULL DEFAULT 'TND',
  `fulfilment_method` varchar(255) NOT NULL DEFAULT 'pickup',
  `payment_method` varchar(255) NOT NULL DEFAULT 'pay_on_pickup',
  `delivery_address` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'awaiting_checkout',
  `checked_out_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marketplace_orders_reference_unique` (`reference`),
  UNIQUE KEY `marketplace_orders_listing_unique` (`marketplace_listing_id`),
  KEY `marketplace_orders_resale_offer_id_foreign` (`resale_offer_id`),
  KEY `marketplace_orders_buyer_id_foreign` (`buyer_id`),
  KEY `marketplace_orders_collection_point_id_foreign` (`collection_point_id`),
  CONSTRAINT `marketplace_orders_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marketplace_orders_collection_point_id_foreign` FOREIGN KEY (`collection_point_id`) REFERENCES `collection_points` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketplace_orders_marketplace_listing_id_foreign` FOREIGN KEY (`marketplace_listing_id`) REFERENCES `marketplace_listings` (`id`),
  CONSTRAINT `marketplace_orders_resale_offer_id_foreign` FOREIGN KEY (`resale_offer_id`) REFERENCES `resale_offers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_orders`
--

LOCK TABLES `marketplace_orders` WRITE;
/*!40000 ALTER TABLE `marketplace_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketplace_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_matches`
--

DROP TABLE IF EXISTS `material_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_matches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_request_id` bigint(20) unsigned NOT NULL,
  `product_return_id` bigint(20) unsigned NOT NULL,
  `match_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `ai_verdict` varchar(255) DEFAULT NULL,
  `ai_confidence` decimal(5,2) DEFAULT NULL,
  `ai_reason` text DEFAULT NULL,
  `ai_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_details`)),
  `ai_source` varchar(255) DEFAULT NULL,
  `ai_analyzed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'suggested',
  `transfer_token` char(36) DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  `transferred_at` timestamp NULL DEFAULT NULL,
  `brand_received_at` timestamp NULL DEFAULT NULL,
  `brand_verified_at` timestamp NULL DEFAULT NULL,
  `brand_review_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_matches_material_request_id_product_return_id_unique` (`material_request_id`,`product_return_id`),
  UNIQUE KEY `material_matches_transfer_token_unique` (`transfer_token`),
  KEY `material_matches_product_return_id_status_index` (`product_return_id`,`status`),
  KEY `material_matches_ai_verdict_status_index` (`ai_verdict`,`status`),
  CONSTRAINT `material_matches_material_request_id_foreign` FOREIGN KEY (`material_request_id`) REFERENCES `material_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_matches_product_return_id_foreign` FOREIGN KEY (`product_return_id`) REFERENCES `product_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_matches`
--

LOCK TABLES `material_matches` WRITE;
/*!40000 ALTER TABLE `material_matches` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_origin_certificates`
--

DROP TABLE IF EXISTS `material_origin_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_origin_certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_batch_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `certificate_number` varchar(255) DEFAULT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `issued_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `ai_extraction` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_extraction`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `review_note` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_origin_certificates_supplier_batch_id_foreign` (`supplier_batch_id`),
  KEY `material_origin_certificates_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `material_origin_certificates_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_origin_certificates_supplier_batch_id_foreign` FOREIGN KEY (`supplier_batch_id`) REFERENCES `supplier_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_origin_certificates`
--

LOCK TABLES `material_origin_certificates` WRITE;
/*!40000 ALTER TABLE `material_origin_certificates` DISABLE KEYS */;
INSERT INTO `material_origin_certificates` VALUES (1,1,'GRS','GRS-2026-4411','Control Union','2025-10-22','2027-10-22',NULL,NULL,NULL,'verified',NULL,1,'2026-09-22 06:59:53','2026-10-22 06:59:53','2026-10-22 06:59:53'),(2,2,'GRS','GRS-2026-4520','Control Union','2025-10-22','2027-10-22',NULL,NULL,NULL,'verified',NULL,1,'2026-09-22 06:59:53','2026-10-22 06:59:53','2026-10-22 06:59:53'),(3,3,'RWS','RWS-NZ-8812','Textile Exchange','2025-10-22','2027-10-22',NULL,NULL,NULL,'verified',NULL,1,'2026-09-22 06:59:53','2026-10-22 06:59:53','2026-10-22 06:59:53'),(4,4,'RWS','RWS-AU-1020','Textile Exchange','2025-10-22','2027-10-22',NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(5,6,'GRS','GRS-2027-11','Control Union','2026-07-22','2027-10-22',NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(6,7,'GRS','GRS-2027-12','Control Union','2026-07-22','2027-10-22',NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(7,8,'OEKO-TEX','OEKO-TEX-8629-8093','Bureau Veritas','2026-02-27','2027-07-14',NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(8,9,'GOTS','GOTS-8481-2499','Control Union','2025-11-24','2028-08-27',NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(9,10,'OEKO-TEX','OEKO-TEX-5761-3547','Bureau Veritas','2026-04-20','2027-09-13',NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(10,11,'origin','origin-6918-3897','Ecocert','2026-05-04','2028-02-27',NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `material_origin_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_product`
--

DROP TABLE IF EXISTS `material_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_product` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `origin_country` varchar(255) DEFAULT NULL,
  `certified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `component` varchar(255) NOT NULL DEFAULT 'main',
  `supplier` varchar(255) DEFAULT NULL,
  `sourcing_type` varchar(255) DEFAULT NULL,
  `evidence_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_product_product_id_material_id_unique` (`product_id`,`material_id`),
  KEY `material_product_material_id_foreign` (`material_id`),
  CONSTRAINT `material_product_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_product`
--

LOCK TABLES `material_product` WRITE;
/*!40000 ALTER TABLE `material_product` DISABLE KEYS */;
INSERT INTO `material_product` VALUES (1,1,22,60.00,'Tunisie',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(2,1,21,40.00,'Nouvelle-Zélande',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(3,2,19,70.00,'Tunisie',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(4,2,23,30.00,'France',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(5,3,22,65.00,'Portugal',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(6,3,28,35.00,'Espagne',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(7,4,21,85.00,'Australie',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(8,4,22,15.00,'Italie',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(9,5,27,80.00,'Mexique',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(10,5,19,20.00,'Tunisie',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(11,6,24,55.00,'France',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(12,6,20,45.00,'Tunisie',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(13,7,25,90.00,'Autriche',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(14,7,19,10.00,'Tunisie',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(15,8,26,75.00,'Italie',0,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL),(16,8,28,25.00,'Espagne',1,'2026-10-22 06:59:50','2026-10-22 06:59:50','main',NULL,NULL,NULL);
/*!40000 ALTER TABLE `material_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_requests`
--

DROP TABLE IF EXISTS `material_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `minimum_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`categories`)),
  `accepted_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accepted_conditions`)),
  `quantity_kg` decimal(10,2) NOT NULL,
  `remaining_kg` decimal(10,2) NOT NULL,
  `city` varchar(255) DEFAULT NULL,
  `dropoff_address` varchar(255) DEFAULT NULL,
  `reward_points` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `roadmap_step_id` bigint(20) unsigned DEFAULT NULL,
  `roadmap_scope_version` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_requests_brand_id_foreign` (`brand_id`),
  KEY `material_requests_status_material_name_index` (`status`,`material_name`),
  KEY `material_requests_roadmap_step_id_foreign` (`roadmap_step_id`),
  CONSTRAINT `material_requests_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_requests_roadmap_step_id_foreign` FOREIGN KEY (`roadmap_step_id`) REFERENCES `roadmap_steps` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_requests`
--

LOCK TABLES `material_requests` WRITE;
/*!40000 ALTER TABLE `material_requests` DISABLE KEYS */;
INSERT INTO `material_requests` VALUES (1,1,'Coton circulaire pour nouvelle collection','Coton',70.00,'[\"Chemise\",\"Pantalon\",\"Robe\"]','[\"good\",\"wearable\",\"minor_damage\",\"damaged\"]',100.00,100.00,'Tunis','EcoFashion Lac 2, Tunis',75,'open','2027-01-22 07:59:52','2026-10-22 06:59:52','2026-10-22 06:59:52',NULL,NULL),(2,1,'Alternative cuir végétale','cactus',60.00,'[\"Sac\",\"Chaussures\"]','[\"good\",\"wearable\",\"minor_damage\"]',40.00,40.00,'Tunis','EcoFashion Lac 2, Tunis',120,'open','2027-02-22 07:59:52','2026-10-22 06:59:52','2026-10-22 06:59:52',NULL,NULL);
/*!40000 ALTER TABLE `material_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_usage_events`
--

DROP TABLE IF EXISTS `material_usage_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_usage_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_usage_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `note` text NOT NULL,
  `files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`files`)),
  `measurements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`measurements`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_usage_events_material_usage_id_foreign` (`material_usage_id`),
  KEY `material_usage_events_user_id_foreign` (`user_id`),
  CONSTRAINT `material_usage_events_material_usage_id_foreign` FOREIGN KEY (`material_usage_id`) REFERENCES `material_usages` (`id`),
  CONSTRAINT `material_usage_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_usage_events`
--

LOCK TABLES `material_usage_events` WRITE;
/*!40000 ALTER TABLE `material_usage_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_usage_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_usages`
--

DROP TABLE IF EXISTS `material_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_match_id` bigint(20) unsigned NOT NULL,
  `label_roadmap_id` bigint(20) unsigned DEFAULT NULL,
  `scope_version` int(10) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'stored',
  `input_kg` decimal(12,2) DEFAULT NULL,
  `recycled_output_kg` decimal(12,2) DEFAULT NULL,
  `incorporated_kg` decimal(12,2) DEFAULT NULL,
  `product_quantities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_quantities`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_usages_material_match_id_unique` (`material_match_id`),
  KEY `material_usages_label_roadmap_id_foreign` (`label_roadmap_id`),
  CONSTRAINT `material_usages_label_roadmap_id_foreign` FOREIGN KEY (`label_roadmap_id`) REFERENCES `label_roadmaps` (`id`),
  CONSTRAINT `material_usages_material_match_id_foreign` FOREIGN KEY (`material_match_id`) REFERENCES `material_matches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_usages`
--

LOCK TABLES `material_usages` WRITE;
/*!40000 ALTER TABLE `material_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `animal_origin` tinyint(1) NOT NULL DEFAULT 0,
  `recyclable` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `materials_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES (1,'Coton conventionnel','plant',0,1,'Fibre naturelle végétale couramment utilisée dans l’habillement.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(2,'Polyester vierge','synthetic',0,1,'Fibre synthétique issue de ressources fossiles.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(3,'Polyamide / Nylon','synthetic',0,1,'Fibre synthétique résistante souvent utilisée dans les vêtements techniques.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(4,'Polyamide recyclé','recycled',0,1,'Polyamide provenant de déchets pré ou post-consommation.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(5,'Élasthanne','synthetic',0,0,'Fibre élastique généralement utilisée en faible proportion.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(6,'Viscose','regenerated',0,1,'Fibre cellulosique régénérée issue de pâte de bois.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(7,'Modal','regenerated',0,1,'Fibre cellulosique régénérée souple et absorbante.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(8,'Cupro','regenerated',0,1,'Fibre cellulosique régénérée issue de linters de coton.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(9,'Acétate','regenerated',0,0,'Fibre artificielle dérivée de cellulose acétylée.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(10,'Acrylique','synthetic',0,0,'Fibre synthétique souvent utilisée comme alternative à la laine.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(11,'Soie','animal',1,1,'Fibre protéique animale produite par le ver à soie.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(12,'Cachemire','animal',1,1,'Fibre animale issue de chèvres cachemire.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(13,'Alpaga','animal',1,1,'Fibre animale issue de l’alpaga.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(14,'Laine recyclée','recycled',1,1,'Laine récupérée et retraitée à partir de déchets textiles.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(15,'Jute','plant',0,1,'Fibre végétale robuste et biodégradable sous conditions.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(16,'Ramie','plant',0,1,'Fibre végétale résistante proche du lin.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(17,'Bambou viscose','regenerated',0,1,'Fibre cellulosique régénérée utilisant du bambou comme matière première.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(18,'Polyuréthane','synthetic',0,0,'Polymère utilisé dans les enductions, mousses et alternatives au cuir.','2026-10-22 06:23:28','2026-10-22 06:23:28'),(19,'Coton recyclé','recycled',0,1,'Fibre issue de déchets textiles pré-consommation et post-consommation.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(20,'Coton biologique','plant',0,1,'Coton cultivé avec des pratiques réduisant les intrants chimiques.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(21,'Laine mérinos','animal',1,1,'Fibre animale nécessitant une traçabilité et des preuves de bien-être animal.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(22,'Polyester recyclé','recycled',0,1,'Fibre synthétique issue majoritairement de matières plastiques recyclées.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(23,'Lin européen','plant',0,1,'Fibre végétale durable, résistante et biodégradable sous conditions.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(24,'Chanvre','plant',0,1,'Fibre végétale robuste demandant peu d’eau lors de sa culture.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(25,'Lyocell','plant',0,1,'Fibre cellulosique produite en circuit de solvants majoritairement fermé.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(26,'Cuir bovin','animal',1,0,'Matière animale à impact élevé nécessitant une traçabilité renforcée.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(27,'Alternative cuir de cactus','plant',0,1,'Alternative végétale au cuir évaluée séparément pour sa teneur en polymères.','2026-10-22 06:59:50','2026-10-22 06:59:50'),(28,'Caoutchouc recyclé','recycled',0,1,'Matière issue de rebuts industriels et de produits en fin de vie.','2026-10-22 06:59:50','2026-10-22 06:59:50');
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_01_01_000001_create_repair_partners_table',1),(5,'2026_01_01_000002_create_repair_workshops_table',1),(6,'2026_10_04_000001_add_role_to_users_table',1),(7,'2026_10_04_000010_create_catalogue_tables',1),(8,'2026_10_04_000020_create_smart_scan_tables',1),(9,'2026_10_04_000030_create_recovery_tables',1),(10,'2026_10_04_000040_create_returns_tables',1),(11,'2026_10_04_000050_create_assessment_tables',1),(12,'2026_10_04_000060_add_image_path_to_products_table',1),(13,'2026_10_05_000100_add_brand_catalogue_and_label_workflow',1),(14,'2026_10_06_000200_add_professional_brand_workflow',1),(15,'2026_10_06_000300_add_consumer_journey',1),(16,'2026_10_06_000400_add_material_exchange_workflow',1),(17,'2026_10_07_000500_add_cloudinary_media_metadata',1),(18,'2026_10_07_000500_create_brand_applications',1),(19,'2026_10_07_000510_add_brand_orientation_labels',1),(20,'2026_10_07_000600_add_passport_proposal_workflow',1),(21,'2026_10_07_000600_create_product_label_roadmaps',1),(22,'2026_10_07_000610_add_partner_points_to_passport_proposals',1),(23,'2026_10_07_000700_expand_textile_material_catalog',1),(24,'2026_10_07_000800_add_ai_compatibility_to_material_matches',1),(25,'2026_10_07_000800_add_product_label_ai_recommendations',1),(26,'2026_10_08_000900_link_materials_to_label_roadmaps',1),(27,'2026_10_17_100000_create_supplier_traceability_tables',1),(28,'2026_10_17_110000_create_esg_impact_tables',1),(29,'2026_10_17_120000_create_repair_marketplace_tables',1),(30,'2026_10_18_100000_add_ai_results_to_circular_modules',1),(31,'2026_10_18_110000_store_certificate_documents',1),(32,'2026_10_18_120000_add_supplier_portal_to_purchase_orders',1),(33,'2026_10_18_130000_add_supplier_response_to_purchase_orders',1),(34,'2026_10_18_140000_add_supplier_accounts',1),(35,'2026_10_18_150000_create_sms_messages_table',1),(36,'2026_10_19_100000_add_order_lines_and_partial_deliveries',1),(37,'2026_10_19_110000_create_supplier_invitations_table',1),(38,'2026_10_19_120000_set_supplier_status_from_account',1),(39,'2026_10_19_130000_cancel_duplicate_repair_requests',1),(40,'2026_10_21_000100_add_eligibility_and_budget_to_label_workflow',1),(41,'2026_10_21_000200_expand_brand_profiles',1),(42,'2026_10_21_000300_create_brand_team_assignments',1),(43,'2026_10_21_000400_add_label_owner_to_roadmaps',1),(44,'2026_10_21_100000_complete_consumer_experience',1),(45,'2026_10_21_110000_add_points_shop_and_profile_photo',1),(46,'2026_10_21_120000_add_marketplace_images_and_community',1),(47,'2026_10_21_160000_create_recycling_loops_tables',1),(48,'2026_10_22_090000_create_advanced_module_tables',1),(49,'2026_10_22_100000_create_blog_tables',1),(50,'2026_10_22_110000_create_chatbot_tables',1),(51,'2026_10_22_120000_add_stats_to_articles_and_create_reactions',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passport_events`
--

DROP TABLE IF EXISTS `passport_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passport_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `passport_proposal_id` bigint(20) unsigned NOT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `actor_role` varchar(255) DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `from_status` varchar(255) DEFAULT NULL,
  `to_status` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `passport_events_actor_id_foreign` (`actor_id`),
  KEY `passport_events_passport_proposal_id_created_at_index` (`passport_proposal_id`,`created_at`),
  CONSTRAINT `passport_events_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `passport_events_passport_proposal_id_foreign` FOREIGN KEY (`passport_proposal_id`) REFERENCES `passport_proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passport_events`
--

LOCK TABLES `passport_events` WRITE;
/*!40000 ALTER TABLE `passport_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `passport_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passport_proposal_collection_point`
--

DROP TABLE IF EXISTS `passport_proposal_collection_point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passport_proposal_collection_point` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `passport_proposal_id` bigint(20) unsigned NOT NULL,
  `collection_point_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposal_point_unique` (`passport_proposal_id`,`collection_point_id`),
  KEY `passport_proposal_collection_point_collection_point_id_foreign` (`collection_point_id`),
  CONSTRAINT `passport_proposal_collection_point_collection_point_id_foreign` FOREIGN KEY (`collection_point_id`) REFERENCES `collection_points` (`id`) ON DELETE CASCADE,
  CONSTRAINT `passport_proposal_collection_point_passport_proposal_id_foreign` FOREIGN KEY (`passport_proposal_id`) REFERENCES `passport_proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passport_proposal_collection_point`
--

LOCK TABLES `passport_proposal_collection_point` WRITE;
/*!40000 ALTER TABLE `passport_proposal_collection_point` DISABLE KEYS */;
/*!40000 ALTER TABLE `passport_proposal_collection_point` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passport_proposals`
--

DROP TABLE IF EXISTS `passport_proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passport_proposals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scan_id` bigint(20) unsigned NOT NULL,
  `consumer_id` bigint(20) unsigned NOT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'admin_pending',
  `proposed_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`proposed_data`)),
  `field_provenance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_provenance`)),
  `admin_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `admin_reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `brand_reviewed_at` timestamp NULL DEFAULT NULL,
  `brand_note` text DEFAULT NULL,
  `ready_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passport_proposals_scan_id_unique` (`scan_id`),
  KEY `passport_proposals_consumer_id_foreign` (`consumer_id`),
  KEY `passport_proposals_product_id_foreign` (`product_id`),
  KEY `passport_proposals_admin_reviewed_by_foreign` (`admin_reviewed_by`),
  KEY `passport_proposals_status_created_at_index` (`status`,`created_at`),
  KEY `passport_proposals_brand_id_status_index` (`brand_id`,`status`),
  CONSTRAINT `passport_proposals_admin_reviewed_by_foreign` FOREIGN KEY (`admin_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `passport_proposals_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `passport_proposals_consumer_id_foreign` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `passport_proposals_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `passport_proposals_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passport_proposals`
--

LOCK TABLES `passport_proposals` WRITE;
/*!40000 ALTER TABLE `passport_proposals` DISABLE KEYS */;
/*!40000 ALTER TABLE `passport_proposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_impact_reports`
--

DROP TABLE IF EXISTS `product_impact_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_impact_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `season` varchar(40) DEFAULT NULL,
  `product_weight_kg` decimal(8,3) NOT NULL,
  `carbon_kg` decimal(12,3) NOT NULL DEFAULT 0.000,
  `water_l` decimal(14,2) NOT NULL DEFAULT 0.00,
  `energy_kwh` decimal(12,3) NOT NULL DEFAULT 0.000,
  `waste_kg` decimal(12,3) NOT NULL DEFAULT 0.000,
  `avoided_waste_kg` decimal(12,3) NOT NULL DEFAULT 0.000,
  `circularity_bonus` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `impact_score` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `grade` char(1) NOT NULL DEFAULT 'E',
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `assessment_id` bigint(20) unsigned DEFAULT NULL,
  `validated_by` bigint(20) unsigned DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `ai_recommendations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_recommendations`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_impact_reports_product_id_foreign` (`product_id`),
  KEY `product_impact_reports_brand_id_foreign` (`brand_id`),
  KEY `product_impact_reports_generated_by_foreign` (`generated_by`),
  KEY `product_impact_reports_assessment_id_foreign` (`assessment_id`),
  KEY `product_impact_reports_validated_by_foreign` (`validated_by`),
  CONSTRAINT `product_impact_reports_assessment_id_foreign` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_impact_reports_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_impact_reports_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_impact_reports_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_impact_reports_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_impact_reports`
--

LOCK TABLES `product_impact_reports` WRITE;
/*!40000 ALTER TABLE `product_impact_reports` DISABLE KEYS */;
INSERT INTO `product_impact_reports` VALUES (1,1,1,1,'AH 2026',1.000,14.660,674.00,72.000,0.330,1.200,10,63,'C','validated',1,1,'2026-10-22 06:59:54','Données fournisseurs et composition vérifiées.',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(2,2,1,1,'AH 2026',0.250,1.625,61.25,11.100,0.068,0.350,9,88,'A','draft',NULL,NULL,NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(3,3,1,1,'AH 2026',0.900,6.233,162.90,49.275,0.243,0.820,9,86,'A','validated',1,1,'2026-10-22 06:59:54','Données fournisseurs et composition vérifiées.',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(4,4,1,1,'AH 2026',1.400,32.431,1863.40,119.700,0.557,1.450,10,37,'D','draft',NULL,NULL,NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(5,5,NULL,1,'PE 2027',0.800,7.392,3264.00,51.520,0.278,0.000,9,53,'C','draft',NULL,NULL,NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(6,6,NULL,1,'PE 2027',0.600,4.653,837.00,33.390,0.188,0.000,9,75,'B','draft',NULL,NULL,NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(7,7,NULL,1,'PE 2027',0.350,2.674,164.50,25.095,0.097,0.000,9,78,'B','draft',NULL,NULL,NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `product_impact_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_label_recommendations`
--

DROP TABLE IF EXISTS `product_label_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_label_recommendations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `label_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `readiness_score` tinyint(3) unsigned NOT NULL,
  `eligibility_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`eligibility_breakdown`)),
  `certification_estimate` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certification_estimate`)),
  `reason` text NOT NULL,
  `gaps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`gaps`)),
  `target_composition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_composition`)),
  `next_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`next_steps`)),
  `environmental_impacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`environmental_impacts`)),
  `reputation_impacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`reputation_impacts`)),
  `source_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`source_snapshot`)),
  `analysis_mode` varchar(255) NOT NULL DEFAULT 'rules',
  `analysis_model` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'suggested',
  `analysed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_label_recommendations_product_id_label_id_unique` (`product_id`,`label_id`),
  KEY `product_label_recommendations_brand_id_foreign` (`brand_id`),
  KEY `product_label_recommendations_label_id_foreign` (`label_id`),
  CONSTRAINT `product_label_recommendations_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_label_recommendations_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_label_recommendations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_label_recommendations`
--

LOCK TABLES `product_label_recommendations` WRITE;
/*!40000 ALTER TABLE `product_label_recommendations` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_label_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_returns`
--

DROP TABLE IF EXISTS `product_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `collection_point_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'declared',
  `weight_kg` decimal(8,2) DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `scan_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL DEFAULT 'recycling',
  `condition` varchar(255) DEFAULT NULL,
  `qr_token` char(36) DEFAULT NULL,
  `confirmed_weight_kg` decimal(8,2) DEFAULT NULL,
  `points_awarded` int(10) unsigned NOT NULL DEFAULT 0,
  `deposited_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `admin_review_status` varchar(255) NOT NULL DEFAULT 'pending',
  `admin_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `admin_review_note` text DEFAULT NULL,
  `admin_verified_at` timestamp NULL DEFAULT NULL,
  `logistics_mode` varchar(255) NOT NULL DEFAULT 'hub_transfer',
  `deposit_declared_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_returns_reference_unique` (`reference`),
  UNIQUE KEY `product_returns_qr_token_unique` (`qr_token`),
  KEY `product_returns_product_id_foreign` (`product_id`),
  KEY `product_returns_collection_point_id_foreign` (`collection_point_id`),
  KEY `product_returns_scan_id_foreign` (`scan_id`),
  KEY `product_returns_user_id_status_index` (`user_id`,`status`),
  KEY `product_returns_admin_reviewed_by_foreign` (`admin_reviewed_by`),
  CONSTRAINT `product_returns_admin_reviewed_by_foreign` FOREIGN KEY (`admin_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_returns_collection_point_id_foreign` FOREIGN KEY (`collection_point_id`) REFERENCES `collection_points` (`id`),
  CONSTRAINT `product_returns_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `product_returns_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_returns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_returns`
--

LOCK TABLES `product_returns` WRITE;
/*!40000 ALTER TABLE `product_returns` DISABLE KEYS */;
INSERT INTO `product_returns` VALUES (1,2,1,1,'RET-2026-0001','completed',1.20,'2026-09-22 06:59:51','Produit en bon état, réemploi prioritaire.','2026-10-22 06:59:51','2026-10-22 06:59:52',NULL,'take_back','wearable','4426ab78-2969-4a43-86f8-0a4df29d92e8',1.20,150,'2026-09-22 06:59:51','2026-09-24 06:59:51',NULL,'pending',NULL,NULL,NULL,'hub_transfer',NULL),(2,3,2,4,'RET-2026-0002','completed',0.35,'2026-09-28 06:59:51','Chemise réemployée après contrôle qualité.','2026-10-22 06:59:51','2026-10-22 06:59:52',NULL,'donation','wearable','f594a2d1-3a7a-487b-be44-46603ceee224',0.35,110,'2026-09-28 06:59:51','2026-09-30 06:59:51',NULL,'pending',NULL,NULL,NULL,'hub_transfer',NULL),(3,3,3,6,'RET-2026-0003','completed',0.82,'2026-10-04 06:59:51','Semelles et textile séparés pour recyclage.','2026-10-22 06:59:51','2026-10-22 06:59:52',NULL,'recycling','wearable','511246fc-9f3f-4ab7-89e7-109a95335ce5',0.82,180,'2026-10-04 06:59:51','2026-10-06 06:59:51',NULL,'pending',NULL,NULL,NULL,'hub_transfer',NULL),(4,2,4,3,'RET-2026-0004','completed',1.45,'2026-10-10 06:59:51','Doublure réparée et manteau remis en circulation.','2026-10-22 06:59:51','2026-10-22 06:59:52',NULL,'repair','wearable','1c0be2a4-4df5-499c-872c-a1e92869a8fe',1.45,90,'2026-10-10 06:59:51','2026-10-12 06:59:51',NULL,'pending',NULL,NULL,NULL,'hub_transfer',NULL),(5,3,5,2,'RET-2026-0005','received',0.58,'2026-10-16 06:59:51','En attente du diagnostic de réparabilité.','2026-10-22 06:59:51','2026-10-22 06:59:52',NULL,'take_back','unknown','80938889-946f-4e1f-8447-110b8480c285',NULL,0,'2026-10-16 06:59:51',NULL,NULL,'pending',NULL,NULL,NULL,'hub_transfer',NULL),(6,3,6,5,'RET-2026-0006','declared',0.72,'2026-10-21 06:59:51','Retour déclaré par le consommateur.','2026-10-22 06:59:51','2026-10-22 06:59:52',NULL,'donation','unknown','30ae9b61-b297-4e50-9426-a2b94d8e9744',NULL,0,NULL,NULL,NULL,'pending',NULL,NULL,NULL,'hub_transfer',NULL),(7,2,8,7,'RET-2026-0007','completed',1.10,'2026-09-12 06:59:51','Composants récupérables séparés du cuir endommagé.','2026-10-22 06:59:51','2026-10-22 06:59:52',NULL,'recycling','wearable','008b2da5-84d0-4c20-89ec-27312a04a020',1.10,180,'2026-09-12 06:59:51','2026-09-14 06:59:51',NULL,'pending',NULL,NULL,NULL,'hub_transfer',NULL);
/*!40000 ALTER TABLE `product_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_supplier_links`
--

DROP TABLE IF EXISTS `product_supplier_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_supplier_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `supplier_batch_id` bigint(20) unsigned NOT NULL,
  `component` varchar(255) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `conformity_status` varchar(255) NOT NULL DEFAULT 'unchecked',
  `conformity_note` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_supplier_links_product_id_supplier_batch_id_unique` (`product_id`,`supplier_batch_id`),
  KEY `product_supplier_links_supplier_batch_id_foreign` (`supplier_batch_id`),
  CONSTRAINT `product_supplier_links_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_supplier_links_supplier_batch_id_foreign` FOREIGN KEY (`supplier_batch_id`) REFERENCES `supplier_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_supplier_links`
--

LOCK TABLES `product_supplier_links` WRITE;
/*!40000 ALTER TABLE `product_supplier_links` DISABLE KEYS */;
INSERT INTO `product_supplier_links` VALUES (1,1,1,'Tissu extérieur',60.00,'conform','Lot, matière, origine et certificat concordent avec la déclaration produit.','2026-10-22 06:59:54','2026-10-22 06:59:54','2026-10-22 06:59:54'),(2,1,3,'Doublure isolante',40.00,'conform','Lot, matière, origine et certificat concordent avec la déclaration produit.','2026-10-22 06:59:54','2026-10-22 06:59:54','2026-10-22 06:59:54'),(3,2,2,'Tissu principal',70.00,'conform','Lot, matière, origine et certificat concordent avec la déclaration produit.','2026-10-22 06:59:54','2026-10-22 06:59:54','2026-10-22 06:59:54'),(4,2,5,'Empiècements',30.00,'mismatch','Le lot LOT-LN-2026-03 n’a pas encore été vérifié par TexTileCycle.\nLe produit déclare une matière certifiée mais le lot n’a aucun certificat valide.\nAucun certificat de provenance vérifié et en cours de validité.','2026-10-22 06:59:54','2026-10-22 06:59:54','2026-10-22 06:59:54'),(5,4,4,'Drap de laine',85.00,'mismatch','Le lot LOT-WM-2026-09 n’a pas encore été vérifié par TexTileCycle.\nLe produit déclare une matière certifiée mais le lot n’a aucun certificat valide.\nAucun certificat de provenance vérifié et en cours de validité.','2026-10-22 06:59:54','2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `product_supplier_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_versions`
--

DROP TABLE IF EXISTS `product_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `version` int(10) unsigned NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_versions_product_id_version_unique` (`product_id`,`version`),
  KEY `product_versions_created_by_foreign` (`created_by`),
  CONSTRAINT `product_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_versions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_versions`
--

LOCK TABLES `product_versions` WRITE;
/*!40000 ALTER TABLE `product_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `brand` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `image_storage_disk` varchar(255) DEFAULT NULL,
  `image_cloudinary_public_id` varchar(255) DEFAULT NULL,
  `image_secure_url` text DEFAULT NULL,
  `image_delivery_type` varchar(255) DEFAULT NULL,
  `image_format` varchar(20) DEFAULT NULL,
  `image_version` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `year` smallint(5) unsigned DEFAULT NULL,
  `repairable` tinyint(1) NOT NULL DEFAULT 0,
  `reusable` tinyint(1) NOT NULL DEFAULT 0,
  `recyclable` tinyint(1) NOT NULL DEFAULT 0,
  `environmental_score` tinyint(3) unsigned DEFAULT NULL,
  `circularity_score` tinyint(3) unsigned DEFAULT NULL,
  `animal_free_score` tinyint(3) unsigned DEFAULT NULL,
  `traceability_score` tinyint(3) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `recyclability_score` tinyint(3) unsigned DEFAULT NULL,
  `recycling_instructions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `completion_score` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `current_version` int(10) unsigned NOT NULL DEFAULT 1,
  `review_comment` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  UNIQUE KEY `products_barcode_unique` (`barcode`),
  UNIQUE KEY `products_qr_code_unique` (`qr_code`),
  KEY `products_brand_id_status_index` (`brand_id`,`status`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Winter Jacket 02','winter-jacket-02-1','EcoFashion','EF-WJ-002','6190000000001','TEXTILECYCLE:PRODUCT:1','Veste','Veste chaude avec passeport textile complet et option de reprise.','Tunisie',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'published',2026,1,1,1,79,88,60,91,'2026-10-22 06:59:50','2026-10-22 06:59:52',88,'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',1,85,1,NULL,NULL,NULL,NULL,NULL,NULL),(2,1,'ReLoop Shirt','reloop-shirt-2','Circular Studio','CS-RS-104','6190000000002','TEXTILECYCLE:PRODUCT:2','Chemise','Chemise légère conçue pour être réparée et recyclée.','Tunisie',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'published',2026,1,1,1,86,92,100,84,'2026-10-22 06:59:50','2026-10-22 06:59:52',92,'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',1,85,1,NULL,NULL,NULL,NULL,NULL,NULL),(3,1,'Ocean Runner','ocean-runner-3','BlueStep','BS-OR-210','6190000000003','TEXTILECYCLE:PRODUCT:3','Chaussures','Sneakers démontables utilisant des composants recyclés.','Tunisie',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'published',2026,1,1,1,82,86,100,78,'2026-10-22 06:59:50','2026-10-22 06:59:52',86,'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',1,85,1,NULL,NULL,NULL,NULL,NULL,NULL),(4,1,'Heritage Wool Coat','heritage-wool-coat-4','North Loom','NL-HC-330','6190000000004','TEXTILECYCLE:PRODUCT:4','Manteau','Manteau durable en laine certifiée avec service de réparation.','Tunisie',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'published',2025,1,1,1,72,81,15,94,'2026-10-22 06:59:50','2026-10-22 06:59:52',81,'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',1,85,1,NULL,NULL,NULL,NULL,NULL,NULL),(5,NULL,'Cactus City Bag','cactus-city-bag-5','Verde Atelier','VA-CB-415','6190000000005','TEXTILECYCLE:PRODUCT:5','Sac','Sac sans matière animale conçu à partir d’une alternative au cuir.','Tunisie',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'published',2026,1,1,1,84,79,100,88,'2026-10-22 06:59:50','2026-10-22 06:59:52',79,'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',1,85,1,NULL,NULL,NULL,NULL,NULL,NULL),(6,NULL,'Hemp Denim 2030','hemp-denim-2030-6','Terra Denim','TD-HD-2030','6190000000006','TEXTILECYCLE:PRODUCT:6','Pantalon','Jean à faible consommation d’eau et forte réparabilité.','Tunisie',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'published',2026,1,1,1,90,93,100,90,'2026-10-22 06:59:50','2026-10-22 06:59:52',93,'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',1,85,1,NULL,NULL,NULL,NULL,NULL,NULL),(7,NULL,'Lyocell Flow Dress','lyocell-flow-dress-7','Nova Fibers','NF-LD-520','6190000000007','TEXTILECYCLE:PRODUCT:7','Robe','Robe fluide mono-dominante favorisant la recyclabilité.','Tunisie',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'published',2026,1,1,1,88,85,100,92,'2026-10-22 06:59:50','2026-10-22 06:59:52',85,'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',1,85,1,NULL,NULL,NULL,NULL,NULL,NULL),(8,NULL,'Classic Leather Boots','classic-leather-boots-8','Heritage Walk','HW-LB-610','6190000000008','TEXTILECYCLE:PRODUCT:8','Chaussures','Produit témoin permettant de comparer cuir animal et alternatives responsables.','Tunisie',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'published',2025,1,1,0,48,55,0,61,'2026-10-22 06:59:50','2026-10-22 06:59:52',55,'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',1,85,1,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_events`
--

DROP TABLE IF EXISTS `purchase_order_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actor` varchar(20) NOT NULL,
  `type` varchar(40) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_events_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_events_user_id_foreign` (`user_id`),
  CONSTRAINT `purchase_order_events_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_events`
--

LOCK TABLES `purchase_order_events` WRITE;
/*!40000 ALTER TABLE `purchase_order_events` DISABLE KEYS */;
INSERT INTO `purchase_order_events` VALUES (1,4,4,'brand','created','Commande créée : 500 kg de Coton recyclé et 200 kg de Polyester recyclé.','2026-10-20 07:12:00','2026-10-20 07:12:00'),(2,5,4,'brand','created','Commande créée : 150 kg de Laine mérinos.','2026-10-16 07:12:00','2026-10-16 07:12:00'),(3,5,6,'supplier','countered','Contre-proposition : Laine mérinos : 120 kg au lieu de 150 kg ; livraison le 26/11/2026. « Tonte retardée par la météo : 120 kg disponibles, deux semaines plus tard. »','2026-10-21 14:40:00','2026-10-21 14:40:00'),(4,6,4,'brand','created','Commande créée : 400 kg de Coton recyclé et 150 kg de Polyester recyclé.','2026-09-17 07:12:00','2026-09-17 07:12:00'),(5,6,5,'supplier','accepted','Commande acceptée telle quelle.','2026-09-18 08:05:00','2026-09-18 08:05:00'),(6,6,5,'supplier','shipped','Livraison expédiée : lot LOT-FM-2027-11, 250 kg de Coton recyclé.','2026-10-08 06:30:00','2026-10-08 06:30:00'),(7,6,4,'brand','received','Livraison réceptionnée : lot LOT-FM-2027-11 (250 kg).','2026-10-11 12:15:00','2026-10-11 12:15:00'),(8,6,5,'supplier','shipped','Livraison expédiée : lot LOT-FM-2027-12, 150 kg de Coton recyclé.','2026-10-20 06:45:00','2026-10-20 06:45:00'),(9,7,4,'brand','created','Commande créée : 60 kg de Laine mérinos.','2026-09-03 07:12:00','2026-09-03 07:12:00'),(10,7,6,'supplier','accepted','Commande acceptée telle quelle.','2026-09-05 09:20:00','2026-09-05 09:20:00');
/*!40000 ALTER TABLE `purchase_order_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_lines`
--

DROP TABLE IF EXISTS `purchase_order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned DEFAULT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `confirmed_kg` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(10,3) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_order_lines_purchase_order_id_material_id_unique` (`purchase_order_id`,`material_id`),
  KEY `purchase_order_lines_material_id_foreign` (`material_id`),
  CONSTRAINT `purchase_order_lines_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_order_lines_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_lines`
--

LOCK TABLES `purchase_order_lines` WRITE;
/*!40000 ALTER TABLE `purchase_order_lines` DISABLE KEYS */;
INSERT INTO `purchase_order_lines` VALUES (1,1,22,420.00,NULL,12.500,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(2,1,19,310.00,NULL,12.500,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(3,2,21,275.00,NULL,12.500,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(4,3,23,150.00,NULL,12.500,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(5,4,19,500.00,NULL,13.500,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(6,4,22,200.00,NULL,9.800,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(7,5,21,150.00,120.00,42.000,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(8,6,19,400.00,NULL,13.200,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(9,6,22,150.00,NULL,9.600,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(10,7,21,60.00,NULL,41.000,'2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `purchase_order_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'ordered',
  `ordered_at` date NOT NULL,
  `expected_at` date DEFAULT NULL,
  `proposed_expected_at` date DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  `closed_short_at` timestamp NULL DEFAULT NULL,
  `close_reason` varchar(500) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `countered_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'TND',
  `notes` text DEFAULT NULL,
  `supplier_note` text DEFAULT NULL,
  `decline_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_reference_unique` (`reference`),
  KEY `purchase_orders_brand_id_foreign` (`brand_id`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `purchase_orders_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,1,1,'PO-DEMO-1','received','2026-07-22','2026-08-22',NULL,'2026-08-22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,9125.00,'TND',NULL,NULL,NULL,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(2,1,2,'PO-DEMO-2','received','2026-07-22','2026-08-22',NULL,'2026-08-22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,3437.50,'TND',NULL,NULL,NULL,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(3,1,3,'PO-DEMO-3','received','2026-07-22','2026-08-22',NULL,'2026-08-22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1875.00,'TND',NULL,NULL,NULL,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(4,1,1,'PO-261022-HQ3SZ','ordered','2026-10-20','2026-11-26',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,8710.00,'TND','Collection Printemps-Été 2027 : livraison en balles de 50 kg.',NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(5,1,2,'PO-261022-5M24R','countered','2026-10-16','2026-11-12','2026-11-26',NULL,NULL,NULL,NULL,NULL,'2026-10-21 14:40:00',NULL,NULL,6300.00,'TND','Mérinos pour les pulls de la capsule hiver.','Tonte retardée par la météo : 120 kg disponibles, deux semaines plus tard.',NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(6,1,1,'PO-261022-UHITX','partially_shipped','2026-09-17','2026-10-26',NULL,NULL,NULL,NULL,NULL,'2026-09-18 08:05:00',NULL,NULL,'2026-10-08 06:30:00',6720.00,'TND','Réassort ReLoop Shirt.',NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(7,1,2,'PO-261022-JGBUX','accepted','2026-09-03','2026-10-17',NULL,NULL,NULL,NULL,NULL,'2026-09-05 09:20:00',NULL,NULL,NULL,2460.00,'TND','Bonnets et écharpes.',NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recovery_programs`
--

DROP TABLE IF EXISTS `recovery_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recovery_programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `accepted_materials` text DEFAULT NULL,
  `reward_points` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` date DEFAULT NULL,
  `ends_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recovery_programs`
--

LOCK TABLES `recovery_programs` WRITE;
/*!40000 ALTER TABLE `recovery_programs` DISABLE KEYS */;
INSERT INTO `recovery_programs` VALUES (1,'Reprise textile toutes marques','EcoFashion Tunis','take_back','Collecte des vêtements pour tri, réemploi et recyclage.','Coton, laine, polyester, lin, lyocell',150,1,'2026-01-01',NULL,'2026-10-22 06:59:50','2026-10-22 06:59:50'),(2,'Repair First','Atelier Seconde Vie','repair','Diagnostic, couture, remplacement de pièces et remise en état.','Denim, coton, laine, lin',90,1,'2026-06-22',NULL,'2026-10-22 06:59:50','2026-10-22 06:59:50'),(3,'Don & Réemploi Solidaire','Textile Solidaire','donation','Collecte, contrôle qualité et redistribution de vêtements réutilisables.','Tous textiles propres et réutilisables',110,1,'2025-10-22',NULL,'2026-10-22 06:59:50','2026-10-22 06:59:50'),(4,'Footwear Loop','BlueStep','recycling','Reprise de chaussures pour séparation des semelles, textiles et accessoires.','Chaussures textiles, caoutchouc, polyester',180,1,'2026-08-22','2027-10-22','2026-10-22 06:59:50','2026-10-22 06:59:50');
/*!40000 ALTER TABLE `recovery_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recycling_loop_items`
--

DROP TABLE IF EXISTS `recycling_loop_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recycling_loop_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recycling_loop_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(20) NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `material_id` bigint(20) unsigned DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `pieces` int(10) unsigned DEFAULT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `issue` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_analysis`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recycling_loop_items_recycling_loop_id_foreign` (`recycling_loop_id`),
  KEY `recycling_loop_items_product_id_foreign` (`product_id`),
  KEY `recycling_loop_items_material_id_foreign` (`material_id`),
  CONSTRAINT `recycling_loop_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recycling_loop_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recycling_loop_items_recycling_loop_id_foreign` FOREIGN KEY (`recycling_loop_id`) REFERENCES `recycling_loops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recycling_loop_items`
--

LOCK TABLES `recycling_loop_items` WRITE;
/*!40000 ALTER TABLE `recycling_loop_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `recycling_loop_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recycling_loops`
--

DROP TABLE IF EXISTS `recycling_loops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recycling_loops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) NOT NULL,
  `brand_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'requested',
  `mode` varchar(20) NOT NULL DEFAULT 'recover',
  `brand_message` text DEFAULT NULL,
  `wished_pickup_at` date DEFAULT NULL,
  `output_material_id` bigint(20) unsigned DEFAULT NULL,
  `expected_output_kg` decimal(10,2) DEFAULT NULL,
  `pricing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing`)),
  `amount` decimal(10,2) DEFAULT NULL,
  `ready_by` date DEFAULT NULL,
  `supplier_message` text DEFAULT NULL,
  `decline_reason` text DEFAULT NULL,
  `received_kg` decimal(10,2) DEFAULT NULL,
  `output_kg` decimal(10,2) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `output_batch_id` bigint(20) unsigned DEFAULT NULL,
  `proposed_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `transformed_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `invoice_number` varchar(40) DEFAULT NULL,
  `invoiced_at` datetime DEFAULT NULL,
  `recycling_schedule_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recycling_loops_reference_unique` (`reference`),
  UNIQUE KEY `recycling_loops_invoice_number_unique` (`invoice_number`),
  KEY `recycling_loops_brand_id_foreign` (`brand_id`),
  KEY `recycling_loops_supplier_id_foreign` (`supplier_id`),
  KEY `recycling_loops_requested_by_foreign` (`requested_by`),
  KEY `recycling_loops_output_material_id_foreign` (`output_material_id`),
  KEY `recycling_loops_output_batch_id_foreign` (`output_batch_id`),
  KEY `recycling_loops_status_index` (`status`),
  KEY `recycling_loops_recycling_schedule_id_index` (`recycling_schedule_id`),
  CONSTRAINT `recycling_loops_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recycling_loops_output_batch_id_foreign` FOREIGN KEY (`output_batch_id`) REFERENCES `supplier_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recycling_loops_output_material_id_foreign` FOREIGN KEY (`output_material_id`) REFERENCES `materials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recycling_loops_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recycling_loops_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recycling_loops`
--

LOCK TABLES `recycling_loops` WRITE;
/*!40000 ALTER TABLE `recycling_loops` DISABLE KEYS */;
/*!40000 ALTER TABLE `recycling_loops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recycling_schedules`
--

DROP TABLE IF EXISTS `recycling_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recycling_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `mode` varchar(20) NOT NULL DEFAULT 'recover',
  `frequency` varchar(20) NOT NULL DEFAULT 'monthly',
  `day_of_month` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `brand_message` text DEFAULT NULL,
  `next_run_on` date NOT NULL,
  `last_run_at` datetime DEFAULT NULL,
  `last_loop_id` bigint(20) unsigned DEFAULT NULL,
  `runs_count` int(10) unsigned NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recycling_schedules_brand_id_foreign` (`brand_id`),
  KEY `recycling_schedules_supplier_id_foreign` (`supplier_id`),
  KEY `recycling_schedules_created_by_foreign` (`created_by`),
  KEY `recycling_schedules_next_run_on_index` (`next_run_on`),
  CONSTRAINT `recycling_schedules_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recycling_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recycling_schedules_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recycling_schedules`
--

LOCK TABLES `recycling_schedules` WRITE;
/*!40000 ALTER TABLE `recycling_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `recycling_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refurbishments`
--

DROP TABLE IF EXISTS `refurbishments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `refurbishments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_order_id` bigint(20) unsigned NOT NULL,
  `actions` text NOT NULL,
  `grade` char(1) NOT NULL DEFAULT 'B',
  `cost` decimal(10,2) DEFAULT NULL,
  `technician` varchar(255) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refurbishments_repair_order_id_foreign` (`repair_order_id`),
  CONSTRAINT `refurbishments_repair_order_id_foreign` FOREIGN KEY (`repair_order_id`) REFERENCES `repair_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refurbishments`
--

LOCK TABLES `refurbishments` WRITE;
/*!40000 ALTER TABLE `refurbishments` DISABLE KEYS */;
INSERT INTO `refurbishments` VALUES (1,1,'Nettoyage écologique, remplacement de la fermeture, contrôle des coutures.','A',16.00,'Sana B.','2026-10-22 06:59:55','2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `refurbishments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_centers`
--

DROP TABLE IF EXISTS `repair_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_centers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `specialties` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `capacity_per_week` smallint(5) unsigned NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_centers`
--

LOCK TABLES `repair_centers` WRITE;
/*!40000 ALTER TABLE `repair_centers` DISABLE KEYS */;
INSERT INTO `repair_centers` VALUES (1,'Atelier Retouche Médina','Tunis',NULL,'Couture, retouches, boutons et fermetures','contact@atelier-retouche-medina.example',NULL,15,1,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,'Cordonnerie Verte','Sousse',NULL,'Chaussures, cuir, semelles','contact@cordonnerie-verte.example',NULL,8,1,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,'ReFab Lab','Sfax',NULL,'Reconditionnement, nettoyage, teinture','contact@refab-lab.example',NULL,25,1,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(4,'Retouche Martinez','Bizerte','9, impasse Bernard Masson','Couture, retouches','pierre76@example.net','+33 3 01 44 52 09',12,1,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(5,'Retouche Bertin','Nabeul','68, rue Leconte','Reconditionnement, nettoyage','maggie45@example.net','05 78 57 31 71',24,1,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `repair_centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_orders`
--

DROP TABLE IF EXISTS `repair_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_return_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `repair_center_id` bigint(20) unsigned DEFAULT NULL,
  `issue_description` text NOT NULL,
  `decision` varchar(255) DEFAULT NULL,
  `consumer_decision` varchar(255) DEFAULT NULL,
  `consumer_decided_at` datetime DEFAULT NULL,
  `preferred_appointment_at` datetime DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `final_cost` decimal(10,2) DEFAULT NULL,
  `reward_points` int(10) unsigned NOT NULL DEFAULT 0,
  `admin_note` text DEFAULT NULL,
  `ai_diagnosis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_diagnosis`)),
  `diagnosed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_orders_reference_unique` (`reference`),
  KEY `repair_orders_user_id_foreign` (`user_id`),
  KEY `repair_orders_product_return_id_foreign` (`product_return_id`),
  KEY `repair_orders_product_id_foreign` (`product_id`),
  KEY `repair_orders_repair_center_id_foreign` (`repair_center_id`),
  CONSTRAINT `repair_orders_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repair_orders_product_return_id_foreign` FOREIGN KEY (`product_return_id`) REFERENCES `product_returns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repair_orders_repair_center_id_foreign` FOREIGN KEY (`repair_center_id`) REFERENCES `repair_centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `repair_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_orders`
--

LOCK TABLES `repair_orders` WRITE;
/*!40000 ALTER TABLE `repair_orders` DISABLE KEYS */;
INSERT INTO `repair_orders` VALUES (1,'REP-261022-OMRMA',2,1,1,3,'Retour RET-2026-0001 orienté vers la seconde vie (état : wearable).','resale','accepted','2026-10-22 08:59:55',NULL,'listed',18.00,16.00,60,NULL,NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55','2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,'REP-261022-SE3HG',3,2,2,1,'Retour RET-2026-0002 orienté vers la seconde vie (état : wearable).','repair','accepted','2026-10-22 08:59:55',NULL,'in_repair',9.00,NULL,0,'Couture d’épaule à reprendre.',NULL,'2026-10-22 06:59:55',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,'REP-261022-7M1VZ',3,3,3,NULL,'Talon usé, semelle à remplacer.','resale',NULL,NULL,NULL,'requested',NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `repair_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_partners`
--

DROP TABLE IF EXISTS `repair_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_partners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('atelier','partenaire','collectif') NOT NULL DEFAULT 'atelier',
  `city` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_partners_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_partners`
--

LOCK TABLES `repair_partners` WRITE;
/*!40000 ALTER TABLE `repair_partners` DISABLE KEYS */;
INSERT INTO `repair_partners` VALUES (1,'Atelier Vert','atelier-vert','atelier','Tunis','Rue de l\'Innovation, 15','Atelier de réparation textile local dédié au renfort, retouche et valorisation de vêtements durables.','Sonia Hmidi','contact@ateliervert.tn','+216 71 234 567','https://ateliervert.example',1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(2,'Matière à Refaire','matiere-a-refaire','partenaire','Sousse','Avenue de la République, 27','Réseau de partenaires pour la collecte, le tri et la réutilisation des textiles usagés.','Nabil Riahi','hello@matierearefaire.tn','+216 73 445 700','https://matierearefaire.example',1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(3,'Thread Loop Collective','thread-loop-collective','collectif','Sfax','Zone industrielle 4, 8','Collectif associatif qui relie couturiers, créateurs locaux et récupérateurs de textile.','Mouna Kefi','team@threadloop.tn','+216 74 110 880','https://threadloop.example',1,'2026-10-22 06:59:51','2026-10-22 06:59:51');
/*!40000 ALTER TABLE `repair_partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_workshops`
--

DROP TABLE IF EXISTS `repair_workshops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_workshops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_partner_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `specialties` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `capacity_per_week` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_workshops_slug_unique` (`slug`),
  KEY `repair_workshops_repair_partner_id_foreign` (`repair_partner_id`),
  CONSTRAINT `repair_workshops_repair_partner_id_foreign` FOREIGN KEY (`repair_partner_id`) REFERENCES `repair_partners` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_workshops`
--

LOCK TABLES `repair_workshops` WRITE;
/*!40000 ALTER TABLE `repair_workshops` DISABLE KEYS */;
INSERT INTO `repair_workshops` VALUES (1,1,'Atelier Vert - Retouches','atelier-vert-retouches','Tunis','Rue de l\'Innovation, 15','Retouches, réparations, renforts','Atelier de démonstration pour le parcours de réparation textile de TexTileCycle.','Sonia Hmidi','atelier@example.com','+216 98 000 000',20,1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(2,1,'Atelier Vert - Réemploi','atelier-vert-reemploi','Tunis','Avenue de la Liberté, 44','Désassemblage, couture, revalorisation','Atelier de démonstration pour le parcours de réparation textile de TexTileCycle.','Youssef Triki','atelier@example.com','+216 98 000 000',15,1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(3,2,'Matière à Refaire - Sousse','matiere-a-refaire-sousse','Sousse','Avenue de la République, 27','Tri, préparation, réparation légère','Atelier de démonstration pour le parcours de réparation textile de TexTileCycle.','Nabil Riahi','atelier@example.com','+216 98 000 000',18,1,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(4,3,'Thread Loop Collective - Sfax','thread-loop-collective-sfax','Sfax','Zone industrielle 4, 8','Couture locale, réparation créative','Atelier de démonstration pour le parcours de réparation textile de TexTileCycle.','Mouna Kefi','atelier@example.com','+216 98 000 000',22,1,'2026-10-22 06:59:51','2026-10-22 06:59:51');
/*!40000 ALTER TABLE `repair_workshops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resale_offers`
--

DROP TABLE IF EXISTS `resale_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resale_offers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `marketplace_listing_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `offered_price` decimal(10,2) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resale_offers_marketplace_listing_id_foreign` (`marketplace_listing_id`),
  KEY `resale_offers_user_id_foreign` (`user_id`),
  CONSTRAINT `resale_offers_marketplace_listing_id_foreign` FOREIGN KEY (`marketplace_listing_id`) REFERENCES `marketplace_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resale_offers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resale_offers`
--

LOCK TABLES `resale_offers` WRITE;
/*!40000 ALTER TABLE `resale_offers` DISABLE KEYS */;
INSERT INTO `resale_offers` VALUES (1,1,3,80.00,'Je peux passer le récupérer cette semaine.','pending',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `resale_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_evidences`
--

DROP TABLE IF EXISTS `return_evidences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `return_evidences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_return_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `storage_disk` varchar(255) DEFAULT NULL,
  `cloudinary_public_id` varchar(255) DEFAULT NULL,
  `secure_url` text DEFAULT NULL,
  `delivery_type` varchar(255) DEFAULT NULL,
  `format` varchar(255) DEFAULT NULL,
  `version` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_evidences_user_id_foreign` (`user_id`),
  KEY `return_evidences_product_return_id_type_index` (`product_return_id`,`type`),
  CONSTRAINT `return_evidences_product_return_id_foreign` FOREIGN KEY (`product_return_id`) REFERENCES `product_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_evidences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_evidences`
--

LOCK TABLES `return_evidences` WRITE;
/*!40000 ALTER TABLE `return_evidences` DISABLE KEYS */;
/*!40000 ALTER TABLE `return_evidences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_status_histories`
--

DROP TABLE IF EXISTS `return_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `return_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_return_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_status_histories_user_id_foreign` (`user_id`),
  KEY `return_status_histories_product_return_id_created_at_index` (`product_return_id`,`created_at`),
  CONSTRAINT `return_status_histories_product_return_id_foreign` FOREIGN KEY (`product_return_id`) REFERENCES `product_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_status_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_status_histories`
--

LOCK TABLES `return_status_histories` WRITE;
/*!40000 ALTER TABLE `return_status_histories` DISABLE KEYS */;
INSERT INTO `return_status_histories` VALUES (1,1,2,'declared','Retour déclaré.',NULL,'2026-09-22 06:59:51','2026-09-22 06:59:51'),(2,1,2,'dropoff_expected','Dépôt attendu au point partenaire.',NULL,'2026-09-22 14:59:51','2026-09-22 14:59:51'),(3,1,2,'received','Textile reçu et pesé.',NULL,'2026-09-22 22:59:51','2026-09-22 22:59:51'),(4,1,2,'inspected','Contrôle qualité terminé.',NULL,'2026-09-23 06:59:51','2026-09-23 06:59:51'),(5,1,2,'routed','Textile orienté vers la filière adaptée.',NULL,'2026-09-23 14:59:51','2026-09-23 14:59:51'),(6,1,2,'completed','Valorisation terminée et récompense validée.',NULL,'2026-09-23 22:59:51','2026-09-23 22:59:51'),(7,2,3,'declared','Retour déclaré.',NULL,'2026-09-28 06:59:51','2026-09-28 06:59:51'),(8,2,3,'dropoff_expected','Dépôt attendu au point partenaire.',NULL,'2026-09-28 14:59:51','2026-09-28 14:59:51'),(9,2,3,'received','Textile reçu et pesé.',NULL,'2026-09-28 22:59:51','2026-09-28 22:59:51'),(10,2,3,'inspected','Contrôle qualité terminé.',NULL,'2026-09-29 06:59:51','2026-09-29 06:59:51'),(11,2,3,'routed','Textile orienté vers la filière adaptée.',NULL,'2026-09-29 14:59:51','2026-09-29 14:59:51'),(12,2,3,'completed','Valorisation terminée et récompense validée.',NULL,'2026-09-29 22:59:51','2026-09-29 22:59:51'),(13,3,3,'declared','Retour déclaré.',NULL,'2026-10-04 06:59:51','2026-10-04 06:59:51'),(14,3,3,'dropoff_expected','Dépôt attendu au point partenaire.',NULL,'2026-10-04 14:59:51','2026-10-04 14:59:51'),(15,3,3,'received','Textile reçu et pesé.',NULL,'2026-10-04 22:59:51','2026-10-04 22:59:51'),(16,3,3,'inspected','Contrôle qualité terminé.',NULL,'2026-10-05 06:59:51','2026-10-05 06:59:51'),(17,3,3,'routed','Textile orienté vers la filière adaptée.',NULL,'2026-10-05 14:59:51','2026-10-05 14:59:51'),(18,3,3,'completed','Valorisation terminée et récompense validée.',NULL,'2026-10-05 22:59:51','2026-10-05 22:59:51'),(19,4,2,'declared','Retour déclaré.',NULL,'2026-10-10 06:59:51','2026-10-10 06:59:51'),(20,4,2,'dropoff_expected','Dépôt attendu au point partenaire.',NULL,'2026-10-10 14:59:51','2026-10-10 14:59:51'),(21,4,2,'received','Textile reçu et pesé.',NULL,'2026-10-10 22:59:51','2026-10-10 22:59:51'),(22,4,2,'inspected','Contrôle qualité terminé.',NULL,'2026-10-11 06:59:51','2026-10-11 06:59:51'),(23,4,2,'routed','Textile orienté vers la filière adaptée.',NULL,'2026-10-11 14:59:51','2026-10-11 14:59:51'),(24,4,2,'completed','Valorisation terminée et récompense validée.',NULL,'2026-10-11 22:59:51','2026-10-11 22:59:51'),(25,5,3,'declared','Retour déclaré.',NULL,'2026-10-16 06:59:51','2026-10-16 06:59:51'),(26,5,3,'dropoff_expected','Dépôt attendu au point partenaire.',NULL,'2026-10-16 14:59:51','2026-10-16 14:59:51'),(27,5,3,'received','Textile reçu et pesé.',NULL,'2026-10-16 22:59:51','2026-10-16 22:59:51'),(28,6,3,'declared','Retour déclaré.',NULL,'2026-10-21 06:59:51','2026-10-21 06:59:51'),(29,7,2,'declared','Retour déclaré.',NULL,'2026-09-12 06:59:51','2026-09-12 06:59:51'),(30,7,2,'dropoff_expected','Dépôt attendu au point partenaire.',NULL,'2026-09-12 14:59:51','2026-09-12 14:59:51'),(31,7,2,'received','Textile reçu et pesé.',NULL,'2026-09-12 22:59:51','2026-09-12 22:59:51'),(32,7,2,'inspected','Contrôle qualité terminé.',NULL,'2026-09-13 06:59:51','2026-09-13 06:59:51'),(33,7,2,'routed','Textile orienté vers la filière adaptée.',NULL,'2026-09-13 14:59:51','2026-09-13 14:59:51'),(34,7,2,'completed','Valorisation terminée et récompense validée.',NULL,'2026-09-13 22:59:51','2026-09-13 22:59:51');
/*!40000 ALTER TABLE `return_status_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_items`
--

DROP TABLE IF EXISTS `reward_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reward_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points_cost` int(10) unsigned NOT NULL,
  `partner_name` varchar(255) DEFAULT NULL,
  `stock` int(10) unsigned DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_items`
--

LOCK TABLES `reward_items` WRITE;
/*!40000 ALTER TABLE `reward_items` DISABLE KEYS */;
INSERT INTO `reward_items` VALUES (1,'Bon partenaire 10 TND','Bon utilisable auprès d’un partenaire TexTileCycle participant.',500,'Réseau TexTileCycle',100,NULL,1,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,'Réparation couture offerte','Une réparation simple : bouton, ourlet ou petite couture.',350,'Atelier Seconde Vie',50,NULL,1,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,'Bonus don solidaire','Doublement des points sur votre prochain don textile.',250,'Textile Solidaire',NULL,NULL,1,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `reward_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_redemptions`
--

DROP TABLE IF EXISTS `reward_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reward_redemptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `reward_item_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `points_spent` int(10) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'issued',
  `expires_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reward_redemptions_reference_unique` (`reference`),
  UNIQUE KEY `reward_redemptions_code_unique` (`code`),
  KEY `reward_redemptions_user_id_foreign` (`user_id`),
  KEY `reward_redemptions_reward_item_id_foreign` (`reward_item_id`),
  CONSTRAINT `reward_redemptions_reward_item_id_foreign` FOREIGN KEY (`reward_item_id`) REFERENCES `reward_items` (`id`),
  CONSTRAINT `reward_redemptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_redemptions`
--

LOCK TABLES `reward_redemptions` WRITE;
/*!40000 ALTER TABLE `reward_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `reward_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_transactions`
--

DROP TABLE IF EXISTS `reward_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reward_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_return_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'return_reward',
  `points` int(11) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `description` varchar(255) NOT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reward_transactions_product_return_id_unique` (`product_return_id`),
  KEY `reward_transactions_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `reward_transactions_product_return_id_foreign` FOREIGN KEY (`product_return_id`) REFERENCES `product_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reward_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_transactions`
--

LOCK TABLES `reward_transactions` WRITE;
/*!40000 ALTER TABLE `reward_transactions` DISABLE KEYS */;
INSERT INTO `reward_transactions` VALUES (1,2,1,'return_reward',150,'validated','Récompense validée pour RET-2026-0001','2026-09-24 06:59:51','2026-10-22 06:59:52','2026-10-22 06:59:52'),(2,3,2,'return_reward',110,'validated','Récompense validée pour RET-2026-0002','2026-09-30 06:59:51','2026-10-22 06:59:52','2026-10-22 06:59:52'),(3,3,3,'return_reward',180,'validated','Récompense validée pour RET-2026-0003','2026-10-06 06:59:51','2026-10-22 06:59:52','2026-10-22 06:59:52'),(4,2,4,'return_reward',90,'validated','Récompense validée pour RET-2026-0004','2026-10-12 06:59:51','2026-10-22 06:59:52','2026-10-22 06:59:52'),(5,3,5,'return_reward',150,'pending','Récompense en attente de validation pour RET-2026-0005',NULL,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(6,3,6,'return_reward',110,'pending','Récompense en attente de validation pour RET-2026-0006',NULL,'2026-10-22 06:59:52','2026-10-22 06:59:52'),(7,2,7,'return_reward',180,'validated','Récompense validée pour RET-2026-0007','2026-09-14 06:59:51','2026-10-22 06:59:52','2026-10-22 06:59:52'),(8,2,NULL,'second_life',60,'validated','Article reconditionné et remis en vente · REP-261022-OMRMA','2026-10-22 06:59:55','2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `reward_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roadmap_steps`
--

DROP TABLE IF EXISTS `roadmap_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roadmap_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label_roadmap_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `instructions` text NOT NULL,
  `required_evidence` text NOT NULL,
  `completion_points` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completion_points`)),
  `position` int(10) unsigned NOT NULL,
  `due_date` date NOT NULL,
  `owner_name` varchar(255) NOT NULL,
  `brand_member_id` bigint(20) unsigned DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'normal',
  `assignment_comment` text DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `goal` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'todo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `material_stage` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `roadmap_steps_label_roadmap_id_foreign` (`label_roadmap_id`),
  KEY `roadmap_steps_brand_member_id_foreign` (`brand_member_id`),
  CONSTRAINT `roadmap_steps_brand_member_id_foreign` FOREIGN KEY (`brand_member_id`) REFERENCES `brand_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `roadmap_steps_label_roadmap_id_foreign` FOREIGN KEY (`label_roadmap_id`) REFERENCES `label_roadmaps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roadmap_steps`
--

LOCK TABLES `roadmap_steps` WRITE;
/*!40000 ALTER TABLE `roadmap_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `roadmap_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roadmap_submissions`
--

DROP TABLE IF EXISTS `roadmap_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roadmap_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `roadmap_step_id` bigint(20) unsigned NOT NULL,
  `submitted_by` bigint(20) unsigned NOT NULL,
  `scope_version` int(10) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `actions` text NOT NULL,
  `results` text NOT NULL,
  `product_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`product_results`)),
  `files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`files`)),
  `scope_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`scope_snapshot`)),
  `step_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`step_snapshot`)),
  `material_record` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`material_record`)),
  `material_match_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `review_comment` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roadmap_submissions_roadmap_step_id_version_unique` (`roadmap_step_id`,`version`),
  KEY `roadmap_submissions_submitted_by_foreign` (`submitted_by`),
  KEY `roadmap_submissions_material_match_id_foreign` (`material_match_id`),
  KEY `roadmap_submissions_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `roadmap_submissions_material_match_id_foreign` FOREIGN KEY (`material_match_id`) REFERENCES `material_matches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `roadmap_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `roadmap_submissions_roadmap_step_id_foreign` FOREIGN KEY (`roadmap_step_id`) REFERENCES `roadmap_steps` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roadmap_submissions_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roadmap_submissions`
--

LOCK TABLES `roadmap_submissions` WRITE;
/*!40000 ALTER TABLE `roadmap_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `roadmap_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scan_assets`
--

DROP TABLE IF EXISTS `scan_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scan_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scan_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `storage_disk` varchar(255) DEFAULT NULL,
  `cloudinary_public_id` varchar(255) DEFAULT NULL,
  `secure_url` text DEFAULT NULL,
  `delivery_type` varchar(255) DEFAULT NULL,
  `format` varchar(20) DEFAULT NULL,
  `version` bigint(20) unsigned DEFAULT NULL,
  `extracted_text` longtext DEFAULT NULL,
  `confidence` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scan_assets_scan_id_type_index` (`scan_id`,`type`),
  CONSTRAINT `scan_assets_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scan_assets`
--

LOCK TABLES `scan_assets` WRITE;
/*!40000 ALTER TABLE `scan_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `scan_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scans`
--

DROP TABLE IF EXISTS `scans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `source_type` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `image_storage_disk` varchar(255) DEFAULT NULL,
  `image_cloudinary_public_id` varchar(255) DEFAULT NULL,
  `image_secure_url` text DEFAULT NULL,
  `image_delivery_type` varchar(255) DEFAULT NULL,
  `image_format` varchar(20) DEFAULT NULL,
  `image_version` bigint(20) unsigned DEFAULT NULL,
  `raw_text` longtext DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `confidence` decimal(5,2) DEFAULT NULL,
  `scanned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `result_summary` text DEFAULT NULL,
  `ai_status` varchar(255) NOT NULL DEFAULT 'pending',
  `extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extracted_data`)),
  `ai_explanation` text DEFAULT NULL,
  `consumer_confirmed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scans_uuid_unique` (`uuid`),
  KEY `scans_user_id_foreign` (`user_id`),
  KEY `scans_product_id_foreign` (`product_id`),
  CONSTRAINT `scans_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scans`
--

LOCK TABLES `scans` WRITE;
/*!40000 ALTER TABLE `scans` DISABLE KEYS */;
INSERT INTO `scans` VALUES (1,'806388de-d5af-4d31-a709-0275bc162eb9',2,'label',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'EcoFashion Winter Jacket EF-WJ-002 60% recycled polyester 40% merino wool','completed',94.50,'2026-09-17 06:59:50','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,'pending',NULL,NULL,NULL),(2,'f3719017-1116-42aa-b428-013ee814dd1a',3,'receipt',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Circular Studio ReLoop Shirt CS-RS-104','completed',96.20,'2026-09-24 06:59:51','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,'pending',NULL,NULL,NULL),(3,'a175159b-1bec-42b4-a38a-22d10525c70e',3,'barcode',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'BlueStep Ocean Runner BS-OR-210','completed',98.10,'2026-10-01 06:59:51','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,'pending',NULL,NULL,NULL),(4,'7f685c55-28fd-4f89-8193-52412063058b',2,'qr_code',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Verde Atelier Cactus City Bag VA-CB-415','completed',99.00,'2026-10-08 06:59:51','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,'pending',NULL,NULL,NULL),(5,'689346e6-f769-487f-b956-ebb7c4971c40',3,'label',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Terra Denim Hemp Denim 2030 TD-HD-2030','completed',93.80,'2026-10-15 06:59:51','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,'pending',NULL,NULL,NULL),(6,'762bae08-b0ed-46df-992d-db4d86607a8b',2,'receipt',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Heritage Walk Classic Leather Boots HW-LB-610','completed',91.40,'2026-10-20 06:59:51','2026-10-22 06:59:51','2026-10-22 06:59:51',NULL,NULL,'pending',NULL,NULL,NULL);
/*!40000 ALTER TABLE `scans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('fvgs3ZW9FF7Yk4lOTkrffrxcSQ6gSU2JUYWbPbgf',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 OPR/137.0.0.0','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiemloSTVhNkx1QVlrd1NQS1B6b3B3M1U4RTkxNXRyTEtJZnFXbUo5SyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6Ijc2NmU2ZTEwNmQyMTM3MzQ5MzU0ZTdjZDc1OGQ4NzUwNmNjN2U1NDc0Mzg1YmVhNGZlYzA2MGM5ZDhmNDYxNWQiO30=',1792659971),('gvKHbb8QL77l7MRp1MDKtzYAVXYd5GLFVJRlKTE4',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.142.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidHN4NjlZdVRUdjJhZURvT1F6UnlwaUdEcGJPNzk5VmltTlh1NmFEUiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hc3Npc3RhbnQtY2hhdC9zdWdnZXN0aW9ucyI7czo1OiJyb3V0ZSI7czoxOToiY2hhdGJvdC5zdWdnZXN0aW9ucyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1792657508),('iU75jlL0XTLJn3BcufRLQCqMNJX1SduA1Lg7XEvr',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/3.1623.1 Chrome/152.0.7977.167 Safari/537.36 MSIX','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiMnpPaDd4cUhXS0RoWjAwckRMWWlISHdXb0VPNkRiR3VhZlc0aURuSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7czo1OiJyb3V0ZSI7czoxNToiYWRtaW4uZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiNmIzOWI0OTczZjgwOWI4MWY3NDk1ZDc1YjE3YTlhNjVhZDFhOTA5OWMxMjFlYjBhN2U4NjFiOTE1OGRkM2U3OSI7fQ==',1792660178),('IWqyuhnN4UrSZOdZuRvLmoSPp2kMhmdtvr8ZrL87',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; fr-FR) WindowsPowerShell/5.1.19041.6456','YTozOntzOjY6Il90b2tlbiI7czo0MDoic1FKTThmcWp2RUZzVTQ0SVF6M0FUU3d6aUxzVmlkdkh0SGVxekV3OCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1792657481),('Q8afZ8pKL2xXRCEoEi3094TyQ8hlNBYCn8CEbrvm',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 OPR/137.0.0.0','YTo1OntzOjY6Il90b2tlbiI7czo0MDoibXZBVjZtM0IycEFvdHQ3eDh5NGdteFhXQUg3ZmFzVldCZjZwd0lvQyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hc3Npc3RhbnQtY2hhdC9zdWdnZXN0aW9ucyI7czo1OiJyb3V0ZSI7czoxOToiY2hhdGJvdC5zdWdnZXN0aW9ucyI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjc7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6IjZlNjFjMzhiN2IxOTRjOTg0YWY2MzcwMjI2NjRmY2M3MDkxZjNmZWUzYTU1Njg2OTYwOWE3OTk3ZmE4NDgyYzgiO30=',1792660171),('SJmJDLZxPIV6MvtyRHgEdJBWT7U0OS4Q2z7Mk7KJ',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiSTVsTUxIMVZjd09YUks4Zmhua09ZMm5RMTVrczRFQlhVOGxVdml6RyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hc3Npc3RhbnQtY2hhdC9zdWdnZXN0aW9ucyI7czo1OiJyb3V0ZSI7czoxOToiY2hhdGJvdC5zdWdnZXN0aW9ucyI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjg7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjQ6IjM0YTcwNDRjMDI5N2M3N2QzODJjZjE3MTk2MDljZjI1OWI0ZmIzYjU4OWNiOTZmNmM3NDhlYzA5MWIxMGIwMzYiO30=',1792660406);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_messages`
--

DROP TABLE IF EXISTS `sms_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `to` varchar(30) NOT NULL,
  `body` text NOT NULL,
  `driver` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `provider_id` varchar(255) DEFAULT NULL,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_messages_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `sms_messages_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_messages`
--

LOCK TABLES `sms_messages` WRITE;
/*!40000 ALTER TABLE `sms_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_batches`
--

DROP TABLE IF EXISTS `supplier_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_order_line_id` bigint(20) unsigned DEFAULT NULL,
  `material_id` bigint(20) unsigned DEFAULT NULL,
  `batch_code` varchar(255) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `origin_country` varchar(100) NOT NULL,
  `production_date` date DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `verification_note` text DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_batches_batch_code_unique` (`batch_code`),
  KEY `supplier_batches_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_batches_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `supplier_batches_material_id_foreign` (`material_id`),
  KEY `supplier_batches_verified_by_foreign` (`verified_by`),
  KEY `supplier_batches_purchase_order_line_id_foreign` (`purchase_order_line_id`),
  CONSTRAINT `supplier_batches_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_batches_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_batches_purchase_order_line_id_foreign` FOREIGN KEY (`purchase_order_line_id`) REFERENCES `purchase_order_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_batches_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_batches_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_batches`
--

LOCK TABLES `supplier_batches` WRITE;
/*!40000 ALTER TABLE `supplier_batches` DISABLE KEYS */;
INSERT INTO `supplier_batches` VALUES (1,1,1,1,22,'LOT-FM-2026-01',420.00,'Tunisie','2026-06-22','2026-08-22','verified',NULL,1,'2026-09-22 06:59:53','2026-08-18 06:59:53','2026-10-22 06:59:53'),(2,1,1,2,19,'LOT-FM-2026-02',310.00,'Tunisie','2026-06-22','2026-08-22','verified',NULL,1,'2026-09-22 06:59:53','2026-08-18 06:59:53','2026-10-22 06:59:53'),(3,2,2,3,21,'LOT-WM-2026-07',180.00,'Nouvelle-Zélande','2026-06-22','2026-08-22','verified',NULL,1,'2026-09-22 06:59:53','2026-08-18 06:59:53','2026-10-22 06:59:53'),(4,2,2,3,21,'LOT-WM-2026-09',95.00,'Australie','2026-06-22','2026-08-22','received',NULL,NULL,NULL,'2026-08-18 06:59:53','2026-10-22 06:59:53'),(5,3,3,4,23,'LOT-LN-2026-03',150.00,'France','2026-06-22','2026-08-22','pending',NULL,NULL,NULL,'2026-08-18 06:59:53','2026-10-22 06:59:53'),(6,1,6,8,19,'LOT-FM-2027-11',250.00,'Tunisie','2026-10-01','2026-10-11','received',NULL,NULL,NULL,'2026-10-08 06:30:00','2026-10-22 06:59:54'),(7,1,6,8,19,'LOT-FM-2027-12',150.00,'Tunisie','2026-10-13',NULL,'pending',NULL,NULL,NULL,'2026-10-20 06:45:00','2026-10-22 06:59:54'),(8,4,NULL,NULL,4,'LOT-MZS-2697',753.81,'France','2026-06-08','2026-09-06','received',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(9,4,NULL,NULL,18,'LOT-HBR-4425',470.67,'France','2026-05-09','2026-10-03','received',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(10,5,NULL,NULL,11,'LOT-7CD-6302',61.21,'Maroc','2026-05-02','2026-10-21','received',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(11,5,NULL,NULL,10,'LOT-RF6-8186',77.31,'Maroc','2026-06-14','2026-08-29','received',NULL,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `supplier_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_invitations`
--

DROP TABLE IF EXISTS `supplier_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `invited_by` bigint(20) unsigned DEFAULT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_invitations_token_hash_unique` (`token_hash`),
  KEY `supplier_invitations_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_invitations_user_id_foreign` (`user_id`),
  KEY `supplier_invitations_invited_by_foreign` (`invited_by`),
  CONSTRAINT `supplier_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_invitations_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_invitations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_invitations`
--

LOCK TABLES `supplier_invitations` WRITE;
/*!40000 ALTER TABLE `supplier_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `certifications` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `compliance_score` tinyint(3) unsigned DEFAULT NULL,
  `ai_risk` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_risk`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_brand_id_name_unique` (`brand_id`,`name`),
  KEY `suppliers_user_id_foreign` (`user_id`),
  CONSTRAINT `suppliers_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suppliers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,1,5,'Filature Méditerranée','Sami Ben Salah','fournisseur@textilecycle.test','+216 73 000 000','Tunisie','Monastir',NULL,'GRS, OEKO-TEX','active',50,NULL,NULL,'2026-10-22 06:59:53','2026-10-22 06:59:54'),(2,1,6,'Wool & Co Merino','Kate Morrison','laine@textilecycle.test',NULL,'Nouvelle-Zélande','Christchurch',NULL,'RWS','active',50,NULL,NULL,'2026-10-22 06:59:53','2026-10-22 06:59:54'),(3,1,NULL,'Linificio Nord','Service qualité','qualite@linificio-nord.example',NULL,'France','Lille',NULL,'Masters of Linen','pending',0,NULL,NULL,'2026-10-22 06:59:53','2026-10-22 06:59:54'),(4,1,NULL,'Tissage Lenoir','Gabriel Benard-Couturier','pinto.laurent@schneider.fr','+33 5 51 72 25 21','France','GayBourg','84, rue de Alexandre','GRS','pending',0,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(5,1,NULL,'Textiles Millet','Manon Deschamps','roussel.corinne@turpin.fr','+33 4 99 65 60 17','Maroc','Fontaine-la-Forêt','37, rue Humbert','GRS','pending',0,NULL,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trade_in_requests`
--

DROP TABLE IF EXISTS `trade_in_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trade_in_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `collection_point_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `condition` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `proposed_points` int(10) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'submitted',
  `qr_token` varchar(255) DEFAULT NULL,
  `deposit_evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deposit_evidence`)),
  `deposited_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `evaluated_by` bigint(20) unsigned DEFAULT NULL,
  `evaluation_note` text DEFAULT NULL,
  `evaluated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trade_in_requests_reference_unique` (`reference`),
  UNIQUE KEY `trade_in_requests_qr_token_unique` (`qr_token`),
  KEY `trade_in_requests_user_id_foreign` (`user_id`),
  KEY `trade_in_requests_product_id_foreign` (`product_id`),
  KEY `trade_in_requests_evaluated_by_foreign` (`evaluated_by`),
  KEY `trade_in_requests_collection_point_id_foreign` (`collection_point_id`),
  CONSTRAINT `trade_in_requests_collection_point_id_foreign` FOREIGN KEY (`collection_point_id`) REFERENCES `collection_points` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trade_in_requests_evaluated_by_foreign` FOREIGN KEY (`evaluated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trade_in_requests_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trade_in_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trade_in_requests`
--

LOCK TABLES `trade_in_requests` WRITE;
/*!40000 ALTER TABLE `trade_in_requests` DISABLE KEYS */;
INSERT INTO `trade_in_requests` VALUES (1,'TRD-ICS8OFHC',3,NULL,NULL,'Chemise en coton','wearable','Temporibus ullam eaque quia qui omnis nemo asperiores.',NULL,'submitted',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(2,'TRD-NUYO8YRN',3,NULL,NULL,'Veste en jean','good','Quia aspernatur repellendus praesentium esse ex consequatur.',NULL,'submitted',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(3,'TRD-DEMO-0001',3,1,NULL,NULL,'good','Portée deux fois, taille M.',NULL,'submitted',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55'),(4,'TRD-DEMO-0002',3,NULL,NULL,'Pull en laine','minor_damage','Petite maille tirée sur la manche.',35,'evaluated',NULL,NULL,NULL,NULL,1,'Réparable en atelier : offre de 35 points.','2026-10-22 06:59:55',NULL,'2026-10-22 06:59:55','2026-10-22 06:59:55');
/*!40000 ALTER TABLE `trade_in_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `treatment_results`
--

DROP TABLE IF EXISTS `treatment_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `treatment_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_return_id` bigint(20) unsigned NOT NULL,
  `recycled_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `reused_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `waste_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `treatment_results_product_return_id_unique` (`product_return_id`),
  CONSTRAINT `treatment_results_product_return_id_foreign` FOREIGN KEY (`product_return_id`) REFERENCES `product_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treatment_results`
--

LOCK TABLES `treatment_results` WRITE;
/*!40000 ALTER TABLE `treatment_results` DISABLE KEYS */;
INSERT INTO `treatment_results` VALUES (1,1,60.00,25.00,15.00,'2026-09-24 06:59:51','Résultat vérifié par le partenaire de traitement.','2026-10-22 06:59:51','2026-10-22 06:59:51'),(2,2,20.00,75.00,5.00,'2026-09-30 06:59:51','Résultat vérifié par le partenaire de traitement.','2026-10-22 06:59:51','2026-10-22 06:59:51'),(3,3,78.00,12.00,10.00,'2026-10-06 06:59:51','Résultat vérifié par le partenaire de traitement.','2026-10-22 06:59:51','2026-10-22 06:59:51'),(4,4,10.00,88.00,2.00,'2026-10-12 06:59:51','Résultat vérifié par le partenaire de traitement.','2026-10-22 06:59:51','2026-10-22 06:59:51'),(5,7,45.00,40.00,15.00,'2026-09-14 06:59:51','Résultat vérifié par le partenaire de traitement.','2026-10-22 06:59:51','2026-10-22 06:59:51');
/*!40000 ALTER TABLE `treatment_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'consumer',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrateur TexTileCycle','admin@textilecycle.test',NULL,'admin','2026-10-22 06:59:51','$2y$12$HO/VCM11XvPoKbDdP3oIiuSP.cbT2hcLU.zBCPIdFhb3O5HWtCt8.',0,NULL,'2026-10-22 06:23:52','2026-10-22 06:59:51'),(2,'Walaeddine Demo','demo@textilecycle.test',NULL,'admin','2026-10-22 06:59:49','$2y$12$Co3mtRkopZuLB.K///RhG.3YZxNZEAq3MKgeiSNQr7S1BsF0dTY6C',0,NULL,'2026-10-22 06:59:49','2026-10-22 06:59:49'),(3,'Consommateur Démo','consumer@textilecycle.test',NULL,'consumer','2026-10-22 06:59:52','$2y$12$SUrhLYyULJQTcLNzirruqOZTFzzShgh7FrMgwFRM68rDJTNDO7OlO',0,NULL,'2026-10-22 06:59:50','2026-10-22 06:59:52'),(4,'Responsable EcoFashion','brand@textilecycle.test',NULL,'brand','2026-10-22 06:59:51','$2y$12$B54necGdAhBiNSrrBWgOt.1qfXpotRegv6mMvwXV19v8iFIu3qzTe',0,NULL,'2026-10-22 06:59:51','2026-10-22 06:59:51'),(5,'Sami Ben Salah','fournisseur@textilecycle.test',NULL,'supplier','2026-10-22 06:59:53','$2y$12$Pu8fFF9NuRSiCGjVXnWkF.nZlsXZkpb8WbGxsdXkyh1P3rg4X78uu',0,NULL,'2026-10-22 06:59:53','2026-10-22 06:59:53'),(6,'Kate Morrison','laine@textilecycle.test',NULL,'supplier','2026-10-22 06:59:54','$2y$12$PMhGv8ok1QvttZvXUxpmSu4/noc/f6.2XJya2.ZztvLncSn8gd/IK',0,NULL,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(7,'cxvxcv','dsffds@gmqil.com',NULL,'consumer',NULL,'$2y$12$rclC5rTzYtRKG42v4liLBOIAu4NWcAAICWNHTg0f9ymE34WtAUwF.',0,NULL,'2026-10-22 07:07:40','2026-10-22 07:07:40'),(8,'amraoui','rabiiclubist@hotmail.com',NULL,'consumer',NULL,'$2y$12$YbvbTiOCONc940VPVkvKPuv/q1aifvnc.4lkKVA1FiBuEwRJ9MQca',0,NULL,'2026-10-22 07:13:20','2026-10-22 07:13:20');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `water_footprints`
--

DROP TABLE IF EXISTS `water_footprints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `water_footprints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_impact_report_id` bigint(20) unsigned NOT NULL,
  `stage` varchar(255) NOT NULL,
  `liters` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `water_footprints_product_impact_report_id_foreign` (`product_impact_report_id`),
  CONSTRAINT `water_footprints_product_impact_report_id_foreign` FOREIGN KEY (`product_impact_report_id`) REFERENCES `product_impact_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `water_footprints`
--

LOCK TABLES `water_footprints` WRITE;
/*!40000 ALTER TABLE `water_footprints` DISABLE KEYS */;
INSERT INTO `water_footprints` VALUES (1,1,'materials',624.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(2,1,'manufacturing',50.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(3,1,'transport',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(4,1,'end_of_life',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(5,2,'materials',48.75,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(6,2,'manufacturing',12.50,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(7,2,'transport',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(8,2,'end_of_life',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(9,3,'materials',117.90,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(10,3,'manufacturing',45.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(11,3,'transport',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(12,3,'end_of_life',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(13,4,'materials',1793.40,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(14,4,'manufacturing',70.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(15,4,'transport',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(16,4,'end_of_life',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(17,5,'materials',3224.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(18,5,'manufacturing',40.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(19,5,'transport',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(20,5,'end_of_life',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(21,6,'materials',807.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(22,6,'manufacturing',30.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(23,6,'transport',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(24,6,'end_of_life',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(25,7,'materials',147.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(26,7,'manufacturing',17.50,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(27,7,'transport',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54'),(28,7,'end_of_life',0.00,'2026-10-22 06:59:54','2026-10-22 06:59:54');
/*!40000 ALTER TABLE `water_footprints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'textilecycle'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-22 11:20:42
