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

echo '<h1>OCR Captcha</h1>';

# Determine OCR success rate solving 100 captcha images
$total = 100;
$passed = 0;

for ($i = 0; $i < $total; $i++) {
    echo sprintf('Captcha %s/%s ..', $i + 1, $total);

    $captcha = new Builder;
    $captcha->distortion = false;
    $captcha->build();

    if ($captcha->isOCRReadable()) {
        $passed++;

        # Store solved captcha image
        $captcha->save("tmp/passed-$passed.jpg");

        echo ' passed!<br>';
    }

    else {
        echo ' failed!<br>';
    }

    $rate = round(100 * $passed / ($i + 1), 2);
    echo "Current pass rate: $rate %<br><br>";
}

echo '<br>';
echo sprintf('Result: %s/%s solved through OCR!', $passed, $total);
