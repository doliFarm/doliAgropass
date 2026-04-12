-- Copyright (C) 2026		SuperAdmin
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE IF NOT EXISTS llx_doliagropass_audit(
    -- Primary Key
    rowid int AUTO_INCREMENT PRIMARY KEY NOT NULL, 
    
    -- Core Identifiers
    ref varchar(128) NOT NULL, 
    entity INTEGER DEFAULT 1,
    uuid varchar(36), 
    
    -- Business Fields
    label varchar(255), 
    fk_soc integer NOT NULL, 
    season int NOT NULL, 
    date_audit date, 
    fk_user_auditor integer, 
    final_score double(8,2), 
    status int DEFAULT 0, 
    
    -- Notes
    note_public text, 
    note_private text, 
    
    -- Audit & Tracking
    datec datetime, 
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
    date_valid DATETIME,
    
    fk_user_creat integer, 
    fk_user_modif integer, 
    fk_user_valid integer,  -- [AGGIUNTO per completezza]
    
    -- Import & Docs
    import_key varchar(14),
    model_pdf       VARCHAR(255),
    last_main_doc   VARCHAR(255)

) ENGINE=innodb;

