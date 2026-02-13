# salesrender/plugin-component-translations

Internationalization (i18n) component for SalesRender plugins, providing JSON-based text localization with CLI tools for managing translation files.

## Overview

This component implements a complete translation system for the SalesRender plugin ecosystem. It enables plugins to support multiple languages by storing translations in structured JSON files organized by categories and source strings.

The `Translator` class acts as the central entry point: it loads locale-specific JSON files, resolves translated strings with optional parameter interpolation, and falls back to the default language when a translation is missing. The component also ships with Symfony Console commands that automate the creation and synchronization of translation files by statically analyzing your plugin source code for `Translator::get()` calls.

Translation files are stored in the `translations/` directory at the project root (resolved relative to Composer's autoloader). Each file is named using the `xx_YY` locale format (e.g., `ru_RU.json`, `en_US.json`) and contains a JSON structure grouping translations by category.

## Installation

```bash
composer require salesrender/plugin-component-translations
```

## Requirements

- PHP >= 7.4
- `ext-json`
- [nikic/php-parser](https://github.com/nikic/PHP-Parser) ^4.3
- [symfony/console](https://github.com/symfony/console) ^5.0
- [haydenpierce/class-finder](https://gitlab.com/hpierce1102/ClassFinder) ^0.4.0
- [xakepehok/path](https://github.com/XAKEPEHOK/Path) ^0.2
- [adbario/php-dot-notation](https://github.com/adbario/php-dot-notation) ^2.2

## Key Classes

### `Translator`

**Namespace:** `SalesRender\Plugin\Components\Translations`

The main static class for configuring and retrieving translations.

#### Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `config` | `static config(string $default): void` | Initializes the translator with a default locale (e.g., `ru_RU`). Must be called before any other method. |
| `get` | `static get(string $category, string $message, array $params = []): string` | Returns a translated string for the given category and message key. Supports parameter interpolation via `{key}` placeholders. Falls back to default language, then to the raw `$message` string. |
| `setLang` | `static setLang(string $lang): void` | Sets the current language. Only applies if the language exists in the available languages list. Accepts both `en_US` and `en-US` formats. |
| `getLang` | `static getLang(): string` | Returns the currently active language code. |
| `getDefaultLang` | `static getDefaultLang(): ?string` | Returns the default language code, or `null` if not configured. |
| `getLanguages` | `static getLanguages(): array` | Returns an array of all available language codes (discovered from translation files plus the default language). |

---

### `Helper`

**Namespace:** `SalesRender\Plugin\Components\Translations\Components`

Utility class for path resolution and language discovery.

#### Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `getTranslationsPath` | `static getTranslationsPath(): Path` | Returns a `Path` object pointing to the `translations/` directory at the project root (resolved via Composer's `ClassLoader`). |
| `getLanguages` | `static getLanguages(): array` | Scans the translations directory and returns an array of available locale codes (filenames matching the `xx_YY` pattern). |

---

### `CrawlerCommand` (abstract)

**Namespace:** `SalesRender\Plugin\Components\Translations\Commands`

Abstract Symfony Console command that provides the core logic for extracting translatable strings from source code. It uses `nikic/php-parser` to parse all PHP classes under the `SalesRender\Plugin` namespace and finds every `Translator::get('category', 'message')` call.

#### Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `crawl` | `protected crawl(): array` | Parses all project classes and extracts `Translator::get()` calls. Returns an associative array `[category => [message => true]]`. |
| `asJson` | `protected asJson(array $data): string` | Converts a translation scheme array into a pretty-printed JSON string. |
| `schemeToExport` | `protected schemeToExport(array $scheme): array` | Transforms the internal scheme format into the export format with `source`/`translated` pairs. |

---

### `LangAddCommand`

**Namespace:** `SalesRender\Plugin\Components\Translations\Commands`

Symfony Console command registered as `lang:add`. Creates a new translation file for a specified locale by crawling the source code for translatable strings.

**Usage:**
```bash
php console lang:add en_US
```

---

### `LangUpdateCommand`

**Namespace:** `SalesRender\Plugin\Components\Translations\Commands`

Symfony Console command registered as `lang:update`. Synchronizes all existing translation files with the current source code, preserving existing translations and creating `.old.json` backups when changes are detected.

**Usage:**
```bash
php console lang:update
```

## Usage

### Basic Configuration

Configure the translator in your plugin's `bootstrap.php` file:

```php
use SalesRender\Plugin\Components\Translations\Translator;

// Set the default language for the plugin
Translator::config('ru_RU');
```

### Retrieving Translations

Use `Translator::get()` with a category and a message key:

```php
use SalesRender\Plugin\Components\Translations\Translator;

// Simple translation
$name = Translator::get('info', 'PLUGIN_NAME');
$description = Translator::get('info', 'PLUGIN_DESCRIPTION');

// Translation with parameter interpolation
$label = Translator::get('autocomplete', 'DYNAMIC_VALUE #{value}', ['value' => $query]);
$range = Translator::get('autocomplete', 'GROUP_FROM_TO ({min}-{max})', ['min' => 1, 'max' => 10]);

// Using in form definitions
$title = Translator::get('settings', 'Settings');
$fieldLabel = Translator::get('settings', 'Answer prefix');
$error = Translator::get('settings', 'Field can not be empty');
```

### Switching Languages at Runtime

```php
use SalesRender\Plugin\Components\Translations\Translator;

// Set language for the current request
Translator::setLang('en_US');

// Hyphenated format is also accepted (automatically converted to underscore)
Translator::setLang('en-US');

// Get the active language
$currentLang = Translator::getLang(); // e.g., "en_US"

// Get all available languages
$languages = Translator::getLanguages(); // e.g., ['ru_RU', 'en_US']
```

### Using with Plugin Info

Translations are commonly used with lazy-evaluated closures for plugin metadata so that the language can be resolved at request time:

```php
use SalesRender\Plugin\Components\Info\Info;
use SalesRender\Plugin\Components\Info\PluginType;
use SalesRender\Plugin\Components\Info\Developer;
use SalesRender\Plugin\Components\Translations\Translator;

Translator::config('ru_RU');

Info::config(
    new PluginType(PluginType::MACROS),
    fn() => Translator::get('info', 'PLUGIN_NAME'),
    fn() => Translator::get('info', 'PLUGIN_DESCRIPTION'),
    new PluginPurpose(
        new MacrosPluginClass(MacrosPluginClass::CLASS_HANDLER),
        new PluginEntity(PluginEntity::ENTITY_ORDER)
    ),
    new Developer('LeadVertex', 'support@leadvertex.com', 'leadvertex.com')
);
```

### Using in Batch Handlers

The batch component sets the language from the batch context before processing:

```php
use SalesRender\Plugin\Components\Translations\Translator;

// Set the language passed from the batch request
Translator::setLang(str_replace('-', '_', $batch->getLang()));

// All subsequent Translator::get() calls will use this language
$errorMessage = Translator::get('batch', 'Failed to create waybill');
```

## Translation File Format

Translation files are stored in the `translations/` directory at the project root:

```
project-root/
  translations/
    ru_RU.json
    en_US.json
```

### JSON Structure

```json
{
    "info": [
        {
            "source": "PLUGIN_NAME",
            "translated": "Example plugin"
        },
        {
            "source": "PLUGIN_DESCRIPTION",
            "translated": "This plugin was created for demo purposes"
        }
    ],
    "settings": [
        {
            "source": "Settings",
            "translated": "Settings"
        },
        {
            "source": "Field can not be empty",
            "translated": "Field can not be empty"
        }
    ],
    "autocomplete": [
        {
            "source": "GROUP_FROM_TO ({min}-{max})",
            "translated": "From {min} to {max}"
        }
    ]
}
```

Each top-level key is a **category** (the first argument to `Translator::get()`). Each category contains an array of objects with:
- `source` -- the message key (second argument to `Translator::get()`)
- `translated` -- the localized text

### Parameter Interpolation

Parameters are referenced with `{key}` syntax in both the source and translated strings:

```json
{
    "sender": [
        {
            "source": "Sender #{number}",
            "translated": "Sender #{number}"
        }
    ]
}
```

```php
Translator::get('sender', 'Sender #{number}', ['number' => $this->number]);
```

## CLI Commands

### Adding a New Language

```bash
php console lang:add en_US
```

Creates `translations/en_US.json` with all discovered translatable strings and empty `translated` values. The locale must match the `xx_YY` format (e.g., `en_US`, `ru_RU`, `de_DE`). The command fails if the file already exists.

### Updating Existing Translations

```bash
php console lang:update
```

Scans all source code under the `SalesRender\Plugin` namespace, then for each existing translation file:
- Adds new strings that were found in the code (with empty `translated` values)
- Removes strings that no longer exist in the code
- Preserves all existing translations for unchanged strings
- Creates a `xx_YY.old.json` backup when changes are detected

## Configuration

The translator requires a single configuration call before use:

```php
Translator::config('ru_RU');
```

The locale must follow the `xx_YY` format (ISO 639-1 language code + underscore + ISO 3166-1 alpha-2 country code). Hyphens are automatically converted to underscores.

Calling any method (`get`, `getLang`, `setLang`, `getLanguages`) before `config()` throws a `RuntimeException` with the message `"Translator was not configured"`.

## API Reference

### `Translator`

```php
public static function config(string $default): void
public static function get(string $category, string $message, array $params = []): string
public static function setLang(string $lang): void
public static function getLang(): string
public static function getDefaultLang(): ?string
public static function getLanguages(): array
```

### `Helper`

```php
public static function getTranslationsPath(): \XAKEPEHOK\Path\Path
public static function getLanguages(): array
```

### CLI Commands

| Command | Arguments | Description |
|---------|-----------|-------------|
| `lang:add` | `lang` (required) -- Locale code in `xx_YY` format | Creates a new translation JSON file |
| `lang:update` | none | Synchronizes all translation files with the current source code |

## Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `nikic/php-parser` | ^4.3 | Static analysis of PHP source code to extract `Translator::get()` calls |
| `symfony/console` | ^5.0 | CLI command infrastructure (`lang:add`, `lang:update`) |
| `haydenpierce/class-finder` | ^0.4.0 | Discovering classes under the `SalesRender\Plugin` namespace for crawling |
| `xakepehok/path` | ^0.2 | Filesystem path resolution for the translations directory |
| `adbario/php-dot-notation` | ^2.2 | Dot notation access to parsed PHP AST nodes during source code crawling |

## See Also

- [salesrender/plugin-component-info](https://github.com/SalesRender/plugin-component-info) -- Uses `Translator` for localized plugin name and description in JSON serialization
- [salesrender/plugin-core](https://github.com/SalesRender/plugin-core) -- Contains `LanguageMiddleware` that calls `Translator::setLang()` based on the HTTP `Accept-Language` header
- [salesrender/plugin-component-batch](https://github.com/SalesRender/plugin-component-batch) -- Calls `Translator::setLang()` to set language from the batch processing context
