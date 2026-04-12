<?php
/* Copyright (C) 2017      Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025 Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026      SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       doliagropassindicator_card.php
 * \ingroup    doliagropass
 * \brief      Page to create/edit/view doliagropassindicator
 */

// Load Dolibarr environment
$res = 0;
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
if (! $res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (! $res) die("Include of main fails");

include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/doliagropass/class/doliagropassindicator.class.php');
dol_include_once('/doliagropass/lib/doliagropass_indicator.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("doliagropass@doliagropass", "other"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOSTINT('lineid');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : getDolDefaultContextPage(__FILE__); 
$backtopage = GETPOST('backtopage', 'alpha');                   
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha'); 
$optioncss = GETPOST('optioncss', 'aZ'); 
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

// Initialize technical objects
$object = new DoliAgroPassIndicator($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->doliagropass->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'card', 'globalcard')); 
$soc = null;

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; 

// Permissions
$enablepermissioncheck = getDolGlobalInt('DOLIAGROPASS_ENABLE_PERMISSION_CHECK');
if ($enablepermissioncheck) {
    $permissiontoread = $user->hasRight('doliagropass', 'doliagropassindicator', 'read');
    $permissiontoadd = $user->hasRight('doliagropass', 'doliagropassindicator', 'write');
    $permissiontodelete = $user->hasRight('doliagropass', 'doliagropassindicator', 'delete');
    $permissionnote = $user->hasRight('doliagropass', 'doliagropassindicator', 'write');
    $permissiondellink = $user->hasRight('doliagropass', 'doliagropassindicator', 'write');
} else {
    $permissiontoread = 1;
    $permissiontoadd = 1;
    $permissiontodelete = 1;
    $permissionnote = 1;
    $permissiondellink = 1;
}

$upload_dir = $conf->doliagropass->multidir_output[isset($object->entity) ? $object->entity : 1].'/doliagropassindicator';

if (!isModEnabled($object->module)) accessforbidden("Module ".$object->module." not enabled");
if (!$permissiontoread) accessforbidden();

$error = 0;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); 
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    $backurlforlist = dol_buildpath('/doliagropass/doliagropassindicator_list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/doliagropass/doliagropassindicator_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
            }
        }
    }

    $triggermodname = $object->TRIGGER_PREFIX.'_MODIFY'; 

    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
    
    // Actions to send emails
    $triggersendname = 'DOLIAGROPASS_MYOBJECT_SENTBYMAIL';
    $autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
    $trackid = 'doliagropassindicator'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("DoliAgroPassIndicator")." - ".$langs->trans('Card');
if ($action == 'create') {
    $title = $langs->trans("NewObject", $langs->transnoentitiesnoconv("DoliAgroPassIndicator"));
}

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-doliagropass page-card');

// Part to create
if ($action == 'create') {
    // [ROBUST] Warning for manual creation
    // Indicators should ideally be created via SQL setup for system consistency, but we allow manual for custom ones.
    if (empty($permissiontoadd)) accessforbidden('NotEnoughPermissions', 0, 1);

    print load_fiche_titre($title, '', $object->picto);

    print '<form method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
    if ($dol_openinpopup) print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';

    print dol_get_fiche_head(array(), '');

    print '<table class="border centpercent tableforfieldcreate">'."\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

    print '</table>'."\n";

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel("Create");

    print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans("DoliAgroPassIndicator"), '', $object->picto);

    print '<form method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';
    if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldedit">'."\n";

    // -------------------------------------------------------------------------
    // [ROBUST] PROTEZIONE CAMPI DI SISTEMA
    // In fase di modifica, impediamo all'utente di corrompere la logica del sistema
    // cambiando codici o metodi di calcolo hardcoded nel PHP.
    // -------------------------------------------------------------------------
    
    // 1. CODICE: Chiave primaria logica. Non si tocca se l'oggetto è salvato.
    $object->fields['code']['disabled'] = 1; 
    $object->fields['code']['help'] = $langs->trans("SystemFieldLocked");

    // 2. METODO DI CALCOLO: Se è un automatismo o VSA (logica complessa), blocchiamo.
    // Lasciamo modificabile solo se era 'MANUAL' generico.
    if (strpos($object->calculation_method, 'AUTO') === 0 || strpos($object->calculation_method, 'VSA') !== false) {
        $object->fields['calculation_method']['disabled'] = 1;
        $object->fields['calculation_method']['help'] = $langs->trans("LogicHardcodedInPHP");
    }

    // 3. TIPO INPUT: Se è VSA (0-2), non possiamo trasformarlo in un numero libero.
    if (strpos($object->input_type, 'SELECT_VSA') !== false) {
        $object->fields['input_type']['disabled'] = 1;
    }

    // -------------------------------------------------------------------------
    // [UI] HELP VISIVO PER TRADUZIONI
    // Mostriamo il valore tradotto corrente accanto al campo di input (che contiene la chiave)
    // -------------------------------------------------------------------------
    $translatedFields = array('label', 'description', 'category_group', 'category_type', 'input_type', 'calculation_method');
    
    foreach ($translatedFields as $tfield) {
        if (isset($object->fields[$tfield])) {
            $currentKey = $object->$tfield;
            $translatedVal = $langs->trans($currentKey);
            // Se la traduzione è diversa dalla chiave, la mostriamo come help
            if ($translatedVal != $currentKey) {
                // Aggiungiamo (o appendiamo) all'help esistente
                $existingHelp = isset($object->fields[$tfield]['help']) ? $object->fields[$tfield]['help'] . '<br>' : '';
                $object->fields[$tfield]['help'] = $existingHelp . $langs->trans("CurrentTranslation") . ': <b>' . $translatedVal . '</b>';
            }
        }
    }

    // Common attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $head = doliagropassindicatorPrepareHead($object);

    print dol_get_fiche_head($head, 'card', $langs->trans("DoliAgroPassIndicator"), -1, $object->picto, 0, '', '', 0, '', 1);

    $formconfirm = '';

    // Confirmation to delete
    if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteDoliAgroPassIndicator'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
    }
    
    // Call Hook formConfirm
    $parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); 
    if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
    elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

    print $formconfirm;

    // Object card
    $linkback = '<a href="'.dol_buildpath('/doliagropass/doliagropassindicator_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
    $morehtmlref = '<div class="refidno"></div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">'."\n";

    // -------------------------------------------------------------------------
    // [MOD] Traduzione Dinamica dei Campi in Visualizzazione
    // Sovrascriviamo temporaneamente i valori nell'oggetto per mostrare il testo tradotto
    // -------------------------------------------------------------------------
    
    // Salvataggio valori originali (per sicurezza)
    $original_label = $object->label;
    $original_desc = $object->description;
    $original_group = $object->category_group;
    $original_type = $object->category_type;
    $original_input = $object->input_type;
    $original_calc = $object->calculation_method;

    // Traduzione
    $object->label = $langs->trans($object->label);
    $object->description = $langs->trans($object->description);
    $object->category_group = $langs->trans($object->category_group);
    $object->category_type = $langs->trans($object->category_type);
    
    // Traduzione con Icone (Coerente con la Lista)
    if (strpos($object->calculation_method, 'AUTO') === 0) {
        $object->calculation_method = '<span class="fa fa-magic text-info"></span> ' . $langs->trans($object->calculation_method);
    } elseif (strpos($object->calculation_method, 'MANUAL') === 0) {
        $object->calculation_method = '<span class="fa fa-user-edit text-warning"></span> ' . $langs->trans($object->calculation_method);
    } else {
        $object->calculation_method = $langs->trans($object->calculation_method);
    }

    if (strpos($object->input_type, 'SELECT') !== false || strpos($object->input_type, 'SCALE') !== false) {
        $object->input_type = '<span class="fa fa-list-ol text-primary"></span>  ' . $langs->trans($object->input_type);
    } elseif ($object->input_type == 'BOOLEAN') {
        $object->input_type = '<span class="fa fa-toggle-on"></span> 

[Image of toggle switch]
 ' . $langs->trans($object->input_type);
    } elseif ($object->input_type == 'NUMBER') {
        $object->input_type = '<span class="fa fa-hashtag"></span> ' . $langs->trans($object->input_type);
    } else {
        $object->input_type = $langs->trans($object->input_type);
    }

    // Render Template
    include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

    // Ripristino valori originali
    $object->label = $original_label;
    $object->description = $original_desc;
    $object->category_group = $original_group;
    $object->category_type = $original_type;
    $object->input_type = $original_input;
    $object->calculation_method = $original_calc;
    // -------------------------------------------------------------------------

    // Other attributes. Fields from hook formObjectOptions and Extrafields.
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();


    /*
     * Buttons for actions
     */
    if ($action != 'presend') {
        print '<div class="tabsAction">'."\n";
        $parameters = array();
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); 
        if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

        if (empty($reshook)) {
            
            // Send
            if (empty($user->socid)) {
                print dolGetButtonAction('', $langs->trans('SendMail'), 'email', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
            }

            // Modify
            print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

            // Clone
            if ($permissiontoadd) {
                print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);
            }

            // Delete
            $deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
            $buttonId = 'action-delete-no-ajax';
            if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {
                $deleteUrl = '';
                $buttonId = 'action-delete';
            }
            print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, array());
        }
        print '</div>'."\n";
    }

    // Documents
    print '<div class="fichecenter"><div class="fichehalfleft">';
    print '<a name="builddoc"></a>'; 
    $objref = dol_sanitizeFileName($object->ref);
    $filedir = $conf->doliagropass->dir_output.'/'.$object->element.'/'.$objref;
    $urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
    print $formfile->showdocuments('doliagropass:DoliAgroPassIndicator', $object->element.'/'.$objref, $filedir, $urlsource, $permissiontoread, $permissiontoadd, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
    print '</div><div class="fichehalfright">';
    $MAXEVENT = 10;
    $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/doliagropass/doliagropassindicator_agenda.php', 1).'?id='.$object->id);
    $includeeventlist = 0;
    if ($includeeventlist) {
        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
        $formactions = new FormActions($db);
        $somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
    }
    print '</div></div>';
}

// Select mail models is same action as presend
if (GETPOST('modelselected')) $action = 'presend';

// Presend form
$modelmail = 'doliagropassindicator';
$defaulttopic = 'InformationMessage';
$diroutput = $conf->doliagropass->dir_output;
$trackid = 'doliagropassindicator'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

llxFooter();
$db->close();
?>