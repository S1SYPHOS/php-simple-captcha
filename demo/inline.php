<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleCaptcha\Builder;

$captcha = Builder::create()->build();

echo '<h1>Inline Captcha</h1>';
echo sprintf('<img src="%s"><br><br>', $captcha->inline());
echo sprintf('Phrase: %s', $captcha->phrase);
