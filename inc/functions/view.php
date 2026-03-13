<?php
// inc/functions/view.php

// Escape padrão para HTML (equivalente a Laravel e() ) [web:743][web:749]
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', false);
}

// Formata moeda no padrão brasileiro R$ 1.234,56 usando number_format [web:747][web:750]
function money(float|int $value): string
{
    return number_format((float)$value, 2, ',', '.');
}

// Data simples d/m/Y [web:754][web:748]
function date_br(?string $date): string
{
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '—';
    }
    return date('d/m/Y', $ts);
}

// Data e hora d/m/Y H:i
function datetime_br(?string $date): string
{
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '—';
    }
    return date('d/m/Y H:i', $ts);
}
