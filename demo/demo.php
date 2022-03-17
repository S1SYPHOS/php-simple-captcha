<?php

require_once __DIR__.'/../vendor/autoload.php';

use SimpleCaptcha\Builder;

Builder::create()
    ->build()
    ->save('out.jpg')
;
