CREATE TABLE IF NOT EXISTS `lab_repotests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mstRepoKey` int NOT NULL,
  `mstTestKey` int NOT NULL,
  `EOrder` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`mstRepoKey`,`mstTestKey`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `mstRepoKey_EOrder` (`mstRepoKey`,`EOrder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

-- No rows found in `lab_repotests`
