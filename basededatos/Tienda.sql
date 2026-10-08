-- =========================================================
-- VendingMiniMarket
-- HU-16: Administración de ubicaciones (Tienda)
-- PostgreSQL / Supabase
-- =========================================================
-- La pantalla Tienda guardaba las ubicaciones solo en el
-- navegador (localStorage). Este script agrega a la tabla
-- tienda los campos que esa pantalla maneja y que aún no
-- existían, para guardarlos en la base de datos:
--
--   es_principal    ubicación principal (true) o secundaria (false)
--   observaciones   detalle operativo opcional
--   fecha_registro  fecha de creación de la ubicación
--
-- Ya existen en la tabla: nombre (único), tipo (TIENDA o
-- BODEGA) y estado (activa/inactiva).
--
-- La relación "abastecida por" no se guarda aquí: vive en
-- la tabla relacion_abastecimiento (HU-18).
-- =========================================================

BEGIN;


-- =========================================================
-- 1. CAMPOS NUEVOS
-- =========================================================
-- Las ubicaciones que ya existen (Ultrapark 1, Ultrapark 2 y
-- UltraLag) se marcan como principales, igual que en la
-- pantalla. Esto se hace solo la primera vez, para que volver
-- a ejecutar el script no pise cambios posteriores.
-- =========================================================

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name   = 'tienda'
          AND column_name  = 'es_principal'
    ) THEN
        ALTER TABLE public.tienda
            ADD COLUMN es_principal BOOLEAN NOT NULL DEFAULT FALSE;

        UPDATE public.tienda
        SET es_principal = TRUE;
    END IF;
END
$$;


ALTER TABLE public.tienda
    ADD COLUMN IF NOT EXISTS observaciones VARCHAR(500);

ALTER TABLE public.tienda
    ADD COLUMN IF NOT EXISTS fecha_registro TIMESTAMPTZ
        NOT NULL DEFAULT NOW();


COMMIT;
