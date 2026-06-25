-- Folio y nivel de combustible en inspecciones
ALTER TABLE inspecciones
    ADD COLUMN folio VARCHAR(30) NULL AFTER id,
    ADD COLUMN nivel_combustible DECIMAL(5,2) NULL AFTER kilometraje;

UPDATE inspecciones
SET folio = CONCAT('INS-', YEAR(fecha), '-', LPAD(id, 4, '0'))
WHERE folio IS NULL;

ALTER TABLE inspecciones
    MODIFY folio VARCHAR(30) NOT NULL,
    ADD UNIQUE KEY uq_inspecciones_folio (folio);
