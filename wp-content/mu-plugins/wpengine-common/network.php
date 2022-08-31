<?php
/**
 * Functions for network sites
 *  Package: 
 *  Owner: 
 *	Notes: 
 */


/*
 * Domain Mapping Integration
 */

//first check if domain mapping is on 
if( defined('SUNRISE') && !is_wpe_snapshot() ) {

	// if domain mapping is on let's add the appropriate actions
	add_action('admin_init','wpe_api_domain_manage');

	/*
 	* Domain Mapping Integration
 	*/
	function wpe_api_domain_manage($domain) {


        // Ensure we have some default values, in case they aren't set (Prevents PHP Notices)
        $page    = isset( $_GET['page'] )       ? sanitize_text_field( wp_unslash( $_GET['page'] ) )      : '';
        $action  = isset( $_POST['action'] )    ? sanitize_text_field(  wp_unslash( $_POST['action']  ) )  : '';
        $request = isset( $_REQUEST['action'] ) ? sanitize_text_field(  wp_unslash( $_REQUEST['action'] ) ): '';

		//make sure we're on a domain mapping plugin
		if( $page == 'dm_domains_admin' OR $page == 'domainmapping') {

			//don't do anything if we're editing		
			if($action == 'edit') {
				
				//maybe oneday we'll do something
			
			//save or add the domain	
			} elseif(!empty($_POST) AND ( $action == 'save' OR $action == 'add') ) {

				//validate the referrer
				check_admin_referer('domain_mapping');

				//load the api class
				include_once(WPE_PLUGIN_DIR.'/class-wpeapi.php');
				$api = new WPE_API();

				$domain = isset( $_POST['domain'] ) ? sanitize_text_field(  wp_unslash( $_POST['domain'] ) ) : '';
				if ( preg_match('/(\;|\,|\?)/',$domain) || ! preg_match('/[A-z]|[1-9]|\./',$domain ) ) {

					$api->set_notice("The domain you entered was not valid.");
					if($api->is_error()) {
						unset($_POST);
					}
					
				} else {
					
					//set the method and domain
					$api->set_arg('method','domain');
					$api->set_arg('domain',$domain);
					
					//do the api request and send the reponse to the admin screen
					
					$api->get()->set_notice();

					
				}
			
			//delete a domain
			} elseif( $request == 'delete' OR $request == 'del' ) {

				check_admin_referer('domain_mapping');
				//load the api class
				include_once(WPE_PLUGIN_DIR.'/class-wpeapi.php');
				$api = new WPE_API();

				$request_domain    = isset( $_REQUEST['domain'] )       ? sanitize_text_field(  wp_unslash( $_REQUEST['domain'] ) )      : '';
				//set the method and domain
				$api->set_arg('method','domain-remove');
				$api->set_arg('domain',$request_domain);

				//do the api request and send the reponse to the admin screen
				$api->get()->set_notice();
				error_log('del:'.var_export($api,true));
			} 
				
		}
	} 
}


