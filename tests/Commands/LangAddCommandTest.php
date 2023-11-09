<?php

namespace SalesRender\Plugin\Components\Translations\Commands;

use InvalidArgumentException;
use RuntimeException;
use SalesRender\Plugin\Components\Translations\Components\CommandTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class LangAddCommandTest extends CommandTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $filesystem = new Filesystem();
        $translatorPath = (string) self::$pathToRootDir->down('TranslatorExample.php');

        if ($filesystem->exists($translatorPath)) {
            $filesystem->remove($translatorPath);
        }

        $filesystem->copy((string) self::$pathToTestsSourceFiles->down('TranslatorExample.txt'), $translatorPath);
    }

    public function testExecuteCommand()
    {
        $cmdTester = new CommandTester(new LangAddCommand());
        $cmdTester->execute(['lang' => 'fr_FR']);
        $filesystem = new Filesystem();
        $translationPath  = (string) self::$pathToTranslations->down('fr_FR.json');
        $this->assertTrue($filesystem->exists($translationPath));
        $translation = json_decode(file_get_contents($translationPath), true);


        $this->assertArrayNotHasKey('ignored', $translation);
        foreach ($translation['main'] as $line) {
            $this->assertNotEquals('Ignored message', $line['source']);
        }

        $this->assertTrue($this->translationHasLine($translation['main'], 'func'));
        $this->assertTrue($this->translationHasLine($translation['main'], 'class:func'));
        $this->assertTrue($this->translationHasLine($translation['main'], 'class:func:func'));
        $this->assertTrue($this->translationHasLine($translation['main'], 'class:func:class:func'));
        $this->assertTrue($this->translationHasLine($translation['main'], 'class:func:class:func:func'));

    }

    public function testWrongLangFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $cmdTester = new CommandTester(new LangAddCommand());
        $cmdTester->execute(['lang' => 'Russian']);
    }

    public function testAddAlreadyPresentedLang()
    {
        $this->expectException(RuntimeException::class);
        $cmdTester = new CommandTester(new LangAddCommand());
        $cmdTester->execute(['lang' => 'en_US']);
    }

    private function translationHasLine(array $translation, string $needle): bool
    {
        foreach ($translation as $line) {
            if ($line['source'] === $needle) {
                return true;
            }
        }
        return false;
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $filesystem = new Filesystem();
        $translatorPath = (string) self::$pathToRootDir->down('TranslatorExample.php');

        if ($filesystem->exists($translatorPath)) {
            $filesystem->remove($translatorPath);
        }
    }
}