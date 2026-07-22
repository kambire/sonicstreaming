<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Cifrado simetrico (AES-256-CBC) para datos sensibles como la
 * contrasena de administracion de cada instancia Shoutcast.
 * La clave proviene de APP_KEY en el .env.
 */
final class Crypto
{
    private const CIPHER = 'aes-256-cbc';

    private static function key(): string
    {
        $key = (string) env('APP_KEY', 'insecure-default-key');
        return hash('sha256', $key, true); // 32 bytes
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $cipher = openssl_encrypt($plain, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public static function decrypt(string $encoded): string
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false) {
            return '';
        }
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        if (strlen($raw) <= $ivLen) {
            return '';
        }
        $iv = substr($raw, 0, $ivLen);
        $cipher = substr($raw, $ivLen);
        $plain = openssl_decrypt($cipher, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }
}
