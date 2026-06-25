-- Collega totocalcio_partecipanti a zz_users
ALTER TABLE totocalcio_partecipanti
  ADD COLUMN id_utente INT(11) DEFAULT NULL AFTER email,
  ADD UNIQUE KEY id_utente (id_utente);

-- Crea gruppo "Totocalcio Giocatori" (se non esiste)
INSERT INTO zz_groups (nome, description)
SELECT 'Totocalcio Giocatori', 'Giocatori del Totocalcio Serie A'
WHERE NOT EXISTS (SELECT 1 FROM zz_groups WHERE nome = 'Totocalcio Giocatori');

-- Ottieni ID gruppo
SET @gruppo_id = (SELECT id FROM zz_groups WHERE nome = 'Totocalcio Giocatori' LIMIT 1);

-- Ottieni ID modulo totocalcio partecipanti
SET @modulo_id = (SELECT id FROM zz_modules WHERE name = 'Totocalcio' LIMIT 1);

-- Assegna permesso rw al gruppo sul modulo Totocalcio
INSERT INTO zz_permissions (idgruppo, idmodule, permissions)
SELECT @gruppo_id, @modulo_id, 'rw'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM zz_permissions WHERE idgruppo = @gruppo_id AND idmodule = @modulo_id
);

-- Configura OAuth2 Keycloak per login
INSERT INTO zz_oauth2 (class, name, client_id, client_secret, config, state, is_login, enabled)
SELECT 'Keycloak', 'Keycloak',
  'osm-totocalcio',
  'osm-secret-key-2026',
  '{"baseUrl":"http://keycloak:8080","realm":"totosport"}',
  'active',
  1, 1
WHERE NOT EXISTS (SELECT 1 FROM zz_oauth2 WHERE name = 'Keycloak');
