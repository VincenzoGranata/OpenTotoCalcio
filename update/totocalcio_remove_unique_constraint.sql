-- Rimuove il constraint UNIQUE che impediva di inserire due pronostici per la stessa partita
-- Questo permette di avere sia il pronostico 1X2 che il risultato esatto per la stessa partita

ALTER TABLE `totocalcio_pronostici` DROP INDEX `colonna_partita`;
