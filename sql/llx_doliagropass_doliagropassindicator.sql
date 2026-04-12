-- Copyright (C) 2026 SuperAdmin
-- Table definition for doliagropass indicators

CREATE TABLE IF NOT EXISTS llx_doliagropass_indicator(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    
    -- Identificazione
    code varchar(32) NOT NULL,              -- Codice univoco (es. 7.1, 1.1.A)
    label varchar(255) NOT NULL,            -- Translation Key (es. DAP_IND_7_1_LABEL)
    description varchar(255),               -- Translation Key descrizione
	entity INTEGER DEFAULT 1,

	note_public text, 
	note_private text, 
    -- Categorizzazione (per le Tab nella UX)
    category_group varchar(64) NOT NULL,    -- Es. DAP_CAT_SCHEDA_7 (Suolo), DAP_CAT_SCHEDA_1 (Produzione)
    category_type varchar(16),              -- Es. A (Obbligatorio), B (Facoltativo) - Opzionale
    
    -- Logica di Input e Calcolo
    input_type varchar(32) DEFAULT 'NUMBER', -- Tipi: SELECT_VSA_0_2, NUMBER, BOOLEAN, SCALE_1_10
    calculation_method varchar(32) DEFAULT 'MANUAL', -- Tipi: MANUAL, MANUAL_VSA_WEIGHTED, AUTO_DOLIFARM_BIO, AUTO_DOLITRACE_ROTATION
    input_params TEXT,

    -- Ponderazione
    unit varchar(32),                       -- Unità di misura (es. %, mq, n)
    weight double(5,2) DEFAULT 1.0,         -- Moltiplicatore del punteggio (Importante per VSA)
    max_score int DEFAULT 10,               -- Punteggio massimo ottenibile per questo indicatore
    
    -- Stato
    active integer DEFAULT 1,
    status INTEGER DEFAULT 1,

    -- Campi Standard Dolibarr
    datec datetime  DEFAULT CURRENT_TIMESTAMP,
	date_valid DATETIME  DEFAULT CURRENT_TIMESTAMP,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer,
    fk_user_modif integer,
    import_key varchar(14),
	model_pdf       VARCHAR(255),
    last_main_doc   VARCHAR(255)
) ENGINE=innodb;