<?php
/**
 * Created for plugin-component-translations
 * Datetime: 14.02.2020 17:52
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace SalesRender\Plugin\Components\Translations\Commands;


use RuntimeException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LangAddCommand extends CrawlerCommand
{

    public function __construct()
    {
        parent::__construct('lang:add');
        $this->addArgument(
            'lang',
            InputArgument::REQUIRED,
            'Create new translation file in some language (en_US, ru_RU)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scheme = $this->crawl();

        $lang = $input->getArgument('lang');
        if (!preg_match('~^[a-z]{2}_[A-Z]{2}$~', $lang)) {
            throw new InvalidArgumentException("Lang should be in format like 'en_US', 'ru_RU', etc...");
        }

        $filename = (string) $this->translationFilesPath->down("/{$lang}.json");
        if (file_exists($filename)) {
            throw new RuntimeException("File '{$lang}.json' already exists");
        }

        file_put_contents($filename, $this->asJson($scheme));

        return 0;
    }

}