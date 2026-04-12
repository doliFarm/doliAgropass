
-- ------------------------------------------------------------------------------
-- 3. DETTAGLIO AUDIT (Le Risposte)
-- Una riga per ogni indicatore valutato
-- ------------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS llx_doliagropass_audit_det (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_audit        INTEGER NOT NULL,
    fk_indicator    INTEGER NOT NULL,
    value_measured  TEXT,
    value_source    VARCHAR(32) DEFAULT 'MANUAL',
    score_calc      DOUBLE(5,2) DEFAULT 0,
    score_override  DOUBLE(5,2),
    
    note_auditor    TEXT,
    
    -- Dolibarr Standard Fields (Tracking anche sulle righe è utile per audit approfonditi)
    datec           DATETIME,
    tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat   INTEGER,
    fk_user_modif   INTEGER,
    import_key      VARCHAR(14),
    UNIQUE INDEX uk_doliagropass_audit_ind (fk_audit, fk_indicator),
    CONSTRAINT fk_doliagropass_det_head FOREIGN KEY (fk_audit) REFERENCES llx_doliagropass_audit(rowid) ON DELETE CASCADE,
    CONSTRAINT fk_doliagropass_det_ind FOREIGN KEY (fk_indicator) REFERENCES llx_doliagropass_indicator(rowid)
) ENGINE=InnoDB;



SET FOREIGN_KEY_CHECKS=1;
