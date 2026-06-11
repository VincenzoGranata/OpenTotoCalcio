# Processo di sviluppo e deploy — Netcaring Srl

## Indice

1. [Architettura generale](#1-architettura-generale)
2. [Infrastruttura](#2-infrastruttura)
3. [Flusso di lavoro completo](#3-flusso-di-lavoro-completo)
4. [Gestione ticket e branch (EasyWork → Gitea)](#4-gestione-ticket-e-branch-easywork--gitea)
5. [Build dell'immagine Docker](#5-build-dellimmagine-docker)
6. [Versionamento delle immagini](#6-versionamento-delle-immagini)
7. [Versionamento dello schema del database](#7-versionamento-dello-schema-del-database)
8. [Pipeline GitOps di deploy](#8-pipeline-gitops-di-deploy)
9. [Configurazione dei repo applicativi](#9-configurazione-dei-repo-applicativi)
10. [Reverse proxy Traefik](#10-reverse-proxy-traefik)
11. [TODO](#11-todo)

---

## 1. Architettura generale

Il processo si divide in due fasi distinte: la fase di **sviluppo e release**, che produce un'immagine Docker versionata, e la fase di **deploy**, che promuove quella versione su un ambiente.

Il processo di sviluppo Netcaring adotta come riferimento i principi della metodologia [**12-Factor App**](https://12factor.net/it/), adattati al contesto applicativo interno. In particolare:

- **Codebase**: ogni applicativo ha un repo sorgente dedicato e versionato in Git
- **Build, release, run**: build dell'immagine, scelta della versione e deploy sono fasi distinte
- **Config**: la configurazione runtime deve rimanere esterna al codice applicativo e al repo sorgente
- **Dependencies**: le dipendenze applicative e di sistema devono essere dichiarate e riproducibili tramite `Dockerfile`
- **Backing services e state**: servizi esterni e persistenza devono essere trattati come risorse esplicite, non come dipendenze implicite della VM

Le sezioni successive traducono questi principi in regole operative per repository, immagini Docker, configurazione e deploy.

### Fase 1 — Sviluppo e release

```
┌─────────────────┐     branch/PR      ┌──────────────────────┐
│  EasyWork       │ ─────────────────► │  Gitea               │
│  (gestionale)   │                    │  (source repos)      │
│  easywork.local │                    │  gitea.netcaring.it  │
└─────────────────┘                    └──────────┬───────────┘
                                                  │ merge su main
                                                  ▼
                                       ┌──────────────────────┐
                                       │  Jenkins             │
                                       │  SonarQube scan      │ ──► sonarqube.netcaring.it
                                       └──────────────────────┘
                                                  │
                                       sviluppatore crea Git tag
                                       (semver) e fa push
                                                  │
                                                  ▼
                                       ┌──────────────────────┐     ┌──────────────────┐
                                       │  Jenkins             │ ──► │  Gitea Registry  │
                                       │  docker-build        │     │  (immagine:ver)  │
                                       └──────────────────────┘     └──────────────────┘
```

### Fase 2 — Deploy

```
  Gitea Registry                    maintainer aggiorna
  (immagine:ver)                    il tag nel repo di deploy
        │                                     │
        │        ┌────────────────────────────┘
        │        ▼
        │  ┌──────────────────────┐
        │  │  Gitea               │
        └─►│  gruppo              │
           │  gestione-applicativa│
           │  (repo per app/env)  │
           └──────────┬───────────┘
                      │ commit su main
                      ▼
           ┌──────────────────────┐
           │  Jenkins             │
           │  gitops-deploy       │
           └──────────┬───────────┘
                      │ docker compose pull + up -d
                      ▼
           ┌──────────────────────┐
           │  VM Sviluppo         │
           │  Docker + Traefik    │
           └──────────────────────┘
```

---

## 2. Infrastruttura

Tutti i servizi di sviluppo sono deployati con Docker sulla **VM di sviluppo Netcaring**.

| Servizio     | Funzione                                      | URL                                  | Autenticazione          |
|--------------|-----------------------------------------------|--------------------------------------|-------------------------|
| **Gitea**    | Hosting Git, registry Docker, gestione PR     | https://gitea.netcaring.it           | account Gitea           |
| **Jenkins**  | Automazione pipeline CI/CD                    | https://jenkins.netcaring.it         | account Jenkins         |
| **Portainer**| Gestione container Docker tramite UI          | https://portainer-dev.netcaring.it       | OAuth2 via Gitea        |
| **SonarQube**| Analisi statica della qualità del codice      | https://sonarqube.netcaring.it       | OAuth2 via Gitea        |
| **Traefik**  | Reverse proxy per esposizione applicativi     | *(gestito tramite label Docker)*     | —                       |
| **EasyWork** | Gestionale commesse e ticket                  | http://easywork.local                | account EasyWork        |

> Portainer e SonarQube utilizzano **OAuth2 con Gitea come identity provider**: è sufficiente accedere con le proprie credenziali Gitea.

### Riferimenti operativi

| Risorsa | URL | Uso |
|---------|-----|-----|
| Gitea — gruppo sorgenti | https://gitea.netcaring.it/netcaring | Repo sorgenti degli applicativi |
| Gitea — gruppo GitOps sviluppo | https://gitea.netcaring.it/gestione-applicativa | Repo deploy degli applicativi in sviluppo |
| Gitea — registry | https://gitea.netcaring.it/netcaring/-/packages | Registry immagini Docker |
| Jenkins — GitOps deploy | https://jenkins.netcaring.it/job/gitops-deploy/ | Pipeline deploy automatico |
| Jenkins — docker-build | https://jenkins.netcaring.it/job/docker-build/ | Pipeline build immagini Docker |

---

## 3. Flusso di lavoro completo

```
1. PM/Team crea ticket su EasyWork per una commessa
       │
       ▼
2. EasyWork genera automaticamente:
   - branch dedicato sul repo sorgente (es. feature/TICKET-123)
   - pull request su Gitea
       │
       ▼
3. Lo sviluppatore lavora sul branch:
   - git checkout feature/TICKET-123
   - implementa le modifiche
   - git commit / git push
       │
       ▼
4. Sviluppatore fa merge della PR e la chiude su Gitea
       │
       ▼
5. [BUILD AUTOMATICA — innescata dal Git tag]
   - lo sviluppatore crea un Git tag semver e fa push
   - la pipeline Jenkins docker-build si avvia in automatico
   - a build completata, l'immagine è pushata nel registry Gitea
       │
       ▼
6. [AGGIORNAMENTO MANUALE — a cura del maintainer]
   Il maintainer del repo di deploy decide se e quando portare la nuova
   versione sull'ambiente:
   - aggiorna il tag immagine nel docker-compose.yaml del repo in gestione-applicativa
   - fa commit/push su main (o apre una PR se richiesto dal processo)
       │
       ▼
7. [PIPELINE DEPLOY — già attiva]
   Il commit su main nel repo gestione-applicativa scatena Jenkins gitops-deploy:
   - esegue docker compose pull + up -d
   - il nuovo container è live sull'ambiente di sviluppo
```

---

## 4. Gestione ticket e branch (EasyWork → Gitea)

### Convenzione branch

I branch vengono creati automaticamente da EasyWork seguendo la convenzione:

```
feature/<TICKET-ID>-<descrizione-breve>
```

Esempio: `feature/NTC-42-aggiunta-modulo-pagamenti`

### Pull Request

Ogni PR creata automaticamente include:
- Riferimento al ticket EasyWork
- Branch sorgente: `feature/<TICKET-ID>-...`
- Branch destinazione: `main`

### Responsabilità dello sviluppatore

1. Verificare che il branch corretto sia stato creato su Gitea
2. Sviluppare localmente sul branch assegnato
3. Eseguire push dei commit sul branch remoto
4. Fare il merge della PR e chiuderla
5. **Controllare i risultati della scansione SonarQube** (vedi sezione seguente)

### Analisi della qualità del codice con SonarQube

Sul gruppo [`netcaring`](https://gitea.netcaring.it/netcaring) è configurato un **webhook di gruppo**: ad ogni commit sul branch `main` o `master` di qualsiasi repo, viene scatenata automaticamente una pipeline Jenkins che esegue la scansione SonarQube e pubblica i risultati sulla dashboard [https://sonarqube.netcaring.it](https://sonarqube.netcaring.it).

Lo sviluppatore deve accedere alla dashboard dopo ogni merge e **prendere visione delle issue rilevate**. Attualmente la scansione **non blocca** il flusso di merge o deploy: si tratta di una scelta temporanea, in attesa dell'introduzione di quality gate vincolanti. Le issue non vanno comunque ignorate: devono essere analizzate e, se rilevanti, risolte nella PR successiva.

---

## 5. Build dell'immagine Docker

La build dell'immagine Docker è **automatizzata** tramite la pipeline Jenkins [`docker-build`](https://jenkins.netcaring.it/job/docker-build/). Lo sviluppatore crea un Git tag semver e fa push: la pipeline si attiva automaticamente via webhook, builda l'immagine e la pusha nel registry Gitea.

Il nome e il tag dell'immagine nel registry corrispondono al nome del repo sorgente e al Git tag creato.

Questa fase implementa i principi [12-Factor](https://12factor.net/it/) relativi a **dependencies** e **build/release/run**: l'immagine deve essere riproducibile, autosufficiente per il runtime e separata dalla configurazione specifica dell'ambiente.

### Prerequisiti del repo sorgente

Perché la pipeline funzioni, il repo deve soddisfare uno dei due requisiti seguenti.

**Caso standard — Dockerfile singolo nella root**

Il repo deve avere un `Dockerfile` nella root. È obbligatorio usare il pattern **multistage build** per tenere le immagini finali leggere e non includere dipendenze di build nel runtime.

Esempio per un'applicazione Node.js con frontend statico:

```dockerfile
# ── Stage 1: build ──────────────────────────────────────────
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ── Stage 2: runtime ────────────────────────────────────────
FROM nginx:alpine AS runtime
COPY --from=builder /app/dist /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

> Adattare gli stage alla tecnologia del progetto (Java/Maven, Python, Go, ecc.).

**Caso monorepo — Docker Bake**

Se il repo non ha il `Dockerfile` nella root, oppure contiene più Dockerfile da cui produrre immagini distinte (monorepo), la pipeline riconosce la presenza del file `docker-bake.hcl` e lo usa come punto di ingresso per la build. In questo caso è necessario che il file `docker-bake.hcl` definisca correttamente tutti i target e le relative path ai Dockerfile.

### Attivazione della pipeline

Il webhook verso la pipeline `docker-build` deve essere configurato sul repo sorgente in Gitea. Verificare con il team DevOps che il webhook sia attivo prima della prima release.

Una volta configurato, per avviare una build è sufficiente:

```bash
git tag 1.1.0
git push origin 1.1.0
```

La pipeline si attiva automaticamente al push del tag. Non è necessario eseguire `docker build` o `docker push` in locale.

### Convenzione di tagging dell'immagine

Il tag dell'immagine prodotta dalla pipeline corrisponde al Git tag:

```
gitea.netcaring.it/netcaring/<nome-repo>:1.1.0
```

Il formato della versione è definito nella sezione seguente.

---

## 6. Versionamento delle immagini

Il tag dell'immagine è il collegamento tra la pipeline di build e il deploy: è il valore che il maintainer copia nel `docker-compose.yaml` per promuovere una versione. È quindi importante che sia leggibile, stabile e non ambiguo.

### Schema: Semantic Versioning

Si adotta il formato **`MAJOR.MINOR.PATCH`** (es. `1.4.2`).

| Componente | Quando incrementare |
|------------|---------------------|
| `MAJOR`    | Modifiche incompatibili con le versioni precedenti (breaking change di API, struttura dati, configurazione) |
| `MINOR`    | Nuove funzionalità retrocompatibili |
| `PATCH`    | Bug fix, aggiustamenti minori, senza nuove funzionalità |

Esempi:
```
gitea.netcaring.it/netcaring/crea:1.0.0   ← prima release stabile
gitea.netcaring.it/netcaring/crea:1.1.0   ← aggiunta nuova funzionalità
gitea.netcaring.it/netcaring/crea:1.1.1   ← bug fix
gitea.netcaring.it/netcaring/crea:2.0.0   ← breaking change
```

### Regole operative

- **Il versionamento delle immagini è indipendente dal merge delle singole PR.** Non ogni merge su `main` genera un nuovo tag: una release può includere una o più PR già integrate.
- **In condizioni normali, il Git tag viene creato a partire da `main`.** Il tag deve puntare al commit esatto che si intende rilasciare.
- **Sono ammesse eccezioni controllate** per build non definitive generate da branch diversi da `main`, ad esempio per test in un ambiente dedicato prima del merge. Queste build non vanno confuse con le release ordinarie e devono essere riconoscibili dal team.
- **Ogni build Docker deve partire da un Git tag in formato semver.** Creare il tag sul commit da rilasciare e fare push: la pipeline `docker-build` si occupa automaticamente di buildare e pushare l'immagine.
  ```bash
  git tag 1.1.0
  git push origin 1.1.0
  ```
  Il tag dell'immagine Docker corrisponde esattamente al Git tag: dato il tag di un'immagine in esercizio, è sempre possibile risalire al commit sorgente esatto su Gitea.

- **Non sovrascrivere mai un tag Git o Docker già pubblicato.** Se serve correggere una build, incrementare il `PATCH` e creare un nuovo tag.
- Il tag `latest` può essere pubblicato in aggiunta per comodità in sviluppo, ma **non va mai usato nel `docker-compose.yaml`** del repo di deploy: renderebbe impossibile sapere quale versione è effettivamente in esecuzione.

---

## 7. Versionamento dello schema del database

Ogni applicativo che utilizza un database relazionale deve garantire che il suo schema sia **tracciato nel repo sorgente** e **riproducibile da zero** in qualsiasi momento. Questo è un requisito tanto importante quanto il `Dockerfile`: un'immagine deployabile con uno schema di DB non tracciato non è un rilascio controllato.

In ottica [12-Factor](https://12factor.net/it/), lo stato persistente non deve essere gestito in modo implicito o manuale sulla VM: schema e relative evoluzioni devono essere dichiarati e versionati insieme all'applicativo.

### Approccio con tool di migration

Se il progetto usa un framework che integra la gestione delle migration (es. Flyway, Liquibase, Django ORM, Alembic, Laravel Migrations, Prisma Migrate, ecc.), le migration devono essere:

- **committate nel repo sorgente** insieme al codice applicativo che le richiede
- **eseguite in ordine** all'avvio dell'applicativo o come step separato del deploy
- **mai modificate retroattivamente** una volta applicate su un ambiente: aggiungere sempre una nuova migration

Struttura tipica nel repo:

```
db/
└── migrations/
    ├── V1__schema_iniziale.sql
    ├── V2__aggiunta_tabella_ordini.sql
    └── V3__indice_su_clienti_email.sql
```

Il prefisso numerico (o timestamp) garantisce l'ordine di applicazione e deve essere incrementale.

### Approccio senza tool di migration

Se non si usa nessun tool, il tracciamento è comunque obbligatorio seguendo queste regole:

1. **Dalla prima versione**, salvare nel repo lo script SQL completo per la creazione dello schema da zero:
   ```
   db/
   ├── schema.sql          ← DDL completo, aggiornato ad ogni migration
   └── migrations/
       ├── 001_init.sql
       ├── 002_aggiunta_colonna_stato.sql
       └── 003_nuova_tabella_log.sql
   ```

2. Per ogni modifica allo schema, creare un nuovo file SQL numerato nella cartella `migrations/` con le sole istruzioni `ALTER`, `CREATE`, `DROP` necessarie.

3. Aggiornare anche `schema.sql` in modo che rifletta sempre lo stato corrente completo — utile per ricreare l'intero DB senza dover applicare tutte le migration in sequenza.

4. Il file di migration va committato **nello stesso commit** (o nella stessa PR) del codice applicativo che dipende da quella modifica.

### Regole generali

- Lo schema del DB è **codice**: va revisionato in PR come tutto il resto.
- Non apportare mai modifiche manuali al DB di un ambiente senza creare la corrispondente migration nel repo.
- Le migration non devono essere modificate retroattivamente né rieseguite manualmente fuori dal flusso previsto dal tool. Se si usano script SQL raw senza un sistema di tracking affidabile, valutare guard-rail idempotenti dove opportuno (es. `IF NOT EXISTS`) per ridurre il rischio di errori operativi.

---

## 8. Pipeline GitOps di deploy

### Gruppo `gestione-applicativa`

[`gestione-applicativa`](https://gitea.netcaring.it/gestione-applicativa) è un **gruppo Gitea** che raccoglie un repo separato per ogni applicativo. Ogni repo contiene il `docker-compose.yaml` con il tag esatto dell'immagine da deployare in quell'ambiente.

**Separazione per ambiente**: per differenziare i permessi tra sviluppo e produzione, l'ambiente di produzione utilizzerà un gruppo Gitea distinto. In questo modo si possono assegnare maintainer e grant diversi per ogni ambiente senza condividere l'accesso.

**Maintainer**: ogni repo ha un proprio maintainer responsabile di:
- aggiornare il tag immagine nel `docker-compose.yaml` quando si vuole promuovere una nuova build
- approvare eventuali PR di aggiornamento versione
- decidere i tempi di deploy sull'ambiente

### Come funziona il deploy automatico

**Trigger**: qualsiasi commit sul branch `main` di un repo nel gruppo `gestione-applicativa` scatena automaticamente la pipeline Jenkins:
[`gitops-deploy`](https://jenkins.netcaring.it/job/gitops-deploy/)

### Cosa fa la pipeline `gitops-deploy`

1. Clona il repo modificato (inclusi eventuali file di configurazione presenti nel repo)
2. Esegue `docker compose pull` per scaricare la nuova immagine
3. Esegue `docker compose up -d` per riavviare il container con la nuova versione
4. Il container è immediatamente raggiungibile tramite Traefik all'URL configurato

### Log del deploy

L'esito di ogni deploy automatico è consultabile nel log del job [`gitops-deploy`](https://jenkins.netcaring.it/job/gitops-deploy/) su Jenkins. In caso di errore (immagine non trovata nel registry, container che non si avvia, ecc.) il log è il primo posto dove cercare.

### Monitoraggio con Portainer

Dopo il deploy, i developer possono monitorare i container attivi tramite [Portainer](https://portainer-dev.netcaring.it) (accesso con le credenziali Gitea).

Le operazioni disponibili direttamente dalla UI:
- **Visualizzare i log** del container in tempo reale
- **Riavviare** un container (es. dopo una modifica a un file di configurazione montato)
- **Stoppare e avviare** manualmente un container
- **Ispezionare** variabili d'ambiente, volumi montati e stato del processo

Portainer non sostituisce il flusso GitOps: qualsiasi modifica strutturale (immagine, configurazione, volumi) va sempre fatta aggiornando il repo in `gestione-applicativa`. Le operazioni su Portainer sono da considerarsi temporanee e operative.

### Rollback

Poiché il tag dell'immagine è esplicito nel `docker-compose.yaml`, tornare a una versione precedente è un'operazione manuale identica a un normale aggiornamento:

1. Il maintainer modifica il tag nel `docker-compose.yaml` riportando la versione precedente
2. Fa commit e push su `main`
3. La pipeline `gitops-deploy` si scatena automaticamente e riporta in esercizio la versione precedente

Non è necessario nessun comando straordinario: il meccanismo è lo stesso del deploy ordinario.

### Aggiunta di un nuovo applicativo

Per aggiungere un nuovo applicativo all'ambiente di sviluppo:

1. Creare un nuovo repo nel gruppo [`gestione-applicativa`](https://gitea.netcaring.it/gestione-applicativa) con il nome dell'applicativo
2. Aggiungere il `docker-compose.yaml` con le label Traefik appropriate (vedi §9)
3. Verificare che il webhook verso Jenkins sia attivo sul repo (il webhook di gruppo dovrebbe coprirlo automaticamente; verificare con il team DevOps)
4. Il sottodominio non richiede configurazione DNS: all'interno della VPN è definito un wildcard `*-dev.netcaring.it` che risolve automaticamente all'IP della VM di sviluppo

---

## 9. Configurazione dei repo applicativi

### Struttura `docker-compose.yaml`

La pipeline `gitops-deploy` clona l'intero repo sulla VM prima di eseguire `docker compose up -d`. È quindi possibile includere nel repo, accanto al `docker-compose.yaml`, qualsiasi file che il container deve montare (file di configurazione, credenziali, certificati, ecc.).

Dal punto di vista [12-Factor](https://12factor.net/it/), il repo di deploy rappresenta la **release configuration** dell'ambiente: l'immagine applicativa resta immutabile, mentre tag, variabili, mount e integrazioni infrastrutturali vengono definiti all'esterno del repo sorgente.

Esempio completo con file montato e named volume:

```yaml
services:
  <nome-servizio>:
    image: gitea.netcaring.it/netcaring/<nome-app>:<versione>
    container_name: <nome-app>_web
    restart: always
    volumes:
      - ./config.ini.php:/var/www/html/config.ini.php:ro   # file di configurazione/credenziali
      - <nome-app>_data:/var/www/html/uploads              # dati persistenti
    networks:
      - web
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.<nome-app>-web.rule=Host(`<nome-app>-dev.netcaring.it`)"
      - "traefik.http.routers.<nome-app>-web.entrypoints=websecure"
      - "traefik.http.routers.<nome-app>-web.tls=true"
      - "traefik.http.services.<nome-app>-web.loadbalancer.server.port=80"

volumes:
  <nome-app>_data:

networks:
  web:
    external: true
```

**File montati** — il percorso `./` è relativo alla root del repo clonato sulla VM. Usare il flag `:ro` (read-only) per i file di sola configurazione. Quando possibile, privilegiare variabili d'ambiente o configurazione esterna parametrica rispetto a file statici. I file che contengono credenziali devono essere presenti, se necessario, solo nel repo di deploy e **non nel repo sorgente**. I repo di deploy sono separati dai repo applicativi e accessibili solo ai responsabili della gestione applicativa.

**Named volume** — dichiarare sempre un named volume per ogni directory che contiene dati persistenti (upload, cache, storage applicativo). I bind mount su path arbitrari della VM sono da evitare.

### Campi da personalizzare per ogni applicativo

| Campo | Esempio | Note |
|-------|---------|-------|
| `image` | `gitea.netcaring.it/netcaring/crea:1.0.0` | aggiornato manualmente dal maintainer |
| `container_name` | `crea_web` | deve essere univoco sulla VM |
| `router.rule` | `` Host(`crea-dev.netcaring.it`) `` | sottodominio coperto dal wildcard DNS `*-dev.netcaring.it` |
| `loadbalancer.server.port` | `80` | porta esposta dal container |

> La rete `web` è una rete Docker esterna condivisa da tutti i servizi, gestita da Traefik.

---

## 10. Reverse proxy Traefik

Traefik è il reverse proxy che espone gli applicativi all'interno della VM di sviluppo tramite HTTPS.

### Meccanismo

Traefik legge le **label Docker** dei container attivi e crea automaticamente le regole di routing. Non è necessaria alcuna modifica alla configurazione di Traefik per aggiungere un nuovo applicativo: è sufficiente definire le label corrette nel `docker-compose.yaml`.

### Requisiti per un nuovo applicativo

1. Il container deve essere connesso alla rete Docker `web` (esterna)
2. Le label `traefik.*` devono essere configurate correttamente
3. Il sottodominio è automaticamente raggiungibile via VPN grazie al wildcard DNS `*-dev.netcaring.it` (nessuna configurazione DNS richiesta)

---

## 11. TODO

Evoluzioni previste o raccomandate del processo:

- Introdurre quality gate SonarQube vincolanti, almeno sui repository o branch principali
- Standardizzare la gestione delle migration DB nei diversi stack applicativi, così da rendere il deploy più uniforme e prevedibile
- Valutare l'introduzione di test automatici minimi di smoke o healthcheck nella pipeline di deploy

---

*Documento mantenuto dal team DevOps Netcaring Srl.*
*Versione: 0.1.5 — Ultima revisione: 2026-04-03*
