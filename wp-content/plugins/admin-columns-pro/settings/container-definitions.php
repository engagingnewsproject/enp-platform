<?php

namespace ACP;

use AC;
use AC\Plugin\Install;
use AC\Plugin\InstallCollection;
use AC\Plugin\Version;
use AC\Storage\OptionDataFactory;
use AC\Storage\OptionFactory;
use AC\Storage\SiteOptionFactory;
use AC\Type\Url\Site;
use AC\Vendor\Psr\Container\ContainerInterface;
use ACA;
use ACP\Access\PermissionChecker;
use ACP\Access\Rule\LocalServer;
use ACP\Admin\PageFactory\Help;
use ACP\ConditionalFormat\RulesRepository;
use ACP\ListScreenRepository\ConditionalFormatHandler;
use ACP\ListScreenRepository\SegmentHandler;
use ACP\Plugin\SetupFactory;
use ACP\RequestHandler\Ajax\ListScreenSettings;
use ACP\Search\SegmentRepository;
use ACP\Service\Storage\TemplateFiles;
use ACP\Storage\Decoder\Version510Factory;
use ACP\Storage\Decoder\Version630Factory;
use ACP\Storage\Decoder\Version700Factory;
use ACP\Storage\Serializer\PhpSerializer;
use ACP\Value\ExtendedValue;

use function AC\Vendor\DI\autowire;
use function AC\Vendor\DI\get;

return [
    AC\RequestHandler\Ajax\ListScreenSettings::class => autowire(ListScreenSettings::class),
    PhpSerializer\File::class                        => static function (PhpSerializer $serializer
    ) {
        return new PhpSerializer\File($serializer);
    },
    Storage\AbstractDecoderFactory::class            => autowire()->constructorParameter(
        0,
        [
            autowire(Version700Factory::class),
            autowire(Version630Factory::class),
            autowire(Version510Factory::class),
        ]
    ),
    OptionDataFactory::class                         => static function (AdminColumnsPro $plugin) {
        return $plugin->is_network_active()
            ? new SiteOptionFactory()
            : new OptionFactory();
    },
    Type\SiteUrl::class                              => static function (AdminColumnsPro $plugin) {
        return new Type\SiteUrl(
            $plugin->is_network_active()
                ? network_site_url()
                : site_url()
        );
    },
    SetupFactory::class                              => static function (
        AC\ListScreenRepository\Storage $storage,
        AdminColumnsPro $plugin
    ) {
        return new SetupFactory(
            'acp_version',
            $plugin,
            $storage,
            new InstallCollection([
                new Install\Database(new Search\Storage\Table\Segment()),
                new Install\Database(new ConditionalFormat\Storage\Table\ConditionalFormat()),
            ])
        );
    },
    AdminColumnsPro::class                           => static function () {
        return new AdminColumnsPro(ACP_FILE, new Version(ACP_VERSION));
    },
    AC\Storage\EncoderFactory::class                 => get(Storage\EncoderFactory::class),
    Storage\EncoderFactory::class                    => static function (
        AdminColumnsPro $plugin,
        ContainerInterface $container
    ) {
        return new Storage\EncoderFactory(
            $plugin->get_version(),
            $container->get(Search\Encoder::class),
            $container->get(ConditionalFormat\Encoder::class),
        );
    },
    ListScreenRepository\Database::class             => autowire()
        ->constructorParameter(
            2,
            autowire(SegmentHandler::class)
                ->constructor(get(SegmentRepository\Database::class))
        )
        ->constructorParameter(
            3,
            autowire(ConditionalFormatHandler::class)
                ->constructor(get(RulesRepository\Database::class))
        ),
    AC\TableIdsFactory::class                        => autowire(AC\TableIdsFactory\Aggregate::class),
    AC\TableScreenFactory::class                     => autowire(AC\TableScreenFactory\Aggregate::class),
    AC\Admin\MenuFactoryInterface::class             => get(Admin\MenuFactory::class),
    Admin\MenuFactory::class                         => autowire()
        ->constructorParameter(0, admin_url('options-general.php')),
    PermissionChecker::class                         => autowire()->methodParameter(
        'add_rule',
        0,
        new LocalServer()
    ),
    Service\Addon::class                             => autowire()->constructorParameter(
        0,
        [
            'acf'                     => ACA\ACF\AdvancedCustomFields::class,
            'beaver-builder'          => ACA\BeaverBuilder\BeaverBuilder::class,
            'buddypress'              => ACA\BP\BuddyPress::class,
            'events-calendar'         => ACA\EC\EventsCalendar::class,
            'gravityforms'            => ACA\GravityForms\GravityForms::class,
            'jetengine'               => ACA\JetEngine\JetEngine::class,
            'media-library-assistant' => ACA\MLA\MediaLibraryAssistant::class,
            'metabox'                 => ACA\MetaBox\MetaBox::class,
            'pods'                    => ACA\Pods\Pods::class,
            'polylang'                => ACA\Polylang\Polylang::class,
            'rankmath'                => ACA\RankMath\RankMath::class,
            'seopress'                => ACA\SeoPress\SeoPress::class,
            'types'                   => ACA\Types\Types::class,
            'woocommerce'             => ACA\WC\WooCommerce::class,
            'yoast-seo'               => ACA\YoastSeo\YoastSeo::class,
        ]
    ),
    Admin\MenuNetworkFactory::class                  => autowire()
        ->constructorParameter(0, network_admin_url('settings.php')),
    TemplateFiles::class                             => static function (): TemplateFiles {
        return TemplateFiles::from_directory(__DIR__ . '/../config/storage/template');
    },
    Help::class                                      => autowire()
        ->constructorParameter(0, get(AdminColumnsPro::class)),
    AC\Service\PluginUpdate::class                   => autowire()
        ->constructorParameter(0, get(AC\AdminColumns::class))
        ->constructorParameter(1, new Site('upgrade-to-version-%s')),
];