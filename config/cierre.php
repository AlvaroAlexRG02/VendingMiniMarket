<?php

/**
 * Cierre operativo diario (HU-53) — mensaje amigable cuando la base de datos
 * rechaza registrar o modificar algo en un día ya cerrado.
 *
 * El bloqueo lo aplican los triggers de basededatos/CierreDiario.sql; este
 * archivo solo traduce su error a un mensaje claro para el usuario.
 */

function mensajeDiaCerrado(Throwable $e): ?string
{
    if (!preg_match('/DIA_CERRADO\|([^|]*)\|(\d{2}\/\d{2}\/\d{4})/u', $e->getMessage(), $m)) {
        return null;
    }

    return "El día {$m[2]} de la tienda {$m[1]} ya fue cerrado y no admite más cambios. "
        . 'Solicite a un Administrador que reabra el cierre para continuar.';
}
