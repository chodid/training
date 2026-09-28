<?php

declare(strict_types=1);

namespace Training\Backup;

/**
 * Verschlüsselung kompatibel zu `openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -md sha256` (D-18):
 * Datei = "Salted__" + 8 Byte Salz + AES-256-CBC(PKCS#7); Schlüssel und IV = PBKDF2-HMAC-SHA256(Passwort, Salz, ITERATIONS, 48 Byte).
 * Entschlüsseln ohne dieses Programm:
 *   openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -md sha256 -in datei.sql.gz.enc -out datei.sql.gz
 * Integrität wird nach dem Entschlüsseln über die gzip-Prüfsumme erkannt (D-18).
 */
final class Encryptor
{
    public const ITERATIONS = 200000;
    public const MIN_PASSWORD_LENGTH = 16;
    private const MAGIC = 'Salted__';

    public static function encrypt(string $data, string $password): string
    {
        self::checkPassword($password);
        $salt = random_bytes(8);
        [$key, $iv] = self::derive($password, $salt);
        $cipher = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new BackupException('Verschlüsselung fehlgeschlagen.');
        }

        return self::MAGIC . $salt . $cipher;
    }

    public static function decrypt(string $data, string $password): string
    {
        if (strlen($data) < 32 || !str_starts_with($data, self::MAGIC)) {
            throw new BackupException('Keine gültige verschlüsselte Backup-Datei.');
        }
        [$key, $iv] = self::derive($password, substr($data, 8, 8));
        $plain = openssl_decrypt(substr($data, 16), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new BackupException('Entschlüsselung fehlgeschlagen (Passwort falsch?).');
        }

        return $plain;
    }

    private static function checkPassword(string $password): void
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new BackupException('BACKUP_PASSWORD fehlt oder ist kürzer als 16 Zeichen.');
        }
    }

    /** @return array{0: string, 1: string} */
    private static function derive(string $password, string $salt): array
    {
        $bytes = hash_pbkdf2('sha256', $password, $salt, self::ITERATIONS, 48, true);

        return [substr($bytes, 0, 32), substr($bytes, 32, 16)];
    }
}
