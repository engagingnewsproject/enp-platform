<?php

return [
    'download_link' => [
        'edd_action' => 'get_download',
        'url'        => urlencode( home_url() ),
        'expires'    => rawurlencode( base64_encode( strtotime( '+10 minutes' ) ) ),
    ]
];
