<?php

namespace Engage\Managers;

class TinyMCE {

    function __construct() {
        
    }

    function run() {
        add_filter('tiny_mce_before_init', [$this, 'ChangeMCEOptions']);
    }

    /**
     * Add to extended_valid_elements for TinyMCE
     *
     * @param $init assoc. array of TinyMCE options
     * @return $init the changed assoc. array
     */
    function changeMCEOptions( $init ) {

        // Command separated string of extended elements
        $ext = 'style[*],script[*],canvas[*]';

        // Add to extended_valid_elements if it alreay exists
        if ( isset( $init['extended_valid_elements'] ) ) {
            $init['extended_valid_elements'] .= ',' . $ext;
        } else {
            $init['extended_valid_elements'] = $ext;
        }
            $init['valid_children'] .= "+figure[div|span|canvas|h1|h2|h3|h4|h5|h6],+div[canvas],+a[em|strong|small|mark|abbr|dfn|i|b|s|u|code|var|samp|kbd|sup|sub|q|cite|span|bdo|bdi|br|wbr|ins|del|img|embed|object|iframe|map|area|noscript|ruby|video|audio|input|textarea|select|button|label|output|datalist|keygen|progress|command|canvas|time|meter|p|hr|pre|ul|ol|dl|div|h1|h2|h3|h4|h5|h6|hgroup|address|blockquote|section|nav|article|aside|header|footer|figure|table|f|m|fieldset|menu|details|style|link],+body[style|link|figure]";

        // Super important: return $init!
        return $init;
    }
}