<?php

namespace ACP\Export;

use AC\Registerable;
use AC\RequestAjaxHandlers;
use AC\RequestAjaxParser;
use AC\Services;
use AC\Vendor\DI;
use ACP\Export\Service\Admin;
use ACP\Export\Service\ExportHandler;
use ACP\Export\Service\TableScreen;
use ACP\Export\Strategy\AggregateFactory;
use ACP\Export\Strategy\CommentFactory;
use ACP\Export\Strategy\PostFactory;
use ACP\Export\Strategy\TaxonomyFactory;
use ACP\Export\Strategy\UserFactory;

use function AC\Vendor\DI\autowire;

final class Addon implements Registerable
{

    private DI\Container $container;

    public function __construct(DI\Container $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        $this->container->set(
            StrategyFactory::class,
            autowire(AggregateFactory::class)
        );

        $request_ajax_handlers = new RequestAjaxHandlers();
        $request_ajax_handlers->add(
            'acp-export-file-name',
            $this->container->get(RequestHandler\Ajax\FileName::class)
        );
        $request_ajax_handlers->add(
            'acp-export-order-preference',
            $this->container->get(RequestHandler\Ajax\SaveExportPreference::class)
        );
        $request_ajax_handlers->add(
            'acp-export-show-export-button',
            $this->container->get(RequestHandler\Ajax\ToggleExportButtonTable::class)
        );

        AggregateFactory::add($this->container->get(PostFactory::class));
        AggregateFactory::add($this->container->get(UserFactory::class));
        AggregateFactory::add($this->container->get(CommentFactory::class));
        AggregateFactory::add($this->container->get(TaxonomyFactory::class));

        return new Services([
            $this->container->get(Admin::class),
            $this->container->get(ExportHandler::class),
            $this->container->get(TableScreen::class),
            new RequestAjaxParser($request_ajax_handlers),
        ]);
    }

}