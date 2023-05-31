<?php

namespace Brannow\Component\Envyml;

use Symfony\Component\Runtime\SymfonyRuntime as DefaultRuntime;
use Symfony\Component\Stopwatch\Stopwatch;

class SymfonyRuntime extends DefaultRuntime
{
    public function __construct(array $options = [])
    {
        $envFilePath =  $_ENV['ENV_FILE_PATH'] ?: __DIR__ . '/../../env.yml';
        (new Envyml(true))->overload($envFilePath);

        $options['disable_dotenv'] = true;
        parent::__construct($options);
    }
}