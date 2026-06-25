<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Models\OAuth2;

$skip_permissions = true;
include_once __DIR__.'/core.php';

$state = get('state');
$code = get('code');

error_log("[OAuth2] Callback received - state: " . substr($state ?? '', 0, 16) . "... code: " . substr($code ?? '', 0, 16) . "... session_id: " . (session_id() ?: 'NONE') . " oauth2_id: " . ($_SESSION['oauth2_id'] ?? 'NONE'));

try {
    // Trova account: prima per stato/sessione, poi per ID
    if (!empty($state)) {
        $account = !empty($_SESSION['oauth2_id']) ? OAuth2::find($_SESSION['oauth2_id']) : null;
        // Fallback: cerca per nome se la sessione è persa
        if (empty($account)) {
            $account = OAuth2::where('name', 'Keycloak')->where('enabled', 1)->first();
        }
    } else {
        $account = OAuth2::find(get('id'));
        if (!empty($account)) {
            $account->access_token = null;
            $account->refresh_token = null;
            $account->save();
            $_SESSION['oauth2_id'] = $account->id;
        }
    }

    if (empty($account)) {
        error_log("[OAuth2] Account not found!");
        $_SESSION['login_error'] = tr('Account OAuth2 non trovato. Contatta l\'amministratore.');
        redirect_url(base_path_osm().'/index.php');
        exit;
    }

    error_log("[OAuth2] Account found: " . $account->id . " (" . $account->name . ") class=" . $account->class . " state=" . ($account->state ?: 'NULL'));

    $response = $account->configure($code, $state);

    if (empty($response['authorization_url'])) {
        $redirect = $account->after_configuration;
    } else {
        $redirect = $response['authorization_url'];
    }

    if (empty($_GET['error'])) {
        if (!empty($response['access_token'])) {
            // Estrai email e username direttamente dal token JWT
            $tokenObj = $response['access_token'];
            $tokenStr = $tokenObj->getToken();
            $jwtParts = explode('.', $tokenStr);
            $payload = json_decode(base64_decode($jwtParts[1] ?? ''), true);

            $userEmail = $payload['email'] ?? $payload['preferred_username'] ?? null;
            $keycloakUsername = $payload['preferred_username'] ?? ($payload['email'] ?? null);
            $userName = $payload['name'] ?? $keycloakUsername;

            error_log("[OAuth2] JWT payload keys: " . implode(', ', array_keys($payload ?? [])));
            error_log("[OAuth2] Email: $userEmail | Username: $keycloakUsername");

            if (empty($userEmail)) {
                $_SESSION['login_error'] = tr('Email non presente nel token Keycloak');
                redirect_url(base_path_osm().'/index.php');
                exit;
            }

            $existingUser = \Models\User::where('email', $userEmail)->first();
            if (!$existingUser) {
                $existingUser = \Models\User::where('username', $keycloakUsername)->first();
            }

            if (!$existingUser) {
                $gruppoRecord = database()->fetchOne("SELECT id FROM zz_groups WHERE nome = 'Totocalcio Giocatori' LIMIT 1");
                if (!empty($gruppoRecord)) {
                    $gruppo = \Models\Group::find($gruppoRecord['id']);
                    if ($gruppo) {
                        $existingUser = \Models\User::build(
                            $gruppo,
                            $keycloakUsername,
                            $userEmail,
                            bin2hex(random_bytes(16))
                        );
                    }
                }
            }

        if ($existingUser) {
            auth_osm()->attempt($existingUser->username, null, true);

            $partecipante = database()->fetchOne('SELECT id FROM totocalcio_partecipanti WHERE id_utente = '.prepare($existingUser->id));
            if (!$partecipante) {
                $partecipante = database()->fetchOne('SELECT id FROM totocalcio_partecipanti WHERE email = '.prepare($userEmail));
            }
            if (!$partecipante) {
                database()->insert('totocalcio_partecipanti', [
                    'nome' => $userName,
                    'email' => $userEmail,
                    'id_utente' => $existingUser->id,
                ]);
            } elseif (empty($partecipante['id_utente'])) {
                database()->update('totocalcio_partecipanti', ['id_utente' => $existingUser->id], ['id' => $partecipante['id']]);
            }

            // Reindirizza al modulo Mio Totocalcio
            $modId = database()->fetchOne("SELECT id FROM zz_modules WHERE name = 'Mio Totocalcio' LIMIT 1");
            if ($modId) {
                redirect_url(base_path_osm().'/controller.php?id_module='.$modId['id']);
            } else {
                redirect_url(base_path_osm().'/');
            }
            exit;
        } else {
            flash()->error(tr('Impossibile creare l\'utente! Contatta l\'amministratore.'));
            redirect_url(base_path_osm().'/');
            exit;
        }
    } else {
            redirect_url($redirect);
        }
        exit;
    }

    echo strip_tags(get('error')).'<br>'.strip_tags(get('error_description')).'<br><br>
    <a href="'.$redirect.'">'.tr('Riprova').'</a>';
} catch (\Throwable $e) {
    $msg = $e->getMessage();
    $logMsg = "[OAuth2] ERROR: $msg | Code: " . $e->getCode() . " | File: " . $e->getFile() . ":" . $e->getLine();
    $logMsg .= " | Class: " . get_class($e);
    if (method_exists($e, 'getResponseBody')) {
        $body = $e->getResponseBody();
        $logMsg .= " | ResponseBody: " . (is_string($body) ? $body : json_encode($body));
    }
    if ($e instanceof \League\OAuth2\Client\Provider\Exception\IdentityProviderException) {
        $logMsg .= " | Error: " . $e->getError() . " | Description: " . $e->getErrorDescription();
    }
    error_log($logMsg);
    error_log("[OAuth2] Trace: " . $e->getTraceAsString());
    $_SESSION['login_error'] = tr('Errore OAuth2: ' . $msg);
    redirect_url(base_path_osm().'/index.php');
    exit;
}
