CREATE TABLE IF NOT EXISTS `lab_rgroups` (
  `mstRGrpKey` int NOT NULL AUTO_INCREMENT,
  `RepoGrp` varchar(100) NOT NULL,
  `PreFix` varchar(3) NOT NULL DEFAULT '0',
  `Suffix` varchar(3) NOT NULL DEFAULT '0',
  `LastNo` int NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `Notes` varchar(50) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mstRGrpKey`),
  UNIQUE KEY `RepoGrp` (`RepoGrp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;

INSERT IGNORE INTO `lab_rgroups` (`mstRGrpKey`, `RepoGrp`, `PreFix`, `Suffix`, `LastNo`, `sort_order`, `Notes`) VALUES
(1, 'Biochemistry', 'BCH', '', 0, 2, ''),
(2, 'Biopsy', 'BP', '', 0, 3, ''),
(3, 'Clinical Pathology', 'CP', '', 0, 4, ''),
(4, 'Infectious Disease', 'INF', '', 0, 5, ''),
(5, 'General', 'GEN', '', 1, 6, ''),
(6, 'Hematology', 'HEM', '', 0, 1, ''),
(8, 'Urine', 'US', '', 0, 8, ''),
(9, 'Fluid Examination', 'FE', '0', 0, 9, '0'),
(10, 'CYTO-PATHOLOGY', 'CYP', '0', 0, 10, '0'),
(11, 'HISTOPATHOLOGY', 'HP', '0', 0, 11, '0'),
(12, 'IMMUNOLOGY-SEROLOGY', 'IS', '0', 0, 12, '0'),
(13, 'Endocrinology', 'END', '0', 0, 13, '0'),
(16, 'Vitamins & Minerals', 'VTM', '', 0, 24, ''),
(18, 'Inflammatory Markers', 'INM', '', 0, 26, ''),
(19, 'Tumor Markers', 'TMR', '', 0, 27, '');
