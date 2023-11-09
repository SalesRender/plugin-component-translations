<?php
/**
 * Created for plugin-component-translations
 * Datetime: 14.02.2020 17:52
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace SalesRender\Plugin\Components\Translations\Commands;


use SalesRender\Plugin\Components\Translations\Components\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LangUpdateCommand extends CrawlerCommand
{

    public function __construct()
    {
        parent::__construct('lang:update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $actual = $this->crawl();

        $path = Helper::getTranslationsPath();
        foreach (Helper::getLanguages() as $language) {

            $file = $path->down("{$language}.json");
            $lang = json_decode(file_get_contents($file), true);

            $loaded = [];
            foreach ($lang as $section => $translations) {
                foreach ($translations as $translation) {
                    $source = $translation['source'];
                    $translated = $translation['translated'];
                    $loaded[$section][$source] = $translated;
                }
            }

            foreach ($actual as $section => $translations) {
                foreach ($translations as $translation => $bool) {
                    $value = '';
                    if (isset($loaded[$section][$translation])) {
                        $value = $loaded[$section][$translation];
                    }
                    $actual[$section][$translation] = $value;
                }
            }

            if (json_encode($loaded) !== json_encode($actual)) {
                file_put_contents($file, $this->asJson($actual));
                file_put_contents(
                    (string) $path->down("{$language}.old.json"),
                    $this->asJson($loaded)
                );
                $output->writeln("Translation for '{$language}' was changed");
            }
        }

        return 0;
    }

}