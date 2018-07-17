<?php

require __DIR__ . '/vendor/autoload.php';

use EastWood\Log\Logger;

Logger::set([
    'path' => '/data1/logs',
    'rotate' => 'daily',
    'application' => 'web',
    'format' => '{date_rfc}||{timestamp}||{application}||{verb}||{host}||{uri}||{level}||{tag}||{message}'
]);

//var_dump( Logger::getVariable() );
Logger::info('我是标签', '我是内容');