# OpenTotoCalcio

Gestione pronostici calcistici per Serie A (Totocalcio) e Mondiali (TotoMondiale).

Basato su OpenSTAManager, personalizzato per la gestione di leghe di pronostici tra partecipanti.

---

## Moduli

### Calendario Serie A
Calendario completo della stagione 2026/27 di Serie A.
- 38 giornate, 10 partite ciascuna
- Dati importati da **Fotmob** (pulsante *Aggiorna calendario*)
- Navigazione: lista giornate → tab Partite → modal dettaglio partita

### Squadre (Serie A)
Gestione delle 20 squadre di Serie A con rose giocatori.
- **Sincronizza squadre + loghi** — importa loghi e ID da TheSportsDB
- **Sincronizza rose giocatori** — importa i giocatori per ogni squadra
- Logo visibile nella scheda di modifica di ogni squadra

### Partecipanti
Anagrafica dei 68 partecipanti al Totocalcio.
- Nome, email, punti totali
- Tre tab per ogni partecipante:
  1. **Pronostici** — storico delle giocate per ogni giornata
  2. **Nuova Giocata** — inserimento pronostici per la giornata corrente
  3. **Vincite** — premi vinti

### Pronostici (Concorsi)
Gestione delle 38 giornate di campionato.
- **Assegna Pannelli** — divide le 10 partite in 6 obbligatorie (1X2, 1pt cad.), 1 risultato esatto (3pt) e 3 scelta (1 su 3, 1pt)
- **Chiudi Giornata** — blocca l'inserimento pronostici per la giornata

### Classifica Totocalcio
Classifica generale dei partecipanti con punti totali.

---

## TotoMondiale

Modulo separato per i pronostici del Mondiale 2026.

### Partite Mondiale
- 48 partite della fase a gironi
- Sync risultati da **worldcup26.ir**
- Calcolo automatico punti pronostici

### Classifica
Classifica dei partecipanti al TotoMondiale con:
- Punti per pronostici partite
- Bonus vincente (3pt) e capocannoniere (2pt)
- Auto-refresh ogni 30s

---

## Fonti dati

| Dato | Fonte | Endpoint |
|------|-------|----------|
| Partite 2026/27 | Fotmob (scraping SSR) | `fotmob.com/leagues/55/overview/2026-2027` |
| Squadre + loghi | TheSportsDB | `thesportsdb.com/api/v1/json/3/` |
| Rose giocatori | TheSportsDB | `lookup_all_players.php?id=TEAM_ID` |
| Calendario 2025/26 (storico) | football.json (GitHub) | `github.com/openfootball/football.json` |
| Risultati Mondiale | worldcup26.ir | `worldcup26.ir/get/games` |

---

## Tecnologia

- **PHP 8.3** (Docker)
- **Laravel 12** + Eloquent ORM
- **MySQL 8.3**
- **Bootstrap 5.3** + AdminLTE
- **Docker** (php-fpm + mysql)

---

## Sviluppo

```bash
docker compose up -d
```

L'app è accessibile su `http://localhost:8080`.

---

## Database

### Tabelle Totocalcio
| Tabella | Contenuto |
|---------|-----------|
| `totocalcio_squadre` | 20 squadre Serie A con logo e ID API |
| `totocalcio_giocatori` | Rose giocatori per squadra |
| `totocalcio_partecipanti` | 68 partecipanti |
| `totocalcio_concorsi` | 38 giornate |
| `totocalcio_partite` | 380 partite (10 x giornata) |
| `totocalcio_colonne` | Giocate dei partecipanti |
| `totocalcio_pronostici` | Pronostici individuali |
| `totocalcio_marcatori_partita` | Marcatori reali |
| `totocalcio_quote_stagionali` | Quote di partecipazione |
| `totocalcio_mini_classifiche` | Classifiche periodiche |
| `totocalcio_vincite` | Premi assegnati |

### Tabelle TotoMondiale
| Tabella | Contenuto |
|---------|-----------|
| `tot_partecipanti` | Partecipanti Mondiale |
| `tot_partite` | Partite Mondiale 2026 con risultati |
| `tot_pronostici` | Pronostici 1X2 |
| `tot_bonus` | Bonus vincente e capocannoniere |

---

## Cronologia sync consigliata

1. **Calendario Serie A** → *Aggiorna calendario* (una tantum a inizio stagione)
2. **Serie A** → *Sincronizza squadre + loghi* (una tantum)
3. **Serie A** → *Sincronizza rose giocatori* (una tantum, o dopo il mercato)
4. **Pronostici** → *Assegna Pannelli* (prima di ogni giornata)
5. **Pronostici** → *Chiudi Giornata* (a inizio partite)
