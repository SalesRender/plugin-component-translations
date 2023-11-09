<?php


namespace SalesRender\Plugin\Components\Translations;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use XAKEPEHOK\Path\Path;

class TranslatorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $filesystem = new Filesystem();
        $translationsPath = (string) (new Path(__DIR__))->up()->down('translations');
        $sourceTranslationsPath = (string) (new Path(__DIR__))->down('SourceFiles')->down('translations');

        if ($filesystem->exists($translationsPath)) {
            $filesystem->remove($translationsPath);
        }

        $filesystem->mirror($sourceTranslationsPath, $translationsPath);
    }

    public function testTranslatorInvalidConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        Translator::config('smth');
    }

    public function testNotConfiguredTranslator()
    {
        $this->expectException(RuntimeException::class);
        Translator::getLanguages();
    }

    public function testInitializeTranslator()
    {
        $this->assertNull(Translator::getDefaultLang());

        Translator::config('ru_RU');
        $this->assertEquals('ru_RU', Translator::getDefaultLang());
        $this->assertEquals('ru_RU', Translator::getLang());
        $this->assertEquals('Тестовое сообщение', Translator::get('main', 'Тестовое сообщение'));

        Translator::setLang('en_US');
        $this->assertEquals('ru_RU', Translator::getDefaultLang());
        $this->assertEquals('en_US', Translator::getLang());
    }

    public function testInitializeTranslatorWithMinus()
    {
        Translator::config('ru-RU');
        $this->assertEquals('ru_RU', Translator::getDefaultLang());
        $this->assertEquals('ru_RU', Translator::getLang());
        $this->assertEquals('Тестовое сообщение', Translator::get('main', 'Тестовое сообщение'));

        Translator::setLang('en-US');
        $this->assertEquals('ru_RU', Translator::getDefaultLang());
        $this->assertEquals('en_US', Translator::getLang());
    }

    public function testGetTranslatorTranslation()
    {
        Translator::config('en_US');
        Translator::setLang('en_US');
        $this->assertEquals('Test message', Translator::get('main', 'Тестовое сообщение'));
        $this->assertEquals('', Translator::get('second', ''));
        $this->assertEquals('', Translator::get('third', 'Тестовое сообщение'));
        $this->assertEquals('UnknownMessage', Translator::get('third', 'UnknownMessage'));
        $this->assertEquals('Сообщение с параметром', Translator::get('main', 'Сообщение с {param}', ['param' => 'параметром']));
        $this->assertEquals('Сообщение с {param}', Translator::get('main', 'Сообщение с {param}'));
        $this->assertEquals('Message with delay to 11AM', Translator::get('main', 'Сообщение с {delay}', ['delay' => 'delay to 11AM']));
        $this->assertEquals('Message with {delay}', Translator::get('main', 'Сообщение с {delay}'));
        $this->assertEquals('Full name', Translator::get('third', 'Ф.И.О.'));
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        $filesystem = new Filesystem();
        $translatorPath = (string) (new Path(__DIR__))->up()->down('src')->down('TranslatorExample.php');

        if ($filesystem->exists($translatorPath)) {
            $filesystem->remove($translatorPath);
        }
    }
}