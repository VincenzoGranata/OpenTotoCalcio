-- TotoMondiale - Tabelle e registrazione moduli

CREATE TABLE IF NOT EXISTS `tot_partecipanti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tot_partite` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_sofascore` varchar(50) DEFAULT NULL,
  `girone` varchar(10) DEFAULT NULL,
  `squadra_casa` varchar(255) NOT NULL,
  `squadra_ospite` varchar(255) NOT NULL,
  `flag_casa` varchar(10) DEFAULT NULL,
  `flag_ospite` varchar(10) DEFAULT NULL,
  `goal_casa` int DEFAULT NULL,
  `goal_ospite` int DEFAULT NULL,
  `data_partita` datetime NOT NULL,
  `stato` varchar(50) DEFAULT 'scheduled',
  `minuto` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_sofascore` (`id_sofascore`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tot_pronostici` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_partecipante` int NOT NULL,
  `id_partita` int NOT NULL,
  `pronostico` enum('1','X','2') NOT NULL,
  `punti` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unico` (`id_partecipante`,`id_partita`),
  KEY `id_partecipante` (`id_partecipante`),
  KEY `id_partita` (`id_partita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tot_bonus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_partecipante` int NOT NULL,
  `tipo` enum('vincente','capocannoniere') NOT NULL,
  `valore` varchar(255) NOT NULL,
  `punti` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unico` (`id_partecipante`,`tipo`),
  KEY `id_partecipante` (`id_partecipante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modulo TotoMondiale (Partecipanti)
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('TotoMondiale', 'totomondiale',
 'SELECT |select| FROM `tot_partecipanti` WHERE 1=1 HAVING 2=2 ORDER BY (SELECT COALESCE(SUM(punti),0) FROM tot_pronostici WHERE id_partecipante = tot_partecipanti.id) DESC',
 '', 'fa fa-futbol-o', '1.0', '*', '1', NULL, '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '1', `id`, 'TotoMondiale', 'TotoMondiale'
FROM `zz_modules` WHERE `name` = 'TotoMondiale';

-- Views per TotoMondiale
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'), 'Nome', '`tot_partecipanti`.`nome`', 1, 1, 0, 0, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'), 'Email', '`tot_partecipanti`.`email`', 2, 1, 0, 0, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'), 'Pronostici', '(SELECT COUNT(*) FROM `tot_pronostici` WHERE `id_partecipante` = `tot_partecipanti`.`id`)', 3, 0, 0, 1, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'), 'Punti', '(SELECT COALESCE(SUM(`punti`),0) FROM `tot_pronostici` WHERE `id_partecipante` = `tot_partecipanti`.`id`)', 4, 0, 0, 1, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'), 'Bonus', '(SELECT COALESCE(SUM(`punti`),0) FROM `tot_bonus` WHERE `id_partecipante` = `tot_partecipanti`.`id`)', 5, 0, 0, 1, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'), 'Totale', '(SELECT COALESCE(SUM(`punti`),0) FROM `tot_pronostici` WHERE `id_partecipante` = `tot_partecipanti`.`id`) + (SELECT COALESCE(SUM(`punti`),0) FROM `tot_bonus` WHERE `id_partecipante` = `tot_partecipanti`.`id`)', 6, 0, 0, 1, 1, 1, 1);

-- Plugin Pronostici su Partecipante (tab nell'editor del partecipante)
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `options`, `directory`) VALUES
('Pronostici',
 (SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'),
 (SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'),
 'tab', 'pronostici.php', 1, 1, 1, '', 'totomondiale');

INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `options`, `directory`) VALUES
('Bonus',
 (SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'),
 (SELECT `id` FROM `zz_modules` WHERE `name` = 'TotoMondiale'),
 'tab', 'bonus.php', 1, 0, 2, '', 'totomondiale');

-- Modulo Partite Mondiale
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Partite Mondiale', 'totomondiale_partite',
 'SELECT |select| FROM `tot_partite` WHERE 1=1 HAVING 2=2 ORDER BY `data_partita` ASC',
 '', 'fa fa-calendar', '1.0', '*', '2', NULL, '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '1', `id`, 'Partite Mondiale', 'Partite Mondiale'
FROM `zz_modules` WHERE `name` = 'Partite Mondiale';

-- Views per Partite Mondiale
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'), 'Girone', '`tot_partite`.`girone`', 1, 1, 0, 0, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'), 'Casa', '`tot_partite`.`squadra_casa`', 2, 1, 0, 0, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'), 'Ospite', '`tot_partite`.`squadra_ospite`', 3, 1, 0, 0, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'), 'Risultato', 'CONCAT(COALESCE(`tot_partite`.`goal_casa`,\'?\'), \' - \', COALESCE(`tot_partite`.`goal_ospite`,\'?\'))', 4, 0, 0, 0, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'), 'Data', '`tot_partite`.`data_partita`', 5, 0, 0, 1, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'), 'Stato', '`tot_partite`.`stato`', 6, 0, 0, 0, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'), 'Minuto', '`tot_partite`.`minuto`', 7, 0, 0, 0, 1, 0, 1);

-- Plugin Pronostici su Partita (tab nell'editor della partita)
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `options`, `directory`) VALUES
('Pronostici Partita',
 (SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'),
 (SELECT `id` FROM `zz_modules` WHERE `name` = 'Partite Mondiale'),
 'tab', 'pronostici.php', 1, 1, 1, '', 'totomondiale_partite');

-- Traduzioni viste
INSERT IGNORE INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, v.id, v.name FROM `zz_views` v WHERE v.id_module = (SELECT id FROM `zz_modules` WHERE name = 'TotoMondiale');
INSERT IGNORE INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 2, v.id, v.name FROM `zz_views` v WHERE v.id_module = (SELECT id FROM `zz_modules` WHERE name = 'TotoMondiale');
INSERT IGNORE INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, v.id, v.name FROM `zz_views` v WHERE v.id_module = (SELECT id FROM `zz_modules` WHERE name = 'Partite Mondiale');
INSERT IGNORE INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 2, v.id, v.name FROM `zz_views` v WHERE v.id_module = (SELECT id FROM `zz_modules` WHERE name = 'Partite Mondiale');

-- Permessi viste per gruppi
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 1, id FROM `zz_views` WHERE id_module = (SELECT id FROM `zz_modules` WHERE name = 'TotoMondiale');
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 2, id FROM `zz_views` WHERE id_module = (SELECT id FROM `zz_modules` WHERE name = 'TotoMondiale');
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 1, id FROM `zz_views` WHERE id_module = (SELECT id FROM `zz_modules` WHERE name = 'Partite Mondiale');
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 2, id FROM `zz_views` WHERE id_module = (SELECT id FROM `zz_modules` WHERE name = 'Partite Mondiale');

-- Modulo Classifica
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Classifica', 'totomondiale_classifica', 'custom', 'fa fa-trophy', '1.0', '*', '3', NULL, '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '1', `id`, 'Classifica', 'Classifica' FROM `zz_modules` WHERE `name` = 'Classifica';

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '2', `id`, 'Leaderboard', 'Leaderboard' FROM `zz_modules` WHERE `name` = 'Classifica';

INSERT INTO `zz_permissions` (`idgruppo`, `idmodule`, `permessi`)
SELECT '1', `id`, 'rw' FROM `zz_modules` WHERE `name` = 'Classifica';

INSERT INTO `zz_permissions` (`idgruppo`, `idmodule`, `permessi`)
SELECT '2', `id`, 'r' FROM `zz_modules` WHERE `name` = 'Classifica';

-- Widget Classifica dashboard
INSERT INTO `zz_widgets` (`name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `more_link`, `more_link_type`, `php_include`, `enabled`, `order`) VALUES
('Classifica TotoMondiale', 'custom',
 (SELECT `id` FROM `zz_modules` WHERE `name` = 'Dashboard'),
 'controller_right', 'col-md-6', NULL, '#00a65a', 'fa fa-trophy',
 './modules/totomondiale/widgets/classifica.php', 'javascript',
 './modules/totomondiale/widgets/classifica.php', 1, 1);
