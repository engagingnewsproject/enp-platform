<?php

namespace NinjaFormsAddonManager\Webhooks;

class Response
{
    public $data;
    public $status_code;

    public function __construct( $data = array(), $status_code = 200 )
    {
        $this->data = $data;
        $this->status_code = $status_code;
    }

    public function respond( $data = array(), $status_code = false )
    {
        if( $status_code ) $this->status_code = $status_code;
        status_header( $this->status_code );

        if( $data ){
            if( ! is_array($data) ) $data = array( $data );
            $this->data = array_merge( $this->data, $data );
        }

        ob_clean();
        echo json_encode( $this->data );
        exit();
    }

    public function set_status_code( $code )
    {
        $this->status_code = $code;
    }
}
