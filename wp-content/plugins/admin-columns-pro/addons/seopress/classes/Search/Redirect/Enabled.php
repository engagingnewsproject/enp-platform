<?php

declare(strict_types=1);

namespace ACA\SeoPress\Search\Redirect;

use AC\Helper\Select\Options;
use ACP\Search\Comparison;
use ACP\Search\Operators;
use ACP\Search\Value;

class Enabled extends Comparison\Meta implements Comparison\Values
{

    public function __construct()
    {
        parent::__construct(new Operators([
            Operators::EQ,
        ]), '_seopress_redirections_enabled');
    }

    protected function get_meta_query(string $operator, Value $value): array
    {
        if ($value->get_value() === 'no') {
            $operator = Operators::IS_EMPTY;
        }

        return parent::get_meta_query($operator, $value);
    }

    public function get_values(): Options
    {
        return Options::create_from_array([
            'yes' => 'Enabled',
            'no'  => 'Disabled',
        ]);
    }

}