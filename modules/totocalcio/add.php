<?php

include_once __DIR__.'/../../core.php';

// Blocca l'accesso per utenti non admin
$is_admin = !empty($user) && $user['idgruppo'] == 1;
if (!$is_admin) {
    echo '<div class="alert alert-danger">'.tr('Accesso negato').'</div>';
    return;
}
?>
<form action="" method="post">
    <input type="hidden" name="op" value="add">
    <div class="card card-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "Nome", "name": "nome_add", "required": 1 ]}
                </div>
                <div class="col-md-6">
                    {[ "type": "text", "label": "Email", "name": "email_add" ]}
                </div>
            </div>
        </div>
    </div>
</form>
