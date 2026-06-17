-- Campos de ticket y observaciones para cargas de combustible
ALTER TABLE combustible_cargas
    ADD COLUMN folio_ticket VARCHAR(100) NULL AFTER kilometraje,
    ADD COLUMN observaciones TEXT NULL AFTER factura_ruta;
