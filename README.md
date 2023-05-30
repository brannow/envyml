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

// ...

// env.yml loader announcement, this will disable dotenv loader
$_SERVER['APP_RUNTIME'] = \Brannow\Component\Envyml\SymfonyRuntime::class;

// set the custom APP_RUNTIME before this line!
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// ...

```

update the path to your env.yml location: 
```
putenv(ENV_FILE_PATH, ABSOLUTE_PATH)
```



## Best Practice

next update `index.php` and `bin/console`     
`index.php`:
```php
<?php

use App\Kernel;

// env.yml loader announcement, this will disable dotenv loader
$_SERVER['APP_RUNTIME'] = \Brannow\Component\Envyml\SymfonyRuntime::class;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

`bin/console`:
```php
#!/usr/bin/env php
<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

if (!is_file(dirname(__DIR__).'/vendor/autoload_runtime.php')) {
    throw new LogicException('Symfony Runtime is missing. Try running "composer require symfony/runtime".');
}

// env.yml loader announcement, this will disable dotenv loader
$_SERVER['APP_RUNTIME'] = \Brannow\Component\Envyml\SymfonyRuntime::class;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new Application($kernel);
};
```

simply add 
```php
$_SERVER['APP_RUNTIME'] = \Brannow\Component\Envyml\SymfonyRuntime::class;
```            
before
```php
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
```

