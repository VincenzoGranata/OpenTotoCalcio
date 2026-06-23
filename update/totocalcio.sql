-- Totocalcio Serie A - Tabelle e registrazione moduli

-- Squadre
CREATE TABLE IF NOT EXISTS `totocalcio_squadre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_api` int DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_api` (`id_api`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Giocatori
CREATE TABLE IF NOT EXISTS `totocalcio_giocatori` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_api` int DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `id_squadra` int NOT NULL,
  `ruolo` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_api` (`id_api`),
  KEY `id_squadra` (`id_squadra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Partecipanti
CREATE TABLE IF NOT EXISTS `totocalcio_partecipanti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Quote stagionali
CREATE TABLE IF NOT EXISTS `totocalcio_quote_stagionali` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_partecipante` int NOT NULL,
  `stagione` varchar(50) NOT NULL,
  `importo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pagato` tinyint(1) NOT NULL DEFAULT '0',
  `data_pagamento` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_partecipante_stagione` (`id_partecipante`,`stagione`),
  KEY `id_partecipante` (`id_partecipante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Concorsi
CREATE TABLE IF NOT EXISTS `totocalcio_concorsi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `giornata` int NOT NULL,
  `data_chiusura` datetime NOT NULL,
  `stato` enum('aperto','chiuso','concluso') DEFAULT 'aperto',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Partite
CREATE TABLE IF NOT EXISTS `totocalcio_partite` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_concorso` int NOT NULL,
  `id_api` int DEFAULT NULL,
  `pannello` enum('obbligatorio','opzionale') NOT NULL,
  `ordine` int NOT NULL,
  `squadra_casa` varchar(255) NOT NULL,
  `squadra_ospite` varchar(255) NOT NULL,
  `logo_casa` varchar(500) DEFAULT NULL,
  `logo_ospite` varchar(500) DEFAULT NULL,
  `goal_casa` int DEFAULT NULL,
  `goal_ospite` int DEFAULT NULL,
  `data_partita` datetime DEFAULT NULL,
  `stato` varchar(50) DEFAULT 'scheduled',
  `minuto` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_api` (`id_api`),
  UNIQUE KEY `concorso_pannello_ordine` (`id_concorso`,`pannello`,`ordine`),
  KEY `id_concorso` (`id_concorso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Marcatori reali per partita
CREATE TABLE IF NOT EXISTS `totocalcio_marcatori_partita` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_partita` int NOT NULL,
  `id_giocatore` int NOT NULL,
  `gol` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partita_giocatore` (`id_partita`,`id_giocatore`),
  KEY `id_partita` (`id_partita`),
  KEY `id_giocatore` (`id_giocatore`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Colonne (giocate)
CREATE TABLE IF NOT EXISTS `totocalcio_colonne` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_partecipante` int NOT NULL,
  `id_concorso` int NOT NULL,
  `punti_totali` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partecipante_concorso` (`id_partecipante`,`id_concorso`),
  KEY `id_partecipante` (`id_partecipante`),
  KEY `id_concorso` (`id_concorso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pronostici
CREATE TABLE IF NOT EXISTS `totocalcio_pronostici` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_colonna` int NOT NULL,
  `id_partita` int NOT NULL,
  `tipo` enum('1x2','risultato_esatto','marcatore') NOT NULL,
  `pronostico` varchar(100) NOT NULL,
  `punti` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `colonna_partita` (`id_colonna`,`id_partita`),
  KEY `id_colonna` (`id_colonna`),
  KEY `id_partita` (`id_partita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mini classifiche
CREATE TABLE IF NOT EXISTS `totocalcio_mini_classifiche` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `data_inizio` date NOT NULL,
  `data_fine` date DEFAULT NULL,
  `stato` enum('attiva','conclusa') DEFAULT 'attiva',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Premi mini classifiche
CREATE TABLE IF NOT EXISTS `totocalcio_mini_classifiche_premi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_mini_classifica` int NOT NULL,
  `posizione` int NOT NULL,
  `importo` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_mini_classifica` (`id_mini_classifica`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vincite
CREATE TABLE IF NOT EXISTS `totocalcio_vincite` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_partecipante` int NOT NULL,
  `id_mini_classifica` int DEFAULT NULL,
  `posizione` int NOT NULL,
  `importo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pagato` tinyint(1) NOT NULL DEFAULT '0',
  `data_pagamento` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_partecipante` (`id_partecipante`),
  KEY `id_mini_classifica` (`id_mini_classifica`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── REGISTRAZIONE MODULI ──────────────────────────

-- Modulo Totocalcio (partecipanti)
INSERT INTO `zz_modules` (`name`, `directory`, `attachments_directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Totocalcio', 'totocalcio', '',
 'SELECT |select| FROM `totocalcio_partecipanti` WHERE 1=1 HAVING 2=2 ORDER BY (SELECT COALESCE(SUM(punti_totali),0) FROM totocalcio_colonne WHERE id_partecipante = totocalcio_partecipanti.id) DESC',
 '', 'fa fa-futbol-o', '1.0', '*', '1', NULL, '1', '1');

SELECT @id_totocalcio := LAST_INSERT_ID();

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '1', @id_totocalcio, 'Totocalcio', 'Totocalcio';

-- Views Totocalcio
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_totocalcio, 'Nome', '`totocalcio_partecipanti`.`nome`', 1, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_totocalcio, 'Email', '`totocalcio_partecipanti`.`email`', 2, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_totocalcio, 'Punti', '(SELECT COALESCE(SUM(`punti_totali`),0) FROM `totocalcio_colonne` WHERE `id_partecipante` = `totocalcio_partecipanti`.`id`)', 3, 0, 0, 1, 0, NULL, NULL, 1, 1, 0, 1);

INSERT IGNORE INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, v.id, v.name FROM `zz_views` v WHERE v.id_module = @id_totocalcio;
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 1, id FROM `zz_views` WHERE id_module = @id_totocalcio;
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 2, id FROM `zz_views` WHERE id_module = @id_totocalcio;

-- Plugin Totocalcio
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `attachments_directory`, `help`) VALUES
('Colonne', @id_totocalcio, @id_totocalcio, 'tab', 'colonne.php', 1, 0, 1, '*', '1.0', NULL, '', 'totocalcio', '', '');

SELECT @id_plugin_colonne := LAST_INSERT_ID();

INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `attachments_directory`, `help`) VALUES
('Nuova Giocata', @id_totocalcio, @id_totocalcio, 'tab', 'nuova_giocata.php', 1, 1, 2, '*', '1.0', NULL, '', 'totocalcio', '', '');

INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `attachments_directory`, `help`) VALUES
('Vincite', @id_totocalcio, @id_totocalcio, 'tab', 'vincite.php', 1, 0, 3, '*', '1.0', NULL, '', 'totocalcio', '', '');

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, p.id, p.name FROM `zz_plugins` p WHERE p.idmodule_from = @id_totocalcio AND p.directory = 'totocalcio';

-- Widget classifica
INSERT INTO `zz_widgets` (`name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `enabled`, `order`, `help`) VALUES
('Classifica Totocalcio', 'custom',
 (SELECT `id` FROM `zz_modules` WHERE `name` = 'Dashboard'),
 'controller_right', 'col-md-6', NULL, '#00a65a', 'fa fa-trophy', NULL,
 './modules/totocalcio/widgets/classifica.php', 'javascript',
 './modules/totocalcio/widgets/classifica.php', 1, 1, '');

-- Modulo Concorsi
INSERT INTO `zz_modules` (`name`, `directory`, `attachments_directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Concorsi Totocalcio', 'totocalcio_concorsi', '',
 'SELECT |select| FROM `totocalcio_concorsi` WHERE 1=1 HAVING 2=2 ORDER BY `giornata` DESC',
 '', 'fa fa-calendar', '1.0', '*', '2', NULL, '1', '1');

SELECT @id_concorsi := LAST_INSERT_ID();

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '1', @id_concorsi, 'Concorsi Totocalcio', 'Concorsi Totocalcio';

-- Views Concorsi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_concorsi, 'Nome', '`totocalcio_concorsi`.`nome`', 1, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_concorsi, 'Giornata', '`totocalcio_concorsi`.`giornata`', 2, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_concorsi, 'Stato', '`totocalcio_concorsi`.`stato`', 3, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_concorsi, 'Chiusura', '`totocalcio_concorsi`.`data_chiusura`', 4, 0, 0, 1, 0, NULL, NULL, 1, 0, 0, 1),
(@id_concorsi, 'Colonne', '(SELECT COUNT(*) FROM `totocalcio_colonne` WHERE `id_concorso` = `totocalcio_concorsi`.`id`)', 5, 0, 0, 1, 0, NULL, NULL, 1, 1, 0, 1),
(@id_concorsi, 'Data', 'IFNULL((SELECT MIN(`data_partita`) FROM `totocalcio_partite` WHERE `id_concorso` = `totocalcio_concorsi`.`id`), \"\")', 6, 0, 0, 1, 0, NULL, NULL, 1, 0, 0, 1);

INSERT IGNORE INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, v.id, v.name FROM `zz_views` v WHERE v.id_module = @id_concorsi;
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 1, id FROM `zz_views` WHERE id_module = @id_concorsi;
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 2, id FROM `zz_views` WHERE id_module = @id_concorsi;

-- Plugin Concorsi
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `attachments_directory`, `help`) VALUES
('Partite', @id_concorsi, @id_concorsi, 'tab', 'partite.php', 1, 1, 1, '*', '1.0', NULL, '', 'totocalcio_concorsi', '', '');

INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `attachments_directory`, `help`) VALUES
('Colonne', @id_concorsi, @id_concorsi, 'tab', 'colonne.php', 1, 0, 2, '*', '1.0', NULL, '', 'totocalcio_concorsi', '', '');

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, p.id, p.name FROM `zz_plugins` p WHERE p.idmodule_from = @id_concorsi AND p.directory = 'totocalcio_concorsi';

-- Modulo Squadre
INSERT INTO `zz_modules` (`name`, `directory`, `attachments_directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Squadre Totocalcio', 'totocalcio_squadre', '',
 'SELECT |select| FROM `totocalcio_squadre` WHERE 1=1 HAVING 2=2 ORDER BY `nome` ASC',
 '', 'fa fa-shield', '1.0', '*', '3', NULL, '1', '1');

SELECT @id_squadre := LAST_INSERT_ID();

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '1', @id_squadre, 'Squadre Totocalcio', 'Squadre Totocalcio';

-- Views Squadre
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_squadre, 'Nome', '`totocalcio_squadre`.`nome`', 1, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_squadre, 'Giocatori', '(SELECT COUNT(*) FROM `totocalcio_giocatori` WHERE `id_squadra` = `totocalcio_squadre`.`id`)', 2, 0, 0, 1, 0, NULL, NULL, 1, 1, 0, 1);

INSERT IGNORE INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, v.id, v.name FROM `zz_views` v WHERE v.id_module = @id_squadre;
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 1, id FROM `zz_views` WHERE id_module = @id_squadre;
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 2, id FROM `zz_views` WHERE id_module = @id_squadre;

-- Modulo Classifica
INSERT INTO `zz_modules` (`name`, `directory`, `attachments_directory`, `options`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Classifica Totocalcio', 'totocalcio_classifica', '', 'custom', 'fa fa-trophy', '1.0', '*', '4', NULL, '1', '1');

SELECT @id_classifica := LAST_INSERT_ID();

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '1', @id_classifica, 'Classifica Totocalcio', 'Classifica Totocalcio';

INSERT INTO `zz_permissions` (`idgruppo`, `idmodule`, `permessi`)
SELECT '1', @id_classifica, 'rw';
INSERT INTO `zz_permissions` (`idgruppo`, `idmodule`, `permessi`)
SELECT '2', @id_classifica, 'r';
