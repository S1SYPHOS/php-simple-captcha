<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleCaptcha\Builder;
use SimpleCaptcha\Helpers\Mime;

# Start session & store phrase in session,
# so you can check it after form submission
session_start();
$captcha = Builder::create();
$_SESSION['phrase'] = $captcha->phrase;

# Render captcha image (using correct header)
header('Content-type: ' . Mime::fromExtension('jpg'));
$captcha->build()->output();
