<?php
/* Copyright (C) 2024 DoliFarm Team */

include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Widget Allerta: Audit Critici (Score < 25)
 * Nota: Il nome della classe è mantenuto 'CertificatesExpiry' per compatibilità
 * con la registrazione del modulo, ma la logica mostra gli Audit Critici.
 */
class doliagropassWidgetCertificatesExpiry extends ModeleBoxes
{
    public $boxcode = "doliagropassCertificatesExpiry";
    public $boximg = "doliagropass@doliagropass";
    public $boxlabel = 'DoliAgroPassCriticalAudits'; // Label interna aggiornata
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

    /**
     * Load data
     */
    public function loadBox($max = 10) // Forziamo default a 10 come richiesto
    {
        global $user, $langs, $conf;
        $this->max = $max;

        // --- CLASSI NECESSARIE ---
        dol_include_once("/doliagropass/class/doliagropassaudit.class.php");
        dol_include_once("/societe/class/societe.class.php");

        // Istanziamo la classe Audit
        if (class_exists('DoliAgroPassAudit')) {
            $auditstatic = new DoliAgroPassAudit($this->db);
        } else {
            $auditstatic = new stdClass();
        }
        $societestatic = new Societe($this->db);
        
        $this->info_box_contents = array();

        // ---------------------------------------------------------------------
        // 1. CONFIGURAZIONE TABELLA
        // ---------------------------------------------------------------------
        $table_audit = MAIN_DB_PREFIX . "doliagropass_audit";

        // Verifica robustezza DB
        $resdesc = $this->db->DDLDescTable($table_audit);
        if (!$resdesc) {
            $this->info_box_contents[][0] = array(
                'td' => 'class="center opacitymedium"',
                'text' => "Tabella $table_audit non trovata."
            );
            return;
        }

        // Determina il campo data (date_audit o fallback su datec)
        $field_date = 'date_audit';
        $rescol = $this->db->query("SHOW COLUMNS FROM ".$table_audit." LIKE 'date_audit'");
        if ($this->db->num_rows($rescol) == 0) {
             $field_date = 'datec'; 
        }

        // ---------------------------------------------------------------------
        // 2. HEADER (Titolo Allarmante)
        // ---------------------------------------------------------------------
        // Contiamo quanti audit sono critici
        $sql_count = "SELECT count(*) as nb FROM " . $table_audit;
        $sql_count .= " WHERE entity = " . $conf->entity . " AND status = 1";
        $sql_count .= " AND final_score < 25";

        $total_critical = 0;
        $res = $this->db->query($sql_count);
        if($res) { $obj = $this->db->fetch_object($res); $total_critical = $obj->nb; }

        // Titolo personalizzato per questo widget
        $title = $langs->trans("CriticalAudits") . " (< 25)";
        if ($title == "CriticalAudits (< 25)") $title = "Audit Critici (< 25/100)"; // Fallback

        // Icona di warning rossa se ci sono casi
        $alert_icon = ($total_critical > 0) ? ' '.img_picto('', 'warning', 'style="color:#bc3333"') : '';

        $this->info_box_head = array(
            'text' => $title . " (" . $total_critical . ")" . $alert_icon,
            'limit' => 0,
            'graph' => 0,
            'sublink' => DOL_URL_ROOT . '/doliagropass/audit_list.php?search_score=25', // Link filtrato (se supportato dalla lista)
            'subtext' => $langs->trans("ShowList"),
            'subpicto' => 'object_audit@doliagropass', 
        );

        // ---------------------------------------------------------------------
        // 3. QUERY SPECIFICA (Score < 25)
        // ---------------------------------------------------------------------
        $sql = "SELECT t.rowid, t.ref, t.label, t.datec, t.final_score, t.status, t.fk_soc,";
        $sql .= " t.".$field_date." as date_eval";
        $sql .= " FROM " . $table_audit . " as t";
        $sql .= " WHERE t.entity = " . $conf->entity;
        $sql .= " AND t.status = 1"; // Solo completati
        $sql .= " AND t.final_score < 25"; // FILTRO CRITICO RICHIESTO
        // Ordinamento: Per data decrescente (i disastri più recenti in alto)
        $sql .= " ORDER BY t.".$field_date." DESC"; 
        $sql .= " LIMIT " . $max;

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                
                // Popoliamo oggetto Audit
                if (method_exists($auditstatic, 'fetch')) {
                    $auditstatic->id = $obj->rowid;
                    $auditstatic->ref = $obj->ref;
                    $auditstatic->label = $obj->label;
                    $auditstatic->status = $obj->status;
                    $link = $auditstatic->getNomUrl(1);
                } else {
                    $link = $obj->ref;
                }

                // Azienda
                $company_html = "";
                if ($obj->fk_soc > 0) {
                    $societestatic->fetch($obj->fk_soc);
                    $company_html = $societestatic->getNomUrl(1, 'customer', 16);
                } else {
                    $company_html = '<span class="opacitymedium">'.$langs->trans("Anonymous").'</span>';
                }

                // Badge Rosso Fisso (dato che sono tutti < 25)
                $badge_class = 'badge-status9'; // Danger red
                
                // Formattazione Data
                $date_display = $obj->date_eval ? dol_print_date($this->db->jdate($obj->date_eval), 'day') : '-';

                // --- RIGHE ---
                // COL 1: Ref Audit
                $this->info_box_contents[$i][] = array(
                    'td' => '',
                    'text' => $link,
                    'asis' => 1,
                );

                // COL 2: Azienda
                $this->info_box_contents[$i][] = array(
                    'td' => 'class="tdoverflowmax150"',
                    'text' => $company_html,
                    'asis' => 1,
                );

                // COL 3: Punteggio (Evidenziato)
                $this->info_box_contents[$i][] = array(
                    'td' => 'class="center"',
                    'text' => '<span class="badge '.$badge_class.' badge-pill" style="font-weight:bold; font-size:1.1em;">'.$obj->final_score.'</span>', // Font più grande per enfasi
                    'asis' => 1,
                );

                // COL 4: Data Valutazione
                $this->info_box_contents[$i][] = array(
                    'td' => 'class="right"',
                    'text' => $date_display,
                );

                $i++;
            }
            if ($num == 0) {
                // Messaggio positivo se non ci sono audit critici
                 $this->info_box_contents[$i][] = array(
                    'td' => 'class="center opacitymedium"',
                    'text' => '<span style="color:#27ae60">'.$langs->trans("NoCriticalAudits").' - Tutto OK</span>',
                    'asis' => 1
                );
            }
        } else {
            $this->info_box_contents[][0] = array(
                'td' => 'class="error"',
                'text' => "SQL Error: " . $this->db->lasterror()
            );
        }
    }

    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
?>