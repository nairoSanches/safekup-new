-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: safekup
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `aplicacao`
--

DROP TABLE IF EXISTS `aplicacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aplicacao` (
  `app_id` int(11) NOT NULL AUTO_INCREMENT,
  `app_nome` varchar(45) NOT NULL,
  `app_descricao` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`app_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='cadastro de setores';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backups_realizados`
--

DROP TABLE IF EXISTS `backups_realizados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups_realizados` (
  `backup_id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_id_computador` int(11) NOT NULL,
  `backup_data` datetime NOT NULL,
  `backup_origem` varchar(45) NOT NULL,
  `backup_status` varchar(45) NOT NULL,
  PRIMARY KEY (`backup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups_realizados`
--

LOCK TABLES `backups_realizados` WRITE;
/*!40000 ALTER TABLE `backups_realizados` DISABLE KEYS */;
/*!40000 ALTER TABLE `backups_realizados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `db_management`
--

DROP TABLE IF EXISTS `db_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `db_management` (
  `bd_tipo` int(11) DEFAULT NULL,
  `bd_app` varchar(100) DEFAULT NULL,
  `bd_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id do computador',
  `bd_sistema_operacional` varchar(45) NOT NULL COMMENT 'sistema operacional do computador',
  `bd_nome_usuario` varchar(100) NOT NULL COMMENT 'nome do usuario da maquina',
  `bd_login` varchar(45) NOT NULL,
  `bd_senha` varchar(100) DEFAULT NULL COMMENT 'senha da conta de usuario do computador',
  `bd_ip` varchar(15) NOT NULL COMMENT 'enredeço ip do computador',
  `bd_porta` varchar(45) DEFAULT NULL COMMENT 'endereço mac do computador',
  `bd_dia_0` int(11) DEFAULT NULL COMMENT 'dia(s) que serão executado o backup',
  `bd_dia_1` int(11) DEFAULT NULL,
  `bd_dia_2` int(11) DEFAULT NULL,
  `bd_dia_3` int(11) DEFAULT NULL,
  `bd_dia_4` int(11) DEFAULT NULL,
  `bd_dia_5` int(11) DEFAULT NULL,
  `bd_dia_6` int(11) DEFAULT NULL,
  `bd_hora_backup` varchar(45) NOT NULL,
  `bd_servidor_backup` int(11) DEFAULT NULL COMMENT 'diretorio onde sera salvo o backup do usuario',
  `bd_data_cadastro` datetime NOT NULL,
  `bd_data_alteracao` varchar(25) NOT NULL,
  `bd_usuario_adm` varchar(255) NOT NULL,
  `bd_backup_ativo` varchar(3) NOT NULL,
  PRIMARY KEY (`bd_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Tabela que armazena o cadastro dos computadores';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `registro_dump`
--

DROP TABLE IF EXISTS `registro_dump`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registro_dump` (
  `registro_dump_id` int(11) NOT NULL AUTO_INCREMENT,
  `registro_dump` varchar(255) NOT NULL,
  PRIMARY KEY (`registro_dump_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `servidores`
--

DROP TABLE IF EXISTS `servidores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servidores` (
  `servidor_id` int(11) NOT NULL AUTO_INCREMENT,
  `servidor_nome` varchar(255) NOT NULL,
  `servidor_ip` varchar(255) NOT NULL,
  `servidor_user_privilegio` varchar(255) NOT NULL,
  `servidor_senha_acesso` varchar(255) NOT NULL,
  `servidor_nome_compartilhamento` varchar(255) NOT NULL,
  `servidor_plataforma` varchar(255) NOT NULL,
  PRIMARY KEY (`servidor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `smtp`
--

DROP TABLE IF EXISTS `smtp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `smtp` (
  `smtp_id` int(11) NOT NULL AUTO_INCREMENT,
  `smtp_nome` varchar(255) NOT NULL,
  `smtp_email_admin` varchar(255) NOT NULL,
  `smtp_endereco` varchar(255) NOT NULL,
  `smtp_porta` int(11) NOT NULL,
  `smtp_senha` varchar(32) NOT NULL,
  PRIMARY KEY (`smtp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smtp`
--

LOCK TABLES `smtp` WRITE;
/*!40000 ALTER TABLE `smtp` DISABLE KEYS */;
/*!40000 ALTER TABLE `smtp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ssh`
--

DROP TABLE IF EXISTS `ssh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ssh` (
  `ssh_id` int(11) NOT NULL AUTO_INCREMENT,
  `ssh_ip` varchar(45) NOT NULL,
  `ssh_user` varchar(45) NOT NULL,
  `ssh_pass` varchar(45) NOT NULL,
  `ssh_status` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`ssh_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `tipo`
--

DROP TABLE IF EXISTS `tipo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo` (
  `tipo_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id do sistema operacional',
  `tipo_nome` varchar(45) NOT NULL COMMENT 'nome do sistema operacional',
  `tipo_plataforma` varchar(45) NOT NULL COMMENT 'plataforma do sistema operacional',
  PRIMARY KEY (`tipo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='tabela que armazena o cadastro de sistema operacional';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `usuario_id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_nome` varchar(45) NOT NULL,
  `usuario_login` varchar(45) NOT NULL,
  `usuario_senha` varchar(45) NOT NULL,
  `usuario_status` varchar(10) DEFAULT NULL,
  `usuario_id_app` int(11) DEFAULT NULL,
  `usuario_email` varchar(100) NOT NULL,
  `usuario_tentativas_invalidas` int(11) NOT NULL,
  `usuario_data_bloqueio` datetime DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  KEY `fk_usuario_setor_idx` (`usuario_id_app`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='tabela que armazena o cadastro de usuarios';
/*!40101 SET character_set_client = @saved_cs_client */;

---
-- Dumping routines for database 'safekup'
--
