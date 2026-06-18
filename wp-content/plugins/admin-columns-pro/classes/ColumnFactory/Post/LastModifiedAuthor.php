<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Search;
use ACP\Sorting;

class LastModifiedAuthor extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private PostTypeSlug $post_type;

    private ACP\Setting\ComponentFactory\UserProperty $user_factory;

    private AC\Setting\ComponentFactory\UserLinkFactory $user_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ACP\Setting\ComponentFactory\UserProperty $user_factory,
        AC\Setting\ComponentFactory\UserLinkFactory $user_link,
        PostTypeSlug $post_type
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);
        $this->user_factory = $user_factory;
        $this->user_link = $user_link;
        $this->post_type = $post_type;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new AC\Formatter\Post\LastModifiedAuthor());
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->user_factory->create($config),
            $this->user_link->create($this->post_type)->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-last_modified_author';
    }

    public function get_label(): string
    {
        return __('Last Modified Author', 'codepress-admin-columns');
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return $this->get_formatters($config)
                    ->add(new AC\Formatter\StripTags());
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return (new Sorting\Model\Post\LastModifiedAuthorFactory())->create(
            $config->get('display_author_as', 'display_name')
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Post\LastModifiedAuthor((string)$this->post_type);
    }

}