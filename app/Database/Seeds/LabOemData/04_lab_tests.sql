CREATE TABLE IF NOT EXISTS `lab_tests` (
  `mstTestKey` int NOT NULL AUTO_INCREMENT,
  `Test` varchar(80) NOT NULL DEFAULT '0',
  `TestID` varchar(15) NOT NULL,
  `Result` varchar(180) NOT NULL DEFAULT '0',
  `Options` text,
  `Formula` varchar(50) DEFAULT NULL,
  `VRule` varchar(50) DEFAULT NULL,
  `VMsg` varchar(50) DEFAULT NULL,
  `Unit` varchar(10) NOT NULL DEFAULT '0',
  `FixedNormals` varchar(30) NOT NULL DEFAULT '0',
  `isGenderSpecific` int NOT NULL DEFAULT '0',
  `FixedNormalsWomen` varchar(30) NOT NULL,
  `loinc_code` varchar(20) DEFAULT NULL,
  `loinc_property` varchar(50) DEFAULT NULL,
  `loinc_system` varchar(50) DEFAULT NULL,
  `loinc_scale` varchar(20) DEFAULT NULL,
  `loinc_synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`mstTestKey`),
  UNIQUE KEY `TestID` (`TestID`),
  KEY `Test` (`Test`),
  KEY `idx_lab_tests_loinc` (`loinc_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

-- No rows found in `lab_tests`
