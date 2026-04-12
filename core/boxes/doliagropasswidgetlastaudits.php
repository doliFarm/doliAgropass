<?php
/* Copyright (C) 2024 DoliFarm Team */

include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

class doliagropassWidgetLastAudits extends ModeleBoxes
{
    public $boxcode = "doliagropassLastAudits";
    public $boximg = "doliagropass@doliagropass";
    public $boxlabel = 'DoliAgroPassLastAudits';
    public $lang = 'doliagropass@doliagropass';
    public $depends = array('doliagropass');
    public $widgettype = '';

    public function __construct(DoliDB $db, $param = '')
    {
        global $user;
        parent::__construct($db, $param);
        $this->param = $param;
        $this->enabled = 1;
    }

    public function loadBox($max = 5)
    {
        global $user, $langs, $conf;
        $this->max = $max;

        // --- CLASSI NECESSARIE ---
        dol_include_once("/doliagropass/class/doliagropassaudit.class.php");
        dol_include_once("/societe/class/societe.class.php");
        dol_include_once("/user/class/user.class.php"); // Per il tecnico

        $auditstatic = new DoliAgroPassAudit($this->db);
        $societestatic = new Societe($this->db);
        $userstatic = new User($this->db);
        
        // Definizioni Tabelle
        $table_audit = MAIN_DB_PREFIX . "doliagropass_audit"; 
        $table_user = MAIN_DB_PREFIX . "user";
        
        $this->info_box_contents = array();

        // ---------------------------------------------------------------------
        // 1. HEADER
        // ---------------------------------------------------------------------
        // Conteggio totale audit completati
        $sql_count = "SELECT count(*) as nb FROM " . $table_audit . " WHERE entity = " . $conf->entity . " AND status = 1";
        $total = 0;
        $res = $this->db->query($sql_count);
        if($res) { $obj = $this->db->fetch_object($res); $total = $obj->nb; }

        $title = $langs->trans("LastAudits");
        if ($title == "LastAudits") $title = $langs->trans("HeaderWidgetLastAudit");

        $this->info_box_head = array(
            'text' => $title . " (" . $total . ")",
            'limit' => 0,
            'graph' => 0,
            'sublink' => DOL_URL_ROOT . '/doliagropass/audit_list.php',
            'subtext' => $langs->trans("ShowList"),
            'subpicto' => 'object_audit@doliagropass', 
        );

        // ---------------------------------------------------------------------
        // 2. QUERY LISTA
        // ---------------------------------------------------------------------
        
        // ATTENZIONE: Assicurati che i campi 'date_audit' e 'fk_user_auditor' esistano nel DB.
        // Se si chiamano diversamente (es. 'date_eval', 'fk_user'), modifica qui sotto.
        $field_date = 'date_audit'; // La data di valutazione
        $field_user = 'fk_user_auditor'; // Il tecnico
        
        // Verifica esistenza campo data (fallback su datec se non esiste)
        // Questo rende il codice robusto anche se il DB non è ancora aggiornato
        $resdesc = $this->db->query("SHOW COLUMNS FROM ".$table_audit." LIKE '".$field_date."'");
        if ($this->db->num_rows($resdesc) == 0) {
             $field_date = 'datec'; // Fallback sulla data creazione
        }

        $sql = "SELECT t.rowid, t.ref, t.label, t.datec, t.final_score, t.status, t.fk_soc,";
        $sql .= " t.".$field_date." as date_eval,"; // Alias per la data valutazione
        // Dati Utente (Tecnico)
        $sql .= " u.rowid as u_id, u.lastname, u.firstname, u.email, u.photo, u.statut as u_statut, u.gender";
        
        $sql .= " FROM " . $table_audit . " as t";
        $sql .= " LEFT JOIN " . $table_user . " as u ON t.".$field_user." = u.rowid";
        
        $sql .= " WHERE t.entity = " . $conf->entity;
        
        // ORDINAMENTO RICHIESTO:
        // Qui puoi cambiare l'ordine. Attuale: Data Valutazione decrescente, poi Punteggio
        $sql .= " ORDER BY t.".$field_date." DESC, t.final_score DESC";
        
        $sql .= " LIMIT " . $max;

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                
                // Setup Audit
                $auditstatic->id = $obj->rowid;
                $auditstatic->ref = $obj->ref;
                $auditstatic->label = $obj->label;
                $auditstatic->datec = $this->db->jdate($obj->datec);
                $auditstatic->status = $obj->status;
                $auditstatic->statut = $obj->status;

                // Setup Azienda (Farm)
                $company_html = "";
                if ($obj->fk_soc > 0) {
                    $societestatic->fetch($obj->fk_soc);
                    $company_html = $societestatic->getNomUrl(1, 'customer', 20);
                } else {
                    $company_html = '<span class="opacitymedium">'.$langs->trans("Anonymous").'</span>';
                }

                // Setup Tecnico (User)
                $technician_html = "";
                if ($obj->u_id > 0) {
                    $userstatic->id = $obj->u_id;
                    $userstatic->lastname = $obj->lastname;
                    $userstatic->firstname = $obj->firstname;
                    $userstatic->email = $obj->email;
                    $userstatic->statut = $obj->u_statut;
                    $userstatic->photo = $obj->photo;
                    $userstatic->gender = $obj->gender;
                    // Mostra avatar + nome breve
                    $technician_html = $userstatic->getNomUrl(1, '', 0, 0, 16); 
                } else {
                    $technician_html = '<span class="opacitymedium">-</span>';
                }

                // Logica Colore Punteggio
                // < 50 Rosso, 50-80 Giallo, > 80 Verde
                $final_score_badge_class = 'badge-status5'; // Grigio
                if ($obj->final_score !== null) {
                    if ($obj->final_score >= 80) $final_score_badge_class = 'badge-status4'; // Verde
                    elseif ($obj->final_score >= 50) $final_score_badge_class = 'badge-status1'; // Giallo
                    else $final_score_badge_class = 'badge-status9'; // Rosso
                }

                // --- COSTRUZIONE RIGHE ---

                // COL 1: Ref Audit
                $this->info_box_contents[$i][] = array(
                    'td' => '',
                    'text' => $auditstatic->getNomUrl(1),
                    'asis' => 1,
                );

                // COL 2: Azienda Agricola
                $this->info_box_contents[$i][] = array(
                    'td' => 'class="tdoverflowmax150"',
                    'text' => $company_html,
                    'asis' => 1,
                );

                // COL 3: Tecnico (Nuova Colonna)
                $this->info_box_contents[$i][] = array(
                    'td' => 'class="tdoverflowmax100"',
                    'text' => $technician_html,
                    'asis' => 1,
                );

                // COL 4: Punteggio (Final_score)
                $final_score_display = ($obj->final_score !== null) ? $obj->final_score . '/100' : '-';
                $this->info_box_contents[$i][] = array(
                    'td' => 'class="center"',
                    'text' => '<span class="badge '.$final_score_badge_class.' badge-pill">'.$final_score_display.'</span>',
                    'asis' => 1,
                );

                // COL 5: Data Valutazione
                // Usiamo date_eval (alias creato nella query)
                $date_display = $obj->date_eval ? dol_print_date($this->db->jdate($obj->date_eval), 'day') : '-';
                $this->info_box_contents[$i][] = array(
                    'td' => 'class="right"',
                    'text' => $date_display,
                );

                $i++;
            }
            if ($num == 0) $this->info_box_contents[][0] = array('td' => 'class="center opacitymedium"', 'text' => $langs->trans("NoData"));
        } else {
            // Error handling robusto
            $this->info_box_contents[][0] = array('td' => 'class="error"', 'text' => $this->db->lasterror());
        }
    }

    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
?>