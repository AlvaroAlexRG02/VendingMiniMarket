-- ==========================================================
-- Estructura para ventas Nayax (HU-49)
--
-- Una fila por línea vendida. Cuando exista la integración/importación
-- de Nayax, basta con insertar aquí las ventas con estado 'CONFIRMADA':
-- los reportes de Ventas, Utilidad y Rotación (api/reportes/reportes.php)
-- detectan la tabla y suman estas ventas automáticamente.
-- Mientras la tabla no exista, los reportes usan solo ventas manuales.
-- ==========================================================

CREATE TABLE IF NOT EXISTS venta_nayax (
    id_venta_nayax   BIGSERIAL PRIMARY KEY,
    id_tienda        BIGINT NOT NULL REFERENCES tienda(id_tienda),
    id_ubicacion     BIGINT REFERENCES ubicacion_inventario(id_ubicacion),
    id_producto      BIGINT NOT NULL REFERENCES producto(id_producto),
    referencia       VARCHAR(100),
    fecha            TIMESTAMPTZ NOT NULL,
    cantidad         INTEGER NOT NULL CHECK (cantidad > 0),
    precio_unitario  NUMERIC(12, 2) NOT NULL DEFAULT 0,
    subtotal         NUMERIC(12, 2) NOT NULL DEFAULT 0,
    impuesto         NUMERIC(12, 2) NOT NULL DEFAULT 0,
    total            NUMERIC(12, 2) NOT NULL DEFAULT 0,
    costo_unitario   NUMERIC(12, 2),
    estado           VARCHAR(20) NOT NULL DEFAULT 'CONFIRMADA',
    fecha_registro   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_venta_nayax_estado CHECK (estado IN ('PENDIENTE', 'CONFIRMADA', 'ANULADA'))
);

CREATE INDEX IF NOT EXISTS idx_venta_nayax_tienda_fecha ON venta_nayax (id_tienda, fecha);
