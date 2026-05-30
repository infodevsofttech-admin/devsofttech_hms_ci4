CREATE TABLE IF NOT EXISTS `lab_tests_option` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mstTestKey` int DEFAULT NULL,
  `sort_id` int DEFAULT NULL,
  `option_value` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `option_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `option_bold` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mstTestKey_sort_id` (`mstTestKey`,`sort_id`),
  UNIQUE KEY `mstTestKey_option_value` (`mstTestKey`,`option_value`),
  KEY `option_value` (`option_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- No rows found in `lab_tests_option`
