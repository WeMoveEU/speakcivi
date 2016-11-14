DROP TABLE IF EXISTS speakcivi_cleanup_languagegroup;
CREATE TABLE `speakcivi_cleanup_languagegroup` (
  `id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS speakcivi_cleanup_languagegroup_gc;
CREATE TABLE speakcivi_cleanup_languagegroup_gc (
  `contact_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`contact_id`)
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

DROP TABLE IF EXISTS speakcivi_english_uk;
CREATE TABLE speakcivi_english_uk (
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Unique Contact ID for UK',
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS speakcivi_english_int;
CREATE TABLE speakcivi_english_int (
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Unique Contact ID for INT (opposite to UK)',
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS speakcivi_english_ids;
CREATE TABLE speakcivi_english_ids (
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Unique Contact ID for temporary using in function',
  PRIMARY KEY (id)
);
