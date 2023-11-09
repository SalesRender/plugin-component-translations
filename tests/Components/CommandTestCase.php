<?php


namespace SalesRender\Plugin\Components\Translations\Components;


use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use XAKEPEHOK\Path\Path;

abstract class CommandTestCase extends TestCase
{

    /**@var Path */
    protected static $pathToRootDir;

    /**@var Path */
    protected static $pathToTestsSourceFiles;

    /** @var Path */
    protected static $pathToTranslations;

    /**@var Path */
    protected static $pathToTestsSourceTranslations;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$pathToRootDir = (new Path(__DIR__))->up()->up()->down('src');
        self::$pathToTestsSourceFiles = (new Path(__DIR__))->up()->down('SourceFiles');
        self::$pathToTranslations = (new Path(__DIR__))->up()->up()->down('translations');
        self::$pathToTestsSourceTranslations = (new Path(self::$pathToTestsSourceFiles))->down('translations');

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