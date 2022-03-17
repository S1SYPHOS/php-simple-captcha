<?php
require_once __DIR__.'/../vendor/autoload.php';

use SimpleCaptcha\Builder;

Builder::create()->build();

?>
<!DOCTYPE html>
<body>
    <html>
        <meta charset="utf-8" />
    </html>
    <body>
        <h1>Inline Captcha</h1>

        <img src="<?php echo $captcha->inline(); ?>"/><br/>
        Phrase: <?php echo $captcha->phrase; ?>

    </body>
</body>
