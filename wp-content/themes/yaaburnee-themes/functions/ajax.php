<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/* -------------------------------------------------------------------------*
 * 							RATING SYSTEM									*
 * -------------------------------------------------------------------------*/
 
function rating_system() {
	$value = $_POST['value'];
	$postID = $_POST['post_id'];
	
	$totalVotesOld = get_post_meta( $postID, THEME_NAME."_total_votes", true );
	if(!$totalVotesOld) $totalVotesOld = 0;
	$votes = $totalVotesOld + 1;
	update_post_meta( $postID, THEME_NAME."_total_votes", $votes, $totalVotesOld ); 

	$totalRatingOld = get_post_meta( $postID, THEME_NAME."_total_rating", true );
	if(!$totalRatingOld) $totalRatingOld = 0;
	$rating = $totalRatingOld + $value;
	update_post_meta( $postID, THEME_NAME."_total_rating", $rating, $totalRatingOld ); 

	echo round($rating/$votes);

	die();

}

/* -------------------------------------------------------------------------*
 * 					HOMEPAGE SAVE DRAG&DROP OPTIONS							*
 * -------------------------------------------------------------------------*/
 
function df_save_options() {
	$fields = $_REQUEST;

	foreach($fields as $key => $field) {
		if($key!="action") {
			//echo $key."-".$field;
			update_option($key,$field);
		}
	}


	die();

}
/* -------------------------------------------------------------------------*
 * 							SLIDER ORDER									*
 * -------------------------------------------------------------------------*/
 
function update_slider() {
	$updateRecordsArray = $_POST['recordsArray'];
	
	if ( !get_option(THEME_NAME."-slide-order-set" ) ) {
		add_option(THEME_NAME."-slide-order-set", "1" );
	}
	
	$listingCounter = 1;
	foreach ($updateRecordsArray as $recordIDValue) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare("UPDATE $wpdb->posts SET menu_order = ".$listingCounter." WHERE ID = " . $recordIDValue  ) ); 

		$listingCounter = $listingCounter + 1;

	}

}

/* -------------------------------------------------------------------------*
 * 							HOMEPAGE ORDER									*
 * -------------------------------------------------------------------------*/
 
function update_homepage() {
	$updateRecordsArray = $_POST['recordsArray'];
	$array = explode(',', $_POST['count']);
	$type = explode(',', $_POST['type']);
	$string = explode(',', $_POST['inputType']);
	$postID = explode(',', $_POST['post_id']);

	$strings = array();
	$array_count = sizeof($array);
	$e = 0;
	for($c = 0; $c < $array_count; $c++) {
		$items = array();
		for($i = 0; $i < $array[$c]; $i++) {
			array_push($items, $string[$e]);
			$e++;
		}
		
		if($array[$c] == 0) {
			$e++;
		}
		array_push($strings, $items);
		
		$items = "";
	}
	
	$homepage_layout = array();
	
	$a=0;
	
	if(!empty($updateRecordsArray)) {
		foreach($updateRecordsArray as $recordIDValue)  {
			$homepage_layout[$a]['type'] = $type[$a];
			$homepage_layout[$a]['inputType'] = $strings[$a];
			$homepage_layout[$a]['id'] = $recordIDValue;
			
			$a++;
		}
	}


	
	update_option(THEME_NAME."_homepage_layout_order_".$postID[0], $homepage_layout );

	die();

}

/* -------------------------------------------------------------------------*
 * 						LOAD NEXT IMAGE IN GALLERY							*
 * -------------------------------------------------------------------------*/
 
function load_next_image(){
	$g = $_POST['gallery_id'];
	$next_image = $_POST['next_image'];

	$galleryImages = get_post_meta ( $g, THEME_NAME."_gallery_images", true ); 
	$imageIDs = explode(",",$galleryImages);


	$c=0;
	$images = array();
	
	foreach($imageIDs as $id) {
		$file = wp_get_attachment_url($id);
		$image = get_post_thumb(false, 1200, 0, false, $file);
		$images[] = $image['src'];
		$c++;
	}
						
				
	echo $images[$next_image-1];
	die();
}

/* -------------------------------------------------------------------------*
 * 							SIDEBAR GENERATOR								*
 * -------------------------------------------------------------------------*/
 
function update_sidebar() {
	$updateRecordsArray = $_POST['recordsArray'];
	$last = array_pop($updateRecordsArray);
	$updateRecordsArray = implode ("|*|", $updateRecordsArray)."|*|".$last."|*|";
	update_option( THEME_NAME."_sidebar_names", $updateRecordsArray);
	echo $updateRecordsArray;
}
function delete_sidebar() {
	$sidebar_name = $_POST['sidebar_name']."|*|";
	$sidebar_names = get_option( THEME_NAME."_sidebar_names" );
	$sidebar_names = explode( "|*|", $sidebar_names );
	$sidebar_name = explode( "|*|", $sidebar_name );
	$result = array_diff($sidebar_names, $sidebar_name);
	$last = array_pop($result);
	$update_sidebar = implode ("|*|", $result)."|*|".$last."|*|";
	if(empty($result) || count($result)<=1){
		$update_sidebar = $last;
		if($last) {
			$update_sidebar.= "|*|";	
		}
	} else {
		$update_sidebar = implode ("|*|", $result)."|*|".$last."|*|";	
	}
	update_option( THEME_NAME."_sidebar_names", $update_sidebar);
	echo $update_sidebar;
}
function edit_sidebar() {
	$new_sidebar_name = sanitize_title($_POST['sidebar_name']);
	$old_name = $_POST['old_name'];

	$sidebar_names = get_option( THEME_NAME."_sidebar_names" );
	$sidebar_names = explode( "|*|", $sidebar_names );
	$new_sidebar_names=array();
	foreach ($sidebar_names as $sidebar_name) {
		if($sidebar_name!="") {
			if ($sidebar_name==$old_name) {
				$new_sidebar_names[]=$new_sidebar_name;
			} else {
				$new_sidebar_names[]=$sidebar_name;
			}
		}
	}
	$last = array_pop($new_sidebar_names);

	if(empty($new_sidebar_names)){
		$update_sidebar =  $last."|*|";
	} else {
		$update_sidebar = implode ("|*|", $new_sidebar_names)."|*|".$last."|*|";
	}
	
	
	update_option( THEME_NAME."_sidebar_names", $update_sidebar);
	echo $update_sidebar;
}



/* -------------------------------------------------------------------------*
 * 								 CONTACT FORM								*
 * -------------------------------------------------------------------------*/
 
 
function contact_form() {
	$contactID = $_POST["contact_id"];
	$mail_to = get_post_meta ($contactID, THEME_NAME."_contact_mail", true );
	

	if(isset($_POST["email"]) && is_email($_POST["email"])){
		$email = is_email($_POST["email"]);
	}
	if(isset($_POST["name"])){
		$u_name = esc_textarea($_POST["name"]);
	}

	if(isset($_POST["comments"])){
		$message = stripslashes(esc_textarea($_POST["comments"]));
	}

	
	$ip = $_SERVER['REMOTE_ADDR'];

	
	if(isset($_POST["form_type"])) {	

		$subject = ( __( 'From' , THEME_NAME ))." ".get_bloginfo('name')." ".( __( 'Contact Page' , THEME_NAME ));
		$subject = html_entity_decode (  $subject, ENT_QUOTES, 'UTF-8' );
				
		$eol="\n";
		$mime_boundary=md5(time());
		$headers = "From: ".$email." <".$email.">".$eol;
		//$headers .= "Reply-To: ".$email."<".$email.">".$eol;
		$headers .= "Message-ID: <".time()."-".$email.">".$eol;
		$headers .= "X-Mailer: PHP v".phpversion().$eol;
		$headers .= 'MIME-Version: 1.0'.$eol;
		$headers .= "Content-Type: text/html; charset=UTF-8; boundary=\"".$mime_boundary."\"".$eol.$eol;

		ob_start(); 
		?>
<?php printf ( __( 'Message:' , THEME_NAME ));?> <?php echo nl2br($message);?>
<div style="padding-top:100px;">
<?php _e( 'Name:' , THEME_NAME );?> <?php echo $u_name;?><br/>
<?php _e( 'E-mail:' , THEME_NAME );?> <?php echo $email;?><br/>
<?php _e( 'IP Address:' , THEME_NAME );?> <?php echo $ip;?><br/>
</div>
<?php
		$message = ob_get_clean();
		wp_mail($mail_to,$subject,$message,$headers);
			
	}
	 
	die();

}

/* -------------------------------------------------------------------------*
 * 							RESERVATION FORM								*
 * -------------------------------------------------------------------------*/
 
 
function reservation_form() {
	
	$pageID = $_POST["pageID"];

	echo $mail_to = get_post_meta ( $pageID, THEME_NAME."_reservation_mail", true ); 
	$terms = get_post_meta ( $pageID, THEME_NAME."_reservation_terms", true );  

	if(isset($_POST["email"]) && is_email($_POST["email"])){
		$email = is_email($_POST["email"]);
	}
	if(isset($_POST["firstname"])){
		$firstname = esc_textarea($_POST["firstname"]);
	}
	if(isset($_POST["lastname"])){
		$lastname = esc_textarea($_POST["lastname"]);
	}
	if(isset($_POST["phone"]) && is_numeric($_POST["phone"])){
		$phone = esc_textarea($_POST["phone"]);
	}
	if(isset($_POST["roomtype"])){
		$roomtype = esc_textarea($_POST["roomtype"]);
	}
	if(isset($_POST["numberofrooms"])){
		$numberofrooms = esc_textarea($_POST["numberofrooms"]);
	}
	if(isset($_POST["numberofadults"])){
		$numberofadults = esc_textarea($_POST["numberofadults"]);
	}
	if(isset($_POST["numberofchildren"])){
		$numberofchildren = esc_textarea($_POST["numberofchildren"]);
	}
	if(isset($_POST["from"])){
		$from = esc_textarea($_POST["from"]);
	}
	if(isset($_POST["to"])){
		$to = esc_textarea($_POST["to"]);
	}
	if(isset($_POST["message"])){
		$message = esc_textarea($_POST["message"]);
	}
	if(isset($_POST["agree"])){
		$agree = $_POST["agree"];
	}

	if(!$terms) {
		$agree = "yes";
	}
	
	$ip = $_SERVER['REMOTE_ADDR'];

	
	if(isset($_POST["form_type"]) && !empty($agree)) {	

		$msg  = '<table style="border:1">
			<tbody>
				<tr>
					<td style="width:300px;">'.__("First name:",THEME_NAME).'</td>
					<td>'.$firstname.'</td>
				</tr>
				<tr>
					<td>'.__("Last name:",THEME_NAME).'</td>
					<td>'.$lastname.'</td>
				</tr>
				<tr>
					<td>'.__("Email adress:",THEME_NAME).'</td>
					<td>'.$email.'</td>
				</tr>
				<tr>
					<td>'.__("Phone number:",THEME_NAME).'</td>
					<td>'.$phone.'</td>
				</tr>
				<tr>
					<td>'.__("Room type:",THEME_NAME).'</td>
					<td>'.$roomtype.'</td>
				</tr>
				<tr>
					<td>'.__("No. of rooms:",THEME_NAME).'</td>
					<td>'.$numberofrooms.'</td>
				</tr>
				<tr>
					<td>'.__("No. of adults:",THEME_NAME).'</td>
					<td>'.$numberofadults.'</td>
				</tr>
				<tr>
					<td>'.__("No. of children:",THEME_NAME).'</td>
					<td>'.$numberofchildren.'</td>
				</tr>
				<tr>
					<td>'.__("Arrival date:",THEME_NAME).'</td>
					<td>'.$from.'</td>
				</tr>
				<tr>
					<td>'.__("Departure date:",THEME_NAME).'</td>
					<td>'.$to.'</td>
				</tr>
				<tr>
					<td>'.__("Additional information:",THEME_NAME).'</td>
					<td>'.nl2br($message).'</td>
				</tr>
			</tbody>
		</table>' . PHP_EOL;

		$msg = wordwrap( $msg, 70 );

		$subject = ( __( 'New Reservation At' , THEME_NAME ))." ".get_bloginfo('name')." - ".$firstname." ".$lastname;
		
		$eol="\n";
		$mime_boundary=md5(time());
		$headers = "From: ".$firstname." ".$lastname." <".$email.">".$eol;
		//$headers .= "Reply-To: ".$email."<".$email.">".$eol;
		$headers .= "Message-ID: <".time()."-".$email.">".$eol;
		$headers .= "X-Mailer: PHP v".phpversion().$eol;
		$headers .= 'MIME-Version: 1.0'.$eol;
		$headers .= "Content-Type: text/html; charset=UTF-8; boundary=\"".$mime_boundary."\"".$eol.$eol;


		wp_mail( $mail_to, $subject, $msg, $headers );

	}

	die();

}


add_action('wp_ajax_update_slider', 'update_slider');
add_action('wp_ajax_update_homepage', 'update_homepage');

add_action('wp_ajax_update_sidebar', 'update_sidebar');
add_action('wp_ajax_delete_sidebar', 'delete_sidebar');
add_action('wp_ajax_edit_sidebar', 'edit_sidebar');


add_action('wp_ajax_nopriv_contact_form', 'contact_form');
add_action('wp_ajax_contact_form', 'contact_form');


add_action('wp_ajax_nopriv_reservation_form', 'reservation_form');
add_action('wp_ajax_reservation_form', 'reservation_form');

add_action('wp_ajax_nopriv_df_save_options', 'df_save_options');
add_action('wp_ajax_df_save_options', 'df_save_options');

add_action('wp_ajax_load_next_image', 'load_next_image');
add_action('wp_ajax_nopriv_load_next_image', 'load_next_image');

?>
