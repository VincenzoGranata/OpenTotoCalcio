# Piano Completo — Moduli Totocalcio Serie A

## Panoramica

Nuovo sistema di pronostici sulle partite di Serie A, strutturato in **concorsi** (uno per giornata). Ogni partecipante invia **una colonna** per concorso con 10 pronostici: 7 obbligatori + 3 opzionali (con categorie diverse). Sistema a punti con **mini classifiche intermedie** e **montepremi reali**.

---

## Regole di Gioco

### Struttura del Concorso

Una giornata Serie A ha **10 partite**. Il sistema le divide random:

- **7 Obbligatori** — tutti da pronosticare (1/X/2)
- **3 Opzionali** — ogni partita assegnata a una delle 3 categorie:

| Categoria | Punti | Descrizione |
|-----------|-------|-------------|
| Scelta (1/X/2) | 1pt | Pronostico classico |
| Risultato Esatto | 3pt | Goal casa e ospite esatti |
| Marcatore | 2pt | Un giocatore che segna almeno 1 gol nella partita |

**Totale massimo per colonna**: 7 + 1 + 3 + 2 = **13pt/giornata**

### Vincite Marcatore

- Il giocatore scelto deve segnare **almeno 1 gol** nella partita
- Non importa il risultato finale della squadra
- Se il giocatore segna più gol, rimangono sempre 2pt

### Regole Generali

- **1 colonna per partecipante per concorso**
- Deadline: entro l'inizio della prima partita della giornata
- Niente 1€/colonna (la quota è unica stagionale, vedi sezione montepremi)

---

## Tabelle Database

### Squadre e Giocatori

```sql
CREATE TABLE totocalcio_squadre (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_api INT UNIQUE,
    nome VARCHAR(255) NOT NULL,
    logo VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE totocalcio_giocatori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_api INT UNIQUE,
    nome VARCHAR(255) NOT NULL,
    id_squadra INT NOT NULL,
    ruolo VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_squadra) REFERENCES totocalcio_squadre(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Partecipanti

```sql
CREATE TABLE totocalcio_partecipanti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Quote Stagionali (pagamento unico)

```sql
CREATE TABLE totocalcio_quote_stagionali (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_partecipante INT NOT NULL,
    stagione VARCHAR(50) NOT NULL,
    importo DECIMAL(10,2) NOT NULL DEFAULT 0,
    pagato TINYINT(1) NOT NULL DEFAULT 0,
    data_pagamento DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_partecipante) REFERENCES totocalcio_partecipanti(id),
    UNIQUE KEY (id_partecipante, stagione)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Concorsi e Partite

```sql
CREATE TABLE totocalcio_concorsi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    giornata INT NOT NULL,
    data_chiusura DATETIME NOT NULL,
    stato ENUM('aperto', 'chiuso', 'concluso') DEFAULT 'aperto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE totocalcio_partite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_concorso INT NOT NULL,
    id_api INT DEFAULT NULL,
    pannello ENUM('obbligatorio', 'opzionale') NOT NULL,
    ordine INT NOT NULL,
    squadra_casa VARCHAR(255) NOT NULL,
    squadra_ospite VARCHAR(255) NOT NULL,
    logo_casa VARCHAR(500) DEFAULT NULL,
    logo_ospite VARCHAR(500) DEFAULT NULL,
    goal_casa INT DEFAULT NULL,
    goal_ospite INT DEFAULT NULL,
    data_partita DATETIME DEFAULT NULL,
    stato VARCHAR(50) DEFAULT 'scheduled',
    minuto VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_concorso) REFERENCES totocalcio_concorsi(id),
    UNIQUE KEY (id_concorso, pannello, ordine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE totocalcio_marcatori_partita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_partita INT NOT NULL,
    id_giocatore INT NOT NULL,
    gol INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_partita) REFERENCES totocalcio_partite(id),
    FOREIGN KEY (id_giocatore) REFERENCES totocalcio_giocatori(id),
    UNIQUE KEY (id_partita, id_giocatore)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Colonne e Pronostici

```sql
CREATE TABLE totocalcio_colonne (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_partecipante INT NOT NULL,
    id_concorso INT NOT NULL,
    punti_totali INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_partecipante) REFERENCES totocalcio_partecipanti(id),
    FOREIGN KEY (id_concorso) REFERENCES totocalcio_concorsi(id),
    UNIQUE KEY (id_partecipante, id_concorso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE totocalcio_pronostici (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_colonna INT NOT NULL,
    id_partita INT NOT NULL,
    tipo ENUM('1x2', 'risultato_esatto', 'marcatore') NOT NULL,
    pronostico VARCHAR(100) NOT NULL,
    punti INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_colonna) REFERENCES totocalcio_colonne(id),
    FOREIGN KEY (id_partita) REFERENCES totocalcio_partite(id),
    UNIQUE KEY (id_colonna, id_partita)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Mini Classifiche e Premi

```sql
CREATE TABLE totocalcio_mini_classifiche (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    data_inizio DATE NOT NULL,
    data_fine DATE DEFAULT NULL,
    stato ENUM('attiva', 'conclusa') DEFAULT 'attiva',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE totocalcio_mini_classifiche_premi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_mini_classifica INT NOT NULL,
    posizione INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mini_classifica) REFERENCES totocalcio_mini_classifiche(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE totocalcio_vincite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_partecipante INT NOT NULL,
    id_mini_classifica INT DEFAULT NULL,
    posizione INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL DEFAULT 0,
    pagato TINYINT(1) NOT NULL DEFAULT 0,
    data_pagamento DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_partecipante) REFERENCES totocalcio_partecipanti(id),
    FOREIGN KEY (id_mini_classifica) REFERENCES totocalcio_mini_classifiche(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## API — api-football (RapidAPI)

### Piano: Free Tier

- **100 richieste/giorno** — più che sufficienti
- **League ID Serie A**: 135
- **Endpoint principali**:

| Chiamata | Frequenza | Utilizzo |
|----------|-----------|----------|
| `GET /teams?league=135&season=2025` | 1x a stagione | Importa 20 squadre |
| `GET /players/squads?team={team_id}` | 20x a stagione | Rose giocatori |
| `GET /fixtures?league=135&season=2025&round=Regular+Season+-+{N}` | 1x/giornata | Partite della giornata |
| `GET /fixtures?id={fixture_id}` | 10x/giornata | Risultati + eventi/marcatori |

### Service Class: `ApiCalcioService.php`

```
namespace TotoCalcio;

class ApiCalcioService {
    syncTeams()         → totocalcio_squadre
    syncPlayers()       → totocalcio_giocatori (per squadra)
    syncGiornata(idConcorso, numeroGiornata) → totocalcio_partite
    updateScores(idConcorso) → update risultati + totocalcio_marcatori_partita
}
```

---

## Punteggio e Calcolo

### Service: `CalcoloPuntiService.php`

```php
calcolaPuntiColonna(idColonna):
  per ogni pronostico in colonna:
    se tipo == '1x2':
      se pronostico == risultato_reale → +1pt
    se tipo == 'risultato_esatto':
      se goalCasa:goalOspite == pronostico → +3pt
    se tipo == 'marcatore':
      se id_giocatore in totocalcio_marcatori_partita → +2pt

calcolaClassificaGenerale():
  SELECT partecipante, SUM(punti_totali) FROM totocalcio_colonne
  GROUP BY id_partecipante ORDER BY SUM DESC

calcolaClassificaRange(dataInizio, dataFine):
  stessa cosa ma filtrando concorsi nel date range
```

---

## Assegnazione Pannelli (Obbligatori / Opzionali)

Le 10 partite della giornata vengono assegnate **random**:

1. Admin crea concorso e clicca "Carica da API" → importa 10 partite
2. Admin clicca "Assegna Pannelli" → il sistema **mescola** le 10 partite:
   - Prime 7 → pannello `obbligatorio` (ordine 1-7)
   - Ultime 3 → pannello `opzionale` (ordine 1-3)
3. L'admin può ripetere l'assegnazione se vuole
4. Dopo la prima colonna inserita, i pannelli sono **bloccati**

---

## Mini Classifiche

### Flusso

1. **Admin** crea una mini classifica:
   - Nome (es. "Torneo di Natale")
   - Data inizio
   - Data fine (o lasciata aperta)
   - Premi per posizione (es. 1°=100€, 2°=50€, 3°=25€)
2. Il sistema calcola la classifica basata sui punti delle colonne nei concorsi con data nel range
3. Alla chiusura, l'admin clicca "Calcola Premi" → il sistema determina vincitori
4. L'admin paga manualmente e marca come "Pagato" nell'interfaccia
5. Il partecipante vede le sue vincite totali nella sua scheda

### Quote e Pagamenti

- **Unica quota stagionale** all'inizio del campionato
- Admin traccia manualmente chi ha pagato (`totocalcio_quote_stagionali`)
- Dalla quota totale, l'admin assegna i premi alle mini classifiche
- Il sistema traccia: `totocalcio_vincite` (chi ha vinto cosa, pagato/non pagato)

---

## Moduli (4 totali)

### 1. Totocalcio (`modules/totocalcio/`)

| File | Funzione |
|------|----------|
| `init.php` | SELECT * FROM totocalcio_partecipanti |
| `add.php` | Form aggiunta partecipante |
| `edit.php` | Scheda partecipante + riepilogo punti/vincite |
| `actions.php` | CRUD + salva colonna + salva pronostici |
| `plugins/colonne.php` | Tab: storico colonne del partecipante |
| `plugins/nuova_giocata.php` | Tab: crea nuova colonna per concorso aperto |
| `plugins/vincite.php` | Tab: riepilogo vincite (importo, pagato/non pagato) |
| `widgets/classifica.php` | Widget dashboard (top 20) |

### 2. Concorsi (`modules/totocalcio_concorsi/`)

| File | Funzione |
|------|----------|
| `init.php` | SELECT * FROM totocalcio_concorsi |
| `edit.php` | Dettaglio concorso + partite |
| `actions.php` | sync partite API, assegna pannelli, update punteggi |
| `controller_before.php` | Toolbar: Carica API, Assegna Pannelli, Aggiorna Risultati |
| `buttons.php` | Pulsante "Aggiorna" nella scheda partita |
| `plugins/partite.php` | Tab: partite del concorso (obblig/opzionali) |
| `plugins/colonne.php` | Tab: colonne del concorso |

### 3. Squadre (`modules/totocalcio_squadre/`)

| File | Funzione |
|------|----------|
| `init.php` | SELECT * FROM totocalcio_squadre |
| `edit.php` | Scheda squadra + rosa giocatori |
| `actions.php` | sync da API (squadre + giocatori) |
| `controller_before.php` | Pulsante "Sincronizza da API" |

### 4. Classifica (`modules/totocalcio_classifica/`)

| File | Funzione |
|------|----------|
| `init.php` | Vuoto |
| `edit.php` | Classifica generale + tabs per mini classifiche |
| `actions.php` | Admin: crea/chiudi mini classifica, segna pagamenti |
| `controller_before.php` | Admin: pulsanti gestione mini classifiche |

---

## Service Layer

```
src/TotoCalcio/
├── ApiCalcioService.php       — Chiamate API api-football
├── CalcoloPuntiService.php    — Calcolo punteggi e classifiche
```

### ApiCalcioService.php

```php
namespace TotoCalcio;

class ApiCalcioService {
    private $apiKey;
    private $baseUrl = 'https://api-football-v1.p.rapidapi.com/v3/';
    private $leagueId = 135; // Serie A

    syncTeams(): int — importa 20 squadre
    syncPlayers(idSquadra): int — importa giocatori di una squadra
    syncGiornata(idConcorso, numeroGiornata): int — importa partite
    updateScores(idConcorso): array — aggiorna risultati e marcatori
    
    private function call($endpoint, $params): array
}
```

### CalcoloPuntiService.php

```php
namespace TotoCalcio;

class CalcoloPuntiService {
    calcolaPuntiColonna(idColonna): int
    calcolaPuntiConcorso(idConcorso): int
    calcolaClassificaGenerale(): array
    calcolaClassificaRange(dataInizio, dataFine): array
    determinaVincitoriMiniClassifica(idMiniClassifica): array
    
    private function getRisultatoSimbolo($goalCasa, $goalOspite): string
    private function getMarcatoriPartita(idPartita): array
}
```

---

## Workflow Tipico

### Settimana Tipo

```
LUNEDÌ
  └─ Admin: Crea Concorso "5ª Giornata" → data_chiusura = Sabato 15:00
  └─ Admin: Click "Carica da API" → 10 partite importate
  └─ Admin: Click "Assegna Pannelli" → 7 obbl + 3 opz (random)

LUNEDÌ–SABATO (entro le 15:00)
  └─ Partecipanti: "Nuova Giocata"
      ├─ Predicono 7 obbligatori (1/X/2)
      ├─ Assegnano 3 opzionali a categorie
      │   ├─ 1 → Scelta 1X2 (1pt)
      │   ├─ 1 → Risultato Esatto (3pt)
      │   └─ 1 → Marcatore (2pt)
      └─ INVIA COLONNA

SABATO 15:01
  └─ Concorso passa automaticamente a "chiuso"

SABATO–DOMENICA (dopo ogni partita)
  └─ Admin: Click "Aggiorna Risultati"
      ├─ Sistema importa risultati + marcatori da API
      └─ Sistema calcola punti per tutte le colonne

A FINE MINI CLASSIFICA
  └─ Admin: Click "Calcola Premi"
      ├─ Sistema classifica partecipanti
      └─ Genera vincite
  └─ Admin: Paga vincitori e marca "Pagato"
```

### Workflow Mini Classifiche

```
INIZIO STAGIONE
  └─ Ogni partecipante versa quota unica
  └─ Admin segna pagamenti in totocalcio_quote_stagionali

DURANTE LA STAGIONE
  └─ Admin crea mini classifica:
      └─ "Gare di Andata" → range: 1ª-19ª giornata
      └─ Premio: 1°=200€, 2°=100€, 3°=50€
  └─ (continua con i concorsi normalmente)

A CHIUSURA MINI CLASSIFICA
  └─ Admin: "Calcola Premi"
  └─ Sistema calcola punti nel range
  └─ Admin vede: Vincitore 1° (X pts) → 200€, ...
  └─ Admin paga e marca "Pagato"

RIEPILOGO PARTECIPANTE
  └─ Nella scheda partecipante → Tab "Vincite"
  └─ Mostra: Totale vinto = 200€ + 50€ = 250€
  └─ Stato: In attesa / Pagato il XX/XX/XXXX
```

---

## SQL di Registrazione Moduli

```sql
-- Modulo Totocalcio
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Totocalcio', 'totocalcio',
 'SELECT |select| FROM `totocalcio_partecipanti` WHERE 1=1 HAVING 2=2 ORDER BY (SELECT COALESCE(SUM(punti_totali),0) FROM totocalcio_colonne WHERE id_partecipante = totocalcio_partecipanti.id) DESC',
 '', 'fa fa-futbol-o', '1.0', '*', '1', NULL, '1', '1');

-- Plugin sul partecipante
INSERT INTO `zz_plugins` VALUES
('Colonne', (SELECT id FROM zz_modules WHERE name='Totocalcio'), (SELECT id FROM zz_modules WHERE name='Totocalcio'), 'tab', 'colonne.php', 1, 0, 1, '', 'totocalcio'),
('Nuova Giocata', (SELECT id FROM zz_modules WHERE name='Totocalcio'), (SELECT id FROM zz_modules WHERE name='Totocalcio'), 'tab', 'nuova_giocata.php', 1, 1, 2, '', 'totocalcio'),
('Vincite', (SELECT id FROM zz_modules WHERE name='Totocalcio'), (SELECT id FROM zz_modules WHERE name='Totocalcio'), 'tab', 'vincite.php', 1, 0, 3, '', 'totocalcio');

-- Modulo Concorsi
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Concorsi Totocalcio', 'totocalcio_concorsi',
 'SELECT |select| FROM `totocalcio_concorsi` WHERE 1=1 HAVING 2=2 ORDER BY `giornata` DESC',
 'fa fa-calendar', '1.0', '*', '2', NULL, '1', '1');

-- Plugin sui concorsi
INSERT INTO `zz_plugins` VALUES
('Partite', (SELECT id FROM zz_modules WHERE name='Concorsi Totocalcio'), (SELECT id FROM zz_modules WHERE name='Concorsi Totocalcio'), 'tab', 'partite.php', 1, 1, 1, '', 'totocalcio_concorsi'),
('Colonne', (SELECT id FROM zz_modules WHERE name='Concorsi Totocalcio'), (SELECT id FROM zz_modules WHERE name='Concorsi Totocalcio'), 'tab', 'colonne.php', 1, 0, 2, '', 'totocalcio_concorsi');

-- Modulo Squadre
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Squadre', 'totocalcio_squadre',
 'SELECT |select| FROM `totocalcio_squadre` WHERE 1=1 HAVING 2=2 ORDER BY `nome` ASC',
 'fa fa-shield', '1.0', '*', '3', NULL, '1', '1');

-- Modulo Classifica
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Classifica Totocalcio', 'totocalcio_classifica', 'custom', 'fa fa-trophy', '1.0', '*', '4', NULL, '1', '1');
```

---

## UI: Nuova Giocata (Mockup)

```
┌─────────────────────────────────────────────────────────────┐
│ ● CONCORSO: 5ª GIORNATA SERIE A                            │
│   Chiusura: Sabato 25/10/2025 ore 15:00                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ ─── OBBLIGATORI (7) — 1pt cad. ───                         │
│                                                             │
│ [1] Milan vs Inter       ◉1  ○X  ○2  │ [2] Juve vs Roma   │
│ ○1  ◉X  ○2               │ ○1  ○X  ◉2                     │
│ ... (7 totali)                                              │
│                                                             │
│ ─── OPZIONALI (3) — assegna ogni partita a una categoria ─ │
│                                                             │
│ ╔══════════════════════════════════════════════════════════╗ │
│ ║ Atalanta vs Fiorentina                                  ║ │
│ ║ ◉ Scelta 1X2 (1pt)  ○ Ris. Esatto (3pt)  ○ Marc (2pt) ║ │
│ ║   ◉1  ○X  ○2                                           ║ │
│ ╚══════════════════════════════════════════════════════════╝ │
│                                                             │
│ ╔══════════════════════════════════════════════════════════╗ │
│ ║ Bologna vs Sassuolo                                     ║ │
│ ║ ○ Scelta 1X2 (1pt)  ◉ Ris. Esatto (3pt)  ○ Marc (2pt) ║ │
│ ║   Casa: [2]  Ospite: [1]                               ║ │
│ ╚══════════════════════════════════════════════════════════╝ │
│                                                             │
│ ╔══════════════════════════════════════════════════════════╗ │
│ ║ Verona vs Cagliari                                      ║ │
│ ║ ○ Scelta 1X2 (1pt)  ○ Ris. Esatto (3pt)  ◉ Marc (2pt) ║ │
│ ║   Marcatore: [Lautaro Martinez (Inter)        ▼]       ║ │
│ ╚══════════════════════════════════════════════════════════╝ │
│                                                             │
│ [INVIA COLONNA]                                             │
└─────────────────────────────────────────────────────────────┘
```

---

---

## Endpoint Pubblico: `calcio.php`

### URL
- **Standalone**: `https://totosport.dev/calcio.php`
- **Laravel**: `https://totosport.dev/calcio` (via route)

### Sezioni della Pagina

| Sezione | Contenuto |
|---------|-----------|
| **Classifica Generale** | Classifica assoluta con punti totali di tutti i concorsi |
| **Mini Classifiche** | Per ogni mini classifica, classifica filterata per date, con premi e stato pagamento |
| **Concorso Corrente** | Il concorso aperto (o l'ultimo concluso) con griglia partite e pronostici di tutti i partecipanti |
| **Concorsi Precedenti** | Lista accordion di tutti i concorsi passati con le relative griglie |

### Griglia Pronostici per Concorso

Ogni concorso mostra:
- Le **7 partite obbligatorie** con pronostici 1/X/2
- Le **3 partite opzionali** con il tipo assegnato (Scelta/Risultato Esatto/Marcatore)
- Per ogni partecipante: pronostico colorato (verde=corretto, rosso=errato, grigio=in attesa)
- Riga riepilogativa dei punti per partecipante in quel concorso

### Dati Recuperati

```sql
-- Classifica generale
SELECT p.id, p.nome, COALESCE(SUM(c.punti_totali), 0) AS totale
FROM totocalcio_partecipanti p
LEFT JOIN totocalcio_colonne c ON c.id_partecipante = p.id
GROUP BY p.id ORDER BY totale DESC

-- Mini classifica (filtered by date range)
SELECT p.id, p.nome, COALESCE(SUM(c.punti_totali), 0) AS totale
FROM totocalcio_partecipanti p
LEFT JOIN totocalcio_colonne c ON c.id_partecipante = p.id
LEFT JOIN totocalcio_concorsi co ON co.id = c.id_concorso
WHERE co.data_chiusura BETWEEN :inizio AND :fine
GROUP BY p.id ORDER BY totale DESC

-- Pronostici per concorso
SELECT pr.*, c.id_partecipante, pa.pannello, pa.ordine
FROM totocalcio_pronostici pr
JOIN totocalcio_colonne c ON c.id = pr.id_colonna
JOIN totocalcio_partite pa ON pa.id = pr.id_partita
WHERE pa.id_concorso = :idConcorso
ORDER BY pa.pannello, pa.ordine
```

### Auto-refresh

La pagina si ricarica automaticamente ogni **30 secondi** (come classifica.php).

---

## Implementazione — Ordine dei Lavori

| Step | Cosa | File |
|------|------|------|
| 1 | SQL tabella + registrazione moduli | `update/totocalcio.sql` |
| 2 | Service API | `src/TotoCalcio/ApiCalcioService.php` |
| 3 | Service punti | `src/TotoCalcio/CalcoloPuntiService.php` |
| 4 | Modulo Squadre | CRUD + sync API |
| 5 | Modulo Concorsi | CRUD + toolbar + actions |
| 6 | Modulo Totocalcio | Partecipanti + plugins colonne/nuova/vincite |
| 7 | Modulo Classifica | Generale + mini classifiche + pagamenti |
| 8 | Widget dashboard | classifica.php |
