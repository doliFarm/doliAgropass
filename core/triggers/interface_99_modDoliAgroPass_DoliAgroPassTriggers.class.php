<?php
/* Copyright (C) 2026 SuperAdmin */

/**
 * \file       core/triggers/interface_99_modDoliAgroPass_DoliAgroPassTriggers.class.php
 * \ingroup    doliagropass
 * \brief      Trigger file for DoliAgroPass module
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/doliagropass/class/AgroScoreCalculator.class.php');

class interface_99_modDoliAgroPass_DoliAgroPassTriggers extends DolibarrTriggers
{
    public $family = 'doliagropass';
    public $description = "Triggers of this module";
    public $version = '1.0.0';
    public $picto = 'doliagropass@doliagropass';

    /**
     * Function called when a Dolibarrr business event is done.
     *
     * @param string        $action     Event action code
     * @param CommonObject  $object     Object affected
     * @param User          $user       Object user
     * @param Translate     $langs      Object langs
     * @param Conf          $conf       Object conf
     * @return int                      <0 if KO, 0 if no action, >0 if OK
     */
    public function run_trigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        // Check if the event relates to our Audit Lines
        // Note: The element name defined in DoliAgroPassAuditLine class is 'doliagropassauditdet'
        
        if ($object->element == 'doliagropassauditdet') {
            
            // Events we care about: CREATE, MODIFY, DELETE
            if ($action == 'DOLIAGROPASS_AUDITDET_CREATE' || 
                $action == 'DOLIAGROPASS_AUDITDET_MODIFY' || 
                $action == 'DOLIAGROPASS_AUDITDET_DELETE') {

                dol_syslog("Trigger DoliAgroPass: Detected change on line " . $object->id . " - Action: " . $action, LOG_INFO);

                // Recuperiamo l'ID dell'Audit genitore
                // In caso di CREATE/MODIFY è $object->fk_audit
                // In caso di DELETE, l'oggetto è ancora pieno prima della cancellazione definitiva in memoria, 
                // ma dolibarr triggers scattano spesso 'AFTER'.
                
                $fk_audit = $object->fk_audit;

                if ($fk_audit > 0) {
                    $calculator = new AgroScoreCalculator($this->db);
                    $new_score = $calculator->calculateGlobalScore($fk_audit);
                    
                    dol_syslog("Trigger DoliAgroPass: Recalculated Score for Audit " . $fk_audit . " = " . $new_score, LOG_INFO);
                }
            }
        }

        return 0;
    }
}