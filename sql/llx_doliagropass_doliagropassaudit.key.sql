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


-- BEGIN MODULEBUILDER INDEXES
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_doliagropass_doliagropassaudit ADD UNIQUE INDEX uk_doliagropass_doliagropassaudit_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_doliagropass_doliagropassaudit ADD CONSTRAINT llx_doliagropass_doliagropassaudit_fk_field FOREIGN KEY (fk_field) REFERENCES llx_doliagropass_myotherobject(rowid);


-- [INDICI & VINCOLI] - Fondamentali per integrità e velocità
ALTER TABLE llx_doliagropass_audit ADD UNIQUE INDEX uk_doliagropass_audit_ref (ref, entity);
ALTER TABLE llx_doliagropass_audit ADD INDEX idx_doliagropass_audit_rowid (rowid);
ALTER TABLE llx_doliagropass_audit ADD INDEX idx_doliagropass_audit_ref (ref);
ALTER TABLE llx_doliagropass_audit ADD INDEX idx_doliagropass_audit_fk_soc (fk_soc);
ALTER TABLE llx_doliagropass_audit ADD INDEX idx_doliagropass_audit_status (status);
