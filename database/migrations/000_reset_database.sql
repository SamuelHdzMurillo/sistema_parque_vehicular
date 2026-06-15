-- SICV — Reinicio completo de base de datos
-- ⚠️ ELIMINA TODOS LOS DATOS. Ejecutar ANTES de 001_schema.sql si quieres reinstalar desde cero.

DROP DATABASE IF EXISTS sicv_cecyte_bcs;

CREATE DATABASE sicv_cecyte_bcs
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
