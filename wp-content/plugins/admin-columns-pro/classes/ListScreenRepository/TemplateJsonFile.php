<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use ACP\Storage\FileIterator;
use ArrayIterator;
use SplFileInfo;

final class TemplateJsonFile extends JsonFile
{

    protected function get_files(): FileIterator
    {
        $iterator = new ArrayIterator();

        $files = (array)apply_filters('acp/storage/template/files', []);

        foreach ($files as $file) {
            $iterator->append(new SplFileInfo($file));
        }

        return new FileIterator($iterator, 'json');
    }

}