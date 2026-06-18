<?php

declare(strict_types=1);

namespace ACP;

use AC;
use AC\Admin\AdminNetwork;
use AC\Admin\PageNetworkRequestHandler;
use AC\Admin\PageNetworkRequestHandlers;
use AC\Admin\PageRequestHandler;
use AC\Admin\PageRequestHandlers;
use AC\Request;
use AC\RequestAjaxHandlers;
use AC\RequestAjaxParser;
use AC\RequestHandlerFactory;
use AC\Service\View;
use AC\Table\ScreenTools;
use ACP\Admin\MenuGroupFactory;
use ACP\Admin\NetworkPageFactory;
use ACP\Admin\PageFactory;
use ACP\ConditionalFormat\ManageValue\RenderableServiceFactory;
use ACP\Plugin\SetupFactory;
use ACP\Service\PluginActionLinks;
use ACP\Service\RequestParser;
use ACP\Service\TermQueryInformation;
use ACP\Table\ManageHeading;
use ACP\Table\ManageValue;
use ACP\Table\PrimaryColumn;
use ACP\Table\SaveHeading;
use ACP\Table\Scripts;
use ACP\Table\TableIdsFactory;
use ACP\TableScreen\RelatedRepository;
use ACP\TableScreen\TableRowsFactory;
use ACP\Updates\PeriodicUpdateCheck;
use ACP\Value\ExtendedValue;

final class Loader extends AC\Loader
{

    public function __construct(AC\DI\Container $container)
    {
        parent::__construct($container, true);
    }

    protected function load(AC\DI\Container $container): void
    {
        parent::load($container);

        $this->register_factories($container);
        $this->register_page_handlers($container);

        $plugin = $container->get(AdminColumnsPro::class);

        $this->register_services($container, $plugin);

        /**
         * @param AC\DI\Container $container A PSR-11 dependency injection container.
         * @param AdminColumnsPro $plugin    The plugin instance.
         */
        do_action('acp/init', $container, $plugin);
    }

    private function register_factories(AC\DI\Container $container): void
    {
        AC\TableIdsFactory\Aggregate::add($container->get(TableIdsFactory::class));

        foreach ($this->get_column_factory_classes() as $class) {
            AC\ColumnFactories\Aggregate::add($container->get($class));
        }

        AC\TableScreenFactory\Aggregate::add($container->get(TableScreen\NetworkSiteFactory::class));
        AC\TableScreenFactory\Aggregate::add($container->get(TableScreen\NetworkUserFactory::class));
        AC\TableScreenFactory\Aggregate::add($container->get(TableScreen\TaxonomyFactory::class));

        AC\Value\ExtendedValueRegistry::add($container->get(ExtendedValue\Post\PostImages::class));
        AC\Value\ExtendedValueRegistry::add($container->get(ExtendedValue\Post\Revisions::class));
        AC\Value\ExtendedValueRegistry::add($container->get(AC\Value\Extended\Value::class));
        AC\Value\ExtendedValueRegistry::add($container->get(ExtendedValue\NetworkSites\Plugins::class));
        AC\Value\ExtendedValueRegistry::add($container->get(ExtendedValue\Media\PostsContainingImage::class));
        AC\Value\ExtendedValueRegistry::add($container->get(ExtendedValue\Media\PostsHavingFeaturedImage::class));

        AC\Admin\MenuGroupFactory\Aggregate::add(new MenuGroupFactory());
        AC\TableScreen\TableRowsFactory\Aggregate::add(new TableRowsFactory());

        RelatedRepository\Aggregate::add($container->get(RelatedRepository\Post::class));
        RelatedRepository\Aggregate::add($container->get(RelatedRepository\User::class));
        RelatedRepository\Aggregate::add($container->get(RelatedRepository\Taxonomy::class));

        AC\Service\ManageHeadings::add($container->get(ManageHeading\WpListTableFactory::class));
        AC\Service\SaveHeadings::add($container->get(SaveHeading\WpListTableFactory::class));

        Query\QueryRegistry::add($container->get(Query\Factory\PostFactory::class));
        Query\QueryRegistry::add($container->get(Query\Factory\CommentFactory::class));
        Query\QueryRegistry::add($container->get(Query\Factory\UserFactory::class));
        Query\QueryRegistry::add($container->get(Query\Factory\TermFactory::class));

        foreach ($this->get_manage_value_factory_classes() as $class) {
            AC\Service\ManageValue::add(
                $container->make(RenderableServiceFactory::class, ['factory' => $container->get($class)])
            );
        }
    }

    private function get_column_factory_classes(): array
    {
        return [
            ColumnFactories\Original\OriginalColumnFactory::class,
            ColumnFactories\Original\CommentFactory::class,
            ColumnFactories\Original\PostFactory::class,
            ColumnFactories\Original\NetworkUsersFactory::class,
            ColumnFactories\Original\MediaFactory::class,
            ColumnFactories\Original\TaxonomyFactory::class,
            ColumnFactories\Original\UserFactory::class,
            ColumnFactories\Original\PostTaxonomyFactory::class,
            ColumnFactories\CommentFactory::class,
            ColumnFactories\PostFactory::class,
            ColumnFactories\MediaFactory::class,
            ColumnFactories\TaxonomyFactory::class,
            ColumnFactories\NetworkSiteFactory::class,
            ColumnFactories\NetworkUsersFactory::class,
            ColumnFactories\UserFactory::class,

            // Deferred loading for third party columns
            AC\ColumnFactories\ThirdPartyFactory::class,
        ];
    }

    private function get_manage_value_factory_classes(): array
    {
        return [
            AC\TableScreen\ManageValue\PostServiceFactory::class,
            AC\TableScreen\ManageValue\UserServiceFactory::class,
            AC\TableScreen\ManageValue\MediaServiceFactory::class,
            AC\TableScreen\ManageValue\CommentServiceFactory::class,
            AC\ThirdParty\MediaLibraryAssistant\TableScreen\ManageValueServiceFactory::class,
            ManageValue\TaxonomyServiceFactory::class,
            ManageValue\NetworkSiteServiceFactory::class,
            ManageValue\NetworkUserServiceFactory::class,
        ];
    }

    private function register_page_handlers(AC\DI\Container $container): void
    {
        $page_handler = new PageRequestHandler();
        $page_handler
            ->add('columns', $container->get(AC\Admin\PageFactory\Columns::class))
            ->add('settings', $container->get(PageFactory\Settings::class))
            ->add('addons', $container->get(PageFactory\Addons::class))
            ->add('import-export', $container->get(PageFactory\Tools::class))
            ->add('license', $container->get(PageFactory\License::class))
            ->add('help', $container->get(AC\Admin\PageFactory\Help::class));

        PageRequestHandlers::add_handler($page_handler);

        $page_network_handler = new PageNetworkRequestHandler();
        $page_network_handler
            ->add('columns', $container->get(NetworkPageFactory\Columns::class))
            ->add('import-export', $container->get(NetworkPageFactory\Tools::class))
            ->add('addons', $container->get(NetworkPageFactory\Addons::class))
            ->add('license', $container->get(NetworkPageFactory\License::class));

        PageNetworkRequestHandlers::add_handler($page_network_handler);
    }

    private function get_ajax_handler_classes(): array
    {
        return [
            'acp-list-screen-create'           => RequestHandler\Ajax\ListScreenCreate::class,
            'acp-list-screen-order'            => RequestHandler\Ajax\ListScreenOrder::class,
            'acp-daily-subscription-update'    => RequestHandler\Ajax\SubscriptionUpdate::class,
            'acp-update-plugins-check'         => RequestHandler\Ajax\UpdatePlugins::class,
            'acp-layout-get-users'             => RequestHandler\Ajax\ListScreenUsers::class,
            'acp-permalinks'                   => RequestHandler\Ajax\Permalinks::class,
            'acp-user-column-reset'            => RequestHandler\Ajax\ColumnReset::class,
            'acp-user-column-order'            => RequestHandler\Ajax\ColumnOrderUser::class,
            'acp-user-column-width'            => RequestHandler\Ajax\ColumnWidthUser::class,
            'acp-user-column-width-reset'      => RequestHandler\Ajax\ColumnWidthUserReset::class,
            'acp-user-list-order'              => RequestHandler\Ajax\ListScreenOrderUser::class,
            'acp-table-save-preference'        => RequestHandler\Ajax\ListScreenTable::class,
            'acp-filtering-comparison-request' => Filtering\RequestHandler\Comparison::class,
            'acp-cf-apply-rules'               => ConditionalFormat\RequestHandler\ApplyRules::class,
            'acp-cf-save-rules'                => ConditionalFormat\RequestHandler\SaveRules::class,
            'acp-cf-retrieve-rules'            => ConditionalFormat\RequestHandler\RetrieveRules::class,
            'acp-cf-remove-rules'              => ConditionalFormat\RequestHandler\RemoveRules::class,
            'acp-list-screen-settings'         => RequestHandler\Ajax\ListScreenAdditionalSettings::class,
            'acp-exportable-list-tables'       => RequestHandler\Ajax\ExportableListTables::class,
            'acp-list-screen-templates'        => RequestHandler\Ajax\ListScreenTemplates::class,
            'acp-import-upload'                => RequestHandler\Ajax\ListScreenImportUpload::class,
            'acp-import-template'              => RequestHandler\Ajax\ListScreenImportTemplate::class,
            'acp-license-get'                  => RequestHandler\Ajax\LicenseInfo::class,
            'acp-license-activate'             => RequestHandler\Ajax\LicenseActivate::class,
            'acp-license-deactivate'           => RequestHandler\Ajax\LicenseDeactivate::class,
            'acp-license-update'               => RequestHandler\Ajax\LicenseUpdate::class,
            'acp-plugin-version'               => RequestHandler\Ajax\PluginVersionInfo::class,
            'acp-plugin-force-updates'         => RequestHandler\Ajax\PluginForceUpdateCheck::class,
            'acp-sorting-reset'                => RequestHandler\Ajax\ResetSorting::class,
            'acp-plugin-permissions'           => RequestHandler\Ajax\PluginPermissions::class,
            'acp-file-storage-migration'       => RequestHandler\Ajax\FileStorageMigration::class,
            'acp-file-storage-settings'        => RequestHandler\Ajax\FileStorageSettings::class,
        ];
    }

    private function get_service_classes(bool $is_network_active): array
    {
        $classes = [
            Updates\UpdatePlugin::class,
            Updates\ViewPluginDetails::class,
            QuickAdd\Addon::class,
            Sorting\Addon::class,
            Editing\Addon::class,
            Export\Addon::class,
            Search\Addon::class,
            ConditionalFormat\Addon::class,
            Filtering\Addon::class,
            Table\HorizontalScrolling::class,
            Table\StickyTableRow::class,
            Table\StickyColumn::class,
            Table\HideElements::class,
            Scripts::class,
            Localize::class,
            TermQueryInformation::class,
            PeriodicUpdateCheck::class,
            PluginActionLinks::class,
            Check\Activation::class,
            Check\Expired::class,
            Check\Renewal::class,
            Check\LockedSettings::class,
            Admin\Scripts::class,
            Service\Addon::class,
            Service\AdminFooter::class,
            Service\DeprecatedAddons::class,
            Service\ForcePluginUpdate::class,
            Service\PostTypes::class,
            Service\PluginNotice::class,
            Service\Tooltips::class,
            ScreenTools::class,
            PrimaryColumn::class,
            Service\ColumnScripts::class,
            Service\Storage::class,
            Service\Storage\Template::class,
            Service\Storage\TemplateFiles::class,
            Service\Permissions::class,
            Service\TableCellWrapping::class,
            Service\FileStorageMigrationHandler::class,
            Service\ColumnEditorTooltips::class,
            AC\Service\PluginUpdate::class,
        ];

        if ($is_network_active) {
            $classes[] = AdminNetwork::class;
        }

        return $classes;
    }

    private function register_services(AC\DI\Container $container, AdminColumnsPro $plugin): void
    {
        $is_network_active = $plugin->is_network_active();
        $setup_factory = $container->get(SetupFactory::class);

        $request_ajax_handlers = new RequestAjaxHandlers();
        foreach ($this->get_ajax_handler_classes() as $key => $class) {
            $request_ajax_handlers->add($key, $container->get($class));
        }

        $request_handler_factory = new RequestHandlerFactory(new Request());
        $request_handler_factory->add('acp-export-list-screen-settings', $container->get(RequestHandler\Export::class));

        $services = [];
        foreach ($this->get_service_classes($is_network_active) as $class) {
            $services[] = $container->get($class);
        }

        $services[] = new View($plugin->get_location());
        $services[] = new RequestParser($request_handler_factory);
        $services[] = new RequestAjaxParser($request_ajax_handlers);
        $services[] = new AC\Service\Setup($setup_factory->create(AC\Plugin\SetupFactory::SITE));

        if ($is_network_active) {
            $services[] = new AC\Service\Setup($setup_factory->create(AC\Plugin\SetupFactory::NETWORK));
        }

        foreach ($services as $service) {
            $service->register();
        }
    }

}