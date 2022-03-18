<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
    </head>
    <body>
        <h1>Captcha examples</h1>

        <?php for ($x = 0; $x < 8; $x++) : ?>
        <?php for ($y = 0; $y < 5; $y++) : ?>
        <img src="output.php?n=<?= 5 * $x + $y ?>">
        <?php endfor ?>
        <br>
        <?php endfor ?>
    </body>
</html>
