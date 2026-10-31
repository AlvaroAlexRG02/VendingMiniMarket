-- ==========================================================
-- HU-53  Cierre operativo diario por tienda
--
-- cierre_diario
--   Un registro por tienda y fecha.
--   CERRADO   -> el dia quedo consolidado y bloqueado.
--   REABIERTO -> un Administrador lo reabrio con justificacion; puede
--                corregirse y volver a cerrarse (version + 1).
--   Sin registro = dia abierto.
--
-- cierre_diario_evento
--   Historial de cada cierre, reapertura y nuevo cierre.
--
-- Bloqueo: los triggers impiden registrar o modificar ventas manuales y
-- movimientos de inventario cuyo dia ya esta CERRADO en esa tienda. Esto
-- se aplica en la base, por lo que cubre todos los modulos.
-- ==========================================================

CREATE TABLE IF NOT EXISTS cierre_diario (
    id_cierre          BIGSERIAL PRIMARY KEY,
    id_tienda          BIGINT NOT NULL REFERENCES tienda(id_tienda),
    fecha              DATE NOT NULL,
    estado             VARCHAR(12) NOT NULL DEFAULT 'CERRADO',
    version            INTEGER NOT NULL DEFAULT 1,
    id_usuario_cierra  BIGINT NOT NULL REFERENCES usuario(id_usuario),
    fecha_cierre       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    observaciones      TEXT,
    resumen            JSONB NOT NULL,
    CONSTRAINT uq_cierre_diario_tienda_fecha UNIQUE (id_tienda, fecha),
    CONSTRAINT chk_cierre_diario_estado CHECK (estado IN ('CERRADO', 'REABIERTO'))
);

CREATE TABLE IF NOT EXISTS cierre_diario_evento (
    id_evento      BIGSERIAL PRIMARY KEY,
    id_cierre      BIGINT NOT NULL REFERENCES cierre_diario(id_cierre) ON DELETE CASCADE,
    accion         VARCHAR(12) NOT NULL,
    version        INTEGER NOT NULL,
    id_usuario     BIGINT NOT NULL REFERENCES usuario(id_usuario),
    fecha          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    justificacion  TEXT,
    observaciones  TEXT,
    CONSTRAINT chk_cierre_diario_evento_accion CHECK (accion IN ('CIERRE', 'REAPERTURA', 'RECIERRE'))
);

CREATE INDEX IF NOT EXISTS idx_cierre_diario_fecha ON cierre_diario (fecha DESC);
CREATE INDEX IF NOT EXISTS idx_cierre_diario_evento_cierre ON cierre_diario_evento (id_cierre, fecha);

-- ---------- Bloqueo de dias cerrados ----------

CREATE OR REPLACE FUNCTION verificar_dia_abierto(p_id_tienda BIGINT, p_fecha DATE)
RETURNS VOID AS $$
BEGIN
    IF p_id_tienda IS NULL OR p_fecha IS NULL THEN
        RETURN;
    END IF;

    IF EXISTS (
        SELECT 1 FROM cierre_diario
        WHERE id_tienda = p_id_tienda AND fecha = p_fecha AND estado = 'CERRADO'
    ) THEN
        RAISE EXCEPTION 'DIA_CERRADO|%|%',
            (SELECT nombre FROM tienda WHERE id_tienda = p_id_tienda),
            TO_CHAR(p_fecha, 'DD/MM/YYYY')
            USING ERRCODE = 'P0001';
    END IF;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION trg_movimiento_dia_abierto()
RETURNS TRIGGER AS $$
DECLARE
    v_fecha DATE;
    v_tienda BIGINT;
    v_ubicacion BIGINT;
BEGIN
    IF TG_OP = 'UPDATE' THEN
        -- Solo la confirmacion de un movimiento pendiente afecta el inventario del dia.
        IF NOT (OLD.estado = 'PENDIENTE' AND NEW.estado <> 'PENDIENTE') THEN
            RETURN NEW;
        END IF;
        v_fecha := (NOW() AT TIME ZONE 'America/Costa_Rica')::DATE;
    ELSE
        v_fecha := (COALESCE(NEW.fecha_movimiento, NOW()) AT TIME ZONE 'America/Costa_Rica')::DATE;
    END IF;

    FOREACH v_ubicacion IN ARRAY ARRAY[NEW.id_ubicacion_origen, NEW.id_ubicacion_destino] LOOP
        IF v_ubicacion IS NOT NULL THEN
            SELECT id_tienda INTO v_tienda FROM ubicacion_inventario WHERE id_ubicacion = v_ubicacion;
            PERFORM verificar_dia_abierto(v_tienda, v_fecha);
        END IF;
    END LOOP;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION trg_venta_manual_dia_abierto()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        PERFORM verificar_dia_abierto(OLD.id_tienda, (OLD.fecha AT TIME ZONE 'America/Costa_Rica')::DATE);
        RETURN OLD;
    END IF;

    PERFORM verificar_dia_abierto(NEW.id_tienda, (NEW.fecha AT TIME ZONE 'America/Costa_Rica')::DATE);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_movimiento_inventario_dia_abierto ON movimiento_inventario;
CREATE TRIGGER trg_movimiento_inventario_dia_abierto
    BEFORE INSERT OR UPDATE OF estado ON movimiento_inventario
    FOR EACH ROW EXECUTE FUNCTION trg_movimiento_dia_abierto();

DROP TRIGGER IF EXISTS trg_venta_manual_dia_abierto ON venta_manual;
CREATE TRIGGER trg_venta_manual_dia_abierto
    BEFORE INSERT OR UPDATE OR DELETE ON venta_manual
    FOR EACH ROW EXECUTE FUNCTION trg_venta_manual_dia_abierto();
