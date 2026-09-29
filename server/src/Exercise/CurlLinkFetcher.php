<?php

declare(strict_types=1);

namespace Training\Exercise;

/**
 * Linkprüfung über curl_multi (E-10, E-19): nur https auf Port 443, kein Ziel im internen Netz (jede aufgelöste
 * Adresse wird geprüft und für die Verbindung fest vorgegeben, damit ein zweiter DNS-Abruf nicht umlenken kann),
 * höchstens 3 Weiterleitungen (jede erneut geprüft), Antwort ≤ 64 kB, Timeout 5 s je Abruf, alle Adressen parallel.
 */
final class CurlLinkFetcher implements LinkFetcher
{
    public const MAX_BYTES = 65536;
    public const MAX_REDIRECTS = 3;

    public function __construct(private readonly string $userAgent = 'training-linkcheck', private readonly int $timeout = 5)
    {
    }

    public function fetchAll(array $urls): array
    {
        $result = [];
        $pending = [];
        foreach (array_values(array_unique($urls)) as $url) {
            $pending[$url] = $url; // Ausgangsadresse → aktuelle Adresse
        }
        for ($hop = 0; $hop <= self::MAX_REDIRECTS && $pending !== []; $hop++) {
            $next = [];
            foreach ($this->round($pending) as $origin => $answer) {
                if (is_array($answer)) { // Weiterleitung
                    if ($hop === self::MAX_REDIRECTS) {
                        $result[$origin] = 'zu viele Weiterleitungen';
                    } else {
                        $next[$origin] = $answer['location'];
                    }
                } else {
                    $result[$origin] = $answer;
                }
            }
            $pending = $next;
        }

        return $result;
    }

    /**
     * Eine Runde paralleler Abrufe.
     * @param array<string, string> $pending Ausgangsadresse → abzurufende Adresse
     * @return array<string, int|string|array{location: string}>
     */
    private function round(array $pending): array
    {
        $out = [];
        $multi = curl_multi_init();
        $handles = [];
        foreach ($pending as $origin => $url) {
            $target = self::target($url);
            if (is_string($target)) {
                $out[$origin] = $target;
                continue;
            }
            $ch = curl_init($url);
            if ($ch === false) {
                $out[$origin] = 'curl nicht verfügbar';
                continue;
            }
            $received = 0;
            curl_setopt_array($ch, [
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => $this->timeout,
                CURLOPT_RESOLVE => [$target['host'] . ':443:' . $target['ip']],
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_HTTPHEADER => ['Accept: text/html,application/json;q=0.9,*/*;q=0.8'],
                // Antwort nicht speichern, nach 64 kB abbrechen (der Status ist dann bekannt)
                CURLOPT_WRITEFUNCTION => static function ($ch, string $data) use (&$received): int {
                    $received += strlen($data);

                    return $received > self::MAX_BYTES ? 0 : strlen($data);
                },
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$origin] = $ch;
        }
        // Ergebniscodes je Abruf kommen bei curl_multi nur über curl_multi_info_read (curl_errno bleibt 0)
        $results = [];
        do {
            $status = curl_multi_exec($multi, $running);
            while (($info = curl_multi_info_read($multi)) !== false) {
                $results[spl_object_id($info['handle'])] = (int) $info['result'];
            }
            if ($running > 0) {
                curl_multi_select($multi, 0.2);
            }
        } while ($running > 0 && $status === CURLM_OK);
        while (($info = curl_multi_info_read($multi)) !== false) {
            $results[spl_object_id($info['handle'])] = (int) $info['result'];
        }

        foreach ($handles as $origin => $ch) {
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $errno = $results[spl_object_id($ch)] ?? CURLE_OPERATION_TIMEDOUT;
            if ($code >= 300 && $code < 400) {
                $location = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
                $out[$origin] = $location !== '' ? ['location' => $location] : $code;
            } elseif ($code > 0 && ($errno === 0 || $errno === CURLE_WRITE_ERROR)) {
                $out[$origin] = $code;
            } else {
                $out[$origin] = 'nicht erreichbar: ' . (curl_strerror($errno) ?? 'Fehler ' . $errno);
            }
            curl_multi_remove_handle($multi, $ch);
        }
        curl_multi_close($multi);

        return $out;
    }

    /**
     * Ziel prüfen: https, Port 443, Host auflösbar, keine Adresse im internen/reservierten Netz.
     * @return array{host: string, ip: string}|string Ziel oder Grund der Ablehnung
     */
    public static function target(string $url): array|string
    {
        $parts = parse_url($url);
        if ($parts === false || strtolower($parts['scheme'] ?? '') !== 'https' || !isset($parts['host'])) {
            return 'nur https-Adressen';
        }
        if (isset($parts['port']) && $parts['port'] !== 443) {
            return 'nur Port 443';
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'Zugangsdaten in der Adresse';
        }
        $host = strtolower(trim($parts['host'], '[]'));
        $ips = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : self::resolve($host);
        if ($ips === []) {
            return 'Host nicht auflösbar';
        }
        foreach ($ips as $ip) {
            if (!self::isPublic($ip)) {
                return 'Ziel im internen Netz gesperrt';
            }
        }

        return ['host' => $host, 'ip' => str_contains($ips[0], ':') ? '[' . $ips[0] . ']' : $ips[0]];
    }

    /** Öffentliche Adresse (FILTER_FLAG_GLOBAL_RANGE: ohne private, reservierte, Link-local, CGNAT, Doku-Netze); IPv4 in IPv6 gesperrt. */
    public static function isPublic(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false && stripos($ip, '::ffff:') !== 0;
    }

    /** @return list<string> IPv4- und IPv6-Adressen */
    private static function resolve(string $host): array
    {
        $ips = gethostbynamel($host) ?: [];
        $aaaa = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($aaaa as $r) {
            if (isset($r['ipv6'])) {
                $ips[] = (string) $r['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
