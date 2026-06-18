<?php

namespace ACP\Sorting\FormatValue;

use AC\Helper;
use ACP\Sorting\FormatValue;

class WordCount implements FormatValue
{

    public function format_value($string)
    {
        return Helper\Strings::create()->word_count((string)$string);
    }

}
