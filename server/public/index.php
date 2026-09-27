<?php

declare(strict_types=1);

ini_set('display_errors', '0');

require dirname(__DIR__) . '/vendor/autoload.php';

(new Training\App(dirname(__DIR__)))->run();
