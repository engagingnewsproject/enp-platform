<?php
/*
Plugin Name: Advanced Code Editor
Plugin URI: http://en.bainternet.info
Description: Enables syntax highlighting in the integrated themes and plugins source code editors with line numbers, AutoComplete and much more. Supports PHP, HTML, CSS and JS.
Version: 2.2.6
Author: BaInternet
Author URI: http://en.bainternet.info
*/
/*
    	* 	Copyright (C) 2011-2014  Ohad Raz
		*	http://en.bainternet.info
		*	admin@bainternet.info

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.
 
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename (__FILE__)) {
	die('Sorry, but you cannot access this page directly.');
}

if (!class_exists('advanced_code_editor')){
	/**
	* advanced_code_editor is the main class...
	* @author Ohad Raz 
	*/
	class advanced_code_editor{

		// Class Variables
		/**
		 * used as localiztion domain name
		 * @var string
		 */
		var $localization_domain = "baace";

		/**
		 * database table name
		 * @var string
		 */
		public $tablename = 'filemeta';

		/**
		 * plugin version 
		 * @var string
		 */
		public $version = '2.2.6';

		/**
		 * Class constarctor
		 */
		public function advanced_code_editor(){
			if( is_admin()){
				$this->tablename = 'filemeta';
				$this->version = '2.2.6';
				//create new file admin ajax
				add_action('wp_ajax_create_file', array($this,'ajax_create_file'));
				//delete file admin ajax
				add_action('wp_ajax_delete_file', array($this,'ajax_delete_file'));
				//create new directory admin ajax
				add_action('wp_ajax_create_directory', array($this,'ajax_create_directory'));
				//ajax settings save
				add_action('wp_ajax_ace_save_settings', array($this,'ajax_save_settings'));
				//ajax settings save
				add_action('wp_ajax_ace_settings_panel', array($this,'ajax_settings_panel'));
				//ajax commit file version
				add_action('wp_ajax_commit_file',array($this,'ajax_commit_file'));
				//ajax revert file version
				add_action('wp_ajax_revert_file',array($this,'ajax_revet_file'));
				//ajax delete file version
				add_action('wp_ajax_delete_version',array($this,'ajax_delete_file_version'));
				//ajax delete file version
				add_action('wp_ajax_delete_all_versions',array($this,'ajax_delete_all_file_version'));
				//ajax get file revisions
				add_action('wp_ajax_get_file_revisions',array($this,'ajax_get_file_revisions'));
				add_action('load-theme-editor.php', array($this,'add_scripts'));
				add_filter( 'admin_footer-theme-editor.php', array($this,'do_edit' ));
				add_action('load-plugin-editor.php', array($this,'add_scripts'));
				add_filter( 'admin_footer-plugin-editor.php', array($this,'do_edit' ));
				//Language Setup
				$locale = get_locale();
				load_plugin_textdomain( $this->localization_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
				$options = get_option('ace_options',$this->get_defaults());
				if (isset($options['use_file_tree'])){
					add_action('admin_footer-plugin-editor.php',array($this,'plugin_file_tree'));
					add_action('admin_footer-theme-editor.php',array($this,'theme_file_tree'));
				}
				$this->update_wpdb($this->tablename);
		    }
			
		    add_filter('init', array($this,'add_query_var_vars'));
			add_action('template_redirect', array($this,'admin_redirect_download_files'));
			add_filter( 'plugin_row_meta', array($this,'_my_plugin_links'), 10, 2 );
			
		}

		/**
		 * get_defaults
		 * @since 2.0
		 * @author Ohad Raz 
		 * @return array           
		 */
		public function get_defaults(){
			return array(
				'use_file_tree' => true,
				'matchBrackets' => true,
				'lineWrapping'  => false,
				'tabSize'       => 4,
				'indentUnit'    => 2
			);
		}

		/**
		 * Show Settings Panel
		 * @author Ohad Raz
		 * @since 2.0
		 * @access public
		 */
		public function ajax_settings_panel(){
			check_ajax_referer('ace_settings_panel');
			$options = get_option('ace_options',$this->get_defaults());
			?>
			<div class="ace_s_panel">
				<div class="ace_fields">
					<p>
						<label for="use_file_tree"><?php _e('Use File Tree','ace'); ?>
							<input type="checkbox" id="use_file_tree" name="use_file_tree" value="ckd" <?php echo isset($options['use_file_tree'])? 'checked="checked"':''; ?>>
						</label>
					</p>
					<p>
						<label for="lineWrapping"><?php _e('Use Line Wrapping','ace'); ?>
							<input type="checkbox" name="matchBrackets" id="lineWrapping" value="ckd" <?php echo isset($options['lineWrapping'])? 'checked="checked"':''; ?>><br />
							<small><?php _e("Whether CodeMirror should scroll or wrap for long lines. Defaults to false (scroll).",'ace');?></small>
						</label>
					</p>
					 
					<p>
						<label for="matchBrackets"><?php _e('Match Brackets','ace'); ?>
							<input type="checkbox" name="matchBrackets" id="matchBrackets" value="ckd" <?php echo isset($options['matchBrackets'])? 'checked="checked"':''; ?>><br />
							<small><?php _e("Determines whether brackets are matched whenever the cursor is moved next to a bracket.",'ace');?></small>
						</label>
					</p>
					<p>
						<label for="tabSize "><?php _e('Tab Size','ace'); ?>
							<input type="text" name="tabSize" id="tabSize" size="1" value="<?php echo isset($options['tabSize'])? $options['tabSize']: '4'; ?>"><br />
							<small><?php _e('The width of a tab character. Defaults to 4.','ace');?></small>
						</label>
					</p>
					<p>
						<label for="indentUnit "><?php _e('Indent Unit','ace'); ?>
							<input type="text" name="indentUnit" id="indentUnit" size="1" value="<?php echo isset($options['indentUnit'])? $options['indentUnit']: '2'; ?>"><br />
							<small><?php _e('How many spaces a block (whatever that means in the edited language) should be indented. The default is 2.','ace');?></small>
						</label>
					</p>
					<input type="hidden" id="save_options_nonce" name="save_options_nonce" value="<?php echo wp_create_nonce('save_options_nonce');?>">
				</div>
			</div>
			<?php
			die();
		}

		/**
		 * Save Settings Panel
		 * @author Ohad Raz
		 * @since 2.0
		 * @access public
		 */
		public function ajax_save_settings(){
			check_ajax_referer('save_options_nonce');
			$options = get_option('ace_options',$this->get_defaults());
			$def = $this->get_defaults();
			$options = array_merge($def,$options);
			foreach ((array)$options as $key => $value) {
				if (isset($_POST[$key]) && $_POST[$key] != "null"){
					if ($_POST[$key] == 'ckd')
						$options[$key] = true;
					else
						$options[$key] = intval($_POST[$key]);
				}else{
					unset($options[$key]);
				}
			}
			update_option('ace_options', $options);
			_e('Settings saved, make sure you refresh your browser to see the changes.','ace');
			die();
		}

		/**
		 * commit File version
		 * @author Ohad Raz
		 * @since 2.0
		 * @access public
		 */
		public function ajax_commit_file(){
			check_ajax_referer('ace_commit_file');
			$filename= esc_sql($_POST['filename']);
			$file_content = esc_sql($_POST['file_content']);
			$message = esc_sql($_POST['message']);
			$date = date("F j, Y, g:i a");
			$value = array(
				'date'=> $date,
				'message' => $message,
				'version' => $file_content
			);
			$result = $this->add_file_meta(1,$filename,$value,false);

	        if (!$result)
	        	_e('Error in commiting file','ace');
	        
			_e('File Version commited and Save!','ace');
			die();
		}

		/**
		 * Delete all File Versions
		 * @author Ohad Raz
		 * @since 2.0
		 * @access public
		 */
		public function ajax_delete_all_file_version(){
			check_ajax_referer('delete_all_versions');
			$this->delete_file_meta(1,$_POST['filename'],'',true);
			$this->ajax_get_file_revisions($_POST['filename']);
			die();
		}

		/**
		 * delete File version
		 * @author Ohad Raz
		 * @since 2.0
		 * @access public
		 */
		public function ajax_delete_file_version(){
			check_ajax_referer('delete_version');
			$res = delete_metadata_by_mid('file', intval($_POST['mid']));
			if (!$res)
				echo 'Error';
			else
				_e('File revision deleted!','ace');
			die();
		}

		/**
		 * revert File version
		 * @author Ohad Raz
		 * @since 2.0
		 * @access public
		 */
		public function ajax_revet_file(){
			check_ajax_referer('revert_file');
			$meta = get_metadata_by_mid('file', intval($_GET['mid']));
			if (!$meta){
				echo json_encode(array('error' => __('Error restoring file version','ace')));
				die();
			}
			$meta = $meta->meta_value;
			$m = __('Make sure to save changes if you want the restore to take place.','ace');
			echo json_encode(array('version' => stripslashes($meta['version']),'m' => $m));
			die();
		}
		
		

		/**
		 * get File versions
		 * @author Ohad Raz
		 * @since 2.0
		 * @access public
		 */
		public function ajax_get_file_revisions($filename = null){
			if ($filename === null){
				check_ajax_referer('get_file_revisions');
				$filename= $_POST['filename'];
			}
			$versions = $this->get_file_meta(1,$filename,false);
			if (isset($versions[$_POST['filename']])){
				$url = plugins_url()."/advanced-code-editor/";
				
				echo '<table border="1" width="95%"><tr><th>'.__('Date','ace').'</th><th>'.__('Message','ace').'</th><th>'.__('Actions','ace').'</th></tr>';
				foreach ((array)$versions as $i => $vs ) {
					if ($i == $_POST['filename']){
						foreach ((array)$vs as $v ) {
							$meta_id = $this->get_file_meta_id($i,$v);
							$v = maybe_unserialize($v);
							echo '<tr>
							<td>'.$v['date'].'</td>
							<td>'.$v['message'].'</td>
							<td><a class="rev_restore" data-mid="'.$meta_id.'" title="'.__('Restore this Version','ace').'"><img src="'.$url.'images/0jpaB.png"></a> <a class="rev_delete" data-mid="'.$meta_id.'" title="'.__("Delete this Version","ace").'"><img src="'.$url.'images/6rQZY.png"></a></td>
							</tr>';
						}
					}
				}
				echo '</table>';
				echo '<a class="deleta_all_meta" title="'.__('delete all saved versions','ace').'" data-filename="'.$_POST['filename'].'"><img src="'.$url.'images/dkgAf.png"></a><br />';
			}else{
				_e('No Revisions for this file found','ace');
			}
			die();
		}

		/**
		 * add plugins entry points to query vars
		 * @author Ohad Raz
		 * @since 1.9
		 * @access public
		 */
		public function add_query_var_vars() {
		    global $wp;
		    $wp->add_query_var('theme_download'); 			//download theme
			$wp->add_query_var('dn_file'); 		 			//download file name
			$wp->add_query_var('plugin_download'); 			//download plugin
			$wp->add_query_var('dnf'); 						//download plugin
			$wp->add_query_var('ttd');						//theme to download
		}

		/**
		 * admin_redirect_download_files handler
		 * @author Ohad   Raz
		 * @since 1.9
		 * @access public
		 * 
		 * @return void
		 */
		public function admin_redirect_download_files(){
			global $wp;
		    global $wp_query;
			//download theme
			 if (array_key_exists('theme_download', $wp->query_vars) && $wp->query_vars['theme_download'] == 'theme_download'){
		        $this->download_theme();
				die();
			}
			if (array_key_exists('plugin_download', $wp->query_vars) && $wp->query_vars['plugin_download'] != ''){
		        $this->download_plugin();
				die();
			}
			if (array_key_exists('dn_file', $wp->query_vars) && $wp->query_vars['dn_file'] != ''){
		        $this->download_file();
				die();
			}
			
		}

		/**
		 * zip and download plugin
		 * 
		 * @author Ohad Raz
		 * @since 1.9
		 * @access public
		 * 
		 * @return zip file
		 */
		public function download_plugin(){
			header('HTTP/1.1 200 OK');
			if ( !current_user_can('edit_plugins') )
					wp_die('<p>'.__('You do not have sufficient permissions to edit plugins for this site.').'</p>');

			$plugin = get_query_var('plugin_download');
			if(isset($plugin) && $plugin != ''){
				
				//Get the directory to zip
				$directory = WP_PLUGIN_DIR .'/'.$plugin;

				$zipname = date('Ymdhis') . '.zip';
				// create object
				$zip = $this->Zip($directory,$zipname,strtolower($plugin).'/');
				if ($zip === false){
					wp_die('<p>'.__('error ziping files.').'</p><script>alert("'.__('error ziping files.').'");</script>');
				}
				

				$file = $zipname;

				$fsize = filesize($file);

				

				header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
		    	header("Content-Disposition: attachment; filename=\"".$plugin . '.zip'."\"");
		    	header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				ob_clean();
				flush();
		    	readfile($file);
				unlink($file);
				exit;
			}
		}

		/**
		 * download_file current edited file
		 * 
		 * @author ohad raz
		 * @since 1.9
		 * @access public
		 * 
		 * @return file
		 */
		public function download_file(){
			header('HTTP/1.1 200 OK');
			$from = get_query_var('dnf');
			if (!isset ($from))
				wp_die('<p>'.__('You do not have sufficient permissions to Download this file.').'</p>');
			
			if ($from == 'theme'){
				if ( !current_user_can('edit_themes') )
					wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site.').'</p>');
			}elseif ($from == 'plugin') {
				if ( !current_user_can('edit_plugins') )
					wp_die('<p>'.__('You do not have sufficient permissions to edit Plugins for this site.').'</p>');
			}else{
				wp_die('<p>'.__('You do not have sufficient permissions to edit files.').'</p>');
			}
			$file = get_query_var('dn_file');			
			$file = isset($file)? $file : (isset($_REQUEST['dn_file'])? $_REQUEST['dn_file'] : false);
			if (!isset($file)){
				wp_die('<p>'.__('Error Downloading file.').'</p>');	
			}
			if ($from == 'plugin'){
				$file = WP_PLUGIN_DIR .'/'.$file;
			}else{
				$t = wp_get_theme($_REQUEST['tmf']);
				if ( $t->exists() ){
					$file = $t->get_stylesheet_directory(). '/' . $file; 
				}else{
					wp_die('<p>'.__('Error Downloading file.').'</p>');	
				}
			}
			
			if(file_exists($file)){
				$content = file_get_contents($file);
				$filename = explode("/","/" . $file);
				$fsize = strlen($content);

				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header('Content-Description: File Transfer');
				header("Content-Disposition: attachment; filename=" . $filename[count($filename)-1]);
				header("Content-Length: ".$fsize);
				header("Expires: 0");
				header("Pragma: public");
				echo $content;
				exit;
			}else{
				wp_die('<p>'.__('Error Downloading file.').'</p>');
			}

		}

		/**
		 * Zip file maker
		 * 
		 * @author Ohad Raz
		 * @since 1.9
		 * @access public
		 * 
		 * @param string $source           file or directory to zip
		 * @param string $destination      zip file to create
		 * @param string $container_folder if you want to put the files inside a directory in the zip then pass it here
		 */
		public function Zip($source, $destination,$container_folder = ''){
		    if (!extension_loaded('zip')){
		    	wp_die('<p>'.__('error ziping files.').'</p><script>alert("'.__('error ziping files. zip extention not loaded').'");</script>');
		    	exit;
		    }
		    if ( !file_exists($source) ) {
		    	wp_die('<p>'.__('error ziping files.').'</p><script>alert("'.__('error ziping files. zip source file is not found').'");</script>');
		    	exit;
		    }
		    if ( ! class_exists('ZipArchive')){
		    	wp_die('<p>'.__('error ziping files.').'</p><script>alert("'.__('error ziping files. ZipArchive is not loaded').'");</script>');
		    	exit;
		    }

		    $zip = new ZipArchive();
		    if (!$zip->open($destination, ZIPARCHIVE::CREATE)){
		    	wp_die('<p>'.__('error ziping files.').'</p><script>alert("'.__('error ziping files. ZipArchive Create Error').'");</script>');
		    	exit;
		    }

		    $source = str_replace('\\', '/', realpath($source));
		    
		    
		    if (is_dir($source) === true){
		        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
		        foreach ($files as $file => $ob){
		            $file = str_replace('\\', '/', realpath($file));
		            if (is_dir($file) === true){
		                $zip->addEmptyDir(str_replace($source . '/', $container_folder, $file . '/'));
		            }else if (is_file($file) === true){
		                $zip->addFromString(str_replace($source . '/', $container_folder, $file), file_get_contents($file));
		            }
		           //echo $file .'<br />';
		        }
		       //die();
		    }else if (is_file($source) === true){
		        $zip->addFromString(basename($source), file_get_contents($source));
		    }else{
		    	wp_die('<p>'.__('error ziping files.').'</p><script>alert("'.__('error ziping files. ZipArchive Create Error').'");</script>');
		    	exit;
		    	return false;
		    }
		    
		    return $zip->close();
		}


		/**
		 * zip and download theme
		 * @author Ohad   Raz
		 * @since 1.9
		 * @access public
		 * 
		 * @return zip file on success and string on faliure 
		 */
		public function download_theme(){
			header('HTTP/1.1 200 OK');
			if ( !current_user_can('edit_themes') )
				wp_die('<p>'.__('error ziping files.').'</p><script>alert("'.__('You do not have sufficient permissions to edit templates for this site.').'");</script>');
			

			if(!isset($_GET['ttd'])){
				$t = wp_get_theme();
			}else{
				//$theme = $_GET['ttd'];
				$t = wp_get_theme($_GET['ttd']);
				
			}
			if ( $t->exists() ){
					$directory = $t->get_stylesheet_directory(). '/';
			}else{
				wp_die('<p>'.__('error ziping files.(1)').'</p><script>alert("'.__('error ziping files.').'");</script>');
			}
			
			
			//Get the directory to zip
			

			$zipname = date('Ymdhis') . '.zip';
			// create object
			$zip = $this->Zip($directory,$zipname,strtolower($t->Name).'/');
			if ($zip === false){
				wp_die('<p>'.__('error ziping files.').'</p><script>alert("'.__('error ziping files.').'");</script>');
			}
			
			

			$file = $zipname;
			$fsize = filesize($file);

			header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
	    	header("Content-Disposition: attachment; filename=\"".strtolower($t->Name) . '.zip'."\"");
	    	header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			ob_clean();
			flush();
	    	readfile($file);
			unlink($file);
			exit;
		}

		/**
		 * is_version
		 * @author Ohad   Raz
		 * @since 2.1
		 * @param  string  $version 
		 * @return boolean          
		 */
		public function is_version( $version = '3.1' ) {
	        global $wp_version;
	        return ($wp_version >= $version) ;
	    }

		//ajax create directory
		/**
		 * function to handle ajax new directory creation
		 */
		function ajax_create_directory(){
			check_ajax_referer('create_directory');
			global $current_user;
			get_currentuserinfo();
			if (isset($_POST['di_name']) && isset($_POST['dir'])){
				if (current_user_can('manage_options')){
					$dir_name = '';
					$new_dir_name = strtolower( str_replace(' ', '-', $_POST['di_name']));
					if (isset($_POST['f_type'])){
						if ($_POST['f_type'] == "plugin" ){
							$dir_name = WP_PLUGIN_DIR . '/' . $_POST['dir'] . '/' . $new_dir_name;
						}elseif ($_POST['f_type'] == "theme" ){
							$t = wp_get_theme($_POST['dir']);
							if ( $t->exists() ){
								$dir_name = $t->get_stylesheet_directory(). '/' . $new_dir_name;; 
							}
						}
						
						//if(!is_dir($dir_name)){
							//echo __("Cannot create directory  Error code 9<br />".$dir_name,"baace");
						//}else{
							$umask = umask(0);
							if (@mkdir($dir_name, 0777)){
								echo __("New directory Created!!!","baace");
							}else{
								echo __("Cannot create directory Error code 8<br />".$dir_name,"baace");
							}
							umask($umask);
						//}
					}else{
						echo __('Error Code 7','baace');
					}
				}else{
					echo __('Error Code 5','baace');
				}
			}else{
				echo __('Error Code 6','baace');
			}
			die();
		}

		//ajax delete file
		/**
		 * function to handle ajax delete file
		 */
		function ajax_delete_file(){
			check_ajax_referer('delete_file');
			global $current_user;
			get_currentuserinfo();
			if(isset($_POST['F_T_D']) && $_POST['F_T_D'] != '' && isset($_POST['f_type'])){
				$f_name = '';
				if($_POST['f_type'] == "plugin" ){
					$f_name = WP_PLUGIN_DIR . '/' .$_POST['F_T_D'];
				}else{
					$f_name = $_POST['F_T_D'];
				}
					@unlink($f_name);
					echo __('File Deleted!!!','baace');
					die();
			}else{
				echo __('Error Code 4','baace');
				die();
			}
		}

		//ajax create file
		/**
		 * function to handle ajax file creation
		 */
		function ajax_create_file(){
			check_ajax_referer('create_new_file');
			global $current_user;
			get_currentuserinfo();
			if(isset($_POST)){
			$checks = false;
			$file_name = '';
				if (isset($_POST['file_name']) && $_POST['file_name'] != ''){
					if (isset($_POST['f_type']) && isset($_POST['dir'])){
						$f_name = strtolower( str_replace(' ', '-', $_POST['file_name']));
						if($_POST['f_type'] == "plugin" ){
							if (current_user_can( 'edit_plugins' )){
								$checks = true;
								$file_name = WP_PLUGIN_DIR . '/' . $_POST['dir'] . '/' . $f_name;
							}
						}elseif( $_POST['f_type'] == "theme" ){
							if (current_user_can( 'edit_themes' )){
								$checks = true;
								$t = wp_get_theme($_POST['dir']);
								if ( $t->exists() ){
									$file_name = $t->get_stylesheet_directory(). '/' . $f_name;; 
								}
							}
						}else{
							echo __('Error Code 3','baace');
							die();
						}
					}else{
						echo __('Error Code 2','baace');
						die();
					}
					if ($checks){
						
						if(file_exists( $file_name)){
							echo __("File already exists","baace");
							die();
						}else{
							$handle = fopen($file_name, 'w') or wp_die('Cannot open file for editing');
							
							$file_contents = '';
							fwrite($handle, $file_contents);
							fclose($handle);
							echo __('New File Created!','baace');
							die();
						}
					}
				}else{
					echo __('you must set a file name','baace');
				}
			}else{
				echo __('Error Code 1','baace');
				die();
			}
			die();
		}

		/**
		 * function to include jQuery form plugin for ajax save ...
		 */
		function add_scripts(){
			
			$url = plugins_url()."/advanced-code-editor/";
			$v = $this->version;
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui' );
		    wp_enqueue_script( 'jquery-form' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			
			wp_enqueue_script('codemirror',$url.'js/codemirror.js',array(),$v,true);
			wp_enqueue_script('codemirror-fold',$url.'js/foldcode.js',array(),$v,true);
			wp_enqueue_script('codemirror-format',$url.'js/formatting.js',array(),$v,true);
			wp_enqueue_script('codemirror-xml',$url.'js/xml.js',array(),$v,true);
			wp_enqueue_script('codemirror-js',$url.'js/javascript.js',array(),$v,true);
			wp_enqueue_script('codemirror-css',$url.'js/css.js',array(),$v,true);
			wp_enqueue_script('codemirror-php',$url.'js/php.js',array(),$v,true);
			wp_enqueue_script('codemirror-clike',$url.'js/clike.js',array(),$v,true);
			wp_enqueue_script('codemirror-search',$url.'js/searchcursor.js',array(),$v,true);
			wp_enqueue_script('codemirror-complete',$url.'js/complete.js',array(),$v,true);
			
			$def = $this->get_defaults();
			$options = get_option('ace_options',array());
			$options =  array_merge((array)$def,(array)$options);
			wp_localize_script('codemirror-complete', 'ace_user', $options);
			$strings = array(
				'imgURL'         => $url . 'images/',
				'url'            => get_bloginfo('url'),
				'unsaved'        => __(' The Editor Contains unsaved changes','baace'),
				'search'         => __('Search','baace'),
				'replace'        => __('Replace','baace'),
				'jump'           => __('Jump To Line','baace'),
				'fullscreen'     => __('Full Screen Editor','baace'),
				'savechanges'    => __('Save Changes','baace'),
				'commentout'     => __('Comment Out','baace'),
				'uncomment'      => __('UnComment','baace'),
				'autof'          => __('Auto Format','baace'),
				'changetheme'    => __('Change editor theme:','baace'),
				'newfile'        => __('Create New File','baace'),
				'deletefile'     => __('Delete Current File','baace'),
				'newdir'         => __('Create New Directory','baace'),
				'tooglefiletree' => __('Toggle File Tree','baace'),
				'editorsettings' => __('Editor Settings','baace'),
				'commitv'        => __('Commit Version','baace'),
				'restorev'       => __('Restore Version','baace'),
				'help'           => __('Help','baace'),
				'about'          => __('About','baace'),
				'searchboxtitle' => __('Search And Replace Box','baace'),
				'searchbtitle'   => __('Search Box','baace'),
				'aboutboxtitle'  => __('About WordPress Advanced Code Editor','baace'),
				'jumpbox'        => __('Jump to Line','baace'),
				'jump'           => __('Jump','baace'),
				'save'           => __('save','baace'),
				'settingsbox'    => __('Advanced Code Editor Settings','baace'),
				'cancel'         => __('Cancel','baace'),
				'dirname'        => __('Directory Name:','baace'),
				'createDir'      => __('Create Directory','baace'),
				'create'         => __('Create','baace'),
				'newfilename'    => __('File Name:','baace'),
				'areyousure'     => __('are you sure you want to delete this file: ','baace'),
				'no'             => __('No','baace'),
				'yesimsure'      => __('Yes I am sure!','baace'),
				'close'          => __('Close', 'baace'),
				'filerevesiobox' => __('Advanced Code Editor File vesrsions','baace'),
				'commit'         => __('Commit','baace'),
				'commitm'        => __('Commit Message','baace'),
				'saveBox'        => __('Save Box','baace'),
				'autoclosein'    => __('this Box will auto close in','baace'),
				'seconds'        => __('seconds','baace'),
				'saving'         => __('Saving Changes', 'baace'),
				'creatingfile'   => __('Creating New File','baace'),
				'creatingdir'    => __('Creating New Directory','baace'),
				'deletingfile'   => __('Deleting File','baace'),
				'lastsaved'      => __('File last Saved at: ','baace'),
				'noChangesyet'   => __('No Changes made yet.', 'baace'),
				'downloadfile'   => __('Download file','baace'),
				'downloadtheme'  => __('Download theme','baace'),
				'downloadplugin' => __('Download Plugin','baace')
			);
			wp_localize_script('codemirror-complete', 'ace_strings', $strings);
			//nonces object
			$nonces = array(
				'ace_settings_panel' => wp_create_nonce( 'ace_settings_panel' ),
				'revert_file'        => wp_create_nonce('revert_file'),
				'delete_version'     => wp_create_nonce('delete_version'),
				'delete_all'         => wp_create_nonce('delete_all_versions'),
				'ace_commit_file'    => wp_create_nonce('ace_commit_file'),
				'get_file_revisions' => wp_create_nonce('get_file_revisions'),
				'delete_file'        => wp_create_nonce( 'delete_file' ),
				'create_directory'   => wp_create_nonce('create_directory'),
				'create_new_file'    => wp_create_nonce('create_new_file'),
			);
			wp_localize_script('codemirror-complete', 'ace_nonce', $nonces);
			wp_enqueue_script('codemirror-baace',$url.'js/baace.js',array(),$v,true);
			//@todo: add tabbed editor
			//wp_enqueue_script('jquery-ui-tabs');
			
			
			//stylesheets
			wp_enqueue_style('jquery-ui', $url.'css/jquery-ui.css', false, false);
			wp_enqueue_style('codemirror', $url.'css/codemirror.css', false, false);
			
			wp_enqueue_style('codemirror-def', $url.'themes/default.css', false, false);
			wp_enqueue_style('codemirror-night', $url.'themes/night.css', false, false);
			wp_enqueue_style('codemirror-elegant', $url.'themes/elegant.css', false, false);
			wp_enqueue_style('codemirror-neat', $url.'themes/neat.css', false, false);
			wp_enqueue_style('codemirror-raverStudio', $url.'themes/raverStudio.css', false, false);
			wp_enqueue_style('codemirror-cobalt', $url.'themes/cobalt.css', false, false);
			wp_enqueue_style('codemirror-eclipse', $url.'themes/eclipse.css', false, false);
			wp_enqueue_style('codemirror-monokai', $url.'themes/monokai.css', false, false);
			wp_enqueue_style('codemirror-rubyb', $url.'themes/rubyblue.css', false, false);
			wp_enqueue_style('codemirror-solarizedDark', $url.'themes/solarizedDark.css', false, false);
			wp_enqueue_style('codemirror-solarizedLight', $url.'themes/solarizedLight.css', false, false);
			
			
		}
	
		/**
		 * This is the money function that adds the editor and all of the feature to it in the editor page.
		 */
		function do_edit(){
			$url = plugins_url()."/advanced-code-editor/"; 
			   /*style todo move to external file*/
		   ?>
			<style>
			
			.ace_tool_bar{list-style: none;}
			.ace_tool_bar li{cursor: pointer;}
			.completions {position: absolute;z-index: 10;overflow: hidden;-webkit-box-shadow: 2px 3px 5px rgba(0,0,0,.2);-moz-box-shadow: 2px 3px 5px rgba(0,0,0,.2);box-shadow: 2px 3px 5px rgba(0,0,0,.2);}
			.completions select {background: #fafafa;outline: none;border: none;padding: 0;margin: 0;font-family: monospace;}
			.CodeMirror {border: 1px solid #eee; overflow-y: hidden;overflow-x: auto;}
			.CodeMirror-scroll {overflow: auto;} 
			.ace_file_status{width: 45%;}
			 <?php if (!is_rtl()){?>

			.fullscreen{background-color: #FFFFFF;height: 89%;left: 0;position: fixed;top: 80px;width: 100%;z-index: 100;}
			.ace_ToolBar{background-color: #FFFFFF;left: 0;min-height: 85px;position: fixed;top: 0;width: 100%;z-index: 100;}
			#template div {margin-right: 0;}

			/*toolbar*/
			
			.ace_tool_bar li{float: left; }
		    .clean_ace{clear:left;}

			<?php }else{ ?>
			#template div {margin-left: 0px;}
			.fullscreen{background-color: #FFFFFF;height: 89%;right: 0;position: fixed;top: 80px;width: 100%;z-index: 100;}
			.ace_ToolBar{background-color: #FFFFFF;right: 0;min-height: 85px;position: fixed;top: 0;width: 100%;z-index: 100;}
			.ace_tool_bar li{float: right; }
			.clean_ace{clear:right;}
			.CodeMirror{direction: ltr;}
			.completions{direction: ltr;}
			  <?php } ?>
			</style>
			<?php /* TODO: move scripts to external file*/ ?>
			<script>
				var lastPos = null, lastQuery = null, marked = [];
				//store original file list
				var templateside = '';
				templateside = jQuery("#templateside").html();
				
				jQuery(document).ready(function($) {
				    // ajax save attach handler to form's submit event 
					$('#template').submit(function(){
						  var options = { 
							  beforeSubmit:  BeforeSave,
							  success:    showResponse 
						  };
						  $(this).ajaxSubmit(options); 
						  // return false to prevent normal browser submit and page navigation 
						  return false; 
					});
					//add toolbar
					jQuery("#newcontent").after("<div class=\"ace\"><h3><?php _e('Advanced Code Editor','baace');?></h3><div class=\"s_r\"></div></div><div class=\"clean_ace\"></div>");
					jQuery('.s_r').append('<ul class=\"ace_tool_bar\"></ul>');
					var toolbar = jQuery('.ace_tool_bar');
					

				   	//Toolbar buttons
					//search
					addToolbarButton(null,"tb_se","ace_tool_s",ace_strings.search,"z4Ulb.png","Search",function() {
						show_dialog("#search",{ focus: function(event, ui){jQuery('#query').focus(); }, title: ace_strings.searchbtitle });
					});
					//search and replace
					addToolbarButton(null,"tb_re","ace_tool_sr",ace_strings.replace,"1smMk.png","Replace",function() {
						show_dialog("#searchR",{ title: ace_strings.searchboxtitle });
					});
					//jump to line
					addToolbarButton(null,"tb_re","ace_tool_jmp",ace_strings.jump,"rmic5.png","Jump To Line",function() {
						show_dialog("#jump_tbox",{ focus: function(event, ui){jQuery('#jump_line_number').focus(); }, title: ace_strings.jumpbox, buttons: 
						[{text: ace_strings.jump,click: function() { jQuery(this).dialog("close"); Jump_to_Line(); },}]});
					});
					//toggle full screen
					addToolbarButton(null,"tb_re","ace_tool_full",ace_strings.fullscreen,"6NDPx.png","Full Screen Editor",function() {
						toggleFullscreenEditing();
					});
					//save button
					addToolbarButton(null,"tb_re","ace_tool_save",ace_strings.savechanges,"suvnt.png","Save Changes");
					//comment current selection
					addToolbarButton(null,"tb_re","ace_tool_comment",ace_strings.commentout,"94deB.png","Comment Out",function() {
						commentSelection(true);
					});
					//uncomment current selection
					addToolbarButton(null,"tb_re","ace_tool_uncomment",ace_strings.uncomment,"UtMCm.png","UnComment",function() {
						commentSelection(false);
					});
					//auto Format
					addToolbarButton(null,"tb_re","ace_tool_af",ace_strings.autof,"qTU1o.png","Auto Format",function() {
						autoFormatSelection();
					});
					//change theme
					addToolbarButton('<li>'+ace_strings.changetheme+'<select id=\"editortheme\" onchange=\"selectTheme(this.value)\"></select></li>');
					//new file
					addToolbarButton(null,"tb_re","ace_tool_new_file",ace_strings.newfile,"ZjkC3.png","Create New File",function(){
						showNewFileDialog();
					});
					//delete file
					addToolbarButton(null,"tb_re","ace_tool_delete",ace_strings.deletefile,"3b5nW.png","Delete Current File",function(){
						showDeleteFileDialog();
					});
					//new directory
					addToolbarButton(null,"tb_re","ace_tool_new_d",ace_strings.newdir,"iAW16.png","Create New Directory",function(){
						showNewDirDialog();
					});
					//toggle file tree 
					addToolbarButton(null,"tb_re","ace_tool_ftree",ace_strings.tooglefiletree,"ADRSB.png","Toggle File Tree",function() {
						toggle_file_list_tree();
					});
					//settings panel
					addToolbarButton(null,"tb_re","ace_tool_set_panel",ace_strings.editorsettings,"zikHH.png","Editor Settings",function(){
						showSettingsPanel();
					});
					//Commit version
					addToolbarButton(null,"tb_re","ace_tool_set_commit",ace_strings.commitv,"hpJok.png","Commit Version",function() {
						commit_file_version();
					});
					//Restore version
					addToolbarButton(null,"tb_re","ace_tool_set_restore",ace_strings.restorev,"0jpaB.png","Restore Version",function() {
						load_version_list();
					});
					//help
					addToolbarButton(null,"tb_re","ace_tool_help",ace_strings.help,"Y1xXZ.png","Help",function() {
						show_dialog("#ace_help",{title: ace_strings.help });
					});
					//about
					addToolbarButton(null,"tb_re","ace_tool_about",ace_strings.about,"Wwa3Z.png","About",function() {
						show_dialog("#ace_about",{title: ace_strings.aboutboxtitle,width: 380 });
					});
					//hidden iframe for downloads
					addToolbarButton('<iframe id="dframe" width="0" height="0" src=""></iframe>');

					//file status container
					jQuery('.s_r').after('<br /><div class="ace_file_status">'+ace_strings.noChangesyet+'</div>');
				
				 	//set theme changer
				 
					var theme_coo = readCookie('adce_theme');
					var tedi = jQuery('#editortheme');
					if (theme_coo) {
					   var theme_names = ["default", "night", "neat", "elegant", "raverStudio", "cobalt", "eclipse", "monokai", "rubyblue","solarizedLight", "solarizedDark"];
					   for(var i in theme_names){
						  if (theme_names[i] == theme_coo){
						 tedi.append('<option selected=\"selected\">'+theme_names[i]+'</option>');
						  }else{
						 tedi.append('<option>'+theme_names[i]+'</option>');	       
						  }
					   }
					}else{
					   tedi.append('<option selected=\"selected\">default</option>');
					   tedi.append('<option>night</option>');
					   tedi.append('<option>neat</option>');
					   tedi.append('<option>elegant</option>');
					   tedi.append('<option>raverStudio</option>');
					   tedi.append('<option>cobalt</option>');
					   tedi.append('<option>eclipse</option>');
					   tedi.append('<option>monokai</option>');
					   tedi.append('<option>rubyblue</option>');
					   tedi.append('<option>solarizedDark</option>');
					   tedi.append('<option>solarizedLight</option>');
					}
					

					//tool Bar others
					//save toolbar button
					jQuery('#ace_tool_save').live('click', function() {jQuery('#submit').click();});
					
					//settings panel
					function showSettingsPanel() {
						aceAJAX({action: 'ace_settings_panel',_ajax_nonce: ace_nonce.ace_settings_panel }, function(r) {
							jQuery('#update_Box').html('<div>' + r + '</div>');
							show_dialog("#update_Box",{ title: ace_strings.settingsbox, buttons: [
								{
									text: ace_strings.save,
									click: function() { Save_settings(); }
								},
								{
									text: ace_strings.cancel,
									click: function() { jQuery(this).dialog("close"); }
								},
							] }); 
						});
					}

					//new directory
					function showNewDirDialog() {
						jQuery("#add_new_file").html('<form action="" method="POST" id="new_d_create"><p>'+ace_strings.dirname+' <input type="text" id="di_name" name="di_name" value=""><br /></p></form>');
						show_dialog("#add_new_file",{ title: ace_strings.createDir, buttons: [
							{
								text: ace_strings.cancel,
								click: function() { jQuery(this).dialog("close"); },
							},
							{
								text: ace_strings.create,
								click: function() { ajax_create_directory(jQuery('#di_name').val()); }
							}
							] });
					}

					//new file toolbar
					function showNewFileDialog() {
						jQuery("#add_new_file").html('<form action="" method="POST" id="new_F_create"><p> '+ace_strings.newfilename+' <input type="text" id="fi_name" name="fi_name" value=""></p></form>');
						show_dialog("#add_new_file",{ title: ace_strings.newfile , buttons: [
							{
								text: ace_strings.cancel,
								click: function() { jQuery(this).dialog("close"); },
							},
							{
								text: ace_strings.create,
								click: function() { create_new_file_callback(); }
							}
							] });
					}

					//delete file toolbar
					function showDeleteFileDialog() {
						var f_type1 = '';
						if (jQuery('input[name="plugin"]').length){
							file_to_delete = jQuery('input[name="plugin"]').val();
							f_type1 = 'plugin';
						}else{
						//theme file
							file_to_delete = jQuery('input[name="file"]').val();
							f_type1 = 'theme';
						}
						jQuery("#add_new_file").html('<p>'+ ace_strings.areyousure+' ' + file_to_delete+'</p>');
						show_dialog("#add_new_file",{title: ace_strings.deletefile, buttons: [
							{
								text: ace_strings.no,
								click: function() { jQuery(this).dialog("close"); },
							},
							{
								text: ace_strings.yesimsure,
								click: function() { ajax_delete_file(file_to_delete,f_type1); }
							}
							] }); 
					}
					
					//set focus on search
					jQuery( "#search" ).bind( "dialogopen", function(event, ui) {
					     jQuery('#query').focus(); 
					});
					//serch on enter key down 
					jQuery('#query').live('keydown',function(e) {
					    
					    if(e.keyCode == 13) {
					       e.preventDefault();
						jQuery("#ace_se").click();
					    }
					});	
				});

			//ajax roll back file
			jQuery('.rev_restore').live('click', function() {
				if (jQuery('input[name="theme"]').length)
					dir = jQuery('input[name="theme"]').val() + "-" + get_file_name;
				else
					dir = get_file_name;
				var data = {
					action: 'revert_file',
					_ajax_nonce: ace_nonce.revert_file,
					mid: jQuery(this).attr('data-mid'),
					filename: dir,
					anti_cache: new Date().getTime()
				};
				jQuery.getJSON(ajaxurl, data, function(res) {
					if (res){
						if (res.error){
							jQuery('#update_Box').html('<div>' + res.error + '</div>');
							show_dialog("#update_Box",{ width: 350, title: ace_strings.filerevesiobox, buttons: [
								{
									text: ace_strings.close,
									click: function() { jQuery('#update_Box').html(''); jQuery('#update_Box').dialog("close");}
								},
							] });	
						}

						if (res.version){
							editor.setValue(res.version);
							editor.refresh();
							jQuery('#update_Box').html('<div>' + res.m + '</div>');
							show_dialog("#update_Box",{ show: 'slide',hide: 'slide', width: 350, title: ace_strings.filerevesiobox, buttons: [
								{
									text: ace_strings.close,
									click: function() { jQuery('#update_Box').html(''); jQuery('#update_Box').dialog("close");}
								},
							] }); 
						}
					}
				});
			});
							
			//delete file version
			jQuery('.rev_delete').live('click', function() {
				if (jQuery('input[name="theme"]').length)
					dir = jQuery('input[name="theme"]').val() + "-" + get_file_name;
				else
					dir = get_file_name;
				var data = {
					action: 'delete_version',
					_ajax_nonce: ace_nonce.delete_version,
					mid: jQuery(this).attr('data-mid'),
					filename: dir,
					anti_cache: new Date().getTime()

				};
				aceAJAX(data, function(response) {
					jQuery('#update_Box').html('<div>' + response + '</div>');
					show_dialog("#update_Box",{ width: 400, title: ace_strings.filerevesiobox, buttons: [
						{
							text: ace_strings.close,
							click: function() { jQuery('#update_Box').html(''); jQuery('#update_Box').dialog("close");}
						},
					] }); 
				});
			});				

			//delete all file versions
			jQuery('.deleta_all_meta').live('click', function() {
				if (jQuery('input[name="theme"]').length)
					dir = jQuery('input[name="theme"]').val() + "-" + get_file_name;
				else
					dir = get_file_name;
				var data = {
					action: 'delete_all_versions',
					_ajax_nonce: ace_nonce.delete_all,
					filename: dir,
					anti_cache: new Date().getTime()
				};
				aceAJAX(data, function(response) {
					jQuery('#update_Box').html('<div>' + response + '</div>');
					show_dialog("#update_Box",{ width: 400, title: ace_strings.filerevesiobox, buttons: [
						{
							text: ace_strings.close,
							click: function() { jQuery('#update_Box').html(''); jQuery('#update_Box').dialog("close");}
						},
					] }); 
				});
			});				
			
			//ajax commit version
			function commit_file_version(){
				jQuery("#add_new_file").html('<div class="ace_commit_version"><div class="commit_filds"><p><label for="commit_message">' + ace_strings.commitm + '<br/><textarea id="commit_message" name="commit_message"></textarea></label></p></div></div>');
				show_dialog("#add_new_file",{title: ace_strings.commitv, buttons: [
					{
						text: ace_strings.cancel,
						click: function() { jQuery(this).dialog("close"); },
					},
					{
						text: ace_strings.commit,
						click: function() { jQuery(this).dialog('close'); do_file_commit(); }
					}
					] 
				});
			}

			//actuall ajax file commit call
			function do_file_commit(){
				if (jQuery('input[name="theme"]').length)
					dir = jQuery('input[name="theme"]').val() + "-" + get_file_name;
				else
					dir = get_file_name;
				var data = {
					action: 'commit_file',
					_ajax_nonce: ace_nonce.ace_commit_file,
					filename: dir,
					message: jQuery("#commit_message").val(),
					file_content: editor.getValue(),
					anti_cache: new Date().getTime()

				};
				aceAJAX(data, function(response) {
					jQuery('#update_Box').html('<div>' + response + '</div>');
					show_dialog("#update_Box",{ title: ace_strings.filerevesiobox, buttons: [
						{
							text: ace_strings.close,
							click: function() { jQuery('#update_Box').html(''); jQuery('#update_Box').dialog("close");}
						},
					] }); 
				});
			}

			//get version list
			function load_version_list(){
				if (jQuery('input[name="theme"]').length)
					dir = jQuery('input[name="theme"]').val() + "-" + get_file_name;
				else
					dir = get_file_name;
				var data = {
					action: 'get_file_revisions',
					_ajax_nonce: ace_nonce.get_file_revisions,
					filename: dir,
					anti_cache: new Date().getTime()
				};
				aceAJAX(data, function(response) {
					jQuery('#update_Box').html('<div>' + response + '</div>');
					show_dialog("#update_Box",{minWidth: 400,  title: ace_strings.filerevesiobox, buttons: [
						{
							text: ace_strings.close,
							click: function() { jQuery('#update_Box').html(''); jQuery('#update_Box').dialog("close");}
						}
					] }); 
				});
			}

			//toggle file tree on off
			function toggle_file_list_tree(){
				var temp = jQuery("#templateside").html();
				jQuery("#templateside").html(templateside);
				templateside = temp;
			}

			//Javascript isset
			function isset(varname) {
				if(typeof( window[ varname ] ) != "undefined") return true;
				else return false;
			}

			//save settings
			function Save_settings(){
				var data = {
					action: 'ace_save_settings',
					_ajax_nonce: jQuery("#save_options_nonce").val(),
					use_file_tree: jQuery("#use_file_tree").is(":checked")? jQuery("#use_file_tree").val() : 'null',
					matchBrackets: jQuery("#matchBrackets").is(":checked")? jQuery("#matchBrackets").val() : 'null',
					lineWrapping: jQuery("#lineWrapping").is(":checked")? jQuery("#lineWrapping").val() : 'null',
					tabSize: jQuery("#tabSize").val(),
					indentUnit: jQuery("#indentUnit").val()
				};
				aceAJAX(data, function(response) {
					jQuery('#update_Box').html('<div>' + response + '</div>');
					show_dialog("#update_Box",{ title: ace_strings.settingsbox, buttons: [
						{
							text: ace_strings.close,
							click: function() { jQuery('#update_Box').html(''); jQuery('#update_Box').dialog("close");}
						},
					] }); 
				});
			}

			//delete file
			function ajax_delete_file(file_to_delete,f_type1){
				jQuery('#add_new_file').html('<p style="text-align:center;">'+ ace_strings.deletingfile +' ...<br/><img src="'+ace_strings.imgURL+'GRZ9W.gif"></p>');
				var data = {
					action: 'delete_file',
					f_type: f_type1,
					F_T_D: file_to_delete,
					_ajax_nonce: ace_nonce.delete_file
				};
				aceAJAX(data, function(response) {
					jQuery(".ui-dialog-content").dialog("close");
					jQuery('#add_new_file').dialog( "destroy" );
					jQuery('#update_Box').html('<div>' + response + '</div>');
					show_dialog("#update_Box",{title: ace_strings.deletefile, buttons: [
						{
							text: ace_strings.close,
							click: function() { jQuery(this).dialog("close"); }
						}
					] }); 
				});
			}

			//create new directory
			function ajax_create_directory(di_name){
				jQuery('#add_new_file').html('<p style="text-align:center;">'+ ace_strings.creatingdir +' ...<br/><img src="'+ace_strings.imgURL+'GRZ9W.gif"></p>');
				var plugin_meta = new Array();
				var f_type2 = '';
				//plugin file
				if (jQuery('input[name="plugin"]').length){
					plugin_meta = jQuery('input[name="plugin"]').val().split('/');
					var plugin_dir = plugin_meta[0];
					var dirs = plugin_meta.length - 1;
					for(i=1; i < dirs; i++) { 
						plugin_dir = plugin_dir + '/' + plugin_meta[i];
					}
					f_type2 = 'plugin';
				}else{
					//theme file
					plugin_dir = theme_to_download;
					f_type2 = 'theme';
				}

				var data = {
					action: 'create_directory',
					dir: plugin_dir,
					f_type: f_type2,
					di_name: di_name,
					_ajax_nonce: ace_nonce.create_directory
				};
				aceAJAX(data, function(response) {
					jQuery(".ui-dialog-content").dialog("close");
					jQuery('#add_new_file').dialog( "destroy" );
					jQuery('#update_Box').html('<div>' + response + '</div>');
					show_dialog("#update_Box",{title: ace_strings.newdir, buttons: [
						{
							text: ace_strings.close,
							click: function() { jQuery(this).dialog("close"); }
						}
					] }); 
				});
			}
			//create new file
			function create_new_file_callback(){
				var file_name = jQuery("#fi_name").val();
				jQuery('#add_new_file').html('<p style="text-align:center;">'+ ace_strings.creatingfile +' ...<br/><img src="'+ace_strings.imgURL+'GRZ9W.gif"></p>');
				var plugin_meta = new Array();
				//plugin file
				var f_type = '';
				if (jQuery('input[name="plugin"]').length){
					plugin_meta = jQuery('input[name="plugin"]').val().split('/');
					var plugin_dir = plugin_meta[0];
					var dirs = plugin_meta.length - 1;
					for(i=1; i < dirs; i++) { 
						plugin_dir = plugin_dir + '/' + plugin_meta[i];
					}
					f_type = 'plugin';
				}else{
					//theme file
					plugin_dir = theme_to_download;
					f_type = 'theme';
				}

				var data = {
					action: 'create_file',
					dir: plugin_dir,
					f_type: f_type,
					file_name: file_name,
					_ajax_nonce: ace_nonce.create_new_file
				};
				aceAJAX(data, function(response) {
					jQuery(".ui-dialog-content").dialog("close");
					jQuery('#add_new_file').dialog( "destroy" );
					jQuery('#update_Box').html('<div>' + response + '</div>');
					show_dialog("#update_Box",{  title: ace_strings.newfile, buttons: [
						{
							text: ace_strings.close,
							click: function() { jQuery(this).dialog("close"); }
						}
					] }); 
				});
			}
			//replace
			jQuery('#ace_re').live('click', function(event) {
			   event.preventDefault();
			   replace();
			});
			//search
			jQuery('#ace_se').live('click', function(event) {
			   event.preventDefault();
			  search();
			});
			//replace all
			jQuery('#ace_res').live('click', function(event) {
			   event.preventDefault();
			   replaceall();
			});
			//jump to line
			jQuery('#ace_jamp').live('click', function(event) {
			   event.preventDefault();
			   Jump_to_Line();
			});
			
			jQuery('#jump_line_number').live('keydown',function(e) {
			    if(e.keyCode == 13) {
			       e.preventDefault();
			       jQuery("#jump_tbox").dialog("close");
				       Jump_to_Line();
			    }
			});
			
			//functions
			//get selection range
			function getSelectedRange() {
				return { from: editor.getCursor(true), to: editor.getCursor(false) };
			}

			//auto format
			function autoFormatSelection() {
				var range = getSelectedRange();
				editor.autoFormatRange(range.from, range.to);
			}
			//comment selection
			function commentSelection(isComment) {
				var range = getSelectedRange();
				editor.commentRange(isComment, range.from, range.to);
			} 
			//jump to line
			function Jump_to_Line(){
				var line = document.getElementById("jump_line_number").value -1;
				if (line && !isNaN(Number(line))) {
					editor.setCursor(Number(line),0);
					editor.setSelection({line:Number(line),ch:0},{line:Number(line)+1,ch:0});
					editor.focus();
				}
			}
			//search unmark
			function unmark() {
				for (var i = 0; i < marked.length; ++i) 
					marked[i].clear();
				marked.length = 0;
			}
		    //change theme
			function selectTheme(theme) {
				var editorDiv = jQuery('.CodeMirror-scroll');
				if (editorDiv.hasClass('fullscreen')) {
					toggleFullscreenEditing();
					editor.setOption("theme", theme);
					createCookie('adce_theme',theme,365);
					toggleFullscreenEditing();
				}else{
					editor.setOption("theme", theme);
					createCookie('adce_theme',theme,365);
				}
			}
			//search
			function search() {
				unmark();
				var text = document.getElementById("query").value;
				if (!text) return;
				for (var cursor = editor.getSearchCursor(text); cursor.findNext();)
				marked.push(editor.markText(cursor.from(), cursor.to(), "searched"));
				if (lastQuery != text) lastPos = null;
				var cursor = editor.getSearchCursor(text, lastPos || editor.getCursor());
				if (!cursor.findNext()) {
					 cursor = editor.getSearchCursor(text);
				   if (!cursor.findNext()) return;
				}
				editor.setSelection(cursor.from(), cursor.to());
				lastQuery = text; lastPos = cursor.to();
			}
			//replace
			function replace() {
				unmark();
				var text = document.getElementById("query1").value,
				replace = document.getElementById("replace").value;
				if (!text) return;
				var cursor = editor.getSearchCursor(text);
				cursor.findNext();
				if (!cursor) return;
				editor.replaceRange(replace, cursor.from(), cursor.to());				
			}
			//replaceall
			function replaceall() {
				unmark();
				var text = document.getElementById("query1").value,
				replace = document.getElementById("replace").value;
				if (!text) return;
				for (var cursor = editor.getSearchCursor(text); cursor.findNext();)
				   editor.replaceRange(replace, cursor.from(), cursor.to());
			}

			//before save
			function BeforeSave() {
			      jQuery("#SaveBox").html('<p style="text-align:center;">'+ ace_strings.saving +' ...<br/><img src="'+ace_strings.imgURL+'GRZ9W.gif"></p>');
			      show_dialog("#SaveBox",{title: ace_strings.saveBox }); 
			      return true; 
			}
			 
			//save response
			function showResponse(responseText)  { 
				var htmlCode = jQuery('#message',jQuery(responseText)).html();
				jQuery(".ui-dialog-content").dialog("close");
				jQuery('#saveBox').dialog( "destroy" );
				jQuery(".ace_file_status").html(ace_strings.lastsaved + ' ' + getTimeStamp() + ' ').addClass('updated');
				jQuery('#update_Box').html('<div>' + htmlCode + '</div><div><small>'+ ace_strings.autoclosein +'  <span class="closein">5</span> '+ace_strings.seconds+'</small></div>');
				show_dialog("#update_Box",{ title: ace_strings.saveBox, buttons: [
					{
						text: ace_strings.close,
						click: function() { jQuery(this).dialog("close"); }
					}]
				}); 
				setTimeout("autoclose_dialog(5)",1000);
			}
			//time stamp function
			function getTimeStamp(){
				var cT = new Date();
				var s = cT.getSeconds();
				var M = cT.getMinutes();
				var h = cT.getHours();
				var y = cT.getFullYear();
				var d = cT.getDate();
				var m = cT.getMonth() + 1;
				var n = m + '/' + d + '/' + y + ' ' + h + ':' + M + ':' + s;
				return n;
			}
			//autoclose save dialog
			function autoclose_dialog(t){
				if (t == 1){
					jQuery('#update_Box').dialog('close');
					editor.focus();
				}else{
					jQuery(".closein").html(t-1);
					setTimeout("autoclose_dialog("+(t-1)+")",1000);
				}
			}
			//fullscreen edit
			function toggleFullscreenEditing(){
				var editorDiv = jQuery('.CodeMirror-scroll');
				var toolbarDiv = jQuery('.ace');
				if (!editorDiv.hasClass('fullscreen')) {
					var bgcolor = editorDiv.css("background-color");
					if (bgcolor == "transparent" || bgcolor == "rgba(0, 0, 0, 0)") bgcolor = "#FFF";
					toggleFullscreenEditing.beforeFullscreen = { height: editorDiv.height(), width: editorDiv.width(),bg: editorDiv.css("background-color") }
					editorDiv.addClass('fullscreen');
					jQuery(".fullscreen").css('background-color',bgcolor);
					editorDiv.height('89%');
					editorDiv.width('100%');
					toolbarDiv.addClass('ace_ToolBar');
					editor.refresh();
				}else {
					editorDiv.removeClass('fullscreen');
					toolbarDiv.removeClass('ace_ToolBar');
					editorDiv.height(toggleFullscreenEditing.beforeFullscreen.height);
					editorDiv.width(toggleFullscreenEditing.beforeFullscreen.width);
					editorDiv.css('background-color','');
					editor.refresh();
				}
			}
			
			//refresh editor
			 //editor.refresh();
			</script>
			<div id="add_new_file" style="display:none;"></div>
			<div id="SaveBox" style="display:none;"></div>
			<div id="update_Box" style="display:none;"></div>
			<div id="search" style="display:none;"><?php _e('Search For: ','baace');?><input type="text" value="" id="query" style="width: 98%"><button class="button"  id="ace_se" type="button"><?php _e('Search','baace');?></button> </div> 
			<div id="jump_tbox" style="display:none;"><?php _e('Jump to Line: ','baace');?><input type="text" value="" id="jump_line_number" style="width: 98%"></div> 
			<div id="searchR" style="display:none;"><?php _e('Search For: ','baace');?><input type="text" value="" id="query1" style="width: 98%"><br/><?php _e('And Replace with:','baace');?><input type="text" id="replace" value="" style="width: 98%"><br /><button class="button"  id="ace_re" type="button"><?php _e('Replace','baace');?></button><?php _e('OR','baace');?> <button class="button"  id="ace_res" type="button"><?php _e('Replace all','baace');?></button> </div> 
			<div id="ace_help" style="display:none;">
				<h4><?php _e('Hot Keys:','baace');?></h4>
			   	<ul>
				  <li><strong>CRTL + Space</strong> -  <?php _e('Triggers AutoComplete.','baace');?></li>
				  <li><strong>CRTL + Z</strong> -  <?php _e('Undo (remembers all changes, so you can use more then one)','baace');?></li>
				  <li><strong>CRTL + Y</strong> -  <?php _e('Redo (remembers all changes, so you can use more then one)','baace');?></li>
				  <li><strong>CRTL + F</strong> -  <?php _e('Search','baace');?></li>
				  <li><strong>CRTL + H</strong> -  <?php _e('Search and Replace','baace');?></li>
				  <li><strong>CRTL + G</strong> -  <?php _e('Jump to Line','baace');?></li>
				  <li><strong>CRTL + M</strong> -  <?php _e('Auto Format code (selection only)','baace');?></li>
				  <li><strong>CRTL + SHIFT + /</strong> -  <?php _e('Comment out code (selection only)','baace');?></li>
				  <li><strong>CRTL + SHIFT + .</strong> -  <?php _e('Comment in code (selection only)','baace');?></li>
				  <li><strong>CRTL + S</strong> -  <?php _e('Save Changes (When cruser is inside editor)','baace');?></li>
				  <li><strong>F11</strong> -  <?php _e('FullScreen Editor (When cruser is inside editor)','baace');?></li>

			   	</ul>
			</div>
			<div id="ace_about" style="display:none;text-align:center;">
			   <h4><?php _e('WordPress Advanced Code Editor','baace');?></h4>
			   <ul style="list-style: square inside none; width: 300px; font-weight: bolder; padding: 20px; border: 2px solid; background-color: #FFFFE0; border-color: #E6DB55;">
				<li> Any feedback or suggestions are welcome at <a href="http://en.bainternet.info/">plugin homepage</a></li>
				<li> <a href="http://wordpress.org/tags/advanced-code-editor/?forum_id=10">Support forum</a> for help and bug submittion</li>
				<li> Also check out <a href="http://en.bainternet.info/category/plugins">my other plugins</a></li>
				<li> And if you like my work <span style="font-weight:bolder;color: #FF0000;">make a donation</span><br/>
				<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PPCPQV8KA3UQA"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"></a>
				 or at least <a href="http://wordpress.org/extend/plugins/bainternet-user-ranks/">rank the plugin</a></li>
			   </ul>
			   <p><?php _e('WordPress Advanced Code Editor uses:','baace');?> </p>
				  <ul>
				 <li><a href="http://codemirror.net" traget="_blank">CodeMirror2</a> by Marijn Haverbeke.</li>
				 <li>icons By:
					<ul>
					   <li><a href="http://www.icons-land.com" traget="_blank">Icons Land</a></li>
					   <li><a href="http://www.oxygen-icons.org/" traget="_blank">Oxygen Team</a></li>
						   <li><a href="http://www.oxygen-icons.org/" traget="_blank">Oliver Scholtz</a></li>
						   <li>Marco Martin</li>
						   <li><a href="http://sa-ki.deviantart.com/" traget="_blank">Alexandre Moore</a></li>
					</ul>
				  </li>
				  </ul>
			</div>
			<?php
		}

		public function plugin_file_tree(){
			$url = plugins_url()."/advanced-code-editor/";
			?>
			<script>
			jQuery(document).ready(function(){
				
				//add downloads
				var toolbar = jQuery('.ace_tool_bar');
				//current file
				get_file_name = jQuery('input[name="plugin"]').val();
				//download file
				addToolbarButton(null,"tb_re","ace_tool_download_file",ace_strings.downloadfile,"download_file.png","Download File",function(){
					get_file_name = jQuery('input[name="plugin"]').val();
					jQuery("#dframe").attr("src",ace_strings.url+'?dn_file=' + get_file_name + '&dnf=plugin');
				});
				
				download_from = 'plugin';
				//zip plugin
				addToolbarButton(null,"tb_re","ace_tool_download_zip",ace_strings.downloadplugin,"download_zip.png","Download Plugin",function(){
					plugin_to_d = get_file_name.split("/")[0]; 
					jQuery("#dframe").attr("src",ace_strings.url+'?plugin_download=' + plugin_to_d);
				});

				var files_count = 0;
				var files = Array;
				jQuery("#templateside").find("ul").children().each(function() {
					files[files_count] = {name: jQuery(this).find('a').text(), link: jQuery(this).find('a').attr('href')};
					files_count++;
				});
				var main = files[0].name;
				main = main.split('/');
				var tree = jQuery('<ul>');
				tree.attr('id', main[0]);
				tree.addClass('class', 'root');
				var cur = jQuery(".highlight").find("a");
				var c_l = jQuery(cur).attr("href");
				for (var i = 0; i < files_count; i++){
					tempName = files[i].name;
					templink = files[i].link;
					if (templink == c_l)
						templink = templink + '" class="cur';
					st = tempName.split("/");
					switch (st.length){
						case 2:
							tree.append('<li><a href="' +templink + '">' + st[1] + '</a></li>');
							break;
						case 3:
							if (tree.find('#'+ st[1]).length > 0){
								tree.find('#'+ st[1]).append('<li><a href="' +templink + '">' + st[2] + '</a></li>');
							}else{
								tree.append('<li class="folder">'+ st[1] +'<ul id="'+ st[1] +'"><li><a href="' +templink + '">' + st[2] + '</a></li></ul></li>');
							}
							break;
						case 4:
							if (tree.find('#'+ st[1]).length > 0){
								if (tree.find('#'+ st[1]).find('#'+st[2]).length > 0){
									tree.find('#'+ st[1]).find('#'+st[2]).append('<li><a href="' +templink + '">' + st[3] + '</a></li>');
								}else{
									tree.find('#' + st[1]).append('<li class="folder">'+ st[2] +'<ul id="'+ st[2] +'"><li><a href="' +templink + '">' + st[3] + '</a></li></ul></li>');
								}
								
							}else{
								tree.append('<li class="folder">'+ st[1] +'<ul id="'+ st[1] +'"><li  class="folder">'+ st[2] +'<ul id="'+st[2]+'"><li><a href="' +templink + '">' + st[3] + '</a></li></ul></li></ul></li>');
							}
							break;
					}
				}
				var root = jQuery('<li>');
				jQuery(root).html(main[0]);
				jQuery(root).addClass('root');
				jQuery(root).append(tree);
				jQuery("#templateside").find("ul").html(root);
				jQuery(".folder").each(function(){
					jQuery(this).find('ul').hide();
				});
				//close - open folders
				jQuery(".folder").live("click",function(){
					var child = jQuery(this).children();
					if (jQuery(child).css('display') == 'none'){
						jQuery(child).show();
					}else{
						jQuery(child).hide();
					}
				});
				//folders
				var fol = jQuery('.folder');
				fol.css('padding-left','26px');
				fol.css('background-image','url('+ace_strings.imgURL+'wPPkk.png)');
				fol.css('background-repeat','no-repeat');
				fol.css('cursor','pointer');
				var jroot = jQuery(".root");
				jroot.css('padding-left','26px');
				jroot.css('background-image','url('+ace_strings.imgURL+'wPPkk.png)');
				jroot.css('background-repeat','no-repeat');
				jroot.find('li').css("min-height","26px");
				jroot.find('li').css('padding-left','26px');
				jroot.find('li').css('background-repeat','no-repeat');
				function icon(ext) {
					return {
						'php': 'images/O57GR.png',
						'css':'images/NbJXD.png',
						'txt':'images/tBtiP.png',
						'js':'images/MjEOb.png',
						'htm':'images/GZVfa.png',
						'html':'images/GZVfa.png'
					}[ext];
				}
				var re = new RegExp('file=[^\.]*.([^&]+).*');
				jroot.find('a').each(function(){				
					jQuery(this).parent().css('background-image','url(<?php echo $url; ?>' + icon(re.exec(jQuery(this).attr('href'))[1]) + ')');
				});
				jQuery(".cur").parent().addClass("highlight");
			});
			
			
			</script>
			<?php
		}

		//theme file tree
		public function theme_file_tree(){
			$url = plugins_url()."/advanced-code-editor/";
			?>
			<script>
			
			jQuery(document).ready(function(){
				//add downloads
				theme_to_download = jQuery('input[name="theme"]').val();
				get_file_name = jQuery('input[name="file"]').val();
				download_from = 'theme';
				//download file
				addToolbarButton(null,'tb_re','ace_tool_download_file',ace_strings.downloadfile,'download_file.png','Download File',function(){
					get_file_name = jQuery('input[name="file"]').val();
					theme_to_download = jQuery('input[name="theme"]').val();
					jQuery("#dframe").attr("src",ace_strings.url+'?dn_file=' + get_file_name + '&dnf=theme&tmf='+theme_to_download);
				});
				//zip theme
				addToolbarButton(null,"tb_re","ace_tool_download_zip",ace_strings.downloadtheme,"download_zip.png","Download theme",function(){
					theme_to_download = jQuery('input[name="theme"]').val();
					jQuery("#dframe").attr("src",ace_strings.url+'?theme_download=theme_download&ttd='+theme_to_download);
				});
				
				var files_count = 0;
				var files = Array;
				jQuery("#templateside").find("ul").children().each(function() {
					files[files_count] = {name: jQuery(this).find('a').text(), link: jQuery(this).find('a').attr('href')};
					files_count++;
				});
				var main = jQuery(".fileedit-sub").find("h3").text();
				main = main.split(':');
				var tree = jQuery('<ul>');
				tree.attr('id', main[0]);
				tree.addClass('class', 'root');
				var cur = '';
				cur = jQuery(".highlight").parent().attr("href");
				for (var i = 0; i < files_count; i++){
					tempName = files[i].name;
					templink = files[i].link;

					if (templink == cur){
						templink = templink + '" class="cur';
						
					}
					st = tempName.split("/");
					switch (st.length){
						case 1:
							tempName = tempName.split("(");
							var tmp = isset(tempName[1])? tempName[1].split(")"):tempName[0];
							tempName[1] = isset(tmp[0])? tmp[0] : tmp;
							tree.append('<li><a href="' +templink + '" title="'+tempName[0]+'">' + tempName[1] + '</a></li>');
							break;
						case 2:
							if (st[1] != "undefined"){
								tree.append('<li><a href="' +templink + '">' + st[1] + '</a></li>');
							}
							break;
						case 3:
							if (tree.find('#'+ st[1]).length > 0){
								tree.find('#'+ st[1]).append('<li><a href="' +templink + '">' + st[2] + '</a></li>');
							}else{
								tree.append('<li class="folder">'+ st[1] +'<ul id="'+ st[1] +'"><li><a href="' +templink + '">' + st[2] + '</a></li></ul></li>');
							}
							break;
						case 4:
							if (tree.find('#'+ st[1]).length > 0){
								if (tree.find('#'+ st[1]).find('#'+st[2]).length > 0){
									tree.find('#'+ st[1]).find('#'+st[2]).append('<li><a href="' +templink + '">' + st[3] + '</a></li>');
								}else{
									tree.find('#' + st[1]).append('<li class="folder">'+ st[2] +'<ul id="'+ st[2] +'"><li><a href="' +templink + '">' + st[3] + '</a></li></ul></li>');
								}
								
							}else{
								tree.append('<li class="folder">'+ st[1] +'<ul id="'+ st[1] +'"><li  class="folder">'+ st[2] +'<ul id="'+st[2]+'"><li><a href="' +templink + '">' + st[3] + '</a></li></ul></li></ul></li>');
							}
							break;
					}
				}
				var root = jQuery('<div>');
				root.html(main[0]);
				root.addClass('root');
				root.append(tree);
				jQuery("#templateside").html('<h3><?php _e("Templates"); ?></h3>');
				jQuery("#templateside").append(root);
				jQuery(".folder").each(function(){
					jQuery(this).find('ul').hide();
				});
				//close - open folders
				jQuery(".folder").live("click",function(){
					var child = jQuery(this).children();
					if (jQuery(child).css('display') == 'none'){
						jQuery(child).show();
					}else{
						jQuery(child).hide();
					}
				});
				jQuery(".cur").parent().addClass("highlight");
				//folders
				var fol = jQuery('.folder');
				fol.css('padding-left','26px');
				fol.css('background-image','url('+ace_strings.imgURL+'wPPkk.png)');
				fol.css('background-repeat','no-repeat');
				fol.css('cursor','pointer');
				var jroot = jQuery(".root");
				jroot.css('padding-left','26px');
				jroot.css('background-image','url('+ace_strings.imgURL+'wPPkk.png)');
				jroot.css('background-repeat','no-repeat');
				jroot.find('li').css("min-height","26px");
				jroot.find('li').css('padding-left','26px');
				jroot.find('li').css('background-repeat','no-repeat');
				function icon(ext) {
					return {
						'php': 'images/O57GR.png',
						'css':'images/NbJXD.png',
						'txt':'images/tBtiP.png',
						'js':'images/MjEOb.png',
						'htm':'images/GZVfa.png',
						'html':'images/GZVfa.png'
					}[ext];
				}
				var re = new RegExp('file=[^\.]*.([^&]+).*');
				jroot.find('a').each(function(){				
					jQuery(this).parent().css('background-image','url(<?php echo $url; ?>' + icon(re.exec(jQuery(this).attr('href'))[1]) + ')');
				});
			});
			
			</script>
			<?php
		}

		/**
		 * _meta_table_exists 
		 * 
		 * Function to check if a table exists in the database
		 * @since 2.0
		 * @param  (string) $table  table name
		 * @return (boolean)
		 */
		public function _meta_table_exists($table = null) {
		    global $wpdb;
		    $table = $this->tablename;
		    $query = "SHOW TABLES LIKE '{$wpdb->prefix}{$table}';";
		    $indexes = $wpdb->get_var(  $query );
		    if ( $indexes )
		            return true; 
		    return false;
		}

		/**   
		 * Create a table for file versions
		 * @since 2.0     *
		 * @return none
		 * 
		 */
		public function _create_meta_table($table=null) {
			$table = $this->tablename;
		    if ( $this->_meta_table_exists($table) ) {
		            return;
		    }     
		    global $wpdb;
		    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		    $query = "CREATE TABLE `{$wpdb->prefix}{$table}` (
		               `meta_id` bigint(20) unsigned not null auto_increment,
		               `file_id` bigint(20) unsigned not null default '0',
		               `meta_key` varchar(255),
		               `meta_value` longtext,
		               PRIMARY KEY (`meta_id`),
		               KEY `file_id` (`file_id`),
		               KEY `meta_key` (`meta_key`)
		            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=2;";
		    dbDelta($query);
		}

		/**   
		 * WordPress doesn't seem to support metadata out
		 * of the box, we need to update $wpdb->table to the correct table
		 * ourself.
		 * @since 2.0
		 * @return none
		 */
		function update_wpdb($table=null) {
			global $wpdb;
			$table = $this->tablename;
		    $wpdb->filemeta = $wpdb->prefix . $table;
		}
		/**
		 * update_file_meta
		 * @since 2.0
		 * @author Ohad Raz
		 * @param  integer $term_id=0  
		 * @param  [type]  $meta_key   
		 * @param  [type]  $meta_value 
		 * @param  string  $prev_value 
		 * @return [type]              
		 */
		function update_file_meta($term_id=0, $meta_key, $meta_value, $prev_value = ''){
			$this->update_wpdb();
		    return update_metadata('file', $term_id, $meta_key, $meta_value, $prev_value);
		}

		/**
		 * add_file_meta 
		 * @since 2.0
		 * @author Ohad Raz
		 * @param integer $term_id=0  
		 * @param [type]  $meta_key   
		 * @param [type]  $meta_value 
		 * @param boolean $unique     
		 */
		function add_file_meta($term_id=0, $meta_key, $meta_value, $unique = false){
			$this->update_wpdb();
		    return add_metadata('file', $term_id, $meta_key, $meta_value, $unique);
		}

		/**
		 * delete_file_meta 
		 * @since 2.0
		 * @author Ohad Raz
		 * @param  integer $term_id=0  
		 * @param  [type]  $meta_key   
		 * @param  string  $meta_value 
		 * @param  boolean $delete_all 
		 * @return [type]              
		 */
		function delete_file_meta($term_id=0, $meta_key, $meta_value = '', $delete_all = false){
			$this->update_wpdb();
		    return delete_metadata('file', $term_id, $meta_key, $meta_value, $delete_all);
		}

		/**
		 * get_file_meta
		 * @since 2.0
		 * @author Ohad Raz
		 * @param  integer $term_id=0 
		 * @param  [type]  $key       
		 * @param  boolean $single    
		 * @return [type]             
		 */
		function get_file_meta($term_id=0, $key, $single = true){
			$this->update_wpdb();
		    return  get_metadata('file', $term_id, $key, $single);
		}

		/**
		 * get_file_meta_id
		 * @since 2.0
		 * @author Ohad Raz
		 * @param  string  $meta_key
		 * @param  mixed   $meta_value
		 * @return int if meta id found else returns false             
		 */
		function get_file_meta_id($meta_key,$meta_val){
			global $wpdb;
			$mid = $wpdb->get_var( $wpdb->prepare("SELECT meta_id FROM $wpdb->filemeta WHERE meta_key = %s AND meta_value = %s", $meta_key,$meta_val));
			if( $mid != '' )
			return (int)$mid;

			return false;
		}

		public function _my_plugin_links($links, $file) {
		    $plugin = plugin_basename(__FILE__); 
		    if ($file == $plugin) // only for this plugin
		            return array_merge( $links,
		        array( '<a href="http://en.bainternet.info/category/plugins">' . __('Other Plugins by this author' ) . '</a>' ),
		        array( '<a href="http://wordpress.org/support/plugin/advanced-code-editor">' . __('Plugin Support') . '</a>' ),
		        array( '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K4MMGF5X3TM5L" target="_blank">' . __('Donate') . '</a>' )
		    );
		    return $links;
		}

		public function uninstall(){
			if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
				exit ();
			global $wpdb;
			$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "filemeta`;" );
		}
	}//END Class
}//END IF
$ace = new advanced_code_editor();
register_activation_hook( __FILE__, array( &$ace, '_create_meta_table'));