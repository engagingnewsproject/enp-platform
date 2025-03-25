<?php
/**
 * Class Press
 */
namespace Engage\Models;

use Timber\Post;

class PressPage extends Post
{
    public $columns;
    public $rows;
    public $html_string;
    /**
     * Estimates time required to read a post.
     *
     * The words per minute are based on the English language, which e.g. is much
     * faster than German or French.
     *
     * @link https://www.irisreading.com/average-reading-speed-in-various-languages/
     *
     * @return string
     */

    public function generate_table()
    {
        $this->generateTableStructure($this->content);
    }
    
    public function generateTableStructure($html_input)
    {
        $removed_tags = explode('</p>', implode("", explode('<p>', $html_input)));
        # column headers will be the first row
        $rows = [];
        foreach ($removed_tags as $comma_row) {
            $row_seperated = explode('|', trim($comma_row));
            if(count($row_seperated) > 0 && strlen($row_seperated[0]) > 0){
                array_push($rows, $row_seperated); 
            }
        }
        $this->columns = array_shift($rows  );
        $this->rows = $rows;
    }
}