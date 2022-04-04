<?php

namespace SimpleCaptcha\Tests;

use SimpleCaptcha\Toolkit;

use Exception;


/**
 * Class BuilderTest
 *
 * Adds tests for class 'Toolkit'
 */
class ToolkitTest extends \PHPUnit\Framework\TestCase
{
    public function testHex2rgb(): void
    {
        # Setup
        # (1) HEX colors
        $colors = [
            ['000', [0, 0, 0]],
            ['#000', [0, 0, 0]],
            ['#fff', [255, 255, 255]],
            ['fa00af', [250, 0, 175]],
            ['#fa00af', [250, 0, 175]],
            ['#cacaca', [202, 202, 202]],
        ];

        # Run function
        foreach ($colors as $color) {
            # Assert result
            $this->assertEquals(Toolkit::hex2rgb($color[0]), $color[1]);
        }
    }


    public function testHex2rgbInvalid(): void
    {
        # Assert exception
        $this->expectException(Exception::class);

        # Run function
        Toolkit::hex2rgb('#zzz');
    }


    public function testHex2rgbTooShort(): void
    {
        # Assert exception
        $this->expectException(Exception::class);

        # Run function
        Toolkit::hex2rgb('#ff');
    }


    public function testHex2rgbTooLong(): void
    {
        # Assert exception
        $this->expectException(Exception::class);

        # Run function
        Toolkit::hex2rgb('#fffffffff');
    }


    public function testInt2rgb(): void
    {
        # Setup
        # (1) Color codes
        $colors = [
            [0, [0, 0, 0]],
            [9001, [0, 35, 41]],
            [16711422, [254, 254, 254]],
        ];

        # Run function
        foreach ($colors as $color) {
            # Assert result
            $this->assertEquals(Toolkit::int2rgb($color[0]), $color[1]);
        }
    }
}
