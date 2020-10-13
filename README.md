# envyml
symfony/dotenv adapter to load env.yml file instead

the env file will be cached with the symfony filecache adaper (the modified time of the `env.yml` will be check every request, updates will immediately invalidate the cache)

the yaml syntax will be reformed to match the flat Environment structure.
nested element keys will be concated via ' _ ' (underscore). (numeric lists are KEY_0, KEY_1)

for example: 
```yaml
Vendor:
  SSO:
    Secret: superSecret
    Provider: stuff
   Captcha: 
    secret: otherSecret
    Domain: http://oldDomain
    
Vendor_Captcha:
  Domain: https://domain
```
will be result in system env:
```php
Vendor_SSO_Secret = superSecret
Vendor_SSO_Provider = stuff
Vendor_Captcha_Secret = otherSecret
Vendor_Captcha_Domain = https://domain
```

## Installation 

composer: `composer require brannow/envyml`

## Post Integration

Disclamer: all code shown is based on symfony 5.0 (other symfony versions may need updates to run)

it is required to remove the dotenv code snippets from the `index.php` and `bin/console`


```php
use Brannow\Component\Envyml\Envyml;

// ...

if (is_array($env = @include dirname(__DIR__).'/.env.local.php') && (!isset($env['APP_ENV']) || ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env['APP_ENV']) === $env['APP_ENV'])) {
    foreach ($env as $k => $v) {
        $_ENV[$k] = $_ENV[$k] ?? (isset($_SERVER[$k]) && 0 !== strpos($k, 'HTTP_') ? $_SERVER[$k] : $v);
    }
} elseif (!class_exists(Envyml::class)) {
    throw new RuntimeException('Please run "composer require brannow/envyml" to load the "env.yml" files configuring the application.');
} else {
    $envFilePath = getenv('ENV_FILE_PATH') ?: __DIR__ . '/../../env.yml';
    (new Envyml())->load($envFilePath);
}

// ...

```

update the path to your env.yml location: 
```
$envFilePath = getenv('ENV_FILE_PATH') ?: __DIR__ . '/../../env.yml';
```



## Best Practice

create `config/bootstrap.php`
this will handle the autoload and env handling of the entire application
```php
<?php

use Brannow\Component\Envyml\Envyml;
    
require dirname(__DIR__).'/vendor/autoload.php';

if (is_array($env = @include dirname(__DIR__).'/.env.local.php') && (!isset($env['APP_ENV']) || ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env['APP_ENV']) === $env['APP_ENV'])) {
    foreach ($env as $k => $v) {
        $_ENV[$k] = $_ENV[$k] ?? (isset($_SERVER[$k]) && 0 !== strpos($k, 'HTTP_') ? $_SERVER[$k] : $v);
    }
} elseif (!class_exists(Envyml::class)) {
    throw new RuntimeException('Please run "composer require brannow/envyml" to load the "env.yml" files configuring the application.');
} else {
    $envFilePath = getenv('ENV_FILE_PATH') ?: __DIR__ . '/../../env.yml';
    (new Envyml())->load($envFilePath);
}

$_SERVER += $_ENV;
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'prod';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
```

next update `index.php` and `bin/console`     
`index.php`:
```php
<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

$env = $_SERVER['APP_ENV'] ?? 'prod';
$debug = $_SERVER['APP_DEBUG'] ?? ('prod' !== $env);

if ($debug) {
    umask(0000);
    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}
$r = [];
$kernel = new Kernel($env, (bool)$debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
```

`bin/console`:
```php
#!/usr/bin/env php
<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\ErrorHandler\Debug;

if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
    echo 'Warning: The console should be invoked via the CLI version of PHP, not the '.PHP_SAPI.' SAPI'.PHP_EOL;
}

set_time_limit(0);

require dirname(__DIR__).'/config/bootstrap.php';

if (!class_exists(Application::class)) {
    throw new LogicException('You need to add "symfony/framework-bundle" as a Composer dependency.');
}

$input = new ArgvInput();
if (null !== $env = $input->getParameterOption(['--env', '-e'], null, true)) {
    putenv('APP_ENV='.$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $env);
}

if ($input->hasParameterOption('--no-debug', true)) {
    putenv('APP_DEBUG='.$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    if (class_exists(Debug::class)) {
        Debug::enable();
    }
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$application = new Application($kernel);
$application->run($input);
```

