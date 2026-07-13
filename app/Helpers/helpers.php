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

if (!function_exists('fd_base64url_encode')) {
    function fd_base64url_encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

if (!function_exists('fd_base64url_decode')) {
    function fd_base64url_decode(string $value): string|false
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}

if (!function_exists('fd_document_secret')) {
    function fd_document_secret(): string
    {
        $secret = (string) (getenv('APP_KEY') ?: getenv('APP_SECRET') ?: '');
        return $secret !== '' ? $secret : hash('sha256', __DIR__ . '|flowdesk-documentos');
    }
}

if (!function_exists('fd_crypto_key')) {
    function fd_crypto_key(): string
    {
        return hash('sha256', fd_document_secret(), true);
    }
}

if (!function_exists('fd_encrypt_secret')) {
    function fd_encrypt_secret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $iv = random_bytes(16);
        $cipher = openssl_encrypt($value, 'aes-256-cbc', fd_crypto_key(), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new RuntimeException('Nao foi possivel criptografar o segredo.');
        }

        $mac = hash_hmac('sha256', $iv . $cipher, fd_crypto_key(), true);

        return 'v1.' . fd_base64url_encode($iv) . '.' . fd_base64url_encode($cipher) . '.' . fd_base64url_encode($mac);
    }
}

if (!function_exists('fd_decrypt_secret')) {
    function fd_decrypt_secret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parts = explode('.', $value);
        if (count($parts) !== 4 || $parts[0] !== 'v1') {
            return null;
        }

        $iv = fd_base64url_decode($parts[1]);
        $cipher = fd_base64url_decode($parts[2]);
        $mac = fd_base64url_decode($parts[3]);
        if ($iv === false || $cipher === false || $mac === false) {
            return null;
        }

        $expected = hash_hmac('sha256', $iv . $cipher, fd_crypto_key(), true);
        if (!hash_equals($expected, $mac)) {
            return null;
        }

        $plain = openssl_decrypt($cipher, 'aes-256-cbc', fd_crypto_key(), OPENSSL_RAW_DATA, $iv);

        return $plain === false ? null : $plain;
    }
}

if (!function_exists('fd_financeiro_documento_token')) {
    function fd_financeiro_documento_token(array $payload): string
    {
        $payload['workspace_id'] = (int) ($payload['workspace_id'] ?? (function_exists('fd_current_workspace_id') ? (fd_current_workspace_id() ?? 0) : 0));
        $payload['exp'] = (int) ($payload['exp'] ?? strtotime('+30 days'));

        ksort($payload);
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return '';
        }

        $body = fd_base64url_encode($json);
        $signature = hash_hmac('sha256', $body, fd_document_secret());

        return $body . '.' . $signature;
    }
}

if (!function_exists('fd_financeiro_documento_payload')) {
    function fd_financeiro_documento_payload(string $token): ?array
    {
        if (!str_contains($token, '.')) {
            return null;
        }

        [$body, $signature] = explode('.', $token, 2);
        $expected = hash_hmac('sha256', $body, fd_document_secret());
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $json = fd_base64url_decode($body);
        if ($json === false) {
            return null;
        }

        $payload = json_decode($json, true);
        if (!is_array($payload) || (int) ($payload['workspace_id'] ?? 0) <= 0) {
            return null;
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }
}

if (!function_exists('fd_public_document_code')) {
    function fd_public_document_code(int $length = 10): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $max = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }
}

if (!function_exists('fd_public_document_code_hash')) {
    function fd_public_document_code_hash(string $code): string
    {
        return hash_hmac('sha256', strtoupper(trim($code)), fd_document_secret());
    }
}

if (!function_exists('fd_financeiro_public_document_url')) {
    function fd_financeiro_public_document_url(string $base, string $token): ?string
    {
        global $pdo;

        if (!($pdo instanceof PDO)) {
            require __DIR__ . '/../../config/db.php';
        }

        $payload = fd_financeiro_documento_payload($token);
        if (!$payload) {
            return null;
        }

        $tokenHash = hash_hmac('sha256', $token, fd_document_secret());

        try {
            $existing = $pdo->prepare('
                SELECT codigo
                FROM public_document_links
                WHERE token_hash = ?
                  AND revoked_at IS NULL
                  AND expires_at > NOW()
                LIMIT 1
            ');
            $existing->execute([$tokenHash]);
            $code = (string) ($existing->fetchColumn() ?: '');
            if ($code !== '') {
                return rtrim($base, '/') . '/cobranca/' . rawurlencode($code);
            }

            $insert = $pdo->prepare('
                INSERT INTO public_document_links
                    (workspace_id, codigo, codigo_hash, token_hash, token_encrypted, tipo, expires_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))
            ');

            for ($attempt = 0; $attempt < 8; $attempt++) {
                $code = fd_public_document_code(12);

                try {
                    $insert->execute([
                        (int) $payload['workspace_id'],
                        $code,
                        fd_public_document_code_hash($code),
                        $tokenHash,
                        fd_encrypt_secret($token),
                        (string) ($payload['tipo'] ?? 'cobranca'),
                        (int) $payload['exp'],
                    ]);

                    return rtrim($base, '/') . '/cobranca/' . rawurlencode($code);
                } catch (PDOException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }
                }
            }
        } catch (Throwable $exception) {
            error_log('[FlowDesk][PublicDocumentLink] ' . $exception->getMessage());
        }

        return null;
    }
}

if (!function_exists('fd_financeiro_public_document_payload')) {
    function fd_financeiro_public_document_payload(string $code): ?array
    {
        global $pdo;

        $code = strtoupper(trim($code));
        if (!preg_match('/^[2-9A-HJ-NP-Z]{8,16}$/', $code)) {
            return null;
        }

        if (!($pdo instanceof PDO)) {
            require __DIR__ . '/../../config/db.php';
        }

        try {
            $stmt = $pdo->prepare('
                SELECT id, token_encrypted
                FROM public_document_links
                WHERE codigo_hash = ?
                  AND revoked_at IS NULL
                  AND expires_at > NOW()
                LIMIT 1
            ');
            $stmt->execute([fd_public_document_code_hash($code)]);
            $link = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$link) {
                return null;
            }

            $token = fd_decrypt_secret((string) $link['token_encrypted']);
            if ($token === null || $token === '') {
                return null;
            }

            $payload = fd_financeiro_documento_payload($token);
            if (!$payload) {
                return null;
            }

            $update = $pdo->prepare('
                UPDATE public_document_links
                SET access_count = access_count + 1,
                    last_accessed_at = NOW()
                WHERE id = ?
                LIMIT 1
            ');
            $update->execute([(int) $link['id']]);

            return $payload;
        } catch (Throwable $exception) {
            error_log('[FlowDesk][PublicDocumentResolve] ' . $exception->getMessage());
            return null;
        }
    }
}

if (!function_exists('fd_financeiro_documento_url')) {
    function fd_financeiro_documento_url(string $base, array $payload): string
    {
        $token = fd_financeiro_documento_token($payload);
        $publicUrl = fd_financeiro_public_document_url($base, $token);
        if ($publicUrl !== null) {
            return $publicUrl;
        }

        return rtrim($base, '/') . '/financeiro/gerar-cobranca?token=' . rawurlencode($token);
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

if (!function_exists('fd_relative_time')) {
    function fd_relative_time($date): string
    {
        if (empty($date)) {
            return '-';
        }

        try {
            $event = $date instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($date) : new DateTimeImmutable((string) $date);
            $now = new DateTimeImmutable('now');
        } catch (Throwable $e) {
            return fd_format_datetime($date);
        }

        $seconds = max(0, $now->getTimestamp() - $event->getTimestamp());

        if ($seconds < 60) {
            return 'agora';
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes === 1 ? '1 minuto atras' : $minutes . ' minutos atras';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours === 1 ? '1 hora atras' : $hours . ' horas atras';
        }

        $days = intdiv($hours, 24);
        if ($days < 30) {
            return $days === 1 ? '1 dia atras' : $days . ' dias atras';
        }

        $months = intdiv($days, 30);
        if ($months < 12) {
            return $months === 1 ? '1 mes atras' : $months . ' meses atras';
        }

        $years = intdiv($months, 12);
        return $years === 1 ? '1 ano atras' : $years . ' anos atras';
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

if (!function_exists('fd_sanitize_task_rich_text')) {
    function fd_sanitize_task_rich_text(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        if ($html === '') {
            return '';
        }

        $allowedTags = [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
            'h1', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote',
            'code', 'pre', 'span', 'a'
        ];

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<!DOCTYPE html><html><body>' . $html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body) {
            return strip_tags($html, '<p><br><strong><b><em><i><u><s><h1><h2><h3><ul><ol><li><blockquote><code><pre><span><a>');
        }

        $sanitizeNode = function (DOMNode $node) use (&$sanitizeNode, $allowedTags, $doc): void {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($node->nodeName);
                if (!in_array($tag, $allowedTags, true)) {
                    $fragment = $doc->createDocumentFragment();
                    while ($node->firstChild) {
                        $child = $node->removeChild($node->firstChild);
                        $fragment->appendChild($child);
                    }
                    $node->parentNode?->replaceChild($fragment, $node);
                    return;
                }

                if ($node->hasAttributes()) {
                    $toRemove = [];
                    foreach (iterator_to_array($node->attributes) as $attribute) {
                        $attrName = strtolower($attribute->nodeName);
                        $attrValue = $attribute->nodeValue ?? '';
                        $remove = true;

                        if ($tag === 'a' && in_array($attrName, ['href', 'target', 'rel'], true)) {
                            if ($attrName === 'href') {
                                $href = trim($attrValue);
                                if (
                                    str_starts_with($href, 'http://') ||
                                    str_starts_with($href, 'https://') ||
                                    str_starts_with($href, 'mailto:') ||
                                    str_starts_with($href, '#')
                                ) {
                                    $remove = false;
                                }
                            } elseif ($attrName === 'target') {
                                $node->setAttribute('target', '_blank');
                                $remove = false;
                            } elseif ($attrName === 'rel') {
                                $node->setAttribute('rel', 'noopener noreferrer');
                                $remove = false;
                            }
                        }

                        if (in_array($tag, ['p', 'span', 'h1', 'h2', 'h3', 'li'], true) && $attrName === 'style') {
                            if (preg_match('/color\s*:\s*([^;]+)/i', $attrValue, $matches)) {
                                $color = trim($matches[1]);
                                if (preg_match('/^(#[0-9a-fA-F]{3,8}|rgb[a]?\([^)]+\)|hsl[a]?\([^)]+\)|[a-zA-Z]+)$/', $color)) {
                                    $node->setAttribute('style', 'color: ' . $color . ';');
                                    $remove = false;
                                }
                            }
                        }

                        if ($tag === 'ul' && $attrName === 'data-checked') {
                            $node->setAttribute('data-checked', $attrValue === 'true' ? 'true' : 'false');
                            $remove = false;
                        }

                        if ($remove) {
                            $toRemove[] = $attribute->nodeName;
                        }
                    }

                    foreach ($toRemove as $attributeName) {
                        $node->removeAttribute($attributeName);
                    }
                }
            }

            foreach (iterator_to_array($node->childNodes) as $child) {
                $sanitizeNode($child);
            }
        };

        foreach (iterator_to_array($body->childNodes) as $child) {
            $sanitizeNode($child);
        }

        $output = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $output .= $doc->saveHTML($child);
        }

        return trim($output);
    }
}

if (!function_exists('fd_task_preview_text')) {
    function fd_task_preview_text(?string $html, int $limit = 180): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)) ?? '');
        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1)) . '...';
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
