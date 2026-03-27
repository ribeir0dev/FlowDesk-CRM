<?php

if (!function_exists('gerarTokenPublico')) {
    function gerarTokenPublico(int $length = 64): string
    {
        $bytesLength = max(1, (int) ($length / 2));

        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($bytesLength));
        }

        $bytes = openssl_random_pseudo_bytes($bytesLength);
        return bin2hex($bytes);
    }
}
