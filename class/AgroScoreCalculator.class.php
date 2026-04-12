<?php
/* Copyright (C) 2026       SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file        custom/doliagropass/class/AgroScoreCalculator.class.php
 * \ingroup     doliagropass
 * \brief       Engine to calculate the Agro-Ecological Score based on audit data
 */

require_once DOL_DOCUMENT_ROOT . '/custom/doliagropass/class/doliagropassaudit.class.php';

/**
 * Class AgroScoreCalculator
 */
class AgroScoreCalculator
{
    /** @var DoliDB Database handler */
    private $db;

    /** @var DoliAgroPassAudit The audit object being processed */
    private $audit;

    /** @var array Cache of indicators definition */
    private $indicators = array();

    /** @var string Error message */
    public $error;

    /**
     * Constructor
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Main entry point to calculate score for an Audit
     * @param int $audit_id ID of the audit to calculate
     * @return float|int Final calculated score or -1 on error
     */
    public function calculateGlobalScore($audit_id)
    {
        global $user, $conf;

        $this->audit = new DoliAgroPassAudit($this->db);
        $result = $this->audit->fetch($audit_id);
        
        if ($result <= 0) {
            $this->error = "Audit not found";
            dol_syslog("AgroScoreCalculator::calculateGlobalScore Audit not found", LOG_ERR);
            return -1;
        }

        // 1. Fetch Audit Lines with Indicator Info (Weight, Max Score)
        // [INTEGRO] Join with indicator table to get weight and method
        $sql = "SELECT d.rowid as line_id, d.fk_indicator, d.score_calc, d.score_override, d.value_measured, ";
        $sql .= " i.code, i.weight, i.max_score, i.calculation_method";
        $sql .= " FROM " . MAIN_DB_PREFIX . "doliagropass_audit_det as d";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "doliagropass_indicator as i ON d.fk_indicator = i.rowid";
        $sql .= " WHERE d.fk_audit = " . ((int)$audit_id);

        $resql = $this->db->query($sql);

        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        $totalScoreWeighted = 0;
        $totalMaxScoreWeighted = 0;
        $processed_lines = 0;

        $this->db->begin();

        while ($obj = $this->db->fetch_object($resql)) {
            
            // [LOGIC] Score Selection: Override wins if present
            // score_override can be '0', so we check for not empty string or numeric
            $effective_score = (isset($obj->score_override) && $obj->score_override !== '') 
                                ? (float)$obj->score_override 
                                : (float)$obj->score_calc;

            $weight = (float)$obj->weight;
            $max_score_ind = (float)$obj->max_score > 0 ? (float)$obj->max_score : 10; // Default max 10 if not set

            // [LOGIC] Calculation Method Specifics
            // Some methods might auto-calculate score_calc here if it was missing (Fallbacks)
            if (empty($effective_score) && strpos($obj->calculation_method, 'AUTO_') === 0) {
                // Here we could re-trigger auto calculation logic if needed, 
                // but usually auto-calc happens at creation or specific action.
                // For now, we trust the DB value.
            }

            // [LOGIC] Weighted Sum Calculation
            if ($weight > 0) {
                // Normalizzazione VSA (0-2) su scala standard se necessario?
                // No, usiamo il metodo della percentuale pesata.
                
                $totalScoreWeighted += ($effective_score * $weight);
                $totalMaxScoreWeighted += ($max_score_ind * $weight);
            }
            
            $processed_lines++;
        }

        // [LOGIC] Final AgroScore Formula (Percentage 0-100)
        $final_score = 0;
        if ($totalMaxScoreWeighted > 0) {
            $final_score = ($totalScoreWeighted / $totalMaxScoreWeighted) * 100;
        }

        // Round to 2 decimals
        $final_score = round($final_score, 2);

        dol_syslog("AgroScoreCalculator: Audit $audit_id - Lines: $processed_lines - Weighted Score: $totalScoreWeighted / $totalMaxScoreWeighted - Final: $final_score", LOG_INFO);

        // Update Header with Final Score
        // Direct SQL update to avoid recursive triggers loops
        $sqlUpd = "UPDATE " . MAIN_DB_PREFIX . "doliagropass_audit";
        $sqlUpd .= " SET final_score = " . ((float)$final_score);
        $sqlUpd .= " WHERE rowid = " . ((int)$this->audit->id);
        
        $resUpd = $this->db->query($sqlUpd);
        
        if ($resUpd) {
            $this->db->commit();
            return $final_score;
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    // ========================================================================
    // HELPER METHODS - DATA FETCHING (Legacy / Specific Automations)
    // ========================================================================

    /**
     * Calcola aree ecologiche da DoliFarm (Placeholder Logic)
     */
    private function calcFromDoliFarmBio($indicator)
    {
        // ... (Logica esistente mantenuta come placeholder)
        return 0;
    }

    /**
     * Calcola rotazioni da DoliTrace (Placeholder Logic)
     */
    private function calcFromDoliTraceRotation($indicator)
    {
        // ... (Logica esistente mantenuta come placeholder)
        return 0; 
    }
}
?>