<?php

declare(strict_types=1);

namespace Training;

use PDO;

final class Database
{
    public static function connect(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->require('DB_HOST'),
            (int) $config->get('DB_PORT', '3306'),
            $config->require('DB_NAME'),
        );

        $pdo = new PDO($dsn, $config->require('DB_USER'), $config->require('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        // Strikter Modus unabhängig von der Hoster-Voreinstellung: ungültige ENUM-Werte, abgeschnittene Texte
        // und Nulldaten werden abgewiesen statt still verändert (AP-03). Zeitwerte werden in UTC geschrieben (Db::ts).
        $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION', time_zone = '+00:00'");

        return $pdo;
    }
}
