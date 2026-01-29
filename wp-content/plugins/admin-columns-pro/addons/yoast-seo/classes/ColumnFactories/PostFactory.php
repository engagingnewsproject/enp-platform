<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\YoastSeo\ColumnFactory;
use Yoast;

class PostFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Post\CanonicalUrlFactory::class,
            ColumnFactory\Post\FocusKeywordCountFactory::class,
            ColumnFactory\Post\SocialDescriptionFactory::class,
            ColumnFactory\Post\SocialImageFactory::class,
            ColumnFactory\Post\SocialTitleFactory::class,
            ColumnFactory\Post\IsIndexedFactory::class,
            ColumnFactory\Post\PrimaryTaxonomyFactory::class,
            ColumnFactory\Post\RelatedKeyphrases::class,
            ColumnFactory\Post\SchemaPageTypeFactory::class,
            ColumnFactory\Post\TwitterDescriptionFactory::class,
            ColumnFactory\Post\TwitterImageFactory::class,
            ColumnFactory\Post\TwitterTitleFactory::class,
            ColumnFactory\Post\ReadabilityScore::class,
        ];

        if ($this->is_article_post_type((string)$table_screen->get_post_type())) {
            $factories[] = ColumnFactory\Post\SchemaArticleTypeFactory::class;
        }

        if (defined('WPSEO_PREMIUM_FILE')) {
            $factories[] = ColumnFactory\Post\ReadingTime::class;
        }

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

    private function is_article_post_type(string $post_type): bool
    {
        return class_exists('Yoast\WP\SEO\Helpers\Schema\Article_Helper') &&
               (new Yoast\WP\SEO\Helpers\Schema\Article_Helper())->is_article_post_type($post_type);
    }
}