# EastWood/Log


Installation
------------
- The minimum PHP 5.4 version required
- It works best with PHP 7

```
composer require eastwood/log
```

Log level
------------
- info
- warning
- error
- debug
- verbose

Log format variable
------------
- message
- date_rfc (default)
- unixtime
- application
- verb
- host
- uri
- tag
- level
- ...

You can call method Logger::getVariable() show varabile list



Example
------------

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use EastWood\Log\Logger;

Logger::set([
    'path' => '/data1/logs',
    'rotate' => 'daily',
    'application' => 'web',
    'format' => '{message}||{<date>(Ymd)}'
]);

//var_dump( Logger::getVariable() );
Logger::info('tag', 'message');


```