<h1>Examples</h1>

<?php for ($x = 0; $x < 8; $x++) : ?>
<?php for ($y = 0; $y < 5; $y++) : ?>
<img src="output.php?n=<?= 5 * $x + $y ?>">
<?php endfor ?>
<br>
<?php endfor ?>
