<?php
/*
 * Modifications to permalinks
 */
namespace Engage\Managers;

class Permalinks {

    public function __construct() {

    }

    public function run() {
        add_action('query_vars', [$this, 'addQueryVars']);
        add_filter('generate_rewrite_rules', [$this, 'addRewrites']);
    }

    public function addQueryVars($vars) {
        $vars[] = 'vertical_base';
        $vars[] = 'query_name';
        return $vars;
    }

    public function getResearchRewrites() {
        $rules = [];
        // vertical only
        $rules['research/vertical/([^/]+)/?$'] = 'index.php?post_type=research&verticals=$matches[1]';

        // research-cats as /research/category/{term}
        $rules['research/category/([^/]+)/?$'] = 'index.php?post_type=research&research-categories=$matches[1]';

        // research-tags as /research/tag/{term}
        $rules['research/tag/([^/]+)/?$'] = 'index.php?post_type=research&research-tags=$matches[1]';

        // double query. append query name at the end
        // research/vertical/{term}/category/{term}
        $rules['research/vertical/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=research&verticals=$matches[1]&research-categories=$matches[2]';

        // research/vertical/{term}/tag/{term}
        $rules['research/vertical/([^/]+)/tag/([^/]+)/?$'] = 'index.php?post_type=research&verticals=$matches[1]&research-tags=$matches[2]';

        // research/vertical/{term}/category/{term}/tag/{term}
        $rules['research/vertical/([^/]+)/category/([^/]+)/tag/([^/]+)/?$'] = 'index.php?post_type=research&verticals=$matches[1]&research-categories=$matches[2]&research-tags=$matches[3]';

        return $rules;
    }


    public function getTeamRewrites() {
        $rules = [];
        // vertical only
        $rules['team/vertical/([^/]+)/?$'] = 'index.php?post_type=team&verticals=$matches[1]&orderby=menu_order&order=ASC';

        // team-cats as /team/category/{term}
        $rules['team/category/([^/]+)/?$'] = 'index.php?post_type=team&team_category=$matches[1]&orderby=menu_order&order=ASC';

        // double query. append query name at the end
        // team/vertical/{term}/category/{term}
        $rules['team/vertical/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=team&verticals=$matches[1]&team_category=$matches[2]&orderby=menu_order&order=ASC';

        return $rules;
    }

    public function getAnnouncementRewrites() {
        $rules = [];
        // vertical only
        $rules['announcement/vertical/([^/]+)/?$'] = 'index.php?post_type=announcement&verticals=$matches[1]';

        // announcement-cats as /announcement/category/{term}
        $rules['announcement/category/([^/]+)/?$'] = 'index.php?post_type=announcement&announcement-category=$matches[1]';

        // double query. append query name at the end
        // announcement/vertical/{term}/category/{term}
        $rules['announcement/vertical/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=announcement&verticals=$matches[1]&announcement-category=$matches[2]';

        return $rules;
    }

    public function getBlogRewrites() {
        $rules = [];
        // vertical only
        $rules['blogs/vertical/([^/]+)/?$'] = 'index.php?post_type=blogs&verticals=$matches[1]';

        // blogs-categories as /blogs/category/{term}
        $rules['blogs/category/([^/]+)/?$'] = 'index.php?post_type=blogs&blogs-category=$matches[1]';

        // double query. append query name at the end
        // blogs/vertical/{term}/category/{term}
        $rules['blogs/vertical/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=blogs&verticals=$matches[1]&blogs-category=$matches[2]';

        return $rules;
    }

    public function getEventsRewrites() {
        $rules = [];
        // this displays ALL upcoming and past events using eventDisplay=custom
        $rules['events/?$'] = 'index.php?post_type=tribe_events&query_name=all_events';

        // tribe defaults to only using upcoming events
        // order by whichever has the closest start date to today
        $rules['events/upcoming/?$'] = 'index.php?post_type=tribe_events&meta_key=_EventStartDate&orderby=_EventStartDate&order=ASC&query_name=upcoming_events';

        $rules['events/past/?$'] = 'index.php?post_type=tribe_events&query_name=past_events';

        // vertical only
        $rules['events/vertical/([^/]+)/?$'] = 'index.php?post_type=tribe_events&verticals=$matches[1]&query_name=all_events';

        // event-categories as /event/category/{term}
        $rules['events/category/([^/]+)/?$'] = 'index.php?post_type=tribe_events&tribe_events_cat=$matches[1]';

        // double query. append query name at the end
        // event/vertical/{term}/category/{term}
        $rules['events/vertical/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=tribe_events&verticals=$matches[1]&tribe_events_cat=$matches[2]';

        return $rules;

    }


    public function getVerticalRewrites() {

        /**
        * Example Post Type Category Rewrite
        * $postTypeSlug
        * $taxonomySlug
        * $rules['vertical/([^/]+)/$postTypeSlug/category/([^/]+)/?$'] = 'index.php?post_type=$postTypeSlug&verticals=$matches[1]&$taxonomySlug=$matches[2]&vertical_base=1';
        *
        */
        $rules = [];
         // everything in vertical
        // /vertical/{ verticalTerm }/
        $rules['vertical/([^/]+)/?$'] = 'index.php?verticals=$matches[1]&vertical_base=1';

        // /vertical/{ verticalTerm }/team/
        // needs to go above the generic one since we're making a specific query for this one
        $rules['vertical/([^/]+)/team/?$'] = 'index.php?post_type=team&verticals=$matches[1]&vertical_base=1&orderby=menu_order&order=ASC';

        // tribe_Events
        // needs to go above the generic one since we're making a specific query for this one
        $rules['vertical/([^/]+)/events/?$'] = 'index.php?post_type=tribe_events&verticals=$matches[1]&vertical_base=1';


        // vertical with a specific post type
        // /vertical/{ verticalTerm }/type/{ postType }
        $rules['vertical/([^/]+)/([^/]+)/?$'] = 'index.php?post_type=$matches[2]&verticals=$matches[1]&vertical_base=1';

        // research-cats as
        // /vertical/{ verticalTerm }/research/category/{ term }
        $rules['vertical/([^/]+)/research/category/([^/]+)/?$'] = 'index.php?post_type=research&verticals=$matches[1]&research-categories=$matches[2]&vertical_base=1';

        $rules['vertical/([^/]+)/research/tag/([^/]+)/?$'] = 'index.php?post_type=research&verticals=$matches[1]&research-tags=$matches[2]&vertical_base=1';

        // /vertical/{ verticalTerm }/team/category/{ term }
        $rules['vertical/([^/]+)/team/category/([^/]+)/?$'] = 'index.php?post_type=team&verticals=$matches[1]&team_category=$matches[2]&vertical_base=1&orderby=menu_order&order=ASC';

        // announcement category
        // /vertical/{ verticalTerm }/announcement/category/{ term }
        $rules['vertical/([^/]+)/announcement/category/([^/]+)/?$'] = 'index.php?post_type=announcement&verticals=$matches[1]&announcement-category=$matches[2]&vertical_base=1';

        // blogs category
        // /vertical/{ verticalTerm }/blogs/category/{ term }
        $rules['vertical/([^/]+)/blogs/category/([^/]+)/?$'] = 'index.php?post_type=blogs&verticals=$matches[1]&blogs-category=$matches[2]&vertical_base=1';

        $rules['vertical/([^/]+)/events/category/([^/]+)/?$'] = 'index.php?post_type=tribe_events&verticals=$matches[1]&tribe_events_cat=$matches[2]&vertical_base=1';

        // post category as/vertical/{ verticalTerm }/post/tag/{ term }
        $rules['vertical/([^/]+)/post/category/([^/]+)/?$'] = 'index.php?post_type=post&verticals=$matches[1]&category_name=$matches[2]&vertical_base=1';


        $rules['vertical/([^/]+)/post/tag/([^/]+)/?$'] = 'index.php?post_type=post&verticals=$matches[1]&tag=$matches[2]&vertical_base=1';

        // post category paginated as/vertical/{ verticalTerm }/post/tag/{ term }/page/{ page number}
        $rules['vertical/([^/]+)/post/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=post&verticals=$matches[1]&category_name=$matches[2]&paged=$matches[3]&vertical_base=1';

        $rules['vertical/([^/]+)/post/tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=post&verticals=$matches[1]&tag=$matches[2]&paged=$matches[3]&vertical_base=1';

        // post category paginated as/vertical/{ verticalTerm }/research/tag/{ term }/page/{ page number}
        $rules['vertical/([^/]+)/research/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&verticals=$matches[1]&category_name=$matches[2]&paged=$matches[3]&vertical_base=1';

        $rules['vertical/([^/]+)/research/tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&verticals=$matches[1]&tag=$matches[2]&paged=$matches[3]&vertical_base=1';

        return $rules;
    }

    public function addRewrites($wp_rewrite) {

        /*$wp_rewrite->rules = $this->getTeamRewrites() + $wp_rewrite->rules;
        $wp_rewrite->rules = $this->getAnnouncementRewrites() + $wp_rewrite->rules;
        $wp_rewrite->rules = $this->getBlogRewrites() + $wp_rewrite->rules;
        $wp_rewrite->rules = $this->getEventsRewrites() + $wp_rewrite->rules;

        // /vertical/{ verticalTerm }/
        //add_rewrite_rule('vertical/([^/]+)/?$', 'index.php?verticals=$matches[1]&vertical_base=1';
        // $rules['vertical/([^/]+)/?$'] = 'index.php?verticals=$matches[1]&vertical_base=1';
        // merge with global rules
        $wp_rewrite->rules = $this->getResearchRewrites() + $wp_rewrite->rules;
        $wp_rewrite->rules = $this->addVerticalRewrites() + $wp_rewrite->rules;*/

        $wp_rewrite->rules= $this->getTeamRewrites() +
                            $this->getAnnouncementRewrites() +
                            $this->getBlogRewrites() +
                            $this->getEventsRewrites() +
                            $this->getResearchRewrites() +
                            $this->getVerticalRewrites() +
                            $wp_rewrite->rules;
    }
}
