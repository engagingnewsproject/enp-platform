<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(class_exists('DspExportImportModel') != true):
class DspExportImportModel
{
   var $exportmodel = null;
	var $importmodel = null;
   public function __construct()
   {}
   
   /**
   * Shows all the menus which is being created 
   *
   * @param array $requested_vars accepts as the arguments
   * returns all the navigation menus  
   */
   public function getListMenus($requested_vars = null)
   {      
      $nav_menus = wp_get_nav_menus();
      return $nav_menus;
   }

   /**
   * Used for creating the JSON for the selected Menu 
   *
   * @param array $requested_vars accepts as the arguments
   * returns the JSON File of the particular Menu 
   */
   public function generateMenusJson($requested_vars = null)
   {
		$response = '';
      if(isset($requested_vars['menu']) && $requested_vars['menu'] != '' && is_numeric($requested_vars['menu']))
      {
         $menuid = $requested_vars['menu'];
         $menuobj = get_term_by('id', $menuid, 'nav_menu');
         if(isset($menuobj->slug) && !empty($menuobj)) {
            $menuname = $menuobj->slug;
         }
         else{
            $menuname = $requested_vars['menu'];
         }
         $navitems = wp_get_nav_menu_items( $menuid );
         if(is_array($navitems) && !empty($navitems))
         {
            if (!isset($data)) 
            $data = array();
            $count = 0;
            foreach ($navitems as $singlenav)
            {
               $navmetas = get_post_meta( $singlenav->ID);
               $data[$count]['post'] = $singlenav;
               $data[$count]['post_metas'] = $navmetas;
               $count++;
            }
            $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            ob_clean();
            header( 'Content-Description: File Transfer' );
            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="wp_menus_' . $menuname . '_backup_' . date( 'd-m-Y-G-i-s' ) . '.json"' );
            header( 'Content-Transfer-Encoding: binary' );
            header( 'Expires: 0' );
            header( 'Cache-Control: must-revalidate' );
            header( 'Pragma: public' );
            echo $data;
            exit;
         }
         else
         {
            $this->exportmodel = 2; //No menu items were found.
         }
      }
      else
      {
         $this->exportmodel = 3; //Please select navigation.
      }
      return $this->exportmodel;
   }
     
   /**
   * Upload the JSON Menu
   *
   * @param array $requested_vars accepts as the arguments
   * returns string $response and create the new Backup Menu
   */
   public function uploadMenusJson($requested_vars = null)
   {
      $nonce = check_ajax_referer( 'menus_nonce_verify','security', false );
      if(empty($nonce) || -1 === $nonce)
		{
			$res["response"] = DSPMENUS_IMPORTMSG7;
			$res["status"] = 0;
			wp_send_json( $res );
		}
      if (!current_user_can('upload_files') ) 
      {
         $res["response"] = DSPMENUS_UPLOADMSG5;
         $res["status"] = 0;
         wp_send_json( $res );
      }
      if(isset($requested_vars['menusfile']['name']))
      {
         $wp_filetype = wp_check_filetype( $requested_vars['menusfile']['name'],array('json' => 'application/json'));
         if (! wp_match_mime_types( 'application/json', $wp_filetype['type'] ) && !isset($requested_vars['isFileTypeChecked'])) {
            $res["response"] = DSPMENUS_UPLOADMSG6;
            $res["status"] = 0;
            wp_send_json( $res );
         }
      }
      
      $res = array('nextMenuPos'=>0,'isContinue'=>0,'menuId'=>0,'oldIds'=>array(),'newIds'=> array(),'isFileTypeChecked' => 1);
      if(empty($requested_vars))
      {
       $res["response"] = DSPMENUS_IMPORTMSG6;
       $res["status"] = 0;
       wp_send_json( $res );
      }
      
      if(empty($requested_vars['dspmenuname']))
      {
       $res["response"] = DSPMENUS_UPLOADMSG4;
       $res["status"] = 0;
       wp_send_json( $res );
      }
      
      if(empty($requested_vars['fileurl']))
      {
       if(empty($requested_vars['menusfile']))
       {
        $res["response"] = DSPMENUS_UPLOADMSG2;
        $res["status"] = 0;
        wp_send_json( $res );
       }
       $upload_dir = wp_get_upload_dir();
       $upload_path = $upload_dir["basedir"]."/menus-exportimport/";                             
       if (!file_exists($upload_path))
       {
        if(!mkdir($upload_path, 0777, true))
        {
          $res["response"] = DSPMENUS_UPLOADMSG3;
          $res["status"] = 0;
          wp_send_json( $res );
        }
       }       	
       if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
       }
       
       $upload_overrides = array( 'test_form' => true,'action' => 'dspImportMenus' );
       add_filter('upload_mimes',array($this,'customMimeTypes'), 1, 1);
       add_filter('upload_dir', array($this,'menusExportImportDir'));
       add_filter('map_meta_cap', array($this,'menusUnfilteredUpload'), 0, 2);
       $movefile = wp_handle_upload( $requested_vars['menusfile'], $upload_overrides );
       remove_filter('upload_mimes', array($this,'customMimeTypes'), 1, 1);
       remove_filter('upload_dir', array($this,'menusExportImportDir'));
       remove_filter('map_meta_cap', array($this,'menusUnfilteredUpload'));   
       if ( $movefile && ! isset( $movefile['error'] ) ) { 
         $res["response"] = DSPMENUS_UPLOADMSG1;
         $res["fileurl"] =  $movefile['url'];
         $res["status"] = 1;
       } else {
         $res["response"] = $movefile['error'];
         $res["status"] = 0;
         wp_send_json( $res );
       }
      }
      $uploadedfile = '' ;
      if(isset($requested_vars[ 'fileurl' ]) && !empty($requested_vars[ 'fileurl' ]) ){
         $uploadedfile = $requested_vars[ 'fileurl' ];   
      }elseif(isset($movefile[ 'url' ]) && !empty($movefile[ 'url' ])){
         $uploadedfile = $movefile[ 'url' ];
      }elseif(isset($movefile[ 'file' ]) && !empty($movefile[ 'file' ])){
         $uploadedfile = $movefile[ 'file' ];
      }
     
      $menuname = sanitize_text_field($requested_vars['dspmenuname']);
            
      $curntMenuPos = ! empty( $requested_vars['curntmenupos'] ) ? sanitize_text_field($requested_vars['curntmenupos']) : 0;
      $menuId = ! empty( $requested_vars['menuId'] ) ? sanitize_text_field($requested_vars['menuId']) : 0 ;
      $oldIds = $newIds = array();
      if(isset($requested_vars['oldIds']) && !empty(json_decode($requested_vars['oldIds'] )))
      {
         $oldIds = json_decode(sanitize_text_field($requested_vars['oldIds']));
      }
      if(isset($requested_vars['newIds']) && !empty(json_decode($requested_vars['newIds'] )))
      {
         $newIds = json_decode(sanitize_text_field($requested_vars['newIds']));
      }
       
      if($menuId == 0)
      {
       $menuexists = wp_get_nav_menu_object( $menuname );
      }
      else
      {
       $menuexists = '';
      }
     
      if( !$menuexists)
      {
       $content = json_decode($this->urlGetContents($uploadedfile));
       if(is_array($content) && !empty($content) && isset($content[$curntMenuPos]->post))
        {
          $nav_count = count($content);
          $temp_arr = $custom_post_meta = array();
          $post = $post_metas = '';
          if(isset($content[$curntMenuPos]->post))
          $post = $content[$curntMenuPos]->post;
          if(isset($content[$curntMenuPos]->post_metas))
          $post_metas = $content[$curntMenuPos]->post_metas;
          $old_pid = $post->ID;
          $post->ID = '';
          $custom_post_meta['menu-item-title'] = $post->post_title;
			 $custom_post_meta['menu-item-description'] = $post->description;
          $post = (array)$post;
          if($menuId == 0)
          $menuId = wp_create_nav_menu($menuname);
          $post_id = wp_insert_post( $post, true );
          array_push($oldIds,$old_pid);
          array_push($newIds,$post_id);
          if(is_numeric($post_id) && $post_id !==0)
          {
           if(is_object($post_metas) && !empty($post_metas))
           {
            foreach($post_metas as $key=>$val)
            {
             $pos = stripos($key, '_');
             if($pos === 0 && $key!= '_ubermenu_custom_item_type' && $key!= '_ubermenu_settings'){
              $custom_key = substr($key, 1);
             }
             else
             {
              $custom_key = $key;
             }
             if($key!= '_ubermenu_custom_item_type' && $key!= '_ubermenu_settings')
             {
              $custom_key = str_replace('_','-',$custom_key);
             }
             if(isset($val[0]))
             {
              if($custom_key == 'menu-item-classes')
              {				
               if(is_serialized($val[0]) && !empty(unserialize($val[0])))
               {
                  $temp = unserialize($val[0]);
                  if(is_array($temp) && !empty($temp))
                  {
                     $temp = implode(" ",$temp);
                  }else
                  {
                     $temp = $val[0];
                  }		
                  $custom_post_meta[$custom_key] = $temp;
               }
               else
               {
                  $custom_post_meta[$custom_key] = $val[0];
               }
              }
              elseif($custom_key == 'menu-item-menu-item-parent')
              {
               if($val[0] != ' ')
               {
                $old_post_ids = $oldIds;
                $new_post_ids = $newIds;
                if($val[0] != 0)
                {
                 $new_var = array_search($val[0],$old_post_ids);
                 $temp_arr[$post_id] = $new_post_ids[$new_var];
                 if(isset($temp_arr[$post_id]))
                 {
                 $custom_post_meta['menu-item-parent-id'] = $temp_arr[$post_id];
                 }
                }
                
               }
               else
               {
                $custom_post_meta['menu-item-parent-id'] = '';
               }
              }
              elseif($custom_key == '_ubermenu_custom_item_type')
              {
               update_post_meta( $post_id, '_ubermenu_custom_item_type', $val[0] );
              }
              elseif($custom_key == '_ubermenu_settings')
              {
               update_post_meta( $post_id, '_ubermenu_settings', unserialize($val[0]));
              }
              else
              {
               $custom_post_meta[$custom_key] = $val[0];
              }
             }
            }
           }
           $menu_update_status = wp_update_nav_menu_item($menuId, $post_id, $custom_post_meta);
           if($nav_count-1 > $curntMenuPos)
           {
            $nextMenuPos = $curntMenuPos + 1;
            $res['response'] = $nextMenuPos.' of '.$nav_count. DSPMENUS_IMPORTMSG4;
            $res['status'] = 1;
            $res['nextMenuPos'] = $nextMenuPos;
            $res['isContinue'] = 1;
            $res['menuId'] = $menuId;
            $res['oldIds'] = $oldIds;
            $res['newIds'] = $newIds;
            $res["fileurl"] = $uploadedfile;
           }
           else
           {
            $res['status'] = 1;
            $res['isContinue'] = 0;
            $res['response'] = DSPMENUS_IMPORTMSG5;
				$this->deleteJsonFile($uploadedfile);
           }
          }
           else
           {
            $res['status'] = 1;
            $res['response'] = DSPMENUS_IMPORTMSG3;
           }
        }
        else
        {
         $res['status'] = 0;
         $res['response'] = DSPMENUS_IMPORTMSG2;
        }
       
      }
      else
      {
       $res['status'] = 0;
       $res['response'] = DSPMENUS_IMPORTMSG1;
      }
      wp_send_json( $res );
   }
	
   /**
   *
   * returns the array of the json type 
   */
	public function customMimeTypes($mimeTypes){
		$new_mime_type = array('json'=>'application/json');
		return $new_mime_type;
	}

   /**
   *
   * @param $param accepts as the arguments
   * returns the upload directory   
   */
	public function menusExportImportDir( $param ){
		$mydir = '/menus-exportimport';
		$param['subdir'] = $mydir;
		$param['path'] = $param['basedir'] . $mydir;
		$param['url'] = $param['baseurl'] . $mydir;
		return $param;
	}
		
		
	/**
   *
   * return the content of a file 
   */
   public function urlGetContents ($url) {
      if(function_exists('file_get_contents')){
			$url_get_contents_data = file_get_contents($url);
				if(empty($url_get_contents_data))
				{
					$url_get_contents_data = file_get_contents(str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $url));
				}
      }elseif(function_exists('fopen') && function_exists('stream_get_contents')){
          $handle = fopen ($url, "r");
          $url_get_contents_data = stream_get_contents($handle);
      }elseif (function_exists('curl_exec')){
          $conn = curl_init($url);
          curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, true);
          curl_setopt($conn, CURLOPT_FRESH_CONNECT,  true);
          curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
          $url_get_contents_data = (curl_exec($conn));
          curl_close($conn);
      }else{
          $url_get_contents_data = false;
      }
      return $url_get_contents_data;
  }
  
   /*
	 *Assign JSON upload capability to the logged in user 
   */
   public function menusUnfilteredUpload( $caps, $cap )
   {
      if ($cap == 'unfiltered_upload') {
         $caps = array();
         $caps[] = $cap;
      }
      return $caps;
   }
   
   /*
    * Delete the upoaded json file after menu creation 
   */
   public function deleteJsonFile ( $url )
   {
      if($url != ''){
            $menu_name = wp_basename($url);
            $uploadsdir = wp_get_upload_dir();
            $menu_url = $uploadsdir['basedir'].'/menus-exportimport/'.$menu_name;
            wp_delete_file( $menu_url );
      }
   }

}//end of class
endif;
?>