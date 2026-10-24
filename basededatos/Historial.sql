-- HU-36: Historial de compras y facturas — protección del historial (criterio 4)
-- Ya ejecutado directamente en Supabase. Este archivo es solo documentación/trazabilidad.

-- Nuevo estado ANULADA (cancelación definitiva, distinta de RECHAZADA que permite reenvío).
ALTER TABLE factura_proveedor DROP CONSTRAINT chk_factura_estado;
ALTER TABLE factura_proveedor ADD CONSTRAINT chk_factura_estado
  CHECK (estado IN ('PENDIENTE', 'EN_REVISION', 'APROBADA', 'RECHAZADA', 'ANULADA'));

-- Trazabilidad de quién y cuándo anuló la factura (el motivo se concatena a observaciones,
-- igual que el motivo de rechazo de HU-34).
ALTER TABLE factura_proveedor ADD COLUMN id_usuario_anula BIGINT REFERENCES usuario(id_usuario);
ALTER TABLE factura_proveedor ADD COLUMN fecha_anulacion TIMESTAMPTZ;
