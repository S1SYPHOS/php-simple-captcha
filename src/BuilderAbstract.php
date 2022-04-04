<?php

namespace SimpleCaptcha;

use SimpleCaptcha\Helpers\A;
use SimpleCaptcha\Helpers\F;
use SimpleCaptcha\Helpers\Str;
use SimpleCaptcha\Helpers\Mime;

use GdImage;
use resource;
use Exception;


/**
 * Class BuilderAbstract
 *
 * Base template for captcha builder
 */
abstract class BuilderAbstract
{
    /**
     * Properties
     */

    /**
     * Captcha phrase
     *
     * @var string
     */
    public string $phrase;


    /**
     * Characters used for building random phrases
     *
     * @var string
     */
    protected static string $charset = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';


    /**
     * Abstract methods
     */

    /**
     * Builds captcha image
     *
     * @param int $width Captcha image width
     * @param int $height Captcha image height
     * @return self
     */
    public abstract function build(int $width, int $height): self;


    /**
     * Saves captcha image to file
     *
     * @param string $filename Output filepath
     * @param int $quality Captcha image quality
     * @return void
     */
    public abstract function save(string $filename, int $quality = 90): void;


    /**
     * Outputs captcha image directly
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image output format
     * @return void
     */
    public abstract function output(int $quality, string $type): void;


    /**
     * Fetches captcha image contents
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image output format
     * @return string
     */
    public abstract function fetch(int $quality, string $type): string;


    /**
     * Fetches captcha image as data URI
     *
     * @param int $quality Captcha image quality
     * @param string $type Captcha image output format
     * @return string
     */
    public abstract function inline(int $quality = 90, string $type): string;


    /**
     * Helper methods
     */

    /**
     * Picks random character
     *
     * @param string $charset Characters to choose from
     * @return string
     */
    public static function randomCharacter(?string $charset = null): string
    {
        # Determine characters to use
        if (is_null($charset)) {
            $charset = self::$charset;
        }

        # Create charset array
        $characters = str_split($charset);

        # Return random character
        #
        # Note: This provides same performance as `array_rand`, which uses `mt_rand` under the hood
        # See https://www.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.rand-srand-aliases
        #
        # For details on `rand` versus `mt_rand` performance,
        # see https://stackoverflow.com/a/7808258
        return $characters[mt_rand(0, count($characters) - 1)];
    }


    /**
     * Builds random phrase
     *
     * @param int $length Number of characters
     * @param string $charset Characters to choose from
     * @return string
     */
    public static function buildPhrase(int $length = 5, ?string $charset = null): string
    {
        # Build random string
        $phrase = '';

        for ($i = 0; $i < $length; $i++) {
            $phrase .= self::randomCharacter($charset);
        }

        return $phrase;
    }


    /**
     * Normalizes characters which look (almost) the same
     *
     * @param string $string
     * @return string
     */
    private static function normalize(string $string): string
    {
        return strtr(Str::lower($string), '01', 'ol');
    }


    /**
     * Checks whether captcha was solved correctly
     *
     * @param string $phrase
     * @return bool
     */
    public function compare(string $phrase, ?string $string = null): bool
    {
        return self::normalize($phrase) == self::normalize($string ?? $this->phrase);
    }


    /**
     * Creates GD image object from file
     *
     * @param string $image
     * @return resource|GdImage
     * @throws \Exception
     */
    protected function img2gd(string $file)
    {
        # If file does not exist ..
        if (!F::exists($file)) {
            # .. fail early
            throw new Exception(sprintf('File does not exist: "%s"', F::filename($file)));
        }

        $methods = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/gif' => 'imagecreatefromgif',
        ];

        $mime = Mime::type($file);

        if (in_array($mime, array_keys($methods))) {
            return $methods[$mime]($file);
        }

        throw new Exception(sprintf('MIME type "%s" not supported!', $mime));
    }


    /**
     * Creates image content from GD image object
     *
     * @param int $quality Captcha image quality
     * @param string $filename Output filepath
     * @param string $type Captcha image output format
     * @return void
     * @throws \Exception
     */
    protected function gd2img(int $quality = 90, ?string $filename = null, string $type = 'jpg'): void
    {
        # Convert filetype to lowercase
        $type = Str::lower($type);

        # If filename is given ..
        if (!is_null($filename)) {
            # .. determine filetype from it
            $type = F::extension($filename);
        }

        if ($type == 'gif') {
            imagegif($this->image, $filename);
        }

        elseif ($type == 'jpg') {
            imagejpeg($this->image, $filename, $quality);
        }

        elseif ($type == 'png') {
            # Normalize quality
            if ($quality > 9) {
                $quality = -1;
            }

            imagepng($this->image, $filename, $quality);
        }

        # .. otherwise ..
        else {
            # .. abort execution
            throw new Exception(sprintf('File type "%s" not supported!', $type));
        }
    }


    /**
     * Determines (and validates) colors
     *
     * @param string|array $color Color values, either HEX (string) or RGB (array)
     * @return array
     * @throws Exception
     */
    public function getColor($color): array
    {
        # If value represents RGB values ..
        if (is_array($color)) {
            # .. validate them
            if (count($color) != 3) {
                throw new Exception(sprintf('Invalid RGB colors: "%s"', A::join($color)));
            }

            return $color;
        }

        return Toolkit::hex2rgb($color);
    }


    /**
     * Fetches color from image coordinates (= pixel)
     *
     * @param $image
     * @param int $x
     * @param int $y
     * @param int $code Color code fallback
     * @return int
     */
    protected function pixel2int($image, int $x, int $y, int $code): int
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($x < 0 || $x >= $width || $y < 0 || $y >= $height) {
            return $code;
        }

        return imagecolorat($image, $x, $y);
    }


    /**
     * Creates image suitable for use with OCR software
     *
     * See https://priteshgupta.com/2011/09/advanced-image-functions-using-php
     * See https://github.com/raoulduke/phpocrad
     *
     * @param string $image Captcha image
     * @param string $file Output file
     * @param int $amount
     * @param int $threshold
     * @return void
     */
    protected function img2ocr($image, ?string $output = null, int $amount = 80, int $threshold = 3): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $canvas = imagecreatetruecolor($width, $height);
        $blurred = imagecreatetruecolor($width, $height);

        # Apply gaussian blur matrix
        $matrix = [
            [1, 2, 1],
            [2, 4, 2],
            [1, 2, 1],
        ];

        imagecopy($blurred, $image, 0, 0, 0, 0, $width, $height);
        imageconvolution($blurred, $matrix, 16, 0);

        if ($threshold > 0) {
            # Calculate the difference between the blurred pixels and the original
            # and set the pixels
            for ($x = 0; $x < $width-1; $x++) {  # each row
                for ($y = 0; $y < $height; $y++) { # each pixel
                    $rgbOrig = imagecolorat($image, $x, $y);

                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = imagecolorat($blurred, $x, $y);

                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    # When the masked pixels differ less from the original
                    # than the threshold specifies, they are set to their original value.
                    $rNew = (abs($rOrig - $rBlur) >= $threshold)
                        ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
                        : $rOrig;
                    $gNew = (abs($gOrig - $gBlur) >= $threshold)
                        ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
                        : $gOrig;
                    $bNew = (abs($bOrig - $bBlur) >= $threshold)
                        ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
                        : $bOrig;

                    if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
                        $pixCol = ImageColorAllocate($image, $rNew, $gNew, $bNew);
                        imagesetpixel($image, $x, $y, $pixCol);
                    }
                }
            }
        }

        else {
            for ($x = 0; $x < $width; $x++) { # each row
                for ($y = 0; $y < $height; $y++) { # each pixel
                    $rgbOrig = imagecolorat($image, $x, $y);

                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = imagecolorat($blurred, $x, $y);

                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
                        if ($rNew > 255) { $rNew = 255; }
                        elseif ($rNew < 0) { $rNew = 0; }
                    $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
                        if ($gNew > 255) { $gNew = 255; }
                        elseif ($gNew < 0) { $gNew = 0; }
                    $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
                        if ($bNew > 255) { $bNew = 255; }
                        elseif ($bNew < 0) { $bNew = 0; }
                    $rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;

                    imagesetpixel($image, $x, $y, $rgbNew);
                }
            }
        }

        # Remove temporary image data
        imagedestroy($canvas);
        imagedestroy($blurred);

        # Create PGM file (grayscale)
        $pgm = 'P5 ' . $width . ' ' . $height . ' 255' . "\n";

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $colors = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $pgm .= chr(0.3 * $colors['red'] + 0.59 * $colors['green'] + 0.11 * $colors['blue']);
            }
        }

        if (empty($output)) {
            $output = sprintf('%s/%s.pgm', F::dirname($file), F::name($file));
        }

        F::write($output, $pgm);
    }
}
