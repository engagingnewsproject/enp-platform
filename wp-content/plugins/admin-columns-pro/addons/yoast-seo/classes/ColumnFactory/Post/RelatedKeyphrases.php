<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\YoastSeo;
use ACA\YoastSeo\Editing;
use ACP;

class RelatedKeyphrases extends ACP\Column\AdvancedColumnFactory
{

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'wpseo-score-related_keyphrases';
    }

    public function get_label(): string
    {
        return __('Related Keyphrases', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta('_yoast_wpseo_focuskeywords'),
            new YoastSeo\Value\Formatter\RelatedKeyphrases(),
        ]);
    }

}