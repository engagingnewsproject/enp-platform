<?php
/**
* A little utility class for paginating
*/
class Enp_quiz_Paginate {
    public $page = '1',
           $offset = '0',
           $limit = '10',
           $total = '0',
           $total_page,
           $total_page_display,
           $url;

    public function __construct($total, $page, $limit, $url) {
        $this->current_page = (isset($_GET['page']) ? (int) $_GET['page'] : 1);

        $this->page = $page;
        $this->limit = $limit;
        $this->offset = ($page * $limit) - $limit;
        $this->total = ($total !== null ? $total : 0);
        $this->url = $this->remove_url_page_queries($url);
        $this->total_pages = ceil($this->total/$this->limit);
        $this->total_page_display = 5;
    }

    /**
    * Remove page=# queries from the url
    */
    public function remove_url_page_queries($url) {

        $url = preg_replace('/&?page=\S*?(&|(?=\/)|$)/', '', $url);

        return $url;
    }

    public function get_pagination_links() {
        if($this->total === 0) {
            return '';
        }


        $page_loop = 0;

        $start_page = $this->get_start_page();
        $page = $start_page;
        $pagination = '';

        if(1 < $this->total_pages ) {
            $pagination = '<ul class="enp-paginate">';

            if(1 < $this->current_page) {
                // $pagination .= $this->get_previous_page_link();
            }

            // see if we need to add a link to the first page or not
            if( ceil($this->total_page_display/2) < $this->current_page && $this->total_page_display < $this->total_pages ) {
                $pagination .= $this->get_first_page_link($start_page);
            }


            while($page < ($this->total_page_display + $start_page) && $page <= $this->total_pages) {
                $pagination .= $this->get_pagination_link($page);
                $page++;
            }

            $last_displayed_page = $page - 1;

            if(($page - 1) < $this->total_pages ) {
                // see if we need to add a link to the last page or not
                $pagination .= $this->get_last_page_link($last_displayed_page);
            }


            if($this->current_page < $this->total_pages) {
                // $pagination .= $this->get_next_page_link();
            }

            $pagination .= '</ul>';
        }



        return $pagination;
    }

    /**
    * If the current page is high (like page 20...) we'll
    * want to start only a few before it
    */
    public function get_start_page() {
        $start_page = 1;
        // Check to see if we have more pages than we can display
        if($this->total_page_display < $this->total_pages) {
            // check if we're closer to the start or end
            if($this->current_page < ($this->total_page_display/2)) {
                $start_page = $this->current_page -  floor($this->total_page_display/2);

                // var_dump('beginning');
            } elseif($this->total_pages < $this->current_page + ($this->total_page_display/2)) {
                $start_page = $this->total_pages - $this->total_page_display + 1;

                // var_dump('end');
            } else {
                $start_page = $this->current_page -  floor($this->total_page_display/2);
                // var_dump('middle');
            }
        }
        // check to make sure the $start_page isn't less than 1
        if($start_page < 1) {
            $start_page = 1;
        }
        return (int) $start_page;
    }

    public function get_pagination_link($page) {
        return '<li class="enp-paginate__item'.($this->current_page === $page ? ' enp-paginate__item--current-page':'').'"><a class="enp-paginate__link'.($this->current_page === $page ? ' enp-paginate__link--current-page':'').'" href="'.$this->url.'&page='.$page.'"><span class="enp-screen-reader-text">'.($this->current_page === $page ? ' Current Page - ':'').'
        Quiz Page </span>'.$page.'</a></li>';
    }

    public function get_previous_page_link() {
        return '<li class="enp-paginate__item enp-paginate__item--previous-page"><a class="enp-paginate__link" href="'.$this->url.'&page='.($this->current_page - 1).'"><svg class="enp-icon enp-paginate__icon"><use xlink:href="#icon-chevron-left"><title>Previous Quiz Page (Page '.($this->current_page - 1).')</title></use></svg></a></li>';
    }

    public function get_first_page_link($start_page) {
        return '<li class="enp-paginate__item enp-paginate__item--first-page'.($start_page === 2 ? 'enp-paginate__item--no-gap' : '' ).'"><a class="enp-paginate__link" href="'.$this->url.'&page=1">1</a></li>';
    }

    public function get_last_page_link($last_displayed_page) {
        return '<li class="enp-paginate__item enp-paginate__item--last-page'.($last_displayed_page < $this->total_pages - 1 ? '' : ' enp-paginate__item--no-gap').'"><a class="enp-paginate__link" href="'.$this->url.'&page='.$this->total_pages.'">'.$this->total_pages.'</a></li>';
    }

    public function get_next_page_link() {
        return '<li class="enp-paginate__item enp-paginate__item--next-page"><a title="Next Page" class="enp-paginate__link" href="'.$this->url.'&page='.($this->current_page + 1).'"><svg class="enp-icon enp-paginate__icon"><use xlink:href="#icon-chevron-right"><title>Next Quiz Page (Page '.($this->current_page + 1).')</title></use></svg></a></li>';
    }
}
