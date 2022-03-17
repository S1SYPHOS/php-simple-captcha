<?php

namespace SimpleCaptcha;

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


    private static array $types = [
        'gif' => 'image/gif',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
    ];


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
     * Builds random phrase
     *
     * @param int $length Number of characters
     * @param string $charset Allowed characters
     * @return string
     */
    public static function buildPhrase(int $length = 5, string $charset = 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        # Create charset array
        $characters = str_split($charset);

        # Build random string
        $phrase = '';

        for ($i = 0; $i < $length; $i++) {
            $phrase .= $characters[array_rand($characters)];
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
     * Makes image background transparent
     *
     * @param resource|GdImage $image
     * @return void
     */
    protected function addTransparency($image): void
    {
        imagealphablending($image, false);
        $transparency = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparency);
        imagesavealpha($image, true);
    }


    /**
     * Determines (and validates) MIME type
     *
     * @param string $file Image filepath
     * @return string
     * @throws \Exception
     */
    protected function getMIME(string $file): string
    {
        # Determine image MIME type
        $mime = Mime::type($file);

        # If not in allowed image MIME type list ..
        if (!in_array($mime, array_values(self::$types))) {
            # .. abort execution
            throw new Exception(sprintf('Invalid MIME type: "%s". Allowed types are: %s', $mime, A::join($allowList, ', ')));
        }

        return $mime;
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

        $mime = $this->getMIME($file);

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
     * Fetches color from image coordinates
     *
     * @param $image
     * @param int $x
     * @param int $y
     * @param int $bgColor Background color
     * @return int
     */
    protected function getColor($image, int $x, int $y, int $bgColor): int
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($x < 0 || $x >= $width || $y < 0 || $y >= $height) {
            return $bgColor;
        }

        return imagecolorat($image, $x, $y);
    }


    /**
     * Converts color identifier to RGB values
     *
     * @param $color Color identifier
     * @return array
     */
    protected function getRGB(int $color): array
    {
        return [
            (int) ($color >> 16) & 0xff,
            (int) ($color >> 8) & 0xff,
            (int) ($color) & 0xff,
        ];
    }


    /**
     * Converts image to PGM (grayscale)
     *
     * See https://priteshgupta.com/2011/09/advanced-image-functions-using-php
     *
     * @param string $file Input file
     * @param string $file Output file
     * @return void
     */
    protected function img2grayscale(string $file, ?string $output = null): void
    {
        $image = $this->img2gd($file);

        $pgm = 'P5 ' . imagesx($image) . ' ' . imagesy($image).' 255' . "\n";

        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
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
