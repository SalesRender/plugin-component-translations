<?php
/**
 * Created for plugin-component-translations
 * Datetime: 19.02.2020 15:54
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace SalesRender\Plugin\Components\Translations\Components;


use Composer\Autoload\ClassLoader;
use FilesystemIterator;
use ReflectionClass;
use XAKEPEHOK\Path\Path;

class Helper
{

    public static function getTranslationsPath(): Path
    {
        $reflection = new ReflectionClass(ClassLoader::class);
        return (new Path($reflection->getFileName()))->up()->up()->up()->down('translations');
    }

    public static function getLanguages(): array
    {
        $path = static::getTranslationsPath();
        if (!is_dir($path)) {
            return [];
        }
        $iterator = new FilesystemIterator($path);
        $translations = [];
        foreach ($iterator as $info) {
            $basename = $info->getBasename('.' . $info->getExtension());
            if (preg_match('~^[a-z]{2}_[A-Z]{2}$~', $basename)) {
                $translations[] = $basename;
            }
        }
        return $translations;
    }

}