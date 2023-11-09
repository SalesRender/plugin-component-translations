<?php

namespace SalesRender\Plugin\Components\Translations\Commands;

use SalesRender\Plugin\Components\Translations\Components\CommandTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class LangUpdateCommandTest extends CommandTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $filesystem = new Filesystem();
        $translatorPath = (string) self::$pathToRootDir->down('TranslatorUpdateExample.php');

        if ($filesystem->exists($translatorPath)) {
            $filesystem->remove($translatorPath);
        }

        $filesystem->copy((string) self::$pathToTestsSourceFiles->down('TranslatorUpdateExample.txt'), $translatorPath);
    }

    public function testExecuteCommand()
    {
        $cmdTester = new CommandTester(new LangUpdateCommand());
        $cmdTester->execute([]);
        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->exists(self::$pathToTranslations->down('en_US.json')));
        $this->assertTrue($filesystem->exists(self::$pathToTranslations->down('en_US.old.json')));
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $filesystem = new Filesystem();
        $translatorPath = (string) self::$pathToRootDir->down('TranslatorUpdateExample.php');

        if ($filesystem->exists($translatorPath)) {
            $filesystem->remove($translatorPath);
        }
    }
}