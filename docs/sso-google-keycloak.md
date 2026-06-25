# Integrazione SSO Google tramite Keycloak

## Architettura

```
Utente → OSM (localhost:8080) → Keycloak (localhost:8081) → Google
                                                              ↓
Utente ← OSM ← Keycloak ← Google (token)
```

OSM non parla direttamente con Google. Keycloak fa da intermediario (broker).

---

## 1. Prerequisiti

- Docker con container Keycloak già configurato (realm `totosport`)
- Google Cloud Platform account

---

## 2. Configurare Google Cloud Console

1. Vai su https://console.cloud.google.com/apis/credentials
2. Crea un progetto o selezionane uno esistente
3. Vai su **"APIs & Services" → "Credentials"**
4. Clicca **"+ Create Credentials" → "OAuth client ID"**
5. Se non hai configurato la schermata di consenso, fallo prima:
   - **User Type**: External
   - **App name**: Totocalcio
   - **Support email**: la tua email
   - **Scopes**: aggiungi `.../auth/userinfo.email` e `.../auth/userinfo.profile`
   - **Test users**: aggiungi la tua email
6. Crea OAuth client:
   - **Application type**: Web application
   - **Name**: Totocalcio Keycloak
   - **Authorized redirect URIs**:

     **Sviluppo (localhost):**
     ```
     http://localhost:8081/realms/totosport/broker/google/endpoint
     ```

     **Produzione:**
     ```
     https://tuodominio.com/realms/totosport/broker/google/endpoint
     ```

7. Clicca **Create**
8. Copia **Client ID** e **Client Secret**

---

## 3. Configurare Keycloak (via API Admin)

### 3.1 Ottenere token admin

```bash
curl -X POST http://localhost:8081/realms/master/protocol/openid-connect/token \
  -d "client_id=admin-cli" \
  -d "username=admin" \
  -d "password=admin" \
  -d "grant_type=password"
```

### 3.2 Aggiungere Google come Identity Provider

```bash
curl -X POST http://localhost:8081/admin/realms/totosport/identity-provider/instances \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "alias": "google",
    "providerId": "google",
    "enabled": true,
    "config": {
      "clientId": "IL_TUO_CLIENT_ID",
      "clientSecret": "IL_TUO_CLIENT_SECRET",
      "useJwksUrl": "true",
      "defaultScope": "openid profile email",
      "syncMode": "IMPORT"
    }
  }'
```

### 3.3 Verificare

```bash
curl http://localhost:8081/admin/realms/totosport/identity-provider/instances \
  -H "Authorization: Bearer TOKEN"
```

---

## 4. Verificare il flusso completo

1. Vai su `http://localhost:8080`
2. Clicca **"Accedi con Keycloak"**
3. Vieni reindirizzato a Keycloak (`localhost:8081`)
4. Clicca **"Google"** (o "Sign in with Google")
5. Google chiede di autorizzare l'app
6. Dopo l'autorizzazione, torni a Keycloak → poi a OSM
7. Se è la prima volta, viene creato l'utente in `zz_users` con gruppo `Totocalcio Giocatori`

---

## 5. Registrazione utenti

Il flusso per i nuovi utenti che si registrano con Google:

1. Prima volta → login con Google
2. Keycloak reindirizza a OSM con le info utente (nome, email)
3. OSM cerca utente in `zz_users` per email
4. Se non trovato → crea nuovo utente (gruppo Totocalcio Giocatori)
5. Cerca/crea record in `totocalcio_partecipanti`
6. Login automatico completato

---

## 6. Passaggio in produzione

### 6.1 DNS e dominio

| Servizio | Sviluppo | Produzione |
|---|---|---|
| OSM | `http://localhost:8080` | `https://totocalcio.tuodominio.com` |
| Keycloak | `http://localhost:8081` | `https://totocalcio.tuodominio.com/realms/totosport` |
| Google redirect URI | `http://localhost:8081/realms/...` | `https://totocalcio.tuodominio.com/realms/...` |

### 6.2 Modifiche necessarie

#### Docker compose

```yaml
keycloak:
  environment:
    KC_HOSTNAME: totocalcio.tuodominio.com
    KC_HOSTNAME_STRICT: true
    KC_HTTPS_PORT: 443
  ports:
    - "443:8443"  # HTTPS
```

#### zz_oauth2 (DB)

```sql
UPDATE zz_oauth2 SET config = '{
  "auth_server_url": "http://keycloak:8080",
  "public_auth_server_url": "https://totocalcio.tuodominio.com",
  "realm": "totosport"
}' WHERE name = 'Keycloak';
```

#### Google Cloud Console

Aggiornare **Authorized redirect URIs**:
```
https://totocalcio.tuodominio.com/realms/totosport/broker/google/endpoint
```

### 6.3 HTTPS (consigliato)

Opzione A — **Traefik** (reverse proxy automatico):
```yaml
traefik:
  image: traefik:v3.0
  command: --providers.docker --entrypoints.websecure.address=:443 --certificatesresolvers.letsencrypt.acme.tlschallenge=true
  ports:
    - "443:443"
  labels:
    - "traefik.http.routers.keycloak.rule=Host(`totocalcio.tuodominio.com`)"
    - "traefik.http.routers.keycloak.tls.certresolver=letsencrypt"
```

Opzione B — **Nginx + Certbot**:
```nginx
server {
    listen 443 ssl;
    server_name totocalcio.tuodominio.com;

    ssl_certificate /etc/letsencrypt/live/totocalcio.tuodominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/totocalcio.tuodominio.com/privkey.pem;

    location / {
        proxy_pass http://keycloak:8080;
        proxy_set_header Host $host;
    }
}
```

---

## 7. Aggiungere altri provider (Microsoft, Facebook, etc.)

Stessa procedura di Google ma con:

| Provider | `providerId` | Endpoint redirect |
|---|---|---|
| Google | `google` | `/realms/totosport/broker/google/endpoint` |
| Microsoft | `microsoft` | `/realms/totosport/broker/microsoft/endpoint` |
| Facebook | `facebook` | `/realms/totosport/broker/facebook/endpoint` |
| GitHub | `github` | `/realms/totosport/broker/github/endpoint` |

---

## 8. Troubleshooting

| Problema | Causa | Soluzione |
|---|---|---|
| "Invalid redirect URI" | Google Cloud Console redirect URI non corrisponde a Keycloak | Aggiornare URI in Google Console |
| "Error 500 after Google login" | Client ID/Secret errati | Ricontrollare credenziali in Keycloak |
| Utente non creato in OSM | Email non presente nel JWT | Verificare scope `email` in Keycloak |
| Keycloak non raggiungibile | Docker container non avviato | `docker compose up -d keycloak` |
| Google blocca login | App in testing mode | Aggiungere utenti in Google Cloud Console > Consent Screen > Test users |
