# salesrender/plugin-component-translations

Компонент интернационализации (i18n) для плагинов SalesRender, обеспечивающий локализацию текстов на основе JSON-файлов с CLI-инструментами для управления переводами.

## Обзор

Данный компонент реализует полноценную систему переводов для экосистемы плагинов SalesRender. Он позволяет плагинам поддерживать несколько языков, храня переводы в структурированных JSON-файлах, организованных по категориям и исходным строкам.

Класс `Translator` является центральной точкой входа: он загружает JSON-файлы для конкретной локали, разрешает переведённые строки с поддержкой подстановки параметров и использует язык по умолчанию, если перевод отсутствует. Компонент также поставляется с командами Symfony Console, которые автоматизируют создание и синхронизацию файлов переводов путём статического анализа исходного кода плагина на наличие вызовов `Translator::get()`.

Файлы переводов хранятся в директории `translations/` в корне проекта (путь определяется относительно автозагрузчика Composer). Каждый файл именуется в формате локали `xx_YY` (например, `ru_RU.json`, `en_US.json`) и содержит JSON-структуру, группирующую переводы по категориям.

## Установка

```bash
composer require salesrender/plugin-component-translations
```

## Требования

- PHP >= 7.4
- `ext-json`
- [nikic/php-parser](https://github.com/nikic/PHP-Parser) ^4.3
- [symfony/console](https://github.com/symfony/console) ^5.0
- [haydenpierce/class-finder](https://gitlab.com/hpierce1102/ClassFinder) ^0.4.0
- [xakepehok/path](https://github.com/XAKEPEHOK/Path) ^0.2
- [adbario/php-dot-notation](https://github.com/adbario/php-dot-notation) ^2.2

## Основные классы

### `Translator`

**Пространство имён:** `SalesRender\Plugin\Components\Translations`

Основной статический класс для настройки и получения переводов.

#### Методы

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `config` | `static config(string $default): void` | Инициализирует переводчик с локалью по умолчанию (например, `ru_RU`). Должен быть вызван перед любым другим методом. |
| `get` | `static get(string $category, string $message, array $params = []): string` | Возвращает переведённую строку для заданной категории и ключа сообщения. Поддерживает подстановку параметров через синтаксис `{key}`. При отсутствии перевода использует язык по умолчанию, а затем исходную строку `$message`. |
| `setLang` | `static setLang(string $lang): void` | Устанавливает текущий язык. Применяется только если язык присутствует в списке доступных языков. Принимает форматы `en_US` и `en-US`. |
| `getLang` | `static getLang(): string` | Возвращает код текущего активного языка. |
| `getDefaultLang` | `static getDefaultLang(): ?string` | Возвращает код языка по умолчанию или `null`, если не настроен. |
| `getLanguages` | `static getLanguages(): array` | Возвращает массив всех доступных кодов языков (найденных в файлах переводов плюс язык по умолчанию). |

---

### `Helper`

**Пространство имён:** `SalesRender\Plugin\Components\Translations\Components`

Вспомогательный класс для определения путей и обнаружения языков.

#### Методы

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `getTranslationsPath` | `static getTranslationsPath(): Path` | Возвращает объект `Path`, указывающий на директорию `translations/` в корне проекта (определяется через `ClassLoader` Composer). |
| `getLanguages` | `static getLanguages(): array` | Сканирует директорию переводов и возвращает массив доступных кодов локалей (имена файлов, соответствующие шаблону `xx_YY`). |

---

### `CrawlerCommand` (абстрактный)

**Пространство имён:** `SalesRender\Plugin\Components\Translations\Commands`

Абстрактная команда Symfony Console, предоставляющая базовую логику для извлечения переводимых строк из исходного кода. Использует `nikic/php-parser` для парсинга всех PHP-классов в пространстве имён `SalesRender\Plugin` и находит каждый вызов `Translator::get('category', 'message')`.

#### Методы

| Метод | Сигнатура | Описание |
|-------|-----------|----------|
| `crawl` | `protected crawl(): array` | Парсит все классы проекта и извлекает вызовы `Translator::get()`. Возвращает ассоциативный массив `[категория => [сообщение => true]]`. |
| `asJson` | `protected asJson(array $data): string` | Конвертирует массив схемы переводов в форматированную JSON-строку. |
| `schemeToExport` | `protected schemeToExport(array $scheme): array` | Преобразует внутренний формат схемы в формат экспорта с парами `source`/`translated`. |

---

### `LangAddCommand`

**Пространство имён:** `SalesRender\Plugin\Components\Translations\Commands`

Команда Symfony Console, зарегистрированная как `lang:add`. Создаёт новый файл перевода для указанной локали, сканируя исходный код на наличие переводимых строк.

**Использование:**
```bash
php console lang:add en_US
```

---

### `LangUpdateCommand`

**Пространство имён:** `SalesRender\Plugin\Components\Translations\Commands`

Команда Symfony Console, зарегистрированная как `lang:update`. Синхронизирует все существующие файлы переводов с текущим исходным кодом, сохраняя имеющиеся переводы и создавая резервные копии `.old.json` при обнаружении изменений.

**Использование:**
```bash
php console lang:update
```

## Использование

### Базовая настройка

Настройте переводчик в файле `bootstrap.php` вашего плагина:

```php
use SalesRender\Plugin\Components\Translations\Translator;

// Установка языка по умолчанию для плагина
Translator::config('ru_RU');
```

### Получение переводов

Используйте `Translator::get()` с указанием категории и ключа сообщения:

```php
use SalesRender\Plugin\Components\Translations\Translator;

// Простой перевод
$name = Translator::get('info', 'PLUGIN_NAME');
$description = Translator::get('info', 'PLUGIN_DESCRIPTION');

// Перевод с подстановкой параметров
$label = Translator::get('autocomplete', 'DYNAMIC_VALUE #{value}', ['value' => $query]);
$range = Translator::get('autocomplete', 'GROUP_FROM_TO ({min}-{max})', ['min' => 1, 'max' => 10]);

// Использование в определениях форм
$title = Translator::get('settings', 'Settings');
$fieldLabel = Translator::get('settings', 'Answer prefix');
$error = Translator::get('settings', 'Field can not be empty');
```

### Переключение языка во время выполнения

```php
use SalesRender\Plugin\Components\Translations\Translator;

// Установка языка для текущего запроса
Translator::setLang('en_US');

// Формат с дефисом также принимается (автоматически конвертируется в подчёркивание)
Translator::setLang('en-US');

// Получение активного языка
$currentLang = Translator::getLang(); // например, "en_US"

// Получение всех доступных языков
$languages = Translator::getLanguages(); // например, ['ru_RU', 'en_US']
```

### Использование с информацией о плагине

Переводы часто применяются с отложенными замыканиями для метаданных плагина, чтобы язык определялся в момент обработки запроса:

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

### Использование в обработчиках пакетной обработки

Компонент batch устанавливает язык из контекста пакетной обработки перед выполнением:

```php
use SalesRender\Plugin\Components\Translations\Translator;

// Установка языка, переданного из запроса пакетной обработки
Translator::setLang(str_replace('-', '_', $batch->getLang()));

// Все последующие вызовы Translator::get() будут использовать этот язык
$errorMessage = Translator::get('batch', 'Не удалось создать накладную');
```

## Формат файлов переводов

Файлы переводов хранятся в директории `translations/` в корне проекта:

```
project-root/
  translations/
    ru_RU.json
    en_US.json
```

### Структура JSON

```json
{
    "info": [
        {
            "source": "PLUGIN_NAME",
            "translated": "Пример плагина"
        },
        {
            "source": "PLUGIN_DESCRIPTION",
            "translated": "Этот плагин создан в демонстрационных целях"
        }
    ],
    "settings": [
        {
            "source": "Settings",
            "translated": "Настройки"
        },
        {
            "source": "Field can not be empty",
            "translated": "Поле не может быть пустым"
        }
    ],
    "autocomplete": [
        {
            "source": "GROUP_FROM_TO ({min}-{max})",
            "translated": "От {min} до {max}"
        }
    ]
}
```

Каждый ключ верхнего уровня -- это **категория** (первый аргумент `Translator::get()`). Каждая категория содержит массив объектов с полями:
- `source` -- ключ сообщения (второй аргумент `Translator::get()`)
- `translated` -- локализованный текст

### Подстановка параметров

Параметры указываются с помощью синтаксиса `{key}` как в исходных, так и в переведённых строках:

```json
{
    "sender": [
        {
            "source": "Отправитель #{number}",
            "translated": "Отправитель #{number}"
        }
    ]
}
```

```php
Translator::get('sender', 'Отправитель #{number}', ['number' => $this->number]);
```

## CLI-команды

### Добавление нового языка

```bash
php console lang:add en_US
```

Создаёт файл `translations/en_US.json` со всеми обнаруженными переводимыми строками и пустыми значениями `translated`. Локаль должна соответствовать формату `xx_YY` (например, `en_US`, `ru_RU`, `de_DE`). Команда завершится с ошибкой, если файл уже существует.

### Обновление существующих переводов

```bash
php console lang:update
```

Сканирует весь исходный код в пространстве имён `SalesRender\Plugin`, затем для каждого существующего файла переводов:
- Добавляет новые строки, найденные в коде (с пустыми значениями `translated`)
- Удаляет строки, которых больше нет в коде
- Сохраняет существующие переводы для неизменённых строк
- Создаёт резервную копию `xx_YY.old.json` при обнаружении изменений

## Настройка

Переводчик требует единственного вызова конфигурации перед использованием:

```php
Translator::config('ru_RU');
```

Локаль должна соответствовать формату `xx_YY` (код языка ISO 639-1 + подчёркивание + код страны ISO 3166-1 alpha-2). Дефисы автоматически преобразуются в подчёркивания.

Вызов любого метода (`get`, `getLang`, `setLang`, `getLanguages`) до вызова `config()` приводит к выбросу исключения `RuntimeException` с сообщением `"Translator was not configured"`.

## Справочник API

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

### CLI-команды

| Команда | Аргументы | Описание |
|---------|-----------|----------|
| `lang:add` | `lang` (обязательный) -- Код локали в формате `xx_YY` | Создаёт новый JSON-файл перевода |
| `lang:update` | нет | Синхронизирует все файлы переводов с текущим исходным кодом |

## Зависимости

| Пакет | Версия | Назначение |
|-------|--------|------------|
| `nikic/php-parser` | ^4.3 | Статический анализ PHP-кода для извлечения вызовов `Translator::get()` |
| `symfony/console` | ^5.0 | Инфраструктура CLI-команд (`lang:add`, `lang:update`) |
| `haydenpierce/class-finder` | ^0.4.0 | Обнаружение классов в пространстве имён `SalesRender\Plugin` для сканирования |
| `xakepehok/path` | ^0.2 | Определение путей к директории переводов в файловой системе |
| `adbario/php-dot-notation` | ^2.2 | Доступ через dot-нотацию к узлам AST-дерева при анализе исходного кода |

## Смотрите также

- [salesrender/plugin-component-info](https://github.com/SalesRender/plugin-component-info) -- Использует `Translator` для локализованных имени и описания плагина при JSON-сериализации
- [salesrender/plugin-core](https://github.com/SalesRender/plugin-core) -- Содержит `LanguageMiddleware`, вызывающий `Translator::setLang()` на основе HTTP-заголовка `Accept-Language`
- [salesrender/plugin-component-batch](https://github.com/SalesRender/plugin-component-batch) -- Вызывает `Translator::setLang()` для установки языка из контекста пакетной обработки
