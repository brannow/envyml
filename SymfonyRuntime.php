<?php

namespace Brannow\Component\Envyml;

use Symfony\Component\Runtime\SymfonyRuntime as DefaultRuntime;
use Symfony\Component\Stopwatch\Stopwatch;

class SymfonyRuntime extends DefaultRuntime
{
    public function __construct(array $options = [])
    {
        if (isset($_ENV['ENV_FILE_PATH'])) {
            $envFilePath = $_ENV['ENV_FILE_PATH'];
        } else {
            // default is root directory (one dir up the entrypoint script e.g. index.php or console)
            $envFilePath = dirname($_SERVER['SCRIPT_NAME']).'/../env.yml';
        }
        (new Envyml(true))->overload($envFilePath);

        $options['disable_dotenv'] = true;
        parent::__construct($options);
    }
}