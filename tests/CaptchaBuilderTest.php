<?php

namespace RefBW\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;


@include_once __DIR__ . '/polyfills.php';


/**
 * Class CaptchaBuilderTest
 *
 * Adds tests for class `CaptchaBuilderTest`
 */
class CaptchaBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Properties
     */

    /**
     * @var Gregwar\Captcha\CaptchaBuilder
     */
    private static $builder;


    /**
     * Virtual directory
     *
     * @var org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;


    /**
     * Setup (global)
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        # Initialize `CaptchaBuilder`
        self::$builder = \SimpleCaptcha\CaptchaBuilder::create()->build();
    }


    public function testBuild()
    {
        # Assert result
        $this->assertInstanceOf('SimpleCaptcha\CaptchaBuilder', self::$builder);
    }


    /**
     * Setup ('testSave')
     *
     * @return void
     */
    public function setUp(): void
    {
        # Create virtual directory
        $this->root = vfsStream::setup('home');
    }


    public function testSave()
    {
        # Setup
        # (1) File path
        $path = vfsStream::url('home/example.jpg');

        # Run function #1
        self::$builder->save($path);

        # Assert result
        $this->assertFileExists($path);
        $this->assertTrue(str_contains(file_get_contents($path), 'quality = 90'));

        # Run function #2
        self::$builder->save($path, 10);

        # Assert result
        $this->assertFileExists($path);
        $this->assertTrue(str_contains(file_get_contents($path), 'quality = 10'));
    }


    public function testGet()
    {
        # Run function #1
        $result = self::$builder->get();

        # Assert result
        $this->assertIsString($result);
        $this->assertTrue(str_contains($result, 'quality = 90'));

        # Run function #2
        $result = self::$builder->get(10);

        # Assert result
        $this->assertTrue(str_contains($result, 'quality = 10'));
    }


    public function testOutput()
    {
        # Setup
        # (1) Output buffering
        ob_start();
        self::$builder->output();

        # Run function #1
        $result = ob_get_clean();

        # Assert result
        $this->assertIsString($result);
    }


    public function testGetFingerprint()
    {
        # Run function
        $result = self::$builder->getFingerprint();

        # Assert result
        $this->assertIsArray($result);
        $this->assertFalse(empty($result));
    }
}
