@echo off
chcp 65001 >nul
title Ngrok - TotoMondiale Tunnel
echo ============================================
echo  Ngrok Tunnel - TotoMondiale 2026
echo ============================================
echo.
echo URL: http://localhost:8080
echo.
echo Per uscire: chiudi questa finestra o premi Ctrl+C
echo.

:loop
echo [%date% %time%] Avvio tunnel...
C:\Users\%USERNAME%\AppData\Local\ngrok\ngrok.exe http 8080 --log=stdout
echo [%date% %time%] Tunnel chiuso. Riavvio tra 5 secondi...
timeout /t 5 /nobreak >nul
goto loop
