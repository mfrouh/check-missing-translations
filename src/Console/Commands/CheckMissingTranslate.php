<?php

namespace  MFrouh\CheckMissingTranslations\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CheckMissingTranslate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-translate {--directory=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if all translations are there for all languages.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $array     = [];
        $directory =  app()->basePath() . '/' . $this->option('directory') ?: app()->basePath() . '/app';

        $pattern1 = '/__\(([\w. \']+)\)/';
        $pattern2 = '/@lang\(([\w. \']+)\)/';
        $pattern3 = '/trans\(([\w. \']+)\)/';

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        
        $languages = $this->getLanguages();
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && !str_contains($file->getPathname(), 'vendor/') && !str_contains($file->getPathname(), 'storage/framework')) {
                $content = file_get_contents($file->getPathname());
                if (preg_match_all($pattern1, $content, $matches) || preg_match_all($pattern2, $content, $matches) || preg_match_all($pattern3, $content, $matches)) {
                    foreach ($matches[1] as $match) {
                        $is_json =  str_contains($match, '.') ? false : true;
                        $fileName =  explode('.', str_replace("'", '', $match))[0];
                        $key      = str_replace($fileName . '.', '', str_replace("'", '', $match));
                        foreach ($languages as $language) {
                            $langFile = (!$is_json) ? './resources/lang/' . $language . '/' . $fileName . '.php' : './resources/lang/' . $language . '.json';
                            $langArray       = file_exists($langFile) ? include $langFile : [];
                            $pathName        =  str_replace($directory, '', $file->getPathname());
                            $match           = str_replace("'", '', $match);
                            $checkValidation = file_exists($langFile) ? (!array_key_exists($key, $langArray) ? true : false) : true;

                            if ($checkValidation) {
                                $array[] = [
                                    'file_name' => $is_json ? 'json' : $fileName,
                                    'key'       => $key,
                                    $language   => 1,
                                    'path'      => $pathName,
                                ];
                            }
                        }
                    }
                }
            }
        }

        $newArray = [];

        if (count($array) == 0) {
            $this->info('✔ All translations are okay!');

            return Command::SUCCESS;
        }

        foreach ($array as $key => $value) {
            if (!array_key_exists($value['key'], $newArray)) {
                $arrayLang      = [];
                $arrayLanguages = array_keys(array_flip($languages));
                foreach ($arrayLanguages as $key => $language) {
                    if ($language == array_keys(array_intersect_key(array_flip($languages), $value))[0]) {
                        $arrayLang[$language] = 'x';
                    } else {
                        $arrayLang[$language] = '✔';
                    }
                }
                $newArray[$value['key']] = [
                    'file_name' => $value['file_name'],
                    'key'       => $value['key'],
                    'path'      => $value['path'],
                ] + $arrayLang;
            } else {
                foreach ($arrayLanguages as $key => $language) {
                    if ($language == array_keys(array_intersect_key(array_flip($languages), $value))[0]) {
                        $arrayLang[$language] = 'x';
                    } else {
                        $arrayLang[$language] = '✔';
                    }
                    if ($newArray[$value['key']][$language] != 'x') {
                        $newArray[$value['key']][$language] = $arrayLang[$language];
                    }
                }
            }
        }

        $this->table(array_merge(['File Name', 'Key', 'Path'], array_keys(array_flip($languages))), $newArray);

        return Command::SUCCESS;
    }

    private function getLanguages(): array
    {
        $languages = [];

        if ($handle = opendir(app()->basePath() . '/resources/lang')) {
            while (false !== ($languageDir = readdir($handle))) {
                if ($languageDir !== '.' && $languageDir !== '..') {
                    $languages[] = str_replace('.json', '', $languageDir);
                }
            }
        }

        closedir($handle);

        return $languages;
    }
}
