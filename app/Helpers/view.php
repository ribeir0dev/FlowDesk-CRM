<?php
// FlowDesk view helpers

// Escape padrao para HTML.
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', false);
}

// Formata moeda no padrao brasileiro: R$ 1.234,56.
function money(float|int $value): string
{
    return number_format((float)$value, 2, ',', '.');
}

// Data simples d/m/Y.
function date_br(?string $date): string
{
    if (empty($date)) {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }
    return date('d/m/Y', $ts);
}

// Data e hora d/m/Y H:i
function datetime_br(?string $date): string
{
    if (empty($date)) {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }
    return date('d/m/Y H:i', $ts);
}


