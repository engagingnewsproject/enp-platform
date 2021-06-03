<?php
/**
 * ImageRecycle pdf & image compression
 *
 * @package ImageRecycle pdf & image compression
 */
defined('ABSPATH') || die('No direct script access allowed!');

class WPIO_queue
{

    private static $images;

    // Upon creation, load up our images from the file
    public function __construct($load = true)
    {
        self::$images = array();
        if ($load) {
            $this->loadImages();
        }
    }

    public function setImages($images)
    {

        self::$images = $images;
    }

    public function loadImages()
    {
        global $wpdb;
        $query = 'Select file From ' . $wpdb->prefix . 'wpio_queue';
        $result = $wpdb->get_col($query);
        if (!empty($result)) {
            self::$images = $result;
        }

    }

    // Add a file to the queue and if we are at our limit, drop one off the end.
    public function enqueue($strFile)
    {
        if (!empty($strFile) && !$this->isFilePresent($strFile)) {
            array_unshift(self::$images, $strFile);

        }
    }

    // Remove a file item from the end of our list
    public function dequeue()
    {
        if (count(self::$images) > 0) {
            return trim(array_pop(self::$images));
        }
        return "";
    }

    // Remove a file item from the end of our list
    public function unqueue($file)
    {
        if ($this->isFilePresent($file)) {
            $to_remove = array($file);
            self::$images = array_diff(self::$images, $to_remove);
            $this->saveChange($file, false);
        }
        return true;
    }

    // Save the contents of our array back to the file.
    public function save()
    {

        global $wpdb;
        $query = 'TRUNCATE TABLE ' . $wpdb->prefix . 'wpio_queue';
        $wpdb->query($query);

        if (count(self::$images) > 0) {
            $total = count(self::$images);
            $default_limit = 1000;
            for ($j = 0; $j < ($total / $default_limit); $j++) {
                $query = "INSERT INTO " . $wpdb->prefix . "wpio_queue (`file`) VALUES ";

                $place_holders = array();
                $values = array();
                for ($i = $j * $default_limit; (($i < $total) && ($i < $j * $default_limit + $default_limit)); $i++) {
                    $place_holders[] = "('%s')";
                    array_push($values, self::$images[$i]);
                }
                $query .= implode(', ', $place_holders);

                if ($wpdb->query($wpdb->prepare($query, $values)) === false) {
                    error_log($wpdb->print_error());;
                    die();
                }
            }

        }

    }

    public function saveChange($file, $add)
    {

        global $wpdb;
        if ($add) { //add file
            $wpdb->insert(
                $wpdb->prefix . "wpio_queue",
                array(
                    'file' => $file,
                ),
                array(
                    '%s'
                )
            );

        } else { //remove file

            $wpdb->delete($wpdb->prefix . "wpio_queue", array('file' => $file), array('%s'));
        }
    }

    // check if queue is empty or not
    public function isEmpty()
    {
        if (count(self::$images) > 0) {
            return false;
        }
        return true;
    }

    public function count()
    {
        return count(self::$images);
    }

    public function dbCount()
    {
        global $wpdb;
        $query = 'Select COUNT(*) From ' . $wpdb->prefix . 'wpio_queue';
        $result = $wpdb->get_var($query);
        return $result;

    }

    public function getLastFile()
    {
        global $wpdb;
        $query = 'Select file From ' . $wpdb->prefix . 'wpio_queue ORDER BY id DESC LIMIT 1';
        $result = $wpdb->get_var($query);
        return $result;
    }

    public function clear()
    {

        self::$images = array();
        $this->save();
    }

    // Check if an item is already in our list. 
    public function isFilePresent($strFile = "")
    {
        if (!empty($strFile)) {
            if (in_array($strFile, self::$images)) {
                return true;
            }
            return false;
        }
        return -1;
    }

    // Mainly a debug function to print our values to screen.
    public function printValues()
    {
        foreach (self::$images as $value) {
            echo "$value<br/>";
        }
    }

}
