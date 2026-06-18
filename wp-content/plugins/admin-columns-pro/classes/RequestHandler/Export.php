<?php

namespace ACP\RequestHandler;

use AC;
use AC\Capabilities;
use AC\ListScreenCollection;
use AC\ListScreenRepository\Storage;
use AC\RequestHandler;
use AC\Type\ListScreenId;
use ACP\Search\SegmentCollection;
use ACP\Tools\Export\ResponseFactory;

final class Export implements RequestHandler
{

    private Storage $storage;

    private ResponseFactory $response_factory;

    private AC\Nonce\Ajax $nonce;

    public function __construct(Storage $storage, ResponseFactory $response_factory, AC\Nonce\Ajax $nonce)
    {
        $this->storage = $storage;
        $this->response_factory = $response_factory;
        $this->nonce = $nonce;
    }

    public function handle(AC\Request $request): void
    {
        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        if ( ! current_user_can(Capabilities::MANAGE)) {
            wp_send_json_error();
        }

        $data = (object)filter_input_array(INPUT_POST, [
            'list_screen_ids' => [
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'flags'  => FILTER_REQUIRE_ARRAY,
            ],
            'segments'        => [
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'flags'  => FILTER_REQUIRE_ARRAY,
            ],
        ]);

        if (empty($data->list_screen_ids)) {
            return;
        }

        $list_screens = $this->get_list_screens_from_request($data->list_screen_ids);

        foreach ($list_screens as $list_screen) {
            $segments = [];
            $unfiltered_segments = $list_screen->get_segments();

            foreach ($unfiltered_segments as $segment) {
                if (is_array($data->segments) && in_array((string)$segment->get_key(), $data->segments, true)) {
                    $segments[] = $segment;
                }
            }

            $list_screen->set_segments(new SegmentCollection($segments));
        }

        $response = $this->response_factory->create(
            $list_screens
        );

        $response->send();
    }

    protected function get_list_screens_from_request(array $ids): ListScreenCollection
    {
        $list_screens = new ListScreenCollection();

        foreach ($ids as $id) {
            $list_screen = $this->storage->find(new ListScreenId($id));

            if ($list_screen) {
                $list_screens->add($list_screen);
            }
        }

        return $list_screens;
    }

}