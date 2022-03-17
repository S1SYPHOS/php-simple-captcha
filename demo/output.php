<?php

require_once __DIR__.'/../vendor/autoload.php';

use SimpleCaptcha\Builder;

header('Content-type: image/jpeg');

Builder::create()->build()->output();
