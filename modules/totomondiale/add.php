<?php

include_once __DIR__.'/../../core.php';
?>
<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add">
    <div class="row">
        <div class="col-md-6">
            {[ "type": "text", "label": "Nome", "name": "nome_add", "required": 1, "value": "" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "text", "label": "Email", "name": "email_add", "value": "" ]}
        </div>
    </div>

    <div class="modal-footer">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> Aggiungi
            </button>
        </div>
    </div>
</form>
