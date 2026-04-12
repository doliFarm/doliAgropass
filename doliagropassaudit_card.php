<?php
/* Copyright (C) 2017      Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025 Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026      SuperAdmin
 */

/**
 * \file       doliagropassaudit_card.php
 * \ingroup    doliagropass
 * \brief      Page to create/edit/view doliagropassaudit
 */

// Load Dolibarr environment
$res = 0;
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"]) . "/main.inc.php";
if (! $res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

// LOAD MODULE CLASSES
dol_include_once('/doliagropass/class/doliagropassaudit.class.php');
dol_include_once('/doliagropass/class/doliagropassindicator.class.php'); 
dol_include_once('/doliagropass/lib/doliagropass_audit.lib.php');
// [INTEGRO] Caricamento Calculator per aggiornamento live
require_once DOL_DOCUMENT_ROOT . '/custom/doliagropass/class/AgroScoreCalculator.class.php';

$langs->loadLangs(array("doliagropass@doliagropass", "other"));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$lineid = GETPOSTINT('lineid');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : getDolDefaultContextPage(__FILE__); 
$backtopage = GETPOST('backtopage', 'alpha'); 
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha'); 
$optioncss = GETPOST('optioncss', 'aZ'); 
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

$object = new DoliAgroPassAudit($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->doliagropass->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('doliagropassauditcard', 'globalcard'));

$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$search = array();
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_'.$key, 'alpha')) {
        $search[$key] = GETPOST('search_'.$key, 'alpha');
    }
}

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 

$enablepermissioncheck = getDolGlobalInt('DOLIAGROPASS_ENABLE_PERMISSION_CHECK');
if ($enablepermissioncheck) {
    $permissiontoread = $user->rights->doliagropass->audit->read;
    $permissiontoadd = $user->rights->doliagropass->audit->write; 
    $permissiontodelete = $user->rights->doliagropass->audit->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
} else {
    $permissiontoread = 1;
    $permissiontoadd = 1; 
    $permissiontodelete = 1;
}

$upload_dir = $conf->doliagropass->multidir_output[isset($object->entity) ? $object->entity : 1].'/doliagropassaudit';

if ($user->socid > 0) accessforbidden();
if (!isModEnabled($object->module)) accessforbidden("Module ".$object->module." not enabled");
if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); 
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $backurlforlist = dol_buildpath('/doliagropass/doliagropassaudit_list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/doliagropass/doliagropassaudit_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
            }
        }
    }

    $triggermodname = 'DOLIAGROPASS_AUDIT_MODIFY'; 

    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

    // -------------------------------------------------------------------------
    // CUSTOM ACTION: ADD LINE
    // -------------------------------------------------------------------------
    if ($action == 'addline' && $permissiontoadd) {
        $fk_indicator = GETPOST('fk_indicator', 'int');
        $value_measured = GETPOST('value_measured', 'alpha');
        $score_calc = GETPOST('score_calc', 'int'); 
        $score_override = GETPOST('score_override', 'int'); 
        $note_auditor = GETPOST('note_auditor', 'alpha');

        if ($fk_indicator > 0) {
            $line = new DoliAgroPassAuditLine($db);
            $line->fk_audit = $object->id;
            $line->fk_indicator = $fk_indicator;
            $line->value_measured = $value_measured;
            $line->score_calc = $score_calc;
            $line->score_override = $score_override;
            $line->note_auditor = $note_auditor;
            $line->value_source = 'MANUAL';

            if ($line->create($user) > 0) {
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
                
                // [INTEGRO] Ricalcolo Punteggio Immediato
                $calculator = new AgroScoreCalculator($db);
                $calculator->calculateGlobalScore($object->id);
                
                $action = 'view';
                $object->fetch($id); // Ricarica oggetto con nuovo punteggio
            } else {
                setEventMessages($line->error, $line->errors, 'errors');
            }
        } else {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Indicator")), null, 'errors');
        }
    }

    // -------------------------------------------------------------------------
    // CUSTOM ACTION: UPDATE LINE
    // -------------------------------------------------------------------------
    if ($action == 'updateline' && $permissiontoadd && $lineid > 0) {
        $line = new DoliAgroPassAuditLine($db);
        if ($line->fetch($lineid) > 0) {
            $line->value_measured = GETPOST('value_measured', 'alpha');
            $line->score_calc = GETPOST('score_calc', 'int');
            $line->score_override = GETPOST('score_override', 'int'); 
            $line->note_auditor = GETPOST('note_auditor', 'alpha');
            
            if ($line->update($user) > 0) {
                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
                
                // [INTEGRO] Ricalcolo Punteggio Immediato
                $calculator = new AgroScoreCalculator($db);
                $calculator->calculateGlobalScore($object->id);

                $action = 'view';
                $object->fetch($id); // Ricarica oggetto con nuovo punteggio
            } else {
                setEventMessages($line->error, $line->errors, 'errors');
            }
        }
    }

    // -------------------------------------------------------------------------
    // CUSTOM ACTION: DELETE LINE
    // -------------------------------------------------------------------------
    if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoadd) {
        $line = new DoliAgroPassAuditLine($db);
        if ($line->fetch($lineid) > 0) {
            if ($line->delete($user) > 0) {
                setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
                
                // [INTEGRO] Ricalcolo Punteggio Immediato
                $calculator = new AgroScoreCalculator($db);
                $calculator->calculateGlobalScore($object->id);

                $action = 'view';
                $object->fetch($id);
            } else {
                setEventMessages($line->error, $line->errors, 'errors');
            }
        }
    }

    $triggersendname = 'DOLIAGROPASS_AUDIT_SENTBYMAIL';
    $autocopy = 'MAIN_MAIL_AUTOCOPY_AUDIT_TO';
    $trackid = 'doliagropassaudit'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("DoliAgroPassAudit")." - ".$langs->trans('Card');
if ($action == 'create') {
    $title = $langs->trans("NewObject", $langs->transnoentitiesnoconv("DoliAgroPassAudit"));
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-doliagropass page-card');

// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden('NotEnoughPermissions', 0, 1);
    }

    print load_fiche_titre($title, '', 'object_' . $object->picto);

    print '<form method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

    print dol_get_fiche_head(array(), '');
    print '<table class="border centpercent tableforfieldcreate">'."\n";

    print '<tr><td class="titlefield create">'.$langs->trans("Ref").'</td>';
    print '<td><span class="badge badge-status4 badge-status">'.$langs->trans("Draft").'</span></td></tr>';
    unset($object->fields['ref']);

    print '<tr><td class="titlefield create">'.$langs->trans("Season").'</td>';
    print '<td>';
    $currentYear = date('Y');
    $years = array();
    for($y = $currentYear - 2; $y <= $currentYear + 2; $y++) { $years[$y] = $y; }
    print $form->selectarray('season', $years, $currentYear);
    print '</td></tr>';
    unset($object->fields['season']);

    include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

    print '</table>'."\n";
    print dol_get_fiche_end();
    print $form->buttonsSaveCancel("Create");
    print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans("DoliAgroPassAudit"), '', 'object_' . $object->picto);

    print '<form method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';
    if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

    print dol_get_fiche_head();
    print '<table class="border centpercent tableforfieldedit">'."\n";
    include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';
    print '</table>';
    print dol_get_fiche_end();
    print $form->buttonsSaveCancel();
    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $head = doliagropassauditPrepareHead($object);

    print dol_get_fiche_head($head, 'card', $langs->trans("DoliAgroPassAudit"), -1, $object->picto, 0, '', '', 0, '', 1);

    $formconfirm = '';

    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteDoliAgroPassAudit'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
    }
    if ($action == 'deleteline') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid.'&token='.newToken().'#lines', $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
    }
    if ($action == 'clone') {
        $formquestion = array();
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
    }

    print $formconfirm;

    $linkback = '<a href="'.dol_buildpath('/doliagropass/doliagropassaudit_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= $langs->trans("Label") . ': ' . $object->label;
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">'."\n";
    include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
    print '</table>';
    print '</div>';
     // [INTEGRO] QR CODE BLOCK
        // Inserito in alto a destra, sopra l'agenda
        $qrcode_lib = DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
        if (file_exists($qrcode_lib)) {
            print '<div class="fichehalfright">';
            // Verifichiamo che l'UUID esista (dovrebbe sempre esserci se salvato)
            if (!empty($object->uuid)) {
                // 1. Contenuto del QR
                $qr_content = dol_buildpath('/doliagropass/doliagropassaudit_card.php', 2) . '?id=' . $object->id;
                
                require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
                // 3. GENERAZIONE DEL CODICE
                $qr_image_data = '';
                if (class_exists('TCPDF2DBarcode')) {
                    // Generazione Immagine in Base64
                    $barcodeobj = new TCPDF2DBarcode($qr_content, 'QRCODE,H');
                    $pngData = $barcodeobj->getBarcodePngData(3, 3, array(0,0,0)); // W=4, H=4
                    $qr_image_data = 'data:image/png;base64,' . base64_encode($pngData);
                }

                // 4. DISPLAY
                print '<div class="box-flex-container" >';
                
                // Visualizzazione UUID
                    print '<div style="margin: 10px 0; font-family:monospace; color:#666;">';
                    print '<span class="fa fa-fingerprint"></span> <b>'.$langs->trans("DigitalPassport").':</b><br>';
                    print $object->uuid;
                    // Immagine QR Code
                    print '<div style="margin: 10px auto;">';
                    if ($qr_image_data) {
                        print '<img src="' . $qr_image_data . '" alt="QR Code" style="border: 1px solid #eee; padding: 5px; background: white;">';
                    } else {
                        // Messaggio di debug utile se fallisce ancora
                        print '<div class="error" style="font-size:0.8em; color:red;">';
                        print 'Errore: Libreria Barcode non trovata.<br>Percorsi controllati:<br>';
                        foreach($tcpdf_paths as $p) print basename(dirname($p)).'/'.basename($p)."<br>";
                        print '</div>';
                    }
                    print '</div>';

                print '</div>'; // Chiusura box container

            } else {
                print '<div class="opacitymedium" style="text-align:center; padding:20px;">';
                print $langs->trans("SaveToGeneratePassport");
                print '</div>';
            }
            print '</div>';
        }
    print '</div>';
    print '<div class="clearboth"></div>';
    print dol_get_fiche_end();


    /*
     * Lines (Audit Details / Questions)
     */
    if (!empty($object->table_element_line)) {
        
        print '<br><a name="lines"></a>';

        $object->fetchLines();
        $existing_indicators = array();
        $current_group = '';
        
        $action_line = ($action != 'editline') ? 'addline' : 'updateline';
        $form_anchor = '#lines';
        if ($action == 'editline') $form_anchor = '#line_'.GETPOSTINT('lineid');

        print ' <form name="addline" id="addline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.$form_anchor.'" method="POST">
        <input type="hidden" name="token" value="' . newToken().'">
        <input type="hidden" name="action" value="' . $action_line . '">
        <input type="hidden" name="mode" value="">
        <input type="hidden" name="id" value="' . $object->id.'">
        ';

        print '<div class="div-table-responsive-no-min">';
        print '<table id="tablelines" class="noborder noshadow" width="100%">';

        // HEADER
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Indicator").'</td>';
        print '<td class="center" style="width: 80px;">'.$langs->trans("InputType").'</td>';
        print '<td class="center" style="width: 80px;">'.$langs->trans("CalculationMethod").'</td>';
        print '<td>'.$langs->trans("ValueMeasured").'</td>'; // Etichetta tradotta
        print '<td class="right">'.$langs->trans("ScoreCalc").'</td>';
        print '<td class="right">'.$langs->trans("ScoreOverride").'</td>';
        print '<td>'.$langs->trans("Note").'</td>';
        print '<td></td>'; 
        print '</tr>';

        // LOOP
        if (!empty($object->lines)) {
            foreach ($object->lines as $line) {
                $existing_indicators[] = $line->fk_indicator;

                // Group Header
                if ($line->category_group != $current_group) {
                    $current_group = $line->category_group;
                    print '<tr class="liste_titre"><td colspan="8" style="background-color: #f0f0f0; color: #333; font-weight: bold; border-top: 2px solid #ddd;">';
                    print $langs->trans($current_group);
                    print '</td></tr>';
                }

                $ind_label = (string)$line->label;
                $ind_code = (string)$line->code;
                
                // Icons
                $calc_icon = '';
                if (strpos($line->calculation_method, 'AUTO') === 0) {
                    $calc_icon = '<span class="fa fa-magic text-info classfortooltip" title="'.$langs->trans("CalcMethodAuto").'"></span>';
                } else {
                    $calc_icon = '<span class="fa fa-user-edit text-warning classfortooltip" title="'.$langs->trans("CalcMethodManual").'"></span>';
                }

                $input_icon = '';
                if (strpos($line->input_type, 'SELECT') !== false || strpos($line->input_type, 'SCALE') !== false) {
                    $input_icon = '<span class="fa fa-list-ol text-primary classfortooltip" title="'.$langs->trans("InputTypeSelect").'"></span>';
                } elseif ($line->input_type == 'BOOLEAN') {
                    $input_icon = '<span class="fa fa-toggle-on classfortooltip" title="'.$langs->trans("InputTypeBoolean").'"></span>';
                } elseif ($line->input_type == 'NUMBER') {
                    $input_icon = '<span class="fa fa-hashtag classfortooltip" title="'.$langs->trans("InputTypeNumber").'"></span>';
                } else {
                    $input_icon = $line->input_type;
                }
                
                // [INTEGRO] PARSING JSON PARAMETERS & TRANSLATION
                // Prepara le opzioni per la select (se servono) e per la visualizzazione tradotta
                $input_params = array();
                if (!empty($line->input_params)) {
                    $raw_params = json_decode($line->input_params, true);
                    if (is_array($raw_params)) {
                        foreach ($raw_params as $keyParam => $valParam) {
                            $input_params[$keyParam] = $langs->trans($valParam); // Traduzione chiave
                        }
                    }
                }

                // --- EDIT MODE ---
                if ($action == 'editline' && GETPOSTINT('lineid') == $line->rowid) { 
                    print '<tr class="oddeven" id="line_'.$line->rowid.'">';
                    print '<td>';
                    print '<a name="line_'.$line->rowid.'"></a>'; 
                    print '<input type="hidden" name="lineid" value="'.$line->rowid.'">';
                    print $ind_code . ' - ' . $langs->trans($ind_label);
                    print '</td>';
                    
                    print '<td class="center">'.$input_icon.'</td>';
                    print '<td class="center">'.$calc_icon.'</td>';
                    
                    // [INTEGRO] DYNAMIC INPUT LOGIC with JSON TRANSLATED PARAMS
                    print '<td>';
                    if ($line->input_type == 'BOOLEAN') {
                        print $form->selectyesno('value_measured', (isset($line->value_measured) ? $line->value_measured : -1), 1);
                    } elseif ($line->input_type == 'NUMBER') {
                        print '<input type="number" class="flat" name="value_measured" step="any" value="'.dol_escape_htmltag((string)$line->value_measured).'">';
                    } elseif ($line->input_type == 'SELECT' || strpos($line->input_type, 'SCALE') !== false) {
                        if (!empty($input_params)) {
                            print $form->selectarray('value_measured', $input_params, $line->value_measured, 1);
                        } else {
                            print '<input type="text" class="flat" name="value_measured" value="'.dol_escape_htmltag((string)$line->value_measured).'">';
                        }
                    } else {
                        print '<input type="text" class="flat" name="value_measured" value="'.dol_escape_htmltag((string)$line->value_measured).'">';
                    }
                    print '</td>';
                    
                    print '<td class="right">'.price((float)$line->score_calc).' <input type="hidden" name="score_calc" value="'.((float)$line->score_calc).'"></td>';
                    print '<td class="right"><input type="number" class="flat" name="score_override" step="0.01" style="width: 60px;" value="'.((float)$line->score_override).'"></td>';
                    
                    print '<td><input type="text" class="flat centpercent" name="note_auditor" value="'.dol_escape_htmltag((string)$line->note_auditor).'"></td>';
                    
                    print '<td class="center">';
                    print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
                    print '<br>';
                    print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#line_'.$line->rowid.'">'.$langs->trans("Cancel").'</a>';
                    print '</td>';
                    print '</tr>';

                } else {
                    // --- READ MODE ---
                    $urlInd = dol_buildpath('/doliagropass/doliagropassindicator_card.php', 1) . '?id=' . $line->fk_indicator;
                    $linkInd = '<a href="'.$urlInd.'">' . $ind_code . '</a>';

                    print '<tr class="oddeven" id="line_'.$line->rowid.'">';
                    print '<td>';
                    print '<a name="line_'.$line->rowid.'"></a>';
                    print $linkInd;
                    print ' <span class="opacitymedium">- ' . $langs->trans($ind_label) . '</span>';
                    print '</td>';
                    
                    print '<td class="center">'.$input_icon.'</td>';
                    print '<td class="center">'.$calc_icon.'</td>';
                    
                    // [INTEGRO] DISPLAY VALUE TRANSLATED
                    $display_value = dol_escape_htmltag((string)$line->value_measured);
                    
                    if ($line->input_type == 'BOOLEAN') {
                        if ($line->value_measured == '1') $display_value = $langs->trans("Yes");
                        elseif ($line->value_measured == '0') $display_value = $langs->trans("No");
                    } elseif (($line->input_type == 'SELECT' || strpos($line->input_type, 'SCALE') !== false) && !empty($input_params)) {
                        // Use translated label from JSON map
                        if (array_key_exists($line->value_measured, $input_params)) {
                            $display_value = $input_params[$line->value_measured];
                        }
                    }
                    print '<td>'.$display_value.'</td>';
                    
                    print '<td class="right">'.price((float)$line->score_calc).'</td>';
                    
                    $override_display = '';
                    if (!empty($line->score_override) || $line->score_override === '0') {
                        $override_display = '<b>'.price((float)$line->score_override).'</b>';
                    }
                    print '<td class="right">'.$override_display.'</td>';
                    
                    print '<td><span class="opacitymedium">'.dol_escape_htmltag((string)$line->note_auditor).'</span></td>';
                    
                    print '<td class="right">';
                    if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
                        print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&lineid='.$line->rowid.'&token='.newToken().'#line_'.$line->rowid.'">';
                        print img_picto($langs->trans("Modify"), 'edit');
                        print '</a>';
                        print '&nbsp;';
                        print '<a class="marginleftonly" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteline&lineid='.$line->rowid.'&token='.newToken().'#lines">';
                        print img_picto($langs->trans("Delete"), 'delete');
                        print '</a>';
                    }
                    print '</td>';
                    print '</tr>';
                }
            }
        } else {
            print '<tr class="oddeven"><td colspan="8" class="opacitymedium">'.$langs->trans("NoLinesFound").'</td></tr>';
        }

        // ADD NEW LINE FORM (Simplified)
        if ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'editline') {
            print '<tr class="liste_titre_add">';
            
            print '<td>';
            $sql = "SELECT rowid, label, code, category_group FROM ".MAIN_DB_PREFIX."doliagropass_indicator WHERE active=1";
            if (!empty($existing_indicators)) {
                $sql .= " AND rowid NOT IN (".implode(',', $existing_indicators).")";
            }
            $sql .= " ORDER BY category_group ASC, code ASC";
            
            $res = $db->query($sql);
            print '<select class="flat minwidth200" name="fk_indicator">';
            print '<option value="-1">&nbsp;</option>';
            if ($res) {
                $last_group_opt = '';
                while($obj_ind = $db->fetch_object($res)) {
                    if ($obj_ind->category_group != $last_group_opt) {
                        if ($last_group_opt != '') print '</optgroup>';
                        print '<optgroup label="'.$langs->trans($obj_ind->category_group).'">';
                        $last_group_opt = $obj_ind->category_group;
                    }
                    print '<option value="'.$obj_ind->rowid.'">'.$obj_ind->code.' - '.$langs->trans((string)$obj_ind->label).'</option>';
                }
                if ($last_group_opt != '') print '</optgroup>';
            }
            print '</select>';
            print '</td>';

            print '<td></td><td></td>'; 

            print '<td><input type="text" class="flat" name="value_measured" placeholder="'.$langs->trans("Value").'"></td>';
            print '<td class="right"><input type="number" class="flat" name="score_calc" step="0.01" style="width: 60px;" placeholder="0.00"></td>';
            print '<td class="right"><input type="number" class="flat" name="score_override" step="0.01" style="width: 60px;" placeholder="0.00"></td>';
            print '<td><input type="text" class="flat centpercent" name="note_auditor" placeholder="'.$langs->trans("Note").'"></td>';

            print '<td class="right">';
            print '<input type="submit" class="button" name="addline_submit" value="'.$langs->trans("Add").'">';
            print '</td>';
            
            print '</tr>';
        }

        print '</table>';
        print '</div>';

        print "</form>\n";
    }

    if ($action != 'presend' && $action != 'editline') {
        print '<div class="tabsAction">'."\n";
        $parameters = array();
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); 
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            if (empty($user->socid)) {
                print dolGetButtonAction('', $langs->trans('SendMail'), 'email', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
            }

            if ($object->status == $object::STATUS_VALIDATED) {
                print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
            }

            if ($object->status == $object::STATUS_DRAFT) {
                print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
            }

            if ($object->status == $object::STATUS_DRAFT) {
                if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
                    print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
                } else {
                    $langs->load("errors");
                    print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans('Validate'), 'default', '#', '', 0);
                }
            }

            if ($permissiontoadd) {
                print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);
            }

            $deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
            $buttonId = 'action-delete-no-ajax';
            if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) { 
                $deleteUrl = '';
                $buttonId = 'action-delete';
            }
            $params = array();
            print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, $params);
        }
        print '</div>'."\n";
    }

    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    if ($action != 'presend') {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<a name="builddoc"></a>'; 

        $includedocgeneration = 1;

        if ($includedocgeneration) {
            $objref = dol_sanitizeFileName($object->ref);
            $relativepath = $objref.'/'.$objref.'.pdf';
            $filedir = $conf->doliagropass->dir_output.'/'.$object->element.'/'.$objref;
            $urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
            $genallowed = $permissiontoread; 
            $delallowed = $permissiontoadd; 
            print $formfile->showdocuments('doliagropass:DoliAgroPassAudit', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
        }

        $tmparray = $form->showLinkToObjectBlock($object, array(), array('doliagropassaudit'), 1);
        if (is_array($tmparray)) {
            $linktoelem = $tmparray['linktoelem'];
            $htmltoenteralink = $tmparray['htmltoenteralink'];
            print $htmltoenteralink;
            $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
        } else {
            $somethingshown = $form->showLinkedObjectBlock($object, $tmparray);
        }
        print '</div>';

        $MAXEVENT = 10;
        $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/doliagropass/doliagropassaudit_agenda.php', 1).'?id='.$object->id);

        $includeeventlist = 1; 

        if ($includeeventlist) {
            include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
            $formactions = new FormActions($db);
            $somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
        }

        print '</div></div>';
    }

    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    $modelmail = 'doliagropassaudit';
    $defaulttopic = 'InformationMessage';
    $diroutput = $conf->doliagropass->dir_output;
    $trackid = 'doliagropassaudit'.$object->id;

    include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

llxFooter();
$db->close();
?>