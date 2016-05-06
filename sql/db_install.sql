DROP TABLE IF EXISTS speakcivi_cleanup_languagegroup;
CREATE TABLE `speakcivi_cleanup_languagegroup` (
  `id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS speakcivi_cleanup_leave;
CREATE TABLE `speakcivi_cleanup_leave` (
  `id` int(10) unsigned NOT NULL,
  `subject` varchar(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS speakcivi_cleanup_join;
CREATE TABLE `speakcivi_cleanup_join` (
  `id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
