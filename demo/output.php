<?php

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-type: image/jpeg');

SimpleCaptcha\Builder::create()->build()->output();
