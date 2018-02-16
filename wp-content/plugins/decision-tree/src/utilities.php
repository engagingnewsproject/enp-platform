<?php
/**
 *  Utility functions for use throughout code
 */
namespace Cme\Utility;



/**
* Build the data URL to get the JSON data
*
* @return String
*/
function get_data_url($slug) {
    return get_server_url()."/data/$slug.json";
}

/**
* Checks to see if it's a slug or not
* Allowed characters are A-Z, a-z, 0-9, and dashes (-)
*
* @param $string (STRING)
* @return  BOOLEAN
*/
function is_slug($string) {
    $is_slug = false;
    // check for disallowed characters and strings that starts or ends in a dash (-)
    // if matches === 1, then it's a slug
    preg_match('/[^a-z0-9-]+|^-|-$/', $string, $matches);

    // check to make sure it's not null/empty
    // if there's a match, it's not a slug
    // also make sure $string !== boolean
    if(is_bool($string) === false && is_int($string) !== true && !empty($string) && empty($matches)) {
        $is_slug = true;
    }

    return $is_slug;
}

/**
* Checks if a string is probably an ID (contains only numbers)
* This could likely live in a better locale, but don't have a good place for it
* and it makes sense that you'd be doing this alongside slugs
*
* @param $string (MIXED String/Integer)
* @return BOOLEAN
*/
function is_id($string) {
    $is_id = false;

    // make sure it's a valid string
    if(is_bool($string) === false && !empty($string)) {
        $string = (string) $string;
        // Regex check where the only allowed characters are 0-9
        // if a match is found, then it's not an ID
        $matches = null;
        preg_match('/[^0-9]/', $string, $matches);
        // if preg_match returns false (0) & it's not null/empty then it's an ID
        if(empty($matches)) {
            $is_id = true;
        }
    }

    return $is_id;
}

function get_tree_slug_by_id($tree_id) {
    // test if it's a valid ID or not
    if(!is_id($tree_id)) {
        return false;
    }
    // use the id to get the slug. Switch to $tree_id bc that's what it is
    $DB = new \ENP\Database\DB();
    $tree = $DB->get_tree($tree_id);
    // return the tree slug
    return $tree['tree_slug'];
}

function get_tree_id_by_slug($tree_slug) {
    // test if it's a valid ID or not
    if(!is_slug($tree_slug)) {
        return false;
    }
    // use the id to get the slug. Switch to $tree_id bc that's what it is
    $DB = new \ENP\Database\DB();
    $tree = $DB->get_tree_by_slug($tree_slug);
    // return the tree slug
    return $tree['tree_id'];
}

// really bare curl implementation to consume our own api
function get_endpoint($path) {
    // Get cURL resource
    $curl = curl_init();
    // Set options
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1, // get response as string
        CURLOPT_URL => TREE_URL.'/api/v1/'.$path
    ));
    // Send the request
    $response = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);

    return $response;
}
