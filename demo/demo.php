<?php

require_once __DIR__ . '/../vendor/autoload.php';

SimpleCaptcha\Builder::create()->build()->save('out.jpg');
