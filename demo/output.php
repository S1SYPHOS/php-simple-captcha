<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleCaptcha\Builder;
use SimpleCaptcha\Helpers\Mime;

# Render captcha image (using correct header)
header('Content-type: ' . Mime::fromExtension('jpg'));
Builder::create()->build()->output();
