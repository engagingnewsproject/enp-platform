<?php
/**
 * Collection of utility functions for the quiz creator
 *
 * @since      1.0.1
 * @package    Enp_quiz
 * @author     Engaging News Project <jones.jeremydavid@gmail.com>
 */
class Enp_quiz_Slugify {

    public function __construct() {

    }

    /**
    * Enter a string and it'll pop out a slug.
    * Allowed characters are A-Z, a-z, 0-9, and dashes (-)
    * Disallowed characters are replaced with a dash (-)
    * and if the last character in the string is a dash it'll remove it.
    *
    * @param $string (STRING)
    * @return $slug (STRING) in format this-is-a-slug
    */
    public function slugify($string) {
        // check if it's a slug already
        $is_slug = $this->is_slug($string);
        if($is_slug === true) {
            // already a slug
            $slug = $string;
        } else {
            // turn it into a slug!
            // trim it
            $slug = trim($string);
            // replace disallowed characters
            $slug=preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
            // pop off dash(es) from beginning and end of string
            $slug=preg_replace('/^-+|-+$/', '', $slug);
            // make it lowercase
            $slug = strtolower($slug);
        }

        return $slug;
    }

    /**
    * Checks to see if it's a slug or not
    * Allowed characters are A-Z, a-z, 0-9, and dashes (-)
    *
    * @param $string (STRING)
    * @return  BOOLEAN
    */
    public function is_slug($string) {
        $validation = new Enp_quiz_Save();
        return $validation->is_slug($string);
    }

    /**
    * Checks if a string is probably an ID (contains only numbers)
    * This could likely live in a better locale, but don't have a good place for it
    * and it makes sense that you'd be doing this alongside slugs
    *
    * @param $string (MIXED String/Integer)
    * @return BOOLEAN
    */
    public function is_id($string) {
        $validation = new Enp_quiz_Save();
        return $validation->is_id($string);
    }
}
