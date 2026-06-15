# FASE 2 — Diseño de Base de Datos
## SICV CECYTE BCS

Documento complementario al esquema SQL en `database/migrations/001_schema.sql`.

## Entidades principales (3FN)

| Entidad | Descripción | PK |
|---------|-------------|-----|
| roles, permissions, role_permissions | RBAC | id |
| users, user_sessions, access_logs, password_resets | Autenticación | id |
| areas | Catálogo institucional | id |
| proveedores | Talleres y gasolineras | id |
| vehiculos, vehiculo_fotos, vehiculo_estado_historial | Flota | id |
| comisiones, comision_fotos | Operación de viajes | id |
| inspecciones, inspeccion_items, inspeccion_fotos | Bitácora | id |
| danios, danio_fotos, danio_seguimiento | Control de daños | id |
| mantenimientos, mantenimiento_fotos | Servicios | id |
| combustible_cargas | Cargas de combustible | id |
| herramientas_vehiculo, herramienta_reposiciones | Inventario por unidad | id |
| documentos | Control documental versionado | id |
| alerta_config, alertas | Alertas automáticas | id |
| auditoria | Trazabilidad | id |

## Vistas SQL

- `v_vehiculos_resumen` — Listados operativos
- `v_costos_vehiculo` — TCO por unidad
- `v_documentos_por_vencer` — Alertas documentales

## Triggers

- `trg_vehiculos_update` — Auditoría de estado/km
- `trg_users_update` — Cambios críticos de usuario

## Índices clave

Estado vehículo, placas, fechas de comisiones/combustible, vencimiento documentos, nivel alertas.
