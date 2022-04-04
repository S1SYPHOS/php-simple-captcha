<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleCaptcha\Builder;
use SimpleCaptcha\Helpers\Dir;
use SimpleCaptcha\Helpers\F;

# Create temporary directory
Dir::make('tmp');

# Remove previously solved captcha images
foreach (Dir::files('tmp') as $file) {
    F::remove($file);
}

Builder::create()->build()->save('tmp/out.jpg');;
