<?php

namespace SimpleCaptcha;

use SimpleCaptcha\Helpers\A;
use SimpleCaptcha\Helpers\Dir;
use SimpleCaptcha\Helpers\Str;
use SimpleCaptcha\Helpers\Mime;

use GdImage;
use resource;
use Exception;


/**
 * Class Builder
 *
 * Utilities for generating captcha images
 */
class Builder extends BuilderAbstract
{
    /**
     * Properties
     */

    /**
     * Captcha image
     *
     * As of PHP 8.0, this is `GdImage` instead of `resource`
     *
     * @var resource|GdImage
     */
    public $image;


    /**
     * Path to captcha font
     *
     * @var string
     */

    public ?string $font = null;


    /**
     * Whether to distort the image
     *
     * @var bool
     */
    public bool $distort = true;


    /**
     * Whether to interpolate the image
     *
     * @var bool
     */
    public bool $interpolate = true;


    /**
     * Maximum number of lines behind the captcha phrase
     *
     * @var int
     */
    public ?int $maxLinesBehind = null;


    /**
     * Maximum number of lines in front of the captcha phrase
     *
     * @var int
     */
    public ?int $maxLinesFront = null;


    /**
     * Maximum character angle
     *
     * @var int
     */
    public int $maxAngle = 8;


    /**
     * Maximum character offset
     *
     * @var int
     */
    public int $maxOffset = 5;


    /**
     * Background color, either ..
     *
     * (1) .. RGB values (array)
     * (2) .. 'transparent' (string)
     *
     * @var null|string|array
     */
    public $bgColor = null;


    /**
     * Background color code
     *
     * @var array
     */
    private int $bgCode;


    /**
     * Line color RGB values
     *
     * @var array
     */
    public ?array $lineColor = null;


    /**
     * Text color RGB values
     *
     * @var array
     */
    public ?array $textColor = null;


    /**
     * Path to background image
     *
     * @var array
     */
    public ?string $bgImage = null;


    /**
     * Whether to apply (any) effects
     *
     * @var bool
     */
    public bool $applyEffects = true;


    /**
     * Whether to apply post effects
     *
     * @var bool
     */
    public bool $applyPostEffects = true;


    /**
     * Whether to enable scatter effect
     *
     * @var bool
     */
    public bool $applyScatterEffect = true;


    /**
     * Constructor
     *
     * @param string $phrase Captcha phrase
     * @return void
     */
    public function __construct(?string $phrase = null)
    {
        # Build random phrase if missing input or empty string
        $this->phrase = $phrase ?: $this->buildPhrase();

        # Determine captcha font
        $this->font = __DIR__ . '/../fonts/captcha' . mt_rand(0, 4) . '.ttf';
    }


    /**
     * Methods
     */

    /**
     * Instantiates 'CaptchaBuilder' object
     *
     * @param string $phrase Captcha phrase
     * @return self
     */
    public static function create(?string $phrase = null): self
    {
        # TODO: See constructor
        return new self($phrase);
    }


    /**
     * Draws lines over the image
     *
     * @param int $color Line color
     * @return void
     */
    private function drawLine(?int $color = null): void
    {
        # Determine direction at random, being either ..
        # (1) .. horizontal
        if (mt_rand(0, 1)) {
            $Xa = mt_rand(0, $this->width / 2);
            $Ya = mt_rand(0, $this->height);
            $Xb = mt_rand($this->width / 2, $this->width);
            $Yb = mt_rand(0, $this->height);

        # (2) .. vertical
        } else {
            $Xa = mt_rand(0, $this->width);
            $Ya = mt_rand(0, $this->height / 2);
            $Xb = mt_rand(0, $this->width);
            $Yb = mt_rand($this->height / 2, $this->height);
        }

        # Unless line color was provided ..
        if (is_null($color)) {
            # .. assign it
            # (1) Determine colors to be mixed
            $mix = $this->lineColor ?? [
                mt_rand(100, 255),  # red
                mt_rand(100, 255),  # green
                mt_rand(100, 255),  # blue
            ];

            # (2) Mix them up
            $color = imagecolorallocate($this->image, $mix[0], $mix[1], $mix[2]);
        }

        # Randomize thickness & draw line
        imagesetthickness($this->image, mt_rand(1, 3));
        imageline($this->image, $Xa, $Ya, $Xb, $Yb, $color);
    }


    /**
     * Writes captcha phrase on captcha image
     *
     * @return void
     */
    private function writePhrase(): void
    {
        # Determine number of characters
        $length = Str::length($this->phrase);

        # Determine text size & start position
        $size = (int) round($this->width / $length) - mt_rand(0, 3) - 1;
        $box = imagettfbbox($size, 0, $this->font, $this->phrase);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) round(($this->width - $textWidth) / 2);
        $y = (int) round(($this->height - $textHeight) / 2) + $size;

        # Write the letters one by one at random angle
        for ($i = 0; $i < $length; $i++) {
            $symbol = Str::substr($this->phrase, $i, 1);
            $box = imagettfbbox($size, 0, $this->font, $symbol);
            $w = $box[2] - $box[0];
            $angle = mt_rand(-$this->maxAngle, $this->maxAngle);
            $offset = mt_rand(-$this->maxOffset, $this->maxOffset);
            imagettftext($this->image, $size, $angle, $x, $y + $offset, $this->textCode, $this->font, $symbol);
            $x += $w;
        }
    }


    /**
     * Applies post effects
     *
     * @return void
     */
    private function applyPostEffects(): void
    {
        if (!function_exists('imagefilter')) {
            return;
        }

        # Scatter/Noise - Added in PHP 7.4
        $scattered = false;

        if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
            if ($this->applyScatterEffect && mt_rand(0, 3) != 0) {
                $scattered = true;
                imagefilter($this->image, IMG_FILTER_SCATTER, 0, 2, [$this->bgCode]);
            }
        }

        # Negate ?
        if (mt_rand(0, 1) == 0) {
            imagefilter($this->image, IMG_FILTER_NEGATE);
        }

        # Edge ?
        if (!$scattered && mt_rand(0, 10) == 0) {
            imagefilter($this->image, IMG_FILTER_EDGEDETECT);
        }

        # Contrast
        imagefilter($this->image, IMG_FILTER_CONTRAST, mt_rand(-50, 10));

        # Colorize
        if (!$scattered && mt_rand(0, 5) == 0) {
            imagefilter($this->image, IMG_FILTER_COLORIZE, mt_rand(-80, 50), mt_rand(-80, 50), mt_rand(-80, 50));
        }
    }


    /**
     * Interpolates image
     *
     * @param int $x
     * @param int $y
     * @param int $nw
     * @param int $ne
     * @param int $sw
     * @param int $se
     * @return int
     */
    private function interpolate(int $x, int $y, int $nw, int $ne, int $sw, int $se): int
    {
        list($r0, $g0, $b0) = $this->getRGB($nw);
        list($r1, $g1, $b1) = $this->getRGB($ne);
        list($r2, $g2, $b2) = $this->getRGB($sw);
        list($r3, $g3, $b3) = $this->getRGB($se);

        $cx = 1.0 - $x;
        $cy = 1.0 - $y;

        $m0 = $cx * $r0 + $x * $r1;
        $m1 = $cx * $r2 + $x * $r3;
        $r  = (int) ($cy * $m0 + $y * $m1);

        $m0 = $cx * $g0 + $x * $g1;
        $m1 = $cx * $g2 + $x * $g3;
        $g  = (int) ($cy * $m0 + $y * $m1);

        $m0 = $cx * $b0 + $x * $b1;
        $m1 = $cx * $b2 + $x * $b3;
        $b  = (int) ($cy * $m0 + $y * $m1);

        return ($r << 16) | ($g << 8) | $b;
    }


    /**
     * Distorts image
     *
     * @return void
     */
    private function distort(): void
    {
        $image = imagecreatetruecolor($this->width, $this->height);

        # If background transparency is enabled ..
        if ($this->bgColor == 'transparent') {
            # .. apply it
            $this->addTransparency($image);

            # .. initialize background color code
            $this->bgCode = mt_rand(0, 100);
        }

        $X = mt_rand(0, $this->width);
        $Y = mt_rand(0, $this->height);
        $phase = mt_rand(0, 10);
        $scale = 1.1 + mt_rand(0, 10000) / 30000;

        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $Vx = $x - $X;
                $Vy = $y - $Y;
                $Vn = sqrt($Vx * $Vx + $Vy * $Vy);

                if ($Vn != 0) {
                    $Vn2 = $Vn + 4 * sin($Vn / 30);
                    $nX  = $X + ($Vx * $Vn2 / $Vn);
                    $nY  = $Y + ($Vy * $Vn2 / $Vn);

                } else {
                    $nX = $X;
                    $nY = $Y;
                }

                $nY = $nY + $scale * sin($phase + $nX * 0.2);

                if ($this->interpolate && $this->bgColor != 'transparent') {
                    $p = $this->interpolate(
                        $nX - floor($nX),
                        $nY - floor($nY),
                        $this->getColor($this->image, floor($nX), floor($nY), $this->bgCode),
                        $this->getColor($this->image, ceil($nX), floor($nY), $this->bgCode),
                        $this->getColor($this->image, floor($nX), ceil($nY), $this->bgCode),
                        $this->getColor($this->image, ceil($nX), ceil($nY), $this->bgCode)
                    );

                } else {
                    $p = $this->getColor($this->image, round($nX), round($nY), $this->bgCode);
                }

                if ($p == 0) {
                    $p = $this->bgCode;
                }

                imagesetpixel($image, $x, $y, $p);
            }
        }

        $this->image = $image;
    }


    /**
     * Builds captcha image
     *
     * @param int $width Captcha image width
     * @param int $height Captcha image height
     * @return self
     */
    public function build(int $width = 150, int $height = 40): self
    {
        # Apply image dimensions
        $this->width = $width;
        $this->height = $height;

        # If background image available ..
        if (!is_null($this->bgImage)) {
            # (1) .. create image from it
            $this->image = $this->img2gd($this->bgImage);

            # (2) .. extract background color from it
            $this->bgCode = imagecolorat($this->image, 0, 0);

        # .. otherwise ..
        } else {
            # .. start from scratch
            $this->image = imagecreatetruecolor($this->width, $this->height);

            # If background transparency is enabled ..
            if ($this->bgColor == 'transparent') {
                # .. apply it
                $this->addTransparency($this->image);
            }

            # .. otherwise ..
            else {
                # .. assign background color
                # (1) Determine colors to be mixed
                $mix = $this->bgColor ?? [
                    mt_rand(200, 255),  # red
                    mt_rand(200, 255),  # green
                    mt_rand(200, 255),  # blue
                ];

                # (2) Mix them up
                $this->bgCode = imagecolorallocate($this->image, $mix[0], $mix[1], $mix[2]);

                # Fill image
                imagefill($this->image, 0, 0, $this->bgCode);
            }
        }

        # Calculate surface size
        $surface = $this->width * $this->height;

        # Apply effects
        if ($this->applyEffects) {
            $effects = mt_rand($surface / 3000, $surface / 2000);

            # Set the maximum number of lines to draw in front of the text
            if (is_int($this->maxLinesBehind) && $this->maxLinesBehind > 0) {
                $effects = min($this->maxLinesBehind, $effects);
            }

            if ($this->maxLinesBehind !== 0) {
                for ($e = 0; $e < $effects; $e++) {
                    $this->drawLine();
                }
            }
        }

        # Assign text color
        # (1) Determine colors to be mixed
        $mix = $this->textColor ?? [
            mt_rand(0, 150),  # red
            mt_rand(0, 150),  # green
            mt_rand(0, 150),  # blue
        ];

        # (2) Mix them up
        $this->textCode = imagecolorallocate($this->image, $mix[0], $mix[1], $mix[2]);

        # Write captcha phrase & returns its color code
        $this->writePhrase();

        # Apply effects
        if ($this->applyEffects) {
            $effects = mt_rand($surface / 3000, $surface / 2000);

            # Set the maximum number of lines to draw in front of the text
            if (is_int($this->maxLinesFront) && $this->maxLinesFront > 0) {
                $effects = min($this->maxLinesFront, $effects);
            }

            if ($this->maxLinesFront !== 0) {
                for ($e = 0; $e < $effects; $e++) {
                    $this->drawLine($this->textCode);
                }
            }

            # Distort the image
            if ($this->distort) {
                $this->distort();
            }
        }

        # Add post effects
        if ($this->applyEffects && $this->applyPostEffects) {
            $this->applyPostEffects();
        }

        return $this;
    }


    /**
     * Checks whether captcha image may be solved through OCR
     *
     * @param string $tmpDir Directory
     * @return bool
     * @throws \Exception
     */
    public function isOCRReadable(string $tmpDir = '.tmp'): bool
    {
        $commands = [
            'ocrad' => 'ocrad --scale=2 --charset=ascii %s',
            'tesseract' => 'tesseract %s stdout -l eng --dpi 2200',
        ];

        $modes = [];

        foreach (array_keys($commands) as $mode) {
            if (empty(exec('command -v ' . $mode))) {
                continue;
            }

            $modes[] = $mode;
        }

        if (empty($modes)) {
            throw new Exception('OCR detection requires either "ocrad" or "tesseract-ocr" to be installed.');
        }

        # Create temporary directory (if necessary)
        Dir::make($tmpDir);

        # Join filepath & generate unique filename
        $pgmFile = sprintf('%s/%s.pgm', $tmpDir, uniqid('captcha'));

        # Create captcha image & convert to grayscale
        $this->img2ocr($this->image, $pgmFile);

        # Create data array for possible matches
        $outputs = [];

        # Iterate over available modes ..
        foreach ($modes as $mode) {
            # .. using (suggested) external library (if available), otherwise ..
            if ($mode == 'tesseract' && class_exists('\thiagoalessio\TesseractOCR\TesseractOCR')) {
                # Execute  `tesseract-ocr-for-php` & store its output
                $tesseract = new \thiagoalessio\TesseractOCR\TesseractOCR($pgmFile);
                $outputs[] = $tesseract->allowlist(range(0, 9), range('a', 'z'), range('A', 'Z'))->dpi(2200)->run();
            }

            # .. falling back to shell commands
            else {
                # Execute OCR library from CLI
                $outputs[] = shell_exec(sprintf($commands[$mode], $pgmFile));
            }
        }

        # Delete temporary files & directory
        Dir::remove($tmpDir);

        # Iterate over possible matches
        foreach ($outputs as $output) {
            # .. clean & validate them
            if ($this->compare(preg_replace('/[^a-z0-9]/i', '', $output))) {
                return true;
            }
        }

        return false;
    }


    /**
     * Builds captcha image until it is (supposedly) unreadable by OCR software
     *
     * @param int $width Captcha image width
     * @param int $height Captcha image height
     * @return self
     */
    public function buildAgainstOCR(int $width = 150, int $height = 40): self
    {
        do {
            $this->build($width, $height);
        } while ($this->isOCRReadable());

        return $this;
    }


    /**
     * Saves captcha image to file
     *
     * @param string $filename Output filepath
     * @param int $quality Captcha image quality
     * @return void
     */
    public function save(string $filename, int $quality = 90): void
    {
        $this->gd2img($quality, $filename);
    }


    /**
     * Outputs captcha image directly
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image filetype
     * @return void
     */
    public function output(int $quality = 90, string $type = 'jpg'): void
    {
        $this->gd2img($quality, null, $type);
    }


    /**
     * Fetches captcha image contents
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image filetype
     * @return string
     */
    public function fetch(int $quality = 90, string $type = 'jpg'): string
    {
        # Enable output buffering
        ob_start();
        $this->output($quality, $type);

        return ob_get_clean();
    }


    /**
     * Fetches captcha image as data URI
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image filetype
     * @return string
     */
    public function inline(int $quality = 90, string $type = 'jpg'): string
    {
        return sprintf('data:%s;base64,%s', Mime::fromExtension($type), base64_encode($this->fetch($quality, $type)));
    }
}
