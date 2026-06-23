-- Modulo Calendario Serie A
INSERT INTO `zz_modules` (`name`, `directory`, `attachments_directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Calendario Serie A', 'totocalcio_calendario', '',
 'SELECT |select| FROM `totocalcio_partite` WHERE 1=1 HAVING 2=2 ORDER BY `data_partita` ASC',
 '', 'fa fa-calendar', '1.0', '*', '5', NULL, '1', '1');

SELECT @id_calendario := LAST_INSERT_ID();

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`)
SELECT '1', @id_calendario, 'Calendario Serie A', 'Calendario Serie A';

-- Views Calendario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_calendario, 'Sq. Casa', '`totocalcio_partite`.`squadra_casa`', 1, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_calendario, 'Sq. Ospite', '`totocalcio_partite`.`squadra_ospite`', 2, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_calendario, 'Risultato', 'CONCAT(IFNULL(`goal_casa`,\"-\"),\"-\",IFNULL(`goal_ospite`,\"-\"))', 3, 0, 0, 0, 0, NULL, NULL, 1, 0, 0, 1),
(@id_calendario, 'Data', '`totocalcio_partite`.`data_partita`', 4, 0, 0, 1, 0, NULL, NULL, 1, 0, 0, 1),
(@id_calendario, 'Stato', '`totocalcio_partite`.`stato`', 5, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 1);

INSERT IGNORE INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, v.id, v.name FROM `zz_views` v WHERE v.id_module = @id_calendario;
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 1, id FROM `zz_views` WHERE id_module = @id_calendario;
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT 2, id FROM `zz_views` WHERE id_module = @id_calendario;
