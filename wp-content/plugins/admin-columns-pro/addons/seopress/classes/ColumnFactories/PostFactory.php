<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use ACA\SeoPress\ColumnFactory;
use ACA\SeoPress\ColumnFactory\Post\MetaNumberStatFactory;
use ACA\SeoPress\ColumnFactory\Post\MetaTextAreaFactory;
use ACA\SeoPress\ColumnFactory\Post\MetaTextFactory;
use ACA\SeoPress\ColumnFactory\Post\MetaUrlFactory;

class PostFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post) {
            return $collection;
        }

        if ($table_screen->get_post_type()->equals('seopress_404')) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Post\NoFollow::class,
            ColumnFactory\Post\NoIndex::class,
            ColumnFactory\Post\Redirect::class,
            ColumnFactory\Post\FacebookImage::class,
            ColumnFactory\Post\FacebookPreview::class,
            ColumnFactory\Post\XImage::class,
            ColumnFactory\Post\XPreview::class,

        ];

        if ($table_screen->get_post_type()->equals('post')) {
            $factories[] = ColumnFactory\Post\PrimaryTaxonomy::class;
        }

        foreach ($factories as $factory) {
            $collection->add(new ColumnFactoryDefinition($factory));
        }

        $collection->add(new ColumnFactoryDefinition(MetaTextAreaFactory::class, [
            'type'     => 'column-sp_desc',
            'label'    => __('Meta description', 'wp-seopress-pro'),
            'meta_key' => '_seopress_titles_desc',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaNumberStatFactory::class, [
            'type'     => 'column-sp_gsc_clicks',
            'label'    => __('Clicks', 'wp-seopress-pro'),
            'meta_key' => '_seopress_search_console_analysis_clicks',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaNumberStatFactory::class, [
            'type'     => 'column-sp_gsc_ctr',
            'label'    => __('CTR', 'wp-seopress-pro'),
            'meta_key' => '_seopress_search_console_analysis_ctr',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaNumberStatFactory::class, [
            'type'     => 'column-sp_gsc_impressions',
            'label'    => __('Impressions', 'wp-seopress-pro'),
            'meta_key' => '_seopress_search_console_analysis_impressions',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaNumberStatFactory::class, [
            'type'     => 'column-sp_gsc_positions',
            'label'    => __('Position', 'wp-seopress-pro'),
            'meta_key' => '_seopress_search_console_analysis_position',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaUrlFactory::class, [
            'type'     => 'column-sp_redirect_url',
            'label'    => __('Redirect URL', 'wp-seopress-pro'),
            'meta_key' => '_seopress_redirections_value',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaUrlFactory::class, [
            'type'     => 'column-sp_canonical',
            'label'    => __('Canonical', 'wp-seopress-pro'),
            'meta_key' => '_seopress_robots_canonical',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaTextFactory::class, [
            'type'     => 'column-sp_title',
            'label'    => __('Meta title', 'wp-seopress-pro'),
            'meta_key' => '_seopress_titles_title',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaTextFactory::class, [
            'type'     => 'column-sp_target_kw',
            'label'    => __('Target keywords', 'wp-seopress-pro'),
            'meta_key' => '_seopress_analysis_target_kw',
        ]));

        // Social Columns
        $collection->add(new ColumnFactoryDefinition(MetaTextFactory::class, [
            'type'     => 'column-sp_facebook_title',
            'label'    => __('Facebook Title', 'wp-seopress-pro'),
            'meta_key' => '_seopress_social_fb_title',
            'group'    => 'seopress_social',
        ]));
        $collection->add(new ColumnFactoryDefinition(MetaTextAreaFactory::class, [
            'type'     => 'column-sp_facebook_description',
            'label'    => __('Facebook description', 'wp-seopress-pro'),
            'meta_key' => '_seopress_social_fb_desc',
            'group'    => 'seopress_social',
        ]));

        $collection->add(new ColumnFactoryDefinition(MetaTextFactory::class, [
            'type'     => 'column-sp_x_title',
            'label'    => __('X Title', 'wp-seopress-pro'),
            'meta_key' => '_seopress_social_twitter_title',
            'group'    => 'seopress_social',
        ]));
        $collection->add(new ColumnFactoryDefinition(MetaTextAreaFactory::class, [
            'type'     => 'column-sp_x_description',
            'label'    => __('X Description', 'wp-seopress-pro'),
            'meta_key' => '_seopress_social_twitter_desc',
            'group'    => 'seopress_social',
        ]));

        return $collection;
    }

}