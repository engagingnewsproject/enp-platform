<?php
$waf_allow_list = array (
  0 => '75.82.202.14',
  1 => '128.62.47.116',
);
return $waf->is_ip_in_array( $waf_allow_list );
