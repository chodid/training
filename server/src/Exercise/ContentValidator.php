<?php

declare(strict_types=1);

namespace Training\Exercise;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Prüft content_json einer Übung gegen server/schemas/exercise.json (AP-16, 4.3). Vom Server gesetzte Linkfelder
 * (embed, geprueft_am, status) werden bei der Eingabe verworfen und neu gesetzt: embed aus der URL (nur art video,
 * E-04), geprueft_am null und status ungeprueft bis zur Linkprüfung (E-10, LinkChecker).
 */
final class ContentValidator
{
    private readonly Validator $validator;

    public function __construct(private readonly string $schemaFile)
    {
        $this->validator = new Validator();
        $this->validator->setMaxErrors(10);
    }

    public static function default(): self
    {
        return new self(dirname(__DIR__, 2) . '/schemas/exercise.json');
    }

    /**
     * @param array<string, mixed> $content Eingabe (dekodiertes JSON)
     * @return array{content: array<string, mixed>, errors: list<string>} vorbereiteter Inhalt und Schemafehler
     */
    public function prepare(array $content): array
    {
        if (isset($content['links']) && is_array($content['links'])) {
            $links = [];
            foreach ($content['links'] as $link) {
                if (is_array($link)) {
                    unset($link['embed'], $link['geprueft_am'], $link['status']);
                    $url = is_string($link['url'] ?? null) ? trim($link['url']) : $link['url'] ?? null;
                    $link = [...$link, 'url' => $url,
                        'embed' => is_string($url) && ($link['art'] ?? null) === 'video' ? Catalog::embedUrl($url) : null,
                        'geprueft_am' => null, 'status' => 'ungeprueft'];
                }
                $links[] = $link;
            }
            $content['links'] = $links;
        }
        $content += ['links' => []];

        return ['content' => $content, 'errors' => $this->validate($content)];
    }

    /**
     * @param array<string, mixed> $content
     * @return list<string> Fehlermeldungen; leer = gültig
     */
    public function validate(array $content): array
    {
        $json = json_decode(json_encode($content === [] ? new \stdClass() : $content, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        $result = $this->validator->validate($json, (string) file_get_contents($this->schemaFile));
        if ($result->isValid()) {
            return [];
        }
        $messages = [];
        foreach ((new ErrorFormatter())->format($result->error(), true) as $pointer => $errors) {
            foreach ($errors as $error) {
                $messages[] = 'content' . ($pointer === '' ? '' : $pointer) . ': ' . $error;
            }
        }

        return $messages;
    }
}
