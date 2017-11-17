-- MySQL dump 10.13  Distrib 5.7.20, for Linux (x86_64)
--
-- Host: localhost    Database: eWallet
-- ------------------------------------------------------
-- Server version	5.7.20-0ubuntu0.17.10.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `accountsID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `accountBalance` float(10,2) NOT NULL DEFAULT '0.00',
  `loanAccountBalance` float(10,2) DEFAULT '0.00',
  `interest` float(10,2) DEFAULT '0.00',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`accountsID`),
  KEY `fk_accounts_1` (`customerID`),
  CONSTRAINT `fk_accounts_1` FOREIGN KEY (`customerID`) REFERENCES `customers` (`customerid`)
) ENGINE=InnoDB AUTO_INCREMENT=365 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accounts_history`
--

DROP TABLE IF EXISTS `accounts_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `accountsID` int(11) NOT NULL,
  `amount` float(10,2) NOT NULL,
  `previous_balance` float(10,2) NOT NULL,
  `interest_amt` float(10,2) DEFAULT '0.00',
  `transaction_type` enum('DR','CR') NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `accountsID` (`accountsID`),
  CONSTRAINT `accounts_history_ibfk_1` FOREIGN KEY (`accountsID`) REFERENCES `accounts` (`accountsID`)
) ENGINE=InnoDB AUTO_INCREMENT=829 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currencies` (
  `currency_id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_name` varchar(30) NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`currency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customerDetails`
--

DROP TABLE IF EXISTS `customerDetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customerDetails` (
  `customerDetailsID` int(11) NOT NULL AUTO_INCREMENT,
  `customerid` int(11) DEFAULT NULL,
  `first_name` varchar(30) DEFAULT NULL,
  `last_name` varchar(30) DEFAULT NULL,
  `ID_number` varchar(45) DEFAULT NULL,
  `mobile_number` varchar(45) DEFAULT NULL,
  `physical_address` varchar(45) DEFAULT NULL,
  `account_pin` text NOT NULL,
  `org_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account_number` varchar(30) NOT NULL,
  PRIMARY KEY (`customerDetailsID`),
  KEY `fk_customerDetails_1` (`customerid`),
  KEY `org_id` (`org_id`),
  CONSTRAINT `customerDetails_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`),
  CONSTRAINT `fk_customerDetails_1` FOREIGN KEY (`customerid`) REFERENCES `customers` (`customerid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customerid` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customerid`)
) ENGINE=InnoDB AUTO_INCREMENT=368 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations` (
  `org_id` int(11) NOT NULL AUTO_INCREMENT,
  `org_code` varchar(30) NOT NULL,
  `customerID` int(11) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`org_id`),
  UNIQUE KEY `org_code` (`org_code`),
  KEY `customerID` (`customerID`),
  CONSTRAINT `organizations_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `customers` (`customerid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organizations_external_float_accounts`
--

DROP TABLE IF EXISTS `organizations_external_float_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations_external_float_accounts` (
  `float_acc_id` int(11) NOT NULL AUTO_INCREMENT,
  `current_float_balance` float(10,2) DEFAULT '0.00',
  `org_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`float_acc_id`),
  KEY `org_id` (`org_id`),
  CONSTRAINT `organizations_external_float_accounts_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organizations_external_float_accounts_history`
--

DROP TABLE IF EXISTS `organizations_external_float_accounts_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations_external_float_accounts_history` (
  `float_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `previous_balance` float(10,2) NOT NULL DEFAULT '0.00',
  `balance` float(10,2) NOT NULL DEFAULT '0.00',
  `float_acc_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`float_history_id`),
  KEY `float_acc_id` (`float_acc_id`),
  CONSTRAINT `organizations_external_float_accounts_history_ibfk_1` FOREIGN KEY (`float_acc_id`) REFERENCES `organizations_external_float_accounts` (`float_acc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organizations_income_targets`
--

DROP TABLE IF EXISTS `organizations_income_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations_income_targets` (
  `target_id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `target_amount` float(10,2) DEFAULT '0.00',
  `target_from` datetime NOT NULL,
  `target_to` datetime NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`target_id`),
  KEY `org_id` (`org_id`),
  CONSTRAINT `organizations_income_targets_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `service_operator_mapping`
--

DROP TABLE IF EXISTS `service_operator_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_operator_mapping` (
  `mapping_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mapping_id`),
  KEY `service_id` (`service_id`),
  KEY `operator_id` (`operator_id`),
  CONSTRAINT `service_operator_mapping_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`serviceid`),
  CONSTRAINT `service_operator_mapping_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `service_operators` (`operator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `service_operators`
--

DROP TABLE IF EXISTS `service_operators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_operators` (
  `operator_id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_code` varchar(30) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`operator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `serviceid` int(11) NOT NULL AUTO_INCREMENT,
  `servicename` varchar(45) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`serviceid`),
  UNIQUE KEY `servicename_UNIQUE` (`servicename`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `services_operator_charges`
--

DROP TABLE IF EXISTS `services_operator_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services_operator_charges` (
  `chargeid` int(11) NOT NULL AUTO_INCREMENT,
  `mapping_id` int(11) NOT NULL,
  `min_range` float(10,2) DEFAULT NULL,
  `max_range` float(10,2) DEFAULT NULL,
  `charge_amount` float(10,2) DEFAULT '0.00',
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chargeid`),
  KEY `mapping_id` (`mapping_id`),
  CONSTRAINT `services_operator_charges_ibfk_1` FOREIGN KEY (`mapping_id`) REFERENCES `service_operator_mapping` (`mapping_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `status_codes`
--

DROP TABLE IF EXISTS `status_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_codes` (
  `status_codes_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_codes` int(11) NOT NULL,
  `description` text NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`status_codes_id`),
  UNIQUE KEY `status_codes_UNIQUE` (`status_codes`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `transactionsid` int(11) NOT NULL AUTO_INCREMENT,
  `accountid` int(11) NOT NULL,
  `serviceid` int(11) NOT NULL,
  `uniqueTrxID` varchar(30) DEFAULT NULL,
  `external_receipt_number` varchar(100) DEFAULT NULL,
  `destination` varchar(45) NOT NULL,
  `amount` float(10,2) NOT NULL,
  `charge_amount` float(10,2) DEFAULT '0.00',
  `transaction_type` varchar(45) DEFAULT NULL,
  `status` enum('PENDING','INPROGRESS','COMPLETE','REVERSED') DEFAULT 'PENDING',
  `date_created` datetime DEFAULT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`transactionsid`),
  KEY `fk_transactions_1` (`serviceid`),
  KEY `accountid` (`accountid`),
  CONSTRAINT `fk_transactions_1` FOREIGN KEY (`serviceid`) REFERENCES `services` (`serviceid`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`accountid`) REFERENCES `accounts` (`accountsID`)
) ENGINE=InnoDB AUTO_INCREMENT=524 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transactions_charges`
--

DROP TABLE IF EXISTS `transactions_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions_charges` (
  `trx_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `transactionsid` int(11) NOT NULL,
  `message` text NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`trx_history_id`),
  KEY `transactionsid` (`transactionsid`),
  CONSTRAINT `transactions_charges_ibfk_1` FOREIGN KEY (`transactionsid`) REFERENCES `transactions` (`transactionsid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unallocatedAccountsTransactions`
--

DROP TABLE IF EXISTS `unallocatedAccountsTransactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unallocatedAccountsTransactions` (
  `alloc_id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(45) NOT NULL,
  `destination` varchar(45) NOT NULL,
  `uniqueTransactionID` varchar(30) NOT NULL,
  `amount` float(10,2) NOT NULL DEFAULT '0.00',
  `status` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`alloc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `userid` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(30) NOT NULL,
  `uid` int(11) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userid`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `api_key_UNIQUE` (`api_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wallet_partners`
--

DROP TABLE IF EXISTS `wallet_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallet_partners` (
  `partner_id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_name` varchar(30) NOT NULL,
  `partner_code` varchar(10) NOT NULL,
  `notify` enum('yes','no') DEFAULT 'no',
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wallet_partners_notification_rules`
--

DROP TABLE IF EXISTS `wallet_partners_notification_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallet_partners_notification_rules` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `notify_level` varchar(30) NOT NULL,
  `notify_url` text NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rule_id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `wallet_partners_notification_rules_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-11-10 13:07:57
