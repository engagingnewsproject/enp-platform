<?php

namespace ACP\Search\RequestHandler;

use AC;
use AC\Exception;
use AC\Exception\RequestException;
use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\Response;
use AC\Type\ColumnId;
use AC\Type\ListScreenId;
use ACP;
use ACP\Controller;
use ACP\Filtering\ApplyFilter\CacheDuration;
use ACP\Search;
use DomainException;

class Comparison extends Controller
{

    protected $list_screen;

    private $storage;

    public function __construct(Storage $storage, Request $request)
    {
        parent::__construct($request);
        $this->storage = $storage;
    }

    /**
     * @throws RequestException
     */
    public function get_options_action(): void
    {
        $id = $this->request->get('layout');

        if ( ! ListScreenId::is_valid_id($id)) {
            throw Exception\RequestException::parameters_invalid();
        }

        $list_screen = $this->storage->find(new ListScreenId($id));

        if ( ! $list_screen) {
            throw Exception\RequestException::parameters_invalid();
        }

        $response = new Response\Json();

        $column = $list_screen->get_column(
            new ColumnId((string)$this->request->filter('column', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS))
        );

        if ( ! $column instanceof ACP\Column) {
            $response->error();
        }

        $comparison = $column->search();

        switch (true) {
            case $comparison instanceof Search\Comparison\RemoteValues :
                $response->set_header(
                    'Cache-Control',
                    'max-age=' . $this->get_cache_duraction_in_seconds($comparison)
                );
                $options = $comparison->get_values();
                $has_more = false;

                break;
            case $comparison instanceof Search\Comparison\SearchableValues :
                $search_term = $this->request->filter('searchterm', '');

                if ('' === $search_term) {
                    $response->set_header(
                        'Cache-Control',
                        'max-age=' . $this->get_cache_duraction_in_seconds($comparison)
                    );
                }

                $options = $comparison->get_values(
                    $search_term,
                    (int)$this->request->filter('page', 1, FILTER_SANITIZE_NUMBER_INT)
                );
                $has_more = ! $options->is_last_page();

                break;
            default :
                throw new DomainException('Invalid Comparison type found.');
        }

        $select = new AC\Helper\Select\Response($options, $has_more);

        $response
            ->set_parameters($select())
            ->success();
    }

    private function get_cache_duraction_in_seconds(Search\Comparison $comparison): int
    {
        return (new CacheDuration($comparison))->apply_filters(300);
    }

}