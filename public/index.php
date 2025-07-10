<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Activer la mise en tampon de sortie pour éviter les erreurs "headers already sent"
// Cela garantit que toute sortie est mise en tampon avant l'envoi des en-têtes.
ob_start();

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
