<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\Formatter\Post\Author;
use AC\Formatter\StringSanitizer;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\FieldType;
use AC\Setting\ComponentFactory\UserProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing\Service;
use ACP\Formatter\User\GravatarUrl;
use ACP\Search;
use ACP\Sorting;
use ACP\Sorting\Type\DataType;

class AuthorName extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private ACP\Setting\ComponentFactory\UserProperty $user_factory;

    private AC\Setting\ComponentFactory\UserLinkFactory $user_link;

    private AC\Setting\ComponentFactory\BeforeAfter $before_after_factory;

    private AC\Type\PostTypeSlug $post_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        AC\Type\PostTypeSlug $post_type,
        ACP\Setting\ComponentFactory\UserProperty $user_factory,
        AC\Setting\ComponentFactory\UserLinkFactory $user_link,
        AC\Setting\ComponentFactory\BeforeAfter $before_after_factory
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);

        $this->user_factory = $user_factory;
        $this->user_link = $user_link;
        $this->before_after_factory = $before_after_factory;
        $this->post_type = $post_type;
    }

    public function get_column_type(): string
    {
        return 'column-author_name';
    }

    public function get_label(): string
    {
        return __('Author', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Author());
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->user_factory->create($config),
            $this->user_link->create($this->post_type)->create($config),
            $this->before_after_factory->create($config),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        switch ($config->get('display_author_as')) {
            case UserProperty::PROPERTY_FIRST_NAME:
                return new Sorting\Model\Post\Author\UserMeta('first_name');
            case UserProperty::PROPERTY_LAST_NAME:
                return new Sorting\Model\Post\Author\UserMeta('last_name');
            case UserProperty::PROPERTY_NICKNAME:
                return new Sorting\Model\Post\Author\UserMeta('nickname');
            case UserProperty::PROPERTY_ROLES:
                return new Sorting\Model\Post\Author\Roles();
            case UserProperty::PROPERTY_NICENAME:
                return new Sorting\Model\Post\Author\UserField('user_nicename');
            case UserProperty::PROPERTY_LOGIN:
                return new Sorting\Model\Post\Author\UserField('user_login');
            case UserProperty::PROPERTY_EMAIL:
                return new Sorting\Model\Post\Author\UserField('user_email');
            case UserProperty::PROPERTY_URL:
                return new Sorting\Model\Post\Author\UserField('user_url');
            case UserProperty::PROPERTY_FULL_NAME:
                return new Sorting\Model\Post\Author\FullName();
            case UserProperty::PROPERTY_DISPLAY_NAME:
                return new Sorting\Model\Post\Author\UserField('display_name');
            case UserProperty::PROPERTY_ID:
                return new Sorting\Model\Post\PostField('post_author', DataType::create_numeric());
            default:
                return null;
        }
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        if ($config->get('display_author_as') === UserProperty::PROPERTY_GRAVATAR) {
            return new FormatterCollection([
                new Author(),
                new GravatarUrl(),
            ]);
        }

        return $this->user_factory->create($config)
                                  ->get_formatters()
                                  ->prepend(new Author())
                                  ->add(new StringSanitizer());
    }

    protected function get_editing(Config $config): ?Service
    {
        return new Service\Post\Author();
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        $display_property = $config->get('display_author_as', '');

        switch ($display_property) {
            case UserProperty::PROPERTY_FIRST_NAME:
            case UserProperty::PROPERTY_LAST_NAME:
            case UserProperty::PROPERTY_NICKNAME:
                return new Search\Comparison\Post\AuthorMeta($display_property);

            case UserProperty::PROPERTY_ROLES:
                return new Search\Comparison\Post\AuthorRole();

            case UserProperty::PROPERTY_NICENAME:
            case UserProperty::PROPERTY_LOGIN:
            case UserProperty::PROPERTY_EMAIL:
            case UserProperty::PROPERTY_URL:
                return new Search\Comparison\Post\AuthorField($display_property);

            case UserProperty::PROPERTY_FULL_NAME:
            case UserProperty::PROPERTY_DISPLAY_NAME:
            case UserProperty::PROPERTY_ID:
                return new Search\Comparison\Post\Author((string)$this->post_type);

            case ACP\Setting\ComponentFactory\UserProperty::PROPERTY_CUSTOM_FIELD:
                switch ($config->get('field_type', '')) {
                    case FieldType::TYPE_DEFAULT :
                    case FieldType::TYPE_BOOLEAN :
                    case FieldType::TYPE_COLOR :
                    case FieldType::TYPE_HTML :
                    case FieldType::TYPE_NUMERIC :
                    case FieldType::TYPE_SELECT :
                    case FieldType::TYPE_TEXT :
                    case FieldType::TYPE_URL :
                        return new Search\Comparison\Post\AuthorMeta($config->get('field', ''));
                    default:
                        return null;
                }
            default:
                return null;
        }
    }

}