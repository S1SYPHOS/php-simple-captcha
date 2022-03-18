<?php
    require_once __DIR__ . '/../vendor/autoload.php';

    $captcha = SimpleCaptcha\Builder::create()->build();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <h1>Inline Captcha</h1>
        <img src="<?= $captcha->inline() ?>"><br>
        Phrase: <?= $captcha->phrase ?>
    </body>
</html>
