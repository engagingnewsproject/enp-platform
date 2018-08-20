<?php
/*
 * Modifications to permalinks
 */
namespace Engage\Managers;

class Permalinks {

    public function __construct() {

    }

    public function run() {
        add_action('init', [$this, 'addResearchRewrites']);
        add_action('init', [$this, 'addTeamRewrites']);
        add_action('init', [$this, 'addVerticalRewrites']);
        add_action('init', [$this, 'addAnnouncementRewrites']);
        add_action('init', [$this, 'addCaseStudyRewrites']);
        add_action('init', [$this, 'addEventsRewrites']);
        add_action('query_vars', [$this, 'addQueryVars']);
    }

    public function addQueryVars($vars) {
        $vars[] = 'vertical_base';
        $vars[] = 'query_name';
        return $vars;
    }   

    public function addResearchRewrites() {
        // vertical only
        add_rewrite_rule('research/vertical/([^/]+)/?$', 'index.php?post_type=research&verticals=$matches[1]', 'top');
        
        // research-cats as /research/category/{term}
        add_rewrite_rule('research/category/([^/]+)/?$', 'index.php?post_type=research&research-categories=$matches[1]', 'top');

        // research-tags as /research/tag/{term}
        add_rewrite_rule('research/tag/([^/]+)/?$', 'index.php?post_type=research&research-tags=$matches[1]', 'top');
        
        // double query. append query name at the end
        // research/vertical/{term}/category/{term}
        add_rewrite_rule('research/vertical/([^/]+)/category/([^/]+)/?$', 'index.php?post_type=research&verticals=$matches[1]&research-categories=$matches[2]', 'top');

        // research/vertical/{term}/tag/{term}
        add_rewrite_rule('research/vertical/([^/]+)/tag/([^/]+)/?$', 'index.php?post_type=research&verticals=$matches[1]&research-tags=$matches[2]', 'top');

        // research/vertical/{term}/category/{term}/tag/{term}
        add_rewrite_rule('research/vertical/([^/]+)/category/([^/]+)/tag/([^/]+)/?$', 'index.php?post_type=research&verticals=$matches[1]&research-categories=$matches[2]&research-tags=$matches[3]', 'top');
    }


    public function addTeamRewrites() {

        // vertical only
        add_rewrite_rule('team/vertical/([^/]+)/?$', 'index.php?post_type=team&verticals=$matches[1]&orderby=menu_order&order=ASC', 'top');
        
        // team-cats as /team/category/{term}
        add_rewrite_rule('team/category/([^/]+)/?$', 'index.php?post_type=team&team_category=$matches[1]&orderby=menu_order&order=ASC', 'top');

        // double query. append query name at the end
        // team/vertical/{term}/category/{term}
        add_rewrite_rule('team/vertical/([^/]+)/category/([^/]+)/?$', 'index.php?post_type=team&verticals=$matches[1]&team_category=$matches[2]&orderby=menu_order&order=ASC', 'top');

    }

    public function addAnnouncementRewrites() {

        // vertical only
        add_rewrite_rule('announcement/vertical/([^/]+)/?$', 'index.php?post_type=announcement&verticals=$matches[1]', 'top');
        
        // announcement-cats as /announcement/category/{term}
        add_rewrite_rule('announcement/category/([^/]+)/?$', 'index.php?post_type=announcement&announcement-category=$matches[1]', 'top');

        // double query. append query name at the end
        // announcement/vertical/{term}/category/{term}
        add_rewrite_rule('announcement/vertical/([^/]+)/category/([^/]+)/?$', 'index.php?post_type=announcement&verticals=$matches[1]&announcement-category=$matches[2]', 'top');

    }

    public function addCaseStudyRewrites() {

        // vertical only
        add_rewrite_rule('case-study/vertical/([^/]+)/?$', 'index.php?post_type=case-study&verticals=$matches[1]', 'top');
        
        // case-study-categories as /case-study/category/{term}
        add_rewrite_rule('case-study/category/([^/]+)/?$', 'index.php?post_type=case-study&case-study-category=$matches[1]', 'top');

        // double query. append query name at the end
        // case-study/vertical/{term}/category/{term}
        add_rewrite_rule('case-study/vertical/([^/]+)/category/([^/]+)/?$', 'index.php?post_type=case-study&verticals=$matches[1]&case-study-category=$matches[2]', 'top');

    }

    public function addEventsRewrites() {
        // this displays ALL upcoming and past events using eventDisplay=custom
        add_rewrite_rule('events/?$', 'index.php?post_type=tribe_events&query_name=all_events', 'top');

        // tribe defaults to only using upcoming events
        // order by whichever has the closest start date to today
        add_rewrite_rule('events/upcoming/?$', 'index.php?post_type=tribe_events&meta_key=_EventStartDate&orderby=_EventStartDate&order=ASC',',', 'top');

        add_rewrite_rule('events/past/?$', 'index.php?post_type=tribe_events&query_name=past_events',',', 'top');

        // vertical only
        add_rewrite_rule('events/vertical/([^/]+)/?$', 'index.php?post_type=tribe_events&verticals=$matches[1]', 'top');
        
        // event-categories as /event/category/{term}
        add_rewrite_rule('events/category/([^/]+)/?$', 'index.php?post_type=tribe_events&tribe_events_cat=$matches[1]', 'top');

        // double query. append query name at the end
        // event/vertical/{term}/category/{term}
        add_rewrite_rule('events/vertical/([^/]+)/category/([^/]+)/?$', 'index.php?post_type=tribe_events&verticals=$matches[1]&tribe_events_cat=$matches[2]', 'top');

    }

    public function addVerticalRewrites() {

        /**
        * Example Post Type Category Rewrite
        * $postTypeSlug
        * $taxonomySlug
        * add_rewrite_rule('vertical/([^/]+)/$postTypeSlug/category/([^/]+)/?$', 'index.php?post_type=$postTypeSlug&verticals=$matches[1]&$taxonomySlug=$matches[2]&vertical_base=1', 'top');
        *
        */

         // everything in vertical
        // /vertical/{ verticalTerm }/
        add_rewrite_rule('vertical/([^/]+)/?$', 'index.php?verticals=$matches[1]&vertical_base=1', 'top');

        // /vertical/{ verticalTerm }/team/
        // needs to go above the generic one since we're making a specific query for this one
        add_rewrite_rule('vertical/([^/]+)/team/?$', 'index.php?post_type=team&verticals=$matches[1]&vertical_base=1&orderby=menu_order&order=ASC', 'top');


        // vertical with a specific post type
        // /vertical/{ verticalTerm }/type/{ postType }
        add_rewrite_rule('vertical/([^/]+)/([^/]+)/?$', 'index.php?post_type=$matches[2]&verticals=$matches[1]&vertical_base=1', 'top');
        
        // research-cats as 
        // /vertical/{ verticalTerm }/research/category/{ term }
        add_rewrite_rule('vertical/([^/]+)/research/category/([^/]+)/?$', 'index.php?post_type=research&verticals=$matches[1]&research-categories=$matches[2]&vertical_base=1', 'top');

        add_rewrite_rule('vertical/([^/]+)/research/tag/([^/]+)/?$', 'index.php?post_type=research&verticals=$matches[1]&research-tags=$matches[2]&vertical_base=1', 'top');

        // /vertical/{ verticalTerm }/team/category/{ term }
        add_rewrite_rule('vertical/([^/]+)/team/category/([^/]+)/?$', 'index.php?post_type=team&verticals=$matches[1]&team_category=$matches[2]&vertical_base=1&orderby=menu_order&order=ASC', 'top');

        // announcement category
        // /vertical/{ verticalTerm }/announcement/category/{ term }
        add_rewrite_rule('vertical/([^/]+)/announcement/category/([^/]+)/?$', 'index.php?post_type=announcement&verticals=$matches[1]&announcement-category=$matches[2]&vertical_base=1', 'top');

        // case-study category
        // /vertical/{ verticalTerm }/case-study/category/{ term }
        add_rewrite_rule('vertical/([^/]+)/case-study/category/([^/]+)/?$', 'index.php?post_type=case-study&verticals=$matches[1]&case-study-category=$matches[2]&vertical_base=1', 'top');

        // tribe_Events
        add_rewrite_rule('vertical/([^/]+)/events/category/([^/]+)/?$', 'index.php?post_type=tribe_events&verticals=$matches[1]&tribe_events_cat=$matches[2]&vertical_base=1', 'top');

        // post category as/vertical/{ verticalTerm }/post/tag/{ term }
       add_rewrite_rule('vertical/([^/]+)/post/category/([^/]+)/?$', 'index.php?post_type=post&verticals=$matches[1]&category_name=$matches[2]&vertical_base=1', 'top');


       add_rewrite_rule('vertical/([^/]+)/post/tag/([^/]+)/?$', 'index.php?post_type=post&verticals=$matches[1]&tag=$matches[2]&vertical_base=1', 'top');

    }
}