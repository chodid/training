<?php

declare(strict_types=1);

namespace Training\Review;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Prüft content_json von Revision, Blockbilanz und Zielklärung gegen server/schemas/review-<kind>.json
 * (AP-15, docs/konzept/blockbilanz.md 4.3; analog PlanValidator). Längen: Listeneinträge ≤ 500 Zeichen,
 * rationale/grund/empfehlung/befund ≤ 1 500, Listen ≤ 20 Einträge.
 */
final class ReviewValidator
{
    public const KINDS = ['revision', 'bilanz', 'zielklaerung'];
    public const STATUSES = ['entwurf', 'bestaetigt'];
    public const SUMMARY_MAX = 255;
    public const REASON_MAX = 255;

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

    public function schemaFile(string $kind): string
    {
        if (!in_array($kind, self::KINDS, true)) {
            throw new \InvalidArgumentException('Unbekannte Art: ' . $kind);
        }

        return $this->schemaDir . '/review-' . $kind . '.json';
    }

    /**
     * @param mixed $content dekodiertes JSON (Objekt als assoziatives Array)
     * @return list<string> Fehlermeldungen mit Pfad (z. B. „content/ziele/0: …“); leer = gültig
     */
    public function validate(string $kind, mixed $content): array
    {
        if (!is_array($content) || ($content !== [] && array_is_list($content))) {
            return ['content: muss ein Objekt sein'];
        }
        $json = json_decode(json_encode($content === [] ? new \stdClass() : $content, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        $result = $this->validator->validate($json, (string) file_get_contents($this->schemaFile($kind)));
        if ($result->isValid()) {
            return $kind === 'bilanz' ? self::bilanzRules($content) : [];
        }
        $messages = [];
        foreach ((new ErrorFormatter())->format($result->error(), true) as $pointer => $errors) {
            foreach ($errors as $error) {
                $messages[] = 'content' . ($pointer === '' ? '' : $pointer) . ': ' . $error;
            }
        }

        return $messages;
    }

    /**
     * Regeln, die das Schema nicht ausdrückt: Zeitraum von ≤ bis.
     * @param array<string, mixed> $content
     * @return list<string>
     */
    private static function bilanzRules(array $content): array
    {
        $z = $content['zeitraum'];

        return $z['bis'] < $z['von'] ? ['content/zeitraum: bis liegt vor von'] : [];
    }
}
