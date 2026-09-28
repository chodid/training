<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Http\Request;
use Training\Http\Response;

/** S6 Verlauf – Platzhalter bis AP-09, damit die Navigation vollständig ist (Branding B-01/B-02). */
final class HistoryController extends AppController
{
    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }

        return $this->page('history', 'Verlauf', 'verlauf');
    }
}
