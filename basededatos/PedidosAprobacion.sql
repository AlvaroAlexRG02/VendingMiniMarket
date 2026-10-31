-- ==========================================================
-- Flujo de aprobación de pedidos a proveedor
--
-- estado_aprobacion:
--   BORRADOR    -> el Dependiente lo registró/editó y aún no lo envía.
--   POR_APROBAR -> enviado; lo reciben Encargado / Administrador.
--   APROBADO    -> aprobado; ya se puede recibir mercadería.
--
-- pedido.requiere_aprobacion se mantiene sincronizado desde la API
-- (TRUE mientras estado_aprobacion <> 'APROBADO'), porque recepciones
-- e inventario ya filtran por esa columna.
-- ==========================================================

ALTER TABLE pedido
    ADD COLUMN IF NOT EXISTS estado_aprobacion VARCHAR(20) NOT NULL DEFAULT 'APROBADO';

ALTER TABLE pedido
    DROP CONSTRAINT IF EXISTS chk_pedido_estado_aprobacion;

ALTER TABLE pedido
    ADD CONSTRAINT chk_pedido_estado_aprobacion
    CHECK (estado_aprobacion IN ('BORRADOR', 'POR_APROBAR', 'APROBADO'));

-- Pedidos que ya estaban marcados como pendientes de aprobación.
UPDATE pedido
SET estado_aprobacion = 'POR_APROBAR'
WHERE requiere_aprobacion = TRUE;
