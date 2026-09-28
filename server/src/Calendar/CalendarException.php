<?php

declare(strict_types=1);

namespace Training\Calendar;

/** Fehler beim Schreiben in den CalDAV-Kalender (Netz, Anmeldung, Server); Meldung ohne Zugangsdaten. */
final class CalendarException extends \RuntimeException
{
}
