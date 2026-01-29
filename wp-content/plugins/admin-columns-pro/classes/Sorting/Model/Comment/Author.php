<?php

namespace ACP\Sorting\Model\Comment;

use AC\FormatterCollection;
use ACP;
use ACP\Sorting\Model\WarningAware;

class Author extends FieldFormat implements WarningAware
{

    public function __construct(FormatterCollection $formatters)
    {
        parent::__construct('user_id', new ACP\Sorting\FormatValue\SettingFormatter($formatters));
    }

}