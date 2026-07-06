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

include_once __DIR__.'/core.php';

$pageTitle = tr('Informazioni');

$paths = App::getPaths();

include_once App::filepath('include|custom|', 'top.php');

echo '
<div class="card">
    <div class="card-header">
        <img src="'.App::getPaths()['img'].'/logo_totosport.png" class="pull-left img-responsive" width="300" alt="'.tr('TotoSport Logo').'">
        <div class="float-right d-none d-sm-inline">
            <i class="fa fa-info"></i> '.tr('Informazioni').'
        </div>
    </div>

    <div class="card-body">';

if (file_exists(base_dir().'/assistenza.php')) {
    include base_dir().'/assistenza.php';
} else {
    echo '
        <div class="row">
            <div class="col-md-8">
                <p>'.tr('<b>TotoSport</b> è un\'applicazione per la <b>gestione di pronostici calcistici</b> dedicata al Totocalcio per Serie A.').'</p>

                <p>'.tr('L\'applicazione permette di gestire leghe di pronostici tra partecipanti, con calendario delle partite, inserimento pronostici, calcolo automatico dei punti e classifiche in tempo reale.').'</p>
            </div>

            <div class="col-md-4">
                <p><b>'.tr('Versione').':</b> '.$version.' <small class="text-secondary">('.(!empty($revision) ? 'R'.$revision : tr('In sviluppo')).')</small></p>

                <p><b>'.tr('Licenza').':</b> <a href="https://www.gnu.org/licenses/gpl-3.0.txt" target="_blank" title="'.tr('Vai al sito per leggere la licenza').'">GPLv3</a></p>

                <p><b>'.tr('Stagione').':</b> 2026/27</p>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title text-uppercase"><i class="fa fa-globe"></i> '.tr('Perchè software libero').'</h3>
                    </div>

                    <div class="card-body">
                        <p>'.tr("TotoSport è un progetto software libero che permette a tutti di conoscere come funziona avendo il codice sorgente del programma e fornisce così la possibilità di studiarlo, modificarlo e adattarlo alle proprie esigenze").'.</p>

                        <p>'.tr("E' importante sapere come funziona per conoscere come vengono trattati i VOSTRI dati, proteggendo così la vostra <b>privacy</b>").'.</p>

                        <p>'.tr('TotoSport è stato sviluppato utilizzando software libero, tra cui').':</p>
                        <ul>
                            <li><a href="https://www.php.net" target="_blank"><i class="fa fa-circle-o-notch"></i> PHP</a></li>
                            <li><a href="https://www.mysql.com" target="_blank"><i class="fa fa-circle-o-notch"></i> MySQL</a></li>
                            <li><a href="https://jquery.com" target="_blank"><i class="fa fa-circle-o-notch"></i> JQuery</a></li>
                            <li><a href="https://getbootstrap.com" target="_blank"><i class="fa fa-circle-o-notch"></i> Bootstrap</a></li>
                            <li><a href="https://fortawesome.com/" target="_blank"><i class="fa fa-circle-o-notch"></i> FontAwesome</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title text-uppercase"><i class="fa fa-download"></i> '.tr('Aggiornamenti e nuove versioni').'</h3>
                    </div>

                    <div class="card-body">
                        <p>'.tr('TotoSport include moduli per la gestione completa dei pronostici').':</p>
                        <ul>
                            <li><i class="fa fa-calendar"></i> '.tr('Calendario Serie A con sincronizzazione automatica').'</li>
                            <li><i class="fa fa-users"></i> '.tr('Gestione partecipanti e pronostici').'</li>
                            <li><i class="fa fa-trophy"></i> '.tr('Classifiche in tempo reale').'</li>
                            <li><i class="fa fa-futbol-o"></i> '.tr('Sincronizzazione risultati partite').'</li>
                        </ul>
                    </div>
                </div>

                <div class="card card-default">
                    <div class="card-header">
                        <h3 class="card-title text-uppercase"><i class="fa fa-book"></i> '.tr('Guida e documentazione tecnica').'</h3>
                    </div>

                    <div class="card-body">
                        <p>'.tr("Le funzionalità principali di <strong>TotoSport</strong> includono").':</p>
                        <ul>
                            <li><i class="fa fa-check"></i> '.tr('Gestione 38 giornate di campionato').'</li>
                            <li><i class="fa fa-check"></i> '.tr('Sistema di pannelli e pronostici').'</li>
                            <li><i class="fa fa-check"></i> '.tr('Calcolo automatico punti e vincite').'</li>
                            <li><i class="fa fa-check"></i> '.tr('Classifiche generali e periodiche').'</li>
                            <li><i class="fa fa-check"></i> '.tr('Sincronizzazione con API esterne per risultati').'</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title text-uppercase"><i class="fa fa-group"></i> '.tr('Community').'</h3>
                    </div>

                    <div class="card-body">
                        <p>'.tr('TotoSport utilizza API esterne per fornire dati aggiornati').':</p>
                        <div class="well">
                            <div class="row">
                                <div class="col-xs-4 text-center">
                                    <a href="https://www.fotmob.com" target="_blank">
                                        <i class="fa fa-2x fa-futbol-o"></i><br>
                                        '.tr('Fotmob').'
                                    </a>
                                </div>
                                <div class="col-xs-4 text-center">
                                    <a href="https://www.thesportsdb.com" target="_blank">
                                        <i class="fa fa-2x fa-database"></i><br>
                                        '.tr('TheSportsDB').'
                                    </a>
                                </div>
                                <div class="col-xs-4 text-center">
                                    <a href="https://github.com/openfootball" target="_blank">
                                        <i class="fa fa-2x fa-github"></i><br>
                                        '.tr('Football.json').'
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title text-uppercase"><i class="fa fa-code"></i> '.tr('Sviluppo').'</h3>
                    </div>

                    <div class="card-body">
                        <p>'.tr('TotoSport è un progetto in continua evoluzione, sviluppato per fornire un\'esperienza completa di gestione pronostici').'.</p>

                        <p>'.tr('L\'applicazione è basata su architettura modulare e può essere personalizzata ed estesa secondo le proprie esigenze').'.</p>

                        <p><b>'.tr('Tecnologia').':</b> PHP 8.3, MySQL 8.3, Bootstrap 5.3, Docker</p>
                    </div>
                </div>
            </div>
        </div>';
}

echo '

	</div>
</div>';

include_once App::filepath('include|custom|', 'bottom.php');
