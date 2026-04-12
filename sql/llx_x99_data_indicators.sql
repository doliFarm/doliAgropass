-- ==============================================================================
-- AGGIORNAMENTO INDICATORI DOLIAGROPASS (v5.0 - SAFE UPSERT)
-- Gestisce Inserimento o Aggiornamento senza cancellare dati esistenti.
-- ==============================================================================

INSERT INTO llx_doliagropass_indicator 
(code, label, description, category_group, input_type, input_params, calculation_method, unit, weight, max_score, active) 
VALUES 

-- ------------------------------------------------------------------------------
-- SCHEDA 1: PRODUZIONE VEGETALE & BIODIVERSITÀ
-- ------------------------------------------------------------------------------
('1.1', 'DAP_IND_1_1_ROTATION', 'Numero medio di anni del piano di rotazione per parcella', 'DAP_CAT_CROP', 'NUMBER', NULL, 'AUTO_DOLITRACE_ROTATION', 'anni', 2.0, 10, 1),

('1.2', 'DAP_IND_1_2_ECO_AREA', 'Superficie coperta da siepi, boschetti, fasce tampone rispetto alla SAU', 'DAP_CAT_BIODIVERSITY', 'NUMBER', NULL, 'MANUAL', '%', 1.5, 100, 1),

('1.3', 'DAP_IND_1_3_WINTER_COVER', 'Gestione della copertura del suolo durante l''inverno', 'DAP_CAT_CROP', 'SELECT', 
'{"0":"DAP_OP_BARE_SOIL", "5":"DAP_OP_CROP_RESIDUE", "10":"DAP_OP_COVER_CROP"}', 
'MANUAL', NULL, 1.5, 10, 1),

-- ------------------------------------------------------------------------------
-- SCHEDA 2: BENESSERE ANIMALE
-- ------------------------------------------------------------------------------
('2.1', 'DAP_IND_2_1_GRAZING', 'Gli animali hanno accesso al pascolo per almeno 180 giorni/anno?', 'DAP_CAT_LIVESTOCK', 'BOOLEAN', NULL, 'MANUAL', NULL, 2.0, 1, 1),

('2.2', 'DAP_IND_2_2_DENSITY', 'Carico di bestiame per ettaro (UBA/ha)', 'DAP_CAT_LIVESTOCK', 'NUMBER', NULL, 'MANUAL', 'UBA/ha', 1.0, 4, 1),

-- ------------------------------------------------------------------------------
-- SCHEDA 3: GESTIONE INPUT (Fertilizzanti & Acqua)
-- ------------------------------------------------------------------------------
('3.1', 'DAP_IND_3_1_NITROGEN', 'Percentuale di Azoto proveniente da fonti organiche (letame, compost)', 'DAP_CAT_INPUTS', 'SCALE', 
'{"1":"0-20%", "2":"20-40%", "3":"40-60%", "4":"60-80%", "5":">80%"}', 
'MANUAL', '%', 1.5, 5, 1),

('3.2', 'DAP_IND_3_2_WATER', 'Utilizzo di sistemi di irrigazione ad alta efficienza (goccia, micro)', 'DAP_CAT_INPUTS', 'BOOLEAN', NULL, 'MANUAL', NULL, 1.0, 1, 1),

-- ------------------------------------------------------------------------------
-- SCHEDA 6: SOSTENIBILITÀ SOCIALE ED ECONOMICA
-- ------------------------------------------------------------------------------
('6.1', 'DAP_IND_6_1_LOCAL_SALES', 'Percentuale di fatturato da vendita diretta o filiera corta', 'DAP_CAT_SOCIAL', 'SCALE', 
'{"1":"< 10% (Insufficiente)", "2":"10-30% (Basso)", "3":"30-50% (Medio)", "4":"50-80% (Buono)", "5":"> 80% (Eccellente)"}', 
'MANUAL', '%', 1.0, 5, 1),

('6.2', 'DAP_IND_6_2_TRAINING', 'Ore di formazione annuale per dipendente su temi di sostenibilità/sicurezza', 'DAP_CAT_SOCIAL', 'NUMBER', NULL, 'MANUAL', 'ore', 0.5, 50, 1),

-- ------------------------------------------------------------------------------
-- SCHEDA 7: QUALITÀ DEL SUOLO (VSA - Visual Soil Assessment)
-- ------------------------------------------------------------------------------
('7.1', 'DAP_IND_7_1_TEXTURE', 'Valutazione della struttura delle zolle dopo il test della vanga', 'DAP_CAT_SOIL_VSA', 'SELECT', 
'{"0":"DAP_VSA_0_TEXTURE", "1":"DAP_VSA_1_TEXTURE", "2":"DAP_VSA_2_TEXTURE"}', 
'MANUAL_VSA_WEIGHTED', NULL, 3.0, 2, 1),

('7.2', 'DAP_IND_7_2_POROSITY', 'Valutazione della macroporosità visibile', 'DAP_CAT_SOIL_VSA', 'SELECT', 
'{"0":"DAP_VSA_0_POROSITY", "1":"DAP_VSA_1_POROSITY", "2":"DAP_VSA_2_POROSITY"}', 
'MANUAL_VSA_WEIGHTED', NULL, 2.0, 2, 1),

('7.3', 'DAP_IND_7_3_COLOR', 'Valutazione del colore e presenza di odori anaerobici', 'DAP_CAT_SOIL_VSA', 'SELECT', 
'{"0":"DAP_VSA_0_COLOR", "1":"DAP_VSA_1_COLOR", "2":"DAP_VSA_2_COLOR"}', 
'MANUAL_VSA_WEIGHTED', NULL, 2.0, 2, 1),

('7.4', 'DAP_IND_7_4_WORMS', 'Conteggio lombrichi in zolla 20x20x20cm', 'DAP_CAT_SOIL_VSA', 'SELECT', 
'{"0":"DAP_VSA_0_WORMS", "1":"DAP_VSA_1_WORMS", "2":"DAP_VSA_2_WORMS"}', 
'MANUAL_VSA_WEIGHTED', 'n', 3.0, 2, 1),

('7.5', 'DAP_IND_7_5_PAN', 'Presenza e profondità della suola di lavorazione', 'DAP_CAT_SOIL_VSA', 'SELECT', 
'{"0":"DAP_VSA_0_PAN", "1":"DAP_VSA_1_PAN", "2":"DAP_VSA_2_PAN"}', 
'MANUAL_VSA_WEIGHTED', NULL, 2.0, 2, 1),

('7.6', 'DAP_IND_7_6_COVER', 'Percentuale di suolo coperto da residui o vegetazione', 'DAP_CAT_SOIL_VSA', 'SELECT', 
'{"0":"DAP_VSA_0_COVER", "1":"DAP_VSA_1_COVER", "2":"DAP_VSA_2_COVER"}', 
'MANUAL_VSA_WEIGHTED', NULL, 1.0, 2, 1)

-- ------------------------------------------------------------------------------
-- LOGICA DI AGGIORNAMENTO (UPSERT)
-- Se il codice esiste, aggiorna i campi per riflettere le modifiche.
-- ------------------------------------------------------------------------------
ON DUPLICATE KEY UPDATE 
    label               = VALUES(label),
    description         = VALUES(description),
    category_group      = VALUES(category_group),
    input_type          = VALUES(input_type),
    input_params        = VALUES(input_params),
    calculation_method  = VALUES(calculation_method),
    unit                = VALUES(unit),
    weight              = VALUES(weight),
    max_score           = VALUES(max_score),
    active              = VALUES(active);