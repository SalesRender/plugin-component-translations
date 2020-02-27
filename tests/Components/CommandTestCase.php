<?php


namespace Leadvertex\Plugin\Components\Translations\Components;


use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use XAKEPEHOK\Path\Path;

abstract class CommandTestCase extends TestCase
{

    static $pathToRootDir;
    static $pathToTestsSourceFiles;
    static $pathToTranslations;
    static $pathToTestsSourceTranslations;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$pathToRootDir = (string) (new Path(__DIR__))->up()->up()->down('src');
        self::$pathToTestsSourceFiles = (string) (new Path(__DIR__))->up()->down('SourceFiles');
        self::$pathToTranslations = (string) (new Path(__DIR__))->up()->up()->down('translations');
        self::$pathToTestsSourceTranslations = (string) (new Path(self::$pathToTestsSourceFiles))->down('translations');

        $filesystem = new Filesystem();

        if ($filesystem->exists(self::$pathToTranslations)) {
            $filesystem->remove(self::$pathToTranslations);
        }

        $filesystem->mirror(self::$pathToTestsSourceTranslations, self::$pathToTranslations);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $filesystem = new Filesystem();

        if ($filesystem->exists(self::$pathToTranslations)) {
            $filesystem->remove(self::$pathToTranslations);
        }

        $filesystem->mirror(self::$pathToTestsSourceTranslations, self::$pathToTranslations);
    }
}