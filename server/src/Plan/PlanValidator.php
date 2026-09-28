<?php

declare(strict_types=1);

namespace Training\Plan;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Prüft plan_json und actual_json einer Einheit gegen die JSON-Schemata in server/schemas/ (Konzept 7.1, AP-03).
 * Zuordnung: kraft, haltung, mobilitaet → kraft_oder_haltung; klettern; ausdauer; ruhe (leer oder nur Notiz).
 */
final class PlanValidator
{
    public const TYPES = ['ausdauer', 'kraft', 'klettern', 'haltung', 'mobilitaet', 'ruhe'];

    private const SCHEMA_BY_TYPE = [
        'ausdauer' => 'ausdauer',
        'kraft' => 'kraft_oder_haltung',
        'haltung' => 'kraft_oder_haltung',
        'mobilitaet' => 'kraft_oder_haltung',
        'klettern' => 'klettern',
        'ruhe' => 'ruhe',
    ];

    private readonly Validator $validator;

    public function __construct(private readonly string $schemaDir)
    {
        $this->validator = new Validator();
        $this->validator->setMaxErrors(10);
    }

    public static function default(): self
    {
        return new self(dirname(__DIR__, 2) . '/schemas');
    }

    /**
     * @param mixed $plan dekodiertes JSON (Arrays) oder null
     * @return list<string> Fehlermeldungen; leer = gültig
     */
    public function validatePlan(string $type, mixed $plan): array
    {
        return $this->validate('plan', $type, $plan);
    }

    /** @return list<string> */
    public function validateActual(string $type, mixed $actual): array
    {
        return $this->validate('actual', $type, $actual);
    }

    /** @return list<string> */
    private function validate(string $kind, string $type, mixed $data): array
    {
        $schema = self::SCHEMA_BY_TYPE[$type] ?? null;
        if ($schema === null) {
            return ['Unbekannter Einheitentyp: ' . $type];
        }
        if ($data === null) {
            // Ruhetage und noch nicht erfasste Ist-Werte dürfen leer sein; andere Pläne brauchen Inhalt.
            return $kind === 'actual' || $type === 'ruhe' ? [] : [$kind . '_json fehlt für Typ ' . $type . '.'];
        }

        $file = $this->schemaDir . '/' . $kind . '-' . $schema . '.json';
        $json = json_decode(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        $result = $this->validator->validate(self::objectify($json, $data), (string) file_get_contents($file));
        if ($result->isValid()) {
            return [];
        }

        $messages = [];
        foreach ((new ErrorFormatter())->format($result->error(), true) as $pointer => $errors) {
            foreach ($errors as $error) {
                $messages[] = ($pointer === '' ? '/' : $pointer) . ': ' . $error;
            }
        }

        return $messages;
    }

    /** Leere PHP-Arrays aus assoziativem Kontext bleiben Objekte (json_decode liefert [] sonst als Liste). */
    private static function objectify(mixed $json, mixed $original): mixed
    {
        if ($original === [] && is_array($json)) {
            return new \stdClass();
        }

        return $json;
    }
}
