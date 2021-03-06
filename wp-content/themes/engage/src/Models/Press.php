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
    // public $html_string;

    public function __construct($pid = null)
    {
        parent::__construct($pid);
        $this->generateTableStructure($this->content);
        // $this->generateHtmlString();
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
        // console_log($this->rows);
        $this->columns = array_shift($rows  );
        $this->rows = $rows;
        // console_log($this->rows);
    }


    // public function generateHtmlString()
    // {
    //     // data rows
    //     foreach ($this->rows as $row) {
    //         $html .= '<tr>';
    //         $html .= '<td style="    border-bottom: solid .5px #000;
    //                 color: #555;
    //                 font-weight: 400;
    //                 font-size: 14px;
    //                 text-transform: uppercase;
    //                 padding: 15px;
    //                 text-shadow: 1px 1px 1px #fff;">' . $row[0] . '</td>';
    //         $html .= '<td style="    border-bottom: solid .5px #000;
    //                 color: #555;
    //                 font-weight: 400;
    //                 font-size: 14px;
    //                 text-transform: capitalized;
    //                 padding: 15px;
    //                 text-shadow: 1px 1px 1px #fff;">' . $row[1] . '</td>';
    //         $html .= '<td style="    border-bottom: solid .5px #000;
    //                 color: #555;
    //                 font-weight: 400;
    //                 font-size: 14px;
    //                 text-transform: uppercase;
    //                 padding: 15px;
    //                 text-shadow: 1px 1px 1px #fff;">' . $row[2] . '</td>';
    //         $html .= '</tr>';
    //     }
        
    //     // finish table and return it
    //     $this->html_string = $html;
    // }
}
