<?php

declare(strict_types=1);

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\ListScreenRepository;
use AC\ListScreenRepository\Filter;
use AC\ListScreenRepository\Storage;
use AC\Nonce\Ajax;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Response\Json;
use ACP\ListScreenRepository\SourceAware;
use ACP\ListScreenRepository\SourceCollection;
use ACP\ListScreenRepository\Types;
use ACP\Tools\Encode\ListScreenEncoder;

class ExportableListTables implements RequestAjaxHandler
{

    private Ajax $nonce;

    private Storage $storage;

    private ListScreenEncoder $encoder;

    public function __construct(Ajax $nonce, Storage $storage, ListScreenEncoder $encoder)
    {
        $this->nonce = $nonce;
        $this->storage = $storage;
        $this->encoder = $encoder;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();
        $response = new Json();

        if ( ! $this->nonce->verify($request)) {
            $response->error();
        }

        $is_network = $request->get('is_network', 'false') === 'true';

        $response->set_parameters([
            'list_tables' => $this->get_table_data($is_network),
        ]);

        $response->success();
    }

    private function get_table_data(bool $network = false): array
    {
        $data = [];

        $filter = $network
            ? new Filter\Network()
            : new Filter\Site();

        foreach ($this->storage->get_repositories() as $repo_id => $_repository) {
            $repository = $_repository->get_list_screen_repository();

            $label = $this->get_repository_label($repo_id);

            $sources = $repository instanceof SourceAware
                ? $repository->get_sources()
                : new SourceCollection();

            foreach ($filter->filter($repository->find_all()) as $list_screen) {
                $id = $list_screen->get_id();

                $list_screen->set_read_only(! $_repository->is_writable());

                $data[] = $this->encoder->encode(
                    $list_screen,
                    $sources->contains($id)
                        ? $sources->get($id)
                        : null,
                    $label
                );
            }
        }

        // Sort
        usort($data, function ($a, $b) {
            return [$a['table'], $a['label']] <=> [$b['table'], $b['label']];
        });

        return $data;
    }

    private function get_repository_label(string $repository_id): string
    {
        $labels = [
            ListScreenRepository\Types::DATABASE => __('Database', 'codepress-admin-columns'),
            Types::FILE                          => __('File', 'codepress-admin-columns'),
        ];

        return $labels[$repository_id] ?? $repository_id;
    }

}