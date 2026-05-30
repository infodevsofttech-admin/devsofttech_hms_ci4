CREATE TABLE IF NOT EXISTS `lab_repo` (
  `mstRepoKey` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(100) NOT NULL,
  `RTFData` longtext,
  `HTMLData` longtext,
  `GrpKey` int NOT NULL,
  `IncludeHeader` tinyint(1) DEFAULT '1',
  `IncludeFooter` tinyint(1) DEFAULT '1',
  `charge_id` int DEFAULT '0',
  `loinc_code` varchar(20) DEFAULT NULL,
  `loinc_synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`mstRepoKey`),
  UNIQUE KEY `Title` (`Title`),
  KEY `GrpKey` (`GrpKey`),
  KEY `charge_id` (`charge_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

-- No rows found in `lab_repo`
