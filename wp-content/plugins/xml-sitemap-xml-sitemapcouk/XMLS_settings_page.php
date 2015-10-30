<div class="wrap">
<h2><?php print XMLS_PUGIN_NAME ." ". XMLS_CURRENT_VERSION. "<sub>(Build ".XMLS_CURRENT_BUILD.")</sub>"; ?></h2>

<form method="post" action="options.php">
    <?php
		settings_fields( 'XMLS-settings-group' );
	?>
    <table class="form-table">
       
        <tr valign="top">
        <th scope="row"><a target="_blank" href="<?php echo site_url(); ?>/sitemap.xml">Click here</a> to view your WordPress XML Sitemap</th>
            <th>OR</th>
        <th scope="row"><?php
$current_user = wp_get_current_user();
$user = (array)$current_user->data;
$user['url'] = "http://" . $_SERVER['SERVER_NAME'];
$url = "http://www.sitemaps.io/new/wp/install";
    foreach($user as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    rtrim($fields_string, '&');

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

    //execute post
    $result = curl_exec($ch);
    //close connection
    curl_close($ch);
?> to view your Full XML Sitemap</th>
        <td></td>
        </tr>
    </table>
    
    <p class="submit">
    
    Plugin provided by <a target="_blank" href="http://www.sitemaps.io">Sitemaps.io</a>
    </p>

</form>
</div>