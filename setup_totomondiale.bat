@echo off
chcp 65001 >nul
echo ============================================
echo  Setup TotoMondiale - PC Sempre Accesso
echo ============================================
echo.

:: 1. Verifica Docker
where docker >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [ERRORE] Docker non trovato. Installa Docker Desktop da:
    echo https://www.docker.com/products/docker-desktop/
    pause
    exit /b 1
)
echo [OK] Docker trovato

:: 2. Porta il progetto nella cartella corrente
set PROJECT_DIR=%CD%\opensta-starter
if not exist "%PROJECT_DIR%" (
    echo [ERRORE] Cartella opensta-starter non trovata in %CD%
    echo Copia prima la cartella del progetto qui.
    pause
    exit /b 1
)
echo [OK] Progetto trovato

:: 3. Avvia i container
cd /d "%PROJECT_DIR%"
echo [INFO] Avvio container Docker...
docker compose up -d
if %ERRORLEVEL% neq 0 (
    echo [ERRORE] Impossibile avviare Docker. Verifica che Docker Desktop sia in esecuzione.
    pause
    exit /b 1
)
echo [OK] Container avviati
timeout /t 5 /nobreak >nul

:: 4. Importa dati
echo [INFO] Importazione dati nel database...
docker exec -i opensta-starter_db mysql -uroot -psecret openstamanager < totomondiale_data.sql 2>nul
echo [OK] Dati importati

:: 5. Copia logo nel container
echo [INFO] Copia logo...
docker cp assets\dist\img\logo_mondiali.webp opensta-starter_web:/var/www/html/assets/dist/img/logo_mondiali.webp 2>nul
docker exec opensta-starter_web sh -c "cp /var/www/html/assets/dist/img/logo_mondiali.webp /var/www/html/assets/dist/img/logo.png && cp /var/www/html/assets/dist/img/logo_mondiali.webp /var/www/html/assets/dist/img/logo_completo.png && cp /var/www/html/assets/dist/img/logo_mondiali.webp /var/www/html/assets/dist/img/logo_header.png && cp /var/www/html/assets/dist/img/logo_mondiali.webp /var/www/html/assets/dist/img/favicon.png" 2>nul
echo [OK] Logo copiato

:: 6. Avvia ngrok
echo.
echo ============================================
echo  TUNNEL NGROK
echo ============================================
echo.
echo Per esporre la classifica online, scarica ngrok da:
echo https://ngrok.com/download
echo.
echo Poi esegui in un terminale SEPARATO:
echo   ngrok http 8080
echo.
echo L'URL pubblico sara' mostrato nel terminale ngrok.
echo Condividi: https://TUO-URL.ngrok-free.app/classifica
echo.
echo ============================================
echo  TUTTO PRONTO!
echo ============================================
echo.
echo Accesso locale:
echo   http://localhost:8080
echo   http://localhost:8080/classifica
echo.
echo Admin OSM:
echo   user: admin / password: admin
echo   (o le credenziali del tuo utente)
echo.
pause
