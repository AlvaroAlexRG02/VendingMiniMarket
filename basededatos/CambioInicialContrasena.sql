-- Obliga a las cuentas nuevas a cambiar la contraseña inicial al ingresar.
-- Ejecutar una vez en la base de datos del sistema.

ALTER TABLE public.usuario
    ADD COLUMN IF NOT EXISTS debe_cambiar_contrasena BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE public.usuario
    ADD COLUMN IF NOT EXISTS origen_cambio_contrasena VARCHAR(20) NOT NULL DEFAULT 'INICIAL';
