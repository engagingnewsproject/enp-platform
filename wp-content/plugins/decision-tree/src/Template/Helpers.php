<?php
namespace Cme\Template;
/**
 * collection of helper functions for handlebars php implementation.
 * each helper here should match the JS helpers in assets/js/handlebars-helpers.js
 * Each helper has to be register in \Cme\Template\Compile.php -> compile()
 */
class Helpers {

    function __construct() {

    }

    /**
    * Get a group title by ID
    */
    public static function environment($options) {
        return 'no-js';
    }

    /**
    * Find out if this question starts a group
    */
    public static function group_start($question_id, $group_id, $groups, $options) {
        foreach($groups as $group) {
            if($group['group_id'] === $group_id) {
                // check if it's the first in the question order
                if($group['questions'][0] === $question_id) {
                    // set the context of the values we'll need
                    return $options['fn'](["group_id"=>$group['group_id'], "group_title"=>$group['title']]);
                } else {
                    return '';
                }
            }
        }
        return '';
    }

    /**
    * Find out if this question ends a group
    */
    public static function group_end($question_id, $group_id, $groups, $options) {
        foreach($groups as $group) {
            if($group['group_id'] === $group_id) {
                // check if it's the first in the question order
                $last_question = array_values(array_slice($group['questions'], -1))[0];
                if($last_question === $question_id) {
                    return $options['fn']();
                } else {
                    return '';
                }
            }
        }
        return '';
    }

    public static function el_number($order) {
        return $order + 1;
    }

    // we don't need this function for the php version
    public static function destination($destination_id, $destination_type, $option_id, $question_index, $options) {
        return '';
    }
}

?>
