<?php
/**
 * Created for plugin-component-translations
 * Datetime: 14.02.2020 17:09
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Translations;


use Adbar\Dot;
use InvalidArgumentException;
use Leadvertex\Plugin\Components\Translations\Components\Helper;
use RuntimeException;

class Translator
{

    protected static string $default;

    protected static string $lang;

    private static array $languages;

    public static function config(string $default)
    {
        $default = str_replace('-', '_', $default);

        if (!preg_match('~^[a-z]{2}_[A-Z]{2}$~', $default)) {
            throw new InvalidArgumentException();
        }

        static::$default = $default;
        static::$lang = $default;
    }

    public static function getLanguages(): array
    {
        static::guardNotConfigured();
        if (!isset(self::$languages)) {
            self::$languages = array_unique(
                array_merge(Helper::getLanguages(), [static::$default])
            );
        }
        return self::$languages;
    }

    public static function getDefaultLang(): ?string
    {
        return static::$default ?? null;
    }

    public static function getLang(): string
    {
        static::guardNotConfigured();
        return static::$lang;
    }

    public static function setLang(string $lang)
    {
        $lang = str_replace('-', '_', $lang);
        static::guardNotConfigured();
        if (in_array($lang, static::getLanguages())) {
            static::$lang = $lang;
        }
    }

    public static function get(string $category, string $message, array $params = []): string
    {
        static::guardNotConfigured();

        $default = static::load(static::$default);
        $lang = static::load(static::$lang);

        $path = "{$category}.{$message}";
        $translation = $lang->get(
            $path,
            $default->get($path, $message)
        );

        foreach ($params as $key => $param) {
            $translation = str_replace('{' . $key . '}', (string) $param, $translation);
        }

        return $translation;
    }

    protected static function load(string $lang): Dot
    {
        $path = Helper::getTranslationsPath();
        $translations = (string) $path->down($lang . '.json');

        if (file_exists($translations)) {
            $translations = file_get_contents($translations);
            $translations = json_decode($translations, true);

            $associative = [];
            foreach ($translations as $category => $translation) {
                foreach ($translation as $value) {
                    $associative[$category][$value['source']] = $value['translated'];
                }
            }

            return new Dot($associative);
        }

        return new Dot([]);
    }
    
    protected static function guardNotConfigured()
    {
        if (!isset(static::$default)) {
            throw new RuntimeException('Translator was not configured');
        }
    }

}