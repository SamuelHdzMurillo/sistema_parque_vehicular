-- Datos de factura del proveedor en mantenimientos
USE sicv_cecyte_bcs;

ALTER TABLE mantenimientos
    ADD COLUMN factura_folio VARCHAR(60) NULL AFTER costo,
    ADD COLUMN factura_uuid VARCHAR(40) NULL AFTER factura_folio,
    ADD COLUMN factura_fecha DATE NULL AFTER factura_uuid,
    ADD COLUMN factura_subtotal DECIMAL(12,2) NULL AFTER factura_fecha,
    ADD COLUMN factura_iva DECIMAL(12,2) NULL AFTER factura_subtotal,
    ADD COLUMN factura_total DECIMAL(12,2) NULL AFTER factura_iva;
