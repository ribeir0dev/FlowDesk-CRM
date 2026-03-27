<?php

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money($value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('fd_allowed_locales')) {
    function fd_allowed_locales(): array
    {
        return ['pt-BR', 'en-US', 'es-ES'];
    }
}

if (!function_exists('fd_allowed_timezones')) {
    function fd_allowed_timezones(): array
    {
        return ['America/Sao_Paulo', 'America/New_York', 'Europe/Lisbon'];
    }
}

if (!function_exists('fd_preferred_locale')) {
    function fd_preferred_locale(): string
    {
        $locale = (string) ($_SESSION['user_locale'] ?? 'pt-BR');
        return in_array($locale, fd_allowed_locales(), true) ? $locale : 'pt-BR';
    }
}

if (!function_exists('fd_preferred_timezone')) {
    function fd_preferred_timezone(): string
    {
        $timezone = (string) ($_SESSION['user_timezone'] ?? 'America/Sao_Paulo');
        return in_array($timezone, fd_allowed_timezones(), true) ? $timezone : 'America/Sao_Paulo';
    }
}

if (!function_exists('fd_apply_runtime_preferences')) {
    function fd_apply_runtime_preferences(): void
    {
        static $applied = false;
        if ($applied) {
            return;
        }

        $timezone = fd_preferred_timezone();
        if (@date_default_timezone_set($timezone)) {
            $applied = true;
            return;
        }

        date_default_timezone_set('America/Sao_Paulo');
        $applied = true;
    }
}

if (!function_exists('fd_format_date')) {
    function fd_format_date($date): string
    {
        if (empty($date)) {
            return '-';
        }

        try {
            $value = $date instanceof DateTimeInterface ? $date : new DateTime((string) $date);
        } catch (Throwable $e) {
            return (string) $date;
        }

        $locale = fd_preferred_locale();

        return match ($locale) {
            'en-US' => $value->format('m/d/Y'),
            default => $value->format('d/m/Y'),
        };
    }
}

if (!function_exists('fd_format_datetime')) {
    function fd_format_datetime($date): string
    {
        if (empty($date)) {
            return '-';
        }

        try {
            $value = $date instanceof DateTimeInterface ? $date : new DateTime((string) $date);
        } catch (Throwable $e) {
            return (string) $date;
        }

        $locale = fd_preferred_locale();

        return match ($locale) {
            'en-US' => $value->format('m/d/Y h:i A'),
            default => $value->format('d/m/Y H:i'),
        };
    }
}

if (!function_exists('fd_format_month_year')) {
    function fd_format_month_year($date): string
    {
        if (empty($date)) {
            return '-';
        }

        try {
            $value = $date instanceof DateTimeInterface ? $date : new DateTime((string) $date);
        } catch (Throwable $e) {
            return (string) $date;
        }

        $locale = fd_preferred_locale();

        return match ($locale) {
            'en-US' => $value->format('m/Y'),
            default => $value->format('m/Y'),
        };
    }
}

if (!function_exists('date_br')) {
    function date_br($date): string
    {
        return fd_format_date($date);
    }
}

if (!function_exists('datetime_br')) {
    function datetime_br($date): string
    {
        return fd_format_datetime($date);
    }
}

fd_apply_runtime_preferences();
