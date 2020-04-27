<?php

namespace Engage\Models;

use Timber\Post;

function console_log($output, $with_script_tags = true)
{
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
        ');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}

class Press extends Post
{
    public $columns;
    public $rows;
    public $html_string;

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->generateTableStructure($this->content);
        $this->generateHtmlString();
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
        console_log($this->rows);
        $this->columns = array_shift($rows  );
        $this->rows = $rows;
        console_log($this->rows);
    }


    public function generateHtmlString()
    {
        $html = '<table style="border-collapse: collapse; border-spacing: 0;">';
        // header row
        $html .= '<thead><tr>';
        foreach ($this->columns as $head) {
            $html .= '<th style="background-color: transparent;
                border-top: solid .5px #000;
                border-bottom: solid .5px #000;
                color: #000;
                font-weight: 700;
                font-size: 17px;
                padding: 10px;
                text-align: left;
                text-shadow: 1px 1px 1px #fff;">' . $head . '</th>';
        }
        $html .= '</tr></thead>';

        // data rows
        foreach ($this->rows as $row) {
            $html .= '<tr>';
            foreach ($row as $point) {
                console_log($point);
                $html .= '<td style="    border-bottom: solid .5px #000;
                    color: #555;
                    font-weight: 400;
                    font-size: 14px;
                    text-transform: uppercase;
                    padding: 15px;
                    text-shadow: 1px 1px 1px #fff;">' . $point . '</td>';
            }
            $html .= '</tr>';
        }

        // finish table and return it

        $html .= '</table>';
        $this->html_string = $html;
    }
}
