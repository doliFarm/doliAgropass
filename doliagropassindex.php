<?php
/* Copyright (C) 2024      Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026      DoliFarm Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       doliagropass/index.php
 * \ingroup    doliagropass
 * \brief      Home page of doliagropass top menu
 */

// 1. CARICAMENTO AMBIENTE DOLIBARR
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// 2. INCLUSIONE CLASSI NECESSARIE
// Include il widget (usa @ per silenziare warning se il file non esiste ancora in fase di dev)
@include_once DOL_DOCUMENT_ROOT.'/custom/doliagropass/core/boxes/doliagropasswidgetlastaudits.php';
// Carica la classe Audit
dol_include_once("/doliagropass/class/doliagropassaudit.class.php");

// Carica traduzioni
$langs->loadLangs(array("doliagropass@doliagropass", "companies"));

$action = GETPOST('action', 'aZ09');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

/// -----------------------------------------------------------------------------
// CONTROLLO PERMESSI (ROBUSTO)
// -----------------------------------------------------------------------------
// Verifica se l'utente ha il permesso specifico 'read_audit' del modulo 'doliagropass'
if (empty($user->rights->doliagropass->audit->read)) {
    accessforbidden();
}
// -----------------------------------------------------------------------------

/*
 * VIEW (Interfaccia)
 */

$form = new Form($db);
// Istanziamo la classe solo se esiste, per evitare fatal error se il modulo è incompleto
if (class_exists('DoliAgroPassAudit')) {
    $doliagropassauditstatic = new DoliAgroPassAudit($db);
} else {
    // Fallback dummy se la classe non è caricata
    $doliagropassauditstatic = new stdClass(); 
    $doliagropassauditstatic->id = 0;
}


llxHeader("", $langs->trans("DoliagropassArea"), '', '', 0, 0, '', '', '', 'mod-doliagropass page-index');
dol_include_once('/dolifarm/lib/dolifarm_icons.lib.php');
print load_fiche_titre($langs->trans("DoliagropassArea"), '', dolifarm_get_fa_icon('doliagropass'));



// ============================================================================
// CONFIGURAZIONE WIDGET (MODIFICA QUI I NOMI DELLE CLASSI)
// ============================================================================
// Verifica aprendo i file in /core/boxes/ come si chiamano esattamente le classi.
// Esempio: "class BoxTotPlots extends ModeleBoxes" -> Il nome è "BoxTotPlots"
// ============================================================================

$widgets_to_load = array(
    array(
        'file'  => 'doliagropasswidgetlowscore.php',      // Nome del file fisico
        'class' => 'doliagropassWidgetCertificatesExpiry'           // Nome della CLASS all'interno del file
    ),
    array(
        'file'  => 'doliagropasswidgetlastaudits.php',
        'class' => 'doliagropassWidgetLastAudits'              // Verifica se è 'TotPlots', 'BoxTotPlots' o 'dolifarm_TotPlots'
    ),
);

print '<div class="fichecenter">';

// ============================================================================
// GENERAZIONE AUTOMATICA WIDGET
// ============================================================================

// Contatore per gestire le colonne (sinistra/destra)
$i = 0;
$total = count($widgets_to_load);
$half = ceil($total / 2);

print '<div class="fichehalfleft">';

foreach ($widgets_to_load as $index => $widget_info) {
    
    // Cambio colonna a metà
    if ($index == $half) {
        print '</div><div class="fichehalfright">';
    }

    $file_path = DOL_DOCUMENT_ROOT . '/custom/doliagropass/core/boxes/' . $widget_info['file'];
    $class_name = $widget_info['class'];

    print '<div class="box-wrapper">';
    
    // 1. Controllo esistenza file
    if (file_exists($file_path)) {
        include_once $file_path;

        // 2. Controllo esistenza classe (Evita Fatal Error)
        if (class_exists($class_name)) {
            try {
                $box = new $class_name($db, '');
                $box->loadBox(5);
                $box->showBox();
            } catch (Exception $e) {
                print '<div class="box-error">Errore nel widget <strong>'.$class_name.'</strong>: '.$e->getMessage().'</div>';
            }
        } else {
            print '<div class="box-error">Errore: La classe <strong>'.$class_name.'</strong> non è stata trovata nel file <em>'.$widget_info['file'].'</em>.<br>Apri il file e controlla la riga "class Xyz extends...".</div>';
        }
    } else {
        print '<div class="box-error">Errore: File non trovato: <em>'.$widget_info['file'].'</em></div>';
    }

    print '</div>'; // Fine box-wrapper
}

print '</div>'; // Fine colonna destra
print '</div>'; // Fine fichecenter


// End of page
llxFooter();
$db->close();
?>