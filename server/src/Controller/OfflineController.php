<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Http\Request;
use Training\Http\Response;

/**
 * Offline-Fähigkeit (D-45): frisches CSRF-Token für gepufferte Eingaben. Der Service Worker sendet Eingaben, die ohne
 * Netz erfasst wurden, später – ggf. nach neuer Anmeldung, wenn das Token im gepufferten Formular nicht mehr gilt.
 * Nur gleiche Herkunft kann die Antwort lesen (kein CORS).
 */
final class OfflineController extends AppController
{
    public function token(Request $request): Response
    {
        $session = $this->app->sessions()->current($request);
        if ($session === null) {
            return Response::json(401, ['fehler' => 'Nicht angemeldet.']);
        }

        return Response::json(200, ['csrf' => $session->csrfToken()]);
    }
}
