-- Ejecutar una sola vez en Supabase SQL Editor, después del SQL anterior de importaciones.
-- Conserva el final ORIGINAL solicitado en Nayax separado de la fecha efectiva de ventas.
ALTER TABLE public.importacion_nayax
  ADD COLUMN IF NOT EXISTS periodo_fin_reporte DATE;
UPDATE public.importacion_nayax
SET periodo_fin_reporte = periodo_fin
WHERE periodo_fin_reporte IS NULL;
