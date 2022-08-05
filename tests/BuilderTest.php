<?php

namespace SimpleCaptcha\Tests;

use SimpleCaptcha\Helpers\Dir;
use SimpleCaptcha\Helpers\Str;
use SimpleCaptcha\Builder;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Exception;


/**
 * Class BuilderTest
 *
 * Adds tests for class 'Builder'
 */
class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Properties
     */

    /**
     * Captcha builder
     *
     * @var SimpleCaptcha\Builder
     */
    private static Builder $builder;


    /**
     * Virtual directory
     *
     * @var org\bovigo\vfs\vfsStreamDirectory
     */
    private vfsStreamDirectory $root;


    /**
     * Captcha phrases
     *
     * @var array
     */
    private array $phrases = [
        '@!#*?',
        's3cr3t',
        'p4ssw0rd',
    ];


    /**
     * Setup (global)
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        # Initialize `Builder`
        self::$builder = Builder::create()->build();
    }


    public function testCreate(): void
    {
        # Assert result
        $this->assertInstanceOf('SimpleCaptcha\Builder', Builder::create());
    }


    public function testCreateFonts(): void
    {
        # Setup
        # (1) Default font files
        $defaultFonts = Dir::files(__DIR__ . '/../fonts', null, true);

        # (2) Create virtual directory
        $root = vfsStream::setup('home');

        # (3) Custom font files
        $customFonts = [
            vfsStream::url('home/example1.ttf'),
            vfsStream::url('home/example2.ttf'),
        ];

        # Assert defaults
        $this->assertEquals(array_map('realpath', self::$builder->fonts), array_map('realpath', $defaultFonts));

        # Run function
        $builder = Builder::create();
        $builder->fonts = $customFonts;

        # Assert funtion
        $this->assertEquals(array_map('realpath', $builder->fonts), array_map('realpath', $customFonts));
    }


    public function testBuild(): void
    {
        # Assert result
        $this->assertInstanceOf('SimpleCaptcha\Builder', Builder::create()->build());

        foreach ($this->phrases as $phrase) {
            # Run function
            $builder = new Builder($phrase);

            # Assert result
            $this->assertEquals($phrase, $builder->phrase);
        }
    }


    public function testBuildInvalidBackgroundImage(): void
    {
        # Setup
        $builder = Builder::create();

        # Assert exception
        $this->expectException(Exception::class);

        # Run function
        $builder->bgImage = 'not-existent.jpg';
        $builder->build();
    }


    public function testBuildInvalidBgColor(): void
    {
        # Setup
        $builder = Builder::create();

        # Assert exception
        $this->expectException(Exception::class);

        # Run function
        $builder->bgColor = [255, 255];
        $builder->build();
    }


    public function testBuildInvalidLineColor(): void
    {
        # Setup
        $builder = Builder::create();

        # Assert exception
        $this->expectException(Exception::class);

        # Run function
        $builder->lineColor = [255, 255];
        $builder->build();
    }


    public function testBuildInvalidTextColor(): void
    {
        # Setup
        $builder = Builder::create();

        # Assert exception
        $this->expectException(Exception::class);

        # Run function
        $builder->textColor = [255, 255];
        $builder->build();
    }


    public function testBuildInvalidFont(): void
    {
        # Setup
        $builder = Builder::create();

        # Assert exception
        $this->expectException(Exception::class);

        # Run function
        $builder->fonts = ['not-existent.ttf'];
        $builder->build();
    }


    public function testBuildAgainstOCR(): void
    {
        try {
            # Assert result
            $this->assertInstanceOf('SimpleCaptcha\Builder', Builder::create()->buildAgainstOCR());
        } catch (Exception $e) {
            if ($e->getMessage() == 'OCR detection requires either "ocrad" or "tesseract-ocr" to be installed.') {
                $this->markTestIncomplete('No OCR software installed, skipping ..');
            }
        }
    }


    public function testIsOCRReadable(): void
    {
        try {
            # Assert result
            $this->assertIsBool(self::$builder->isOCRReadable());
        } catch (Exception $e) {
            if ($e->getMessage() == 'OCR detection requires either "ocrad" or "tesseract-ocr" to be installed.') {
                $this->markTestIncomplete('No OCR software installed, skipping ..');
            }
        }
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


    public function testSaveJPG(): void
    {
        # Setup
        # (1) File path
        $path = vfsStream::url('home/example.jpg');

        # Run function #1
        $result1 = self::$builder->save($path);
        $content1 = file_get_contents($path);

        # Assert result
        $this->assertFileExists($path);
        $this->assertIsString($content1);
        $this->assertTrue(Str::contains($content1, 'quality = 90'));

        # Run function #2
        $result2 = self::$builder->save($path, 10);
        $content2 = file_get_contents($path);

        # Assert result
        $this->assertFileExists($path);
        $this->assertIsString($content2);
        $this->assertTrue(Str::contains($content2, 'quality = 10'));

        # Run function #3
        $image1 = imagecreatefromjpeg($path);

        # Assert result
        $this->assertEquals(imagesx($image1), 150);
        $this->assertEquals(imagesy($image1), 40);

        # Run function #4
        $builder = Builder::create()->build(12, 4)->save($path);
        $image2 = imagecreatefromjpeg($path);

        # Assert result
        $this->assertEquals(imagesx($image2), 12);
        $this->assertEquals(imagesy($image2), 4);
    }


    public function testSaveGIF(): void
    {
        # Setup
        # (1) File path
        $path = vfsStream::url('home/example.gif');

        # Run function #1
        self::$builder->save($path);
        $content = file_get_contents($path);

        # Assert result
        $this->assertFileExists($path);
        $this->assertIsString($content);
        $this->assertTrue(Str::startsWith(file_get_contents($path), 'GIF'));

        # Run function #2
        $image1 = imagecreatefromgif($path);

        # Assert result
        $this->assertEquals(imagesx($image1), 150);
        $this->assertEquals(imagesy($image1), 40);

        # Run function #3
        $builder = Builder::create()->build(12, 4)->save($path);
        $image2 = imagecreatefromgif($path);

        # Assert result
        $this->assertEquals(imagesx($image2), 12);
        $this->assertEquals(imagesy($image2), 4);
    }


    public function testSavePNG(): void
    {
        # Setup
        # (1) File path
        $path = vfsStream::url('home/example.png');

        # Run function #1
        self::$builder->save($path, 90);
        $content1 = file_get_contents($path);

        # Assert result
        $this->assertFileExists($path);
        $this->assertIsString($content1);
        $this->assertTrue(Str::startsWith($content1, 'PNG'));

        # Run function #2
        self::$builder->save($path, 10);
        $content2 = file_get_contents($path);

        # Assert result
        $this->assertFileExists($path);
        $this->assertIsString($content2);
        $this->assertTrue(Str::startsWith($content2, 'PNG'));

        # Run function #3
        $image1 = imagecreatefrompng($path);

        # Assert result
        $this->assertEquals(imagesx($image1), 150);
        $this->assertEquals(imagesy($image1), 40);

        # Run function #4
        $builder = Builder::create()->build(12, 4)->save($path);
        $image2 = imagecreatefrompng($path);

        # Assert result
        $this->assertEquals(imagesx($image2), 12);
        $this->assertEquals(imagesy($image2), 4);
    }


    public function testFetch(): void
    {
        # Run function #1
        $result1 = self::$builder->fetch();

        # Assert result
        $this->assertIsString($result1);
        $this->assertTrue(Str::contains($result1, 'quality = 90'));

        # Run function #2
        $result2 = self::$builder->fetch(10);

        # Assert result
        $this->assertTrue(Str::contains($result2, 'quality = 10'));
    }


    public function testInline(): void
    {
        # Run function #1
        $result1 = self::$builder->inline();
        $parts1 = explode(',', $result1);

        # Assert result
        $this->assertTrue(Str::contains($result1, 'data:image/jpeg;base64'));
        $this->assertTrue(Str::contains(base64_decode($parts1[1]), 'quality = 90'));

        # Run function #2
        $result2 = self::$builder->inline(10);
        $parts2 = explode(',', $result2);

        # Assert result
        $this->assertTrue(Str::contains($result2, 'data:image/jpeg;base64'));
        $this->assertTrue(Str::contains(base64_decode($parts2[1]), 'quality = 10'));
    }


    public function testOutput(): void
    {
        # Run function #1
        ob_start();
        self::$builder->output();
        $result1 = ob_get_clean();

        # Assert result
        $this->assertIsString($result1);
        $this->assertTrue(Str::contains($result1, 'quality = 90'));

        # Run function #2
        ob_start();
        self::$builder->output(10);
        $result2 = ob_get_clean();

        # Assert result
        $this->assertIsString($result2);
        $this->assertTrue(Str::contains($result2, 'quality = 10'));
    }


    public function testRandomCharacter(): void
    {
        # Setup
        # (1) Lengths
        $fixtures = [
            [
                'charset' => 'abc',
                'regex' => '/([abc])/',
            ],
            [
                'charset' => '123',
                'regex' => '/([123])/',
            ],
            [
                'charset' => 'ABC',
                'regex' => '/([ABC])/',
            ],
            [
                'charset' => 'abcABC123',
                'regex' => '/([abcABC123])/',
            ],
        ];

        # Run function #1
        $result1 = Builder::randomCharacter();
        preg_match('/(\w)/', $result1, $expected1);

        # Assert result
        $this->assertEquals(Str::length($result1), 1);
        $this->assertEquals($result1, $expected1[0]);

        foreach ($fixtures as $fixture) {
            # Run function #2
            $result2 = Builder::randomCharacter($fixture['charset']);
            preg_match($fixture['regex'], $result2, $expected2);

            # Assert result
            $this->assertEquals(Str::length($result2), 1);
            $this->assertEquals($result2, $expected2[0]);
        }
    }


    public function testBuildPhrase(): void
    {
        # Setup
        # (1) Lengths
        $fixtures = [
            [
                'length' => 2,
                'charset' => 'abc',
                'regex' => '/([abc]+)/',
            ],
            [
                'length' => 8,
                'charset' => '123',
                'regex' => '/([123]+)/',
            ],
            [
                'length' => 32,
                'charset' => 'ABC',
                'regex' => '/([ABC]+)/',
            ],
            [
                'length' => 128,
                'charset' => 'abcABC123',
                'regex' => '/([abcABC123]+)/',
            ],
        ];

        # Run function #1
        $result1 = Builder::buildPhrase();
        preg_match('/(\w+)/', $result1, $expected1);

        # Assert result
        $this->assertEquals(Str::length($result1), 5);
        $this->assertEquals($result1, $expected1[0]);

        foreach ($fixtures as $fixture) {
            # Run function #2
            $result2 = Builder::buildPhrase($fixture['length'], $fixture['charset']);
            preg_match($fixture['regex'], $result2, $expected2);

            # Assert result
            $this->assertEquals(Str::length($result2), $fixture['length']);
            $this->assertEquals($result2, $expected2[0]);
        }
    }


    public function testCompare(): void
    {
        # Assert result
        $this->assertTrue(self::$builder->compare('phrase', 'phrase'));
        $this->assertTrue(self::$builder->compare('s01om0n', 'sol0mon'));
        $this->assertFalse(self::$builder->compare('phrase1', 'phrase2'));

        foreach ($this->phrases as $phrase) {
            # Run function
            $builder = new Builder($phrase);

            # Assert result
            $this->assertTrue($builder->compare($phrase));
        }
    }
}
