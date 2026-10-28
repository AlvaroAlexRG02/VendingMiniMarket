<?php

function proximaEjecucionResumenInventario(
    string $frecuencia,
    int $diaSemana,
    string $hora,
    ?DateTimeImmutable $desde = null
): DateTimeImmutable {
    $zona = new DateTimeZone('America/Costa_Rica');
    $ahora = ($desde ?? new DateTimeImmutable('now', $zona))->setTimezone($zona);
    $horaProgramada = DateTimeImmutable::createFromFormat('!H:i', $hora, $zona);
    if (!$horaProgramada || $horaProgramada->format('H:i') !== $hora) {
        throw new InvalidArgumentException('La hora programada no es válida.');
    }

    $candidato = $ahora->setTime(
        (int)$horaProgramada->format('H'),
        (int)$horaProgramada->format('i')
    );
    if ($frecuencia === 'DIARIA') {
        return $candidato > $ahora ? $candidato : $candidato->modify('+1 day');
    }

    $diasHasta = ($diaSemana - (int)$ahora->format('N') + 7) % 7;
    $candidato = $candidato->modify('+' . $diasHasta . ' days');
    return $candidato > $ahora ? $candidato : $candidato->modify('+7 days');
}
