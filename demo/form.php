<?php

require_once __DIR__.'/../vendor/autoload.php';

use SimpleCaptcha\Builder;

# Start session & store phrase in session,
# so you can check it after form submission
session_start();

?>

<html>
    <body>
        <h1>Captcha & form submission</h1>
        <form method="post">
            <img src="session.php"><br>
            Enter CAPTCHA:
            <!-- See `session.php` -->
            <input type="text" name="phrase">
            <input type="submit">
        </form>
        <?php
            # If 'POST' request is being made ..
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                # .. compare phrase stored in session & phrase entered in form
                $captcha = Builder::create();

                if (isset($_SESSION['phrase']) && $captcha->compare($_SESSION['phrase'], $_POST['phrase'])) {
                    echo 'Captcha is valid!';
                }

                else {
                    echo 'Captcha is invalid!';
                }

                # Delete phrase stored in session
                unset($_SESSION['phrase']);
            }
        ?>
    </body>
</html>
