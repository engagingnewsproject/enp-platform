<?php

class BlogVault {

	private static $instance = NULL; 
	public $status;

	static public function getInstance() {
		if (self::$instance === NULL)
			self::$instance = new BlogVault();
		return self::$instance;
	}

	/**
	* PHP5 constructor.
	*/
	function __construct() {
		$this->status = array("blogvault" => "response");
	}

	/**
	 * PHP4 constructor.
	 */
	function BlogVault() {
		BlogVault::__construct();
	}

	function addStatus($key, $value) {
		$this->status[$key] = $value;
	}

	function addArrayToStatus($key, $value) {
		if (!isset($this->status[$key])) {
			$this->status[$key] = array();
		}
		$this->status[$key][] = $value;
	}

	function objectToArray($obj) {
		return json_decode(json_encode($obj), true);
	}
	
	function isServerWritable() {
		if ($this->is_pantheon()) {
			return false;
		}

		if ((!defined('FTP_HOST') || !defined('FTP_USER')) && (get_filesystem_method(array(), false) != 'direct')) {
			return false;
		} else {
			return true;
		}
	}

	function is_pantheon() {
		return !empty($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] !== 'dev';
	}

	function getTransient($name) {
		if (function_exists('get_site_transient')) {
			$transient = get_site_transient($name);
			if ($transient) {
				return $transient;
			}
		}
		return false;
	}

	function autoLogin($username, $isHttps) {
		$user = get_user_by('login', $username);
		if ($user != FALSE) {
			wp_set_current_user( $user->ID );
			if ($isHttps) {
				wp_set_auth_cookie( $user->ID, false, true );
			} else {
				# As we are not sure about wp-cofig.php settings for sure login
				wp_set_auth_cookie( $user->ID, false, true );
				wp_set_auth_cookie( $user->ID, false, false );
			}
			$redirect_to = get_admin_url();
			wp_safe_redirect( $redirect_to );
			exit;
		}
	}

	function serverSig($full = false) {
		$sig = sha1($_SERVER['SERVER_ADDR'].ABSPATH);
		if ($full)
			return $sig;
		else
			return substr($sig, 0, 6);
	}

	function serializeThemeInfo($theme) {
		if (is_object($theme)) {
			$pdata = array(
				'name' => $theme->Name,
				'title' => $theme->Title,
				'stylesheet' => $theme->get_stylesheet(),
				'template' => $theme->Template,
				'version' => $theme->Version
			);
		} else {
			$pdata = array(
				'name' => $theme["Name"],
				'title' => $theme["Title"],
				'stylesheet' => $theme["Stylesheet"],
				'template' => $theme["Template"],
				'version' => $theme["Version"]
			);
		}
		return $pdata;
	}

	function dbSig($full = false) {
		global $blogvault;
		if (defined('DB_USER') && defined('DB_NAME') &&
				defined('DB_PASSWORD') && defined('DB_HOST')) {
			$sig = sha1(DB_USER.DB_NAME.DB_PASSWORD.DB_HOST);
		} else {
			$sig = "bvnone".$blogvault->randString(34);
		}
		if ($full)
			return $sig;
		else
			return substr($sig, 0, 6);
	}
	function terminate($is_authenticated = true) {
		global $bvVersion;
		$this->addStatus("signature", "Blogvault API");
		$this->addStatus("asymauth", "true");
		$this->addStatus("dbsig", $this->dbSig(false));
		$this->addStatus("serversig", $this->serverSig());
		$this->addStatus("public", substr($this->getOption('bvPublic'), 0, 6));
		if ($is_authenticated) {
			$this->addStatus("bvversion", $bvVersion);
			$this->addStatus("abspath", urldecode(ABSPATH));
			$this->addStatus("serverip", urlencode($_SERVER['SERVER_ADDR']));
			$this->addStatus("siteurl", urlencode($this->wpurl()));
		}
		
		die("bvbvbvbvbv".serialize($this->status)."bvbvbvbvbv");
		exit;
	}

	function getUrl($method) {
		global $bvVersion;
		$baseurl = "/bvapi/";
		$time = time();
		if ($time < $this->getOption('bvLastSendTime')) {
			$time = $this->getOption('bvLastSendTime') + 1;
		}
		$this->updateOption('bvLastSendTime', $time);
		$public = $this->getPubKeyParam();
		$secret = $this->getSecretKeyParam();
		$serverip = urlencode($_SERVER['SERVER_ADDR']);
		$time = urlencode($time);
		$version = urlencode($bvVersion);
		$sig = sha1($public.$secret.$time.$version);
		$serversig = $this->serverSig();
		$dbsig = $this->dbSig(false);
		$url = $baseurl.$method."?sha1=1&sig=".$sig."&bvTime=".$time."&bvPublic=".$public."&bvVersion=".$version."&serverip=".$serverip."&serversig=".$serversig."&dbsig=".$dbsig;
		return $url;
	}

	function randString($length) {
		$chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		
		$str = "";
		$size = strlen($chars);
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[rand(0, $size - 1)];
		}
		return $str;
	}

	function fileStat($relfile) {
		$absfile = ABSPATH.$relfile;
		$fdata = array();
		$fdata["filename"] = $relfile;
		$stats = @stat($absfile);
		if ($stats) {
			foreach (preg_grep('#size|uid|gid|mode|mtime#i', array_keys($stats)) as $key ) {
				$fdata[$key] = $stats[$key];
			}
			if (is_link($absfile)) {
				$fdata["link"] = @readlink($absfile);
			}
		} else {
			$fdata["failed"] = true;
		}
		return $fdata;
	}

	function scanFilesUsingGlob($initdir = "./", $offset = 0, $limit = 0, $bsize = 512, $regex = '{.??,}*') {
		$i = 0;
		$dirs = array();
		$dirs[] = $initdir;
		$bfc = 0;
		$bfa = array();
		$current = 0;
		$recurse = true;
		if (array_key_exists('recurse', $_REQUEST) && $_REQUEST["recurse"] == "false") {
			$recurse = false;
		}
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$abspath = realpath(ABSPATH).'/';
		$abslen = strlen($abspath);
		$url = $this->getUrl("listfiles")."&recurse=".$_REQUEST["recurse"]."&offset=".$offset."&initdir=".urlencode($initdir)."&regex=".urlencode($regex);
		$clt->uploadChunkedFile($url, "fileslist", "allfiles");
		while ($i < count($dirs)) {
			$dir = $dirs[$i];

			foreach (glob($abspath.$dir.$regex, GLOB_NOSORT | GLOB_BRACE) as $absfile) {
				$relfile = substr($absfile, $abslen);
				if (is_dir($absfile) && !is_link($absfile)) {
					$dirs[] = $relfile."/";
				}
				$current++;
				if ($offset >= $current)
					continue;
				if (($limit != 0) && (($current - $offset) > $limit)) {
					$i = count($dirs);
					break;
				}
				$bfa[] = $this->fileStat($relfile);
				$bfc++;
				if ($bfc == $bsize) {
					$str = serialize($bfa);
					$clt->newChunkedPart($str);
					$bfc = 0;
					$bfa = array();
				}
			}
			$regex = '{.??,}*';
			$i++;
			if ($recurse == false)
				break;
		}
		if ($bfc != 0) {
			$str = serialize($bfa);
			$clt->newChunkedPart($str);
		}
		$clt->closeChunkedPart();
		$resp = $clt->getResponse();
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}

	function scanFiles($initdir = "./", $offset = 0, $limit = 0, $bsize = 512) {
		$i = 0;
		$dirs = array();
		$dirs[] = $initdir;
		$bfc = 0;
		$bfa = array();
		$current = 0;
		$recurse = true;
		if (array_key_exists('recurse', $_REQUEST) && $_REQUEST["recurse"] == "false") {
			$recurse = false;
		}
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$clt->uploadChunkedFile($this->getUrl("listfiles")."&recurse=".$_REQUEST["recurse"]."&offset=".$offset."&initdir=".urlencode($initdir), "fileslist", "allfiles");
		while ($i < count($dirs)) {
			$dir = $dirs[$i];
			$d = @opendir(ABSPATH.$dir);
			if ($d) {
				while (($file = readdir($d)) !== false) {
					if ($file == '.' || $file == '..') { continue; }
					$relfile = $dir.$file;
					$absfile = ABSPATH.$relfile;
					if (is_dir($absfile) && !is_link($absfile)) {
						$dirs[] = $relfile."/";
					}
					$current++;
					if ($offset >= $current)
						continue;
					if (($limit != 0) && (($current - $offset) > $limit)) {
						$i = count($dirs);
						break;
					}
					$bfa[] = $this->fileStat($relfile);
					$bfc++;
					if ($bfc == $bsize) {
						$str = serialize($bfa);
						$clt->newChunkedPart($str);
						$bfc = 0;
						$bfa = array();
					}
				}
				closedir($d);
			}
			$i++;
			if ($recurse == false)
				break;
		}
		if ($bfc != 0) {
			$str = serialize($bfa);
			$clt->newChunkedPart($str);
		}
		$clt->closeChunkedPart();
		$resp = $clt->getResponse();
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}

	function calculateMd5($absfile, $fdata, $offset, $limit, $bsize) {
		if ($offset == 0 && $limit == 0) {
			$md5 = md5_file($absfile);
		} else {
			if ($limit == 0)
				$limit = $fdata["size"];
			if ($offset + $limit < $fdata["size"])
				$limit = $fdata["size"] - $offset;
			$handle = fopen($absfile, "rb");
			$ctx = hash_init('md5');
			fseek($handle, $offset, SEEK_SET);
			$dlen = 1;
			while (($limit > 0) && ($dlen > 0)) {
				if ($bsize > $limit)
					$bsize = $limit;
				$d = fread($handle, $bsize);
				$dlen = strlen($d);
				hash_update($ctx, $d);
				$limit -= $dlen;
			}
			fclose($handle);
			$md5 = hash_final($ctx);
		}
		return $md5;
	}

	function uploadFilesMd5($files, $offset = 0, $limit = 0, $bsize = 102400) {
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$clt->uploadChunkedFile($this->getUrl("filesmd5")."&offset=".$offset, "filemd5", "list");
		foreach ($files as $file) {
			$fdata = $this->fileStat($file);
			$absfile = ABSPATH.$file;
			if (!is_readable($absfile)) {
				$this->addArrayToStatus("missingfiles", $file);
				continue;
			}
			$fdata["md5"] = $this->calculateMd5($absfile, $fdata, $offset, $limit, $bsize);
			$sfdata = serialize($fdata);
			$clt->newChunkedPart($sfdata);
		}
		$clt->closeChunkedPart();
		$resp = $clt->getResponse();
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}

		return true;
	}

	function getFilesStats($files, $offset = 0, $limit = 0, $bsize = 102400) {
		foreach ($files as $file) {
			$fdata = $this->fileStat($file);
			$absfile = ABSPATH.$file;
			if (!is_readable($absfile)) {
				$this->addArrayToStatus("missingfiles", $file);
				continue;
			}
			if (array_key_exists('md5', $_REQUEST)) {
				$fdata["md5"] = $this->calculateMd5($absfile, $fdata, $offset, $limit, $bsize);
			}
			$this->addArrayToStatus("stats", $fdata);
		}
		return true;
	}

	function uploadFiles($files, $offset = 0, $limit = 0, $bsize = 102400) {
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$clt->uploadChunkedFile($this->getUrl("filedump")."&offset=".$offset, "filedump", "data");

		foreach ($files as $file) {
			if (!is_readable(ABSPATH.$file)) {
				$this->addArrayToStatus("missingfiles", $file);
				continue;
			}
			$handle = fopen(ABSPATH.$file, "rb");
			if (($handle != null) && is_resource($handle)) {
				$fdata = $this->fileStat($file);
				$_limit = $limit;
				$_bsize = $bsize;
				if ($_limit == 0)
					$_limit = $fdata["size"];
				if ($offset + $_limit > $fdata["size"])
					$_limit = $fdata["size"] - $offset;
				$fdata["limit"] = $_limit;
				$sfdata = serialize($fdata);
				$clt->newChunkedPart($sfdata);
				fseek($handle, $offset, SEEK_SET);
				$dlen = 1;
				while (($_limit > 0) && ($dlen > 0)) {
					if ($_bsize > $_limit)
						$_bsize = $_limit;
					$d = fread($handle, $_bsize);
					$dlen = strlen($d);
					$clt->newChunkedPart($d);
					$_limit -= $dlen;
				}
				fclose($handle);
			} else {
				$this->addArrayToStatus("unreadablefiles", $file);
			}
		}
		$clt->closeChunkedPart();
		$resp = $clt->getResponse();
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}

	function wpurl() {
		if (function_exists('network_site_url'))
			return network_site_url();
		else
			return get_bloginfo('wpurl');
	}

	/* This informs the server about the activation */
	function activate() {
		global $blogvault;
		$body = $blogvault->basicInfo();
		if (defined('DB_CHARSET'))
			$body['dbcharset'] = urlencode(DB_CHARSET);
		$body['dbprefix'] = urlencode($this->dbprefix());
		if (extension_loaded('openssl')) {
			$body['openssl'] = "1";
		}
		if (function_exists('is_ssl') && is_ssl()) {
			$body['https'] = "1";
		}
		$body['sha1'] = "1";
		$all_tables = $this->getAllTables();
		$i = 0;
		foreach ($all_tables as $table) {
			$body["all_tables[$i]"] = urlencode($table);
			$i++;
		}
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$resp = $clt->post($this->getUrl("activate"), array(), $body);
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}

	/* This informs the presence of the plugin in site everyday */
	function dailyping() {
		global $blogvault;
		if (!$blogvault->getOption('bvPublic') || $blogvault->getOption('bvDailyPing') == "no") {
			return false;
		}
		$body = $blogvault->basicInfo();
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$resp = $clt->post($blogvault->getUrl("dailyping"), array(), $body);
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}

	function updateDailyPing($value) {
		if(update_option("bvDailyPing", $value)) {
			return $value;
		}
		return "failed";
	}

	function disableBadge() {
		if(update_option("bvBadgeInFooter", "false")) {
			return "false";
		}
		return "failed";
	}

	function basicInfo() {
		global $bvVersion;
		global $blogvault;
		$body = array();
		$body['wpurl'] = urlencode($blogvault->wpurl());
		$body['url2'] = urlencode(get_bloginfo('wpurl'));
		$body['bvversion'] = urlencode($bvVersion);
		$body['serverip'] = urlencode($_SERVER['SERVER_ADDR']);
		$body['abspath'] = urlencode(ABSPATH);
		$body['dynsync'] = urlencode($blogvault->getOption('bvDynSyncActive'));
		$body['woodyn'] = urlencode($blogvault->getOption('bvWooDynSync'));
		return $body;
	}

	function siteInfoTags($bvAdminPage) {
		global $bvVersion;
		$bvnonce = wp_create_nonce("bvnonce");
		$bvadmin_url = $this->bvAdminUrl($bvAdminPage);
		$multisite = $this->isMultisite();
		$tags = "<input type='hidden' name='url' value='{$this->wpurl()}'/>\n".
				"<input type='hidden' name='secret' value='{$this->getOption('bvSecretKey')}'/>\n".
				"<input type='hidden' name='bvnonce' value='{$bvnonce}'/>\n".
	 			"<input type='hidden' name='serverip' value='{$_SERVER["SERVER_ADDR"]}'/>\n".
				"<input type='hidden' name='adminurl' value='{$bvadmin_url}'/>\n".
				"<input type='hidden' name='multisite' value='{$multisite}'/>\n".
				"<input type='hidden' name='dbsig' value='{$this->dbSig(true)}'/>\n".
				"<input type='hidden' name='bvversion' value='{$bvVersion}'/>\n".
				"<input type='hidden' name='serversig' value='{$this->serverSig(true)}'/>\n";
		if (defined('IS_AMIMOTO')) {
			$tags .= "<input type='hidden' name='amimoto' value='true'/>\n";
		}
		return $tags;
	}

	function bvAdminUrl($bvAdminPage, $_params = '') {
		if (function_exists('network_admin_url')) {
			return network_admin_url('admin.php?page='.$bvAdminPage.$_params);
		} else {
			return admin_url('admin.php?page='.$bvAdminPage.$_params);
		}
	}

	function listTables() {
		global $wpdb;

		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$clt->uploadChunkedFile($this->getUrl("listtables"), "tableslist", "status");
		$data["listtables"] = $wpdb->get_results( "SHOW TABLE STATUS", ARRAY_A);
		$data["tables"] = $wpdb->get_results( "SHOW TABLES", ARRAY_N);
		$str = serialize($data);
		$clt->newChunkedPart($str);
		$clt->closeChunkedPart();
		$resp = $clt->getResponse();
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}

	function dbprefix() {
		global $wpdb;
		$prefix = $wpdb->base_prefix ? $wpdb->base_prefix : $wpdb->prefix;
		return $prefix;
	}

	function getFullTableName($name) {
		return ($this->dbprefix() . $name);
	}

	function tableKeys($table) {
		global $wpdb, $blogvault;
		$info = $wpdb->get_results("SHOW KEYS FROM $table;", ARRAY_A);
		$blogvault->addStatus("table_keys", $info);
		return true;
	}

	function describeTable($table) {
		global $wpdb, $blogvault;
		$info = $wpdb->get_results("DESCRIBE $table;", ARRAY_A);
		$blogvault->addStatus("table_description", $info);
		return true;
	}

	function checkTable($table, $type) {
		global $wpdb, $blogvault;
		$info = $wpdb->get_results("CHECK TABLE $table $type;", ARRAY_A);
		$blogvault->addStatus("status", $info);
		return true;
	}

	function repairTable($table) {
		global $wpdb, $blogvault;
		$info = $wpdb->get_results("REPAIR TABLE $table;", ARRAY_A);
		$blogvault->addStatus("status", $info);
		return true;
	}

	function tableCreate($table) {
		global $wpdb;
		return $wpdb->get_var("SHOW CREATE TABLE $table;", 1);
	}

	function rowsCount($table) {
		global $wpdb;
		$count = $wpdb->get_var("SELECT COUNT(*) FROM $table;");
		return intval($count);
	}

	function getTableContent($table, $fields = '*', $filter = '', $limit = 0, $offset = 0) {
		global $wpdb;
		$query = "SELECT $fields from $table $filter";
		if ($limit > 0)
			$query .= " LIMIT $limit";
		if ($offset > 0)
			$query .= " OFFSET $offset";
		$rows = $wpdb->get_results($query, ARRAY_A);
		return $rows;
	}

	function tableInfo($table, $tname, $rcount, $offset = 0, $limit = 0, $bsize = 512, $filter = "") {
		global $wpdb;

		$data = array();
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$clt->uploadChunkedFile($this->getUrl("tableinfo")."&offset=".$offset."&rcount=".$rcount."&tname=".urlencode($tname), "tableinfo", "data");
		if (array_key_exists('create', $_REQUEST) || array_key_exists('crt', $_REQUEST)) {
			$data["create"] = $this->tableCreate($table);
		}
		if (array_key_exists('count', $_REQUEST)) {
			$data["count"] = $this->rowsCount($table);
		}
		$str = serialize($data);
		$clt->newChunkedPart($str);

		if ($limit == 0) {
			$limit = $rows_count;
		}
		$srows = 1;
		while (($limit > 0) && ($srows > 0)) {
			if ($bsize > $limit)
				$bsize = $limit;
			$rows = $this->getTableContent($table, '*', $filter, $bsize, $offset);
			$srows = sizeof($rows);
			$data = array();
			$data["table"] = $table;
			$data["offset"] = $offset;
			$data["size"] = $srows;
			$data["md5"] = md5(serialize($rows));
			$str = serialize($data);
			$clt->newChunkedPart($str);
			$offset += $srows;
			$limit -= $srows;
		}
		$clt->closeChunkedPart();
		$resp = $clt->getResponse();
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}

	function uploadRows($table, $tname, $rcount, $offset = 0, $limit = 0, $bsize = 512, $filter = "") {
		global $wpdb;
		$clt = new BVHttpClient();
		if (strlen($clt->errormsg) > 0) {
			return false;
		}
		$clt->uploadChunkedFile($this->getUrl("uploadrows")."&offset=".$offset."&rcount=".$rcount."&tname=".urlencode($tname), "uploadrows", "data");

		if ($limit == 0) {
			$limit = $this->rowsCount($table);
		}
		$srows = 1;
		while (($limit > 0) && ($srows > 0)) {
			if ($bsize > $limit)
				$bsize = $limit;
			$rows = $this->getTableContent($table, '*', $filter, $bsize, $offset);
			$srows = sizeof($rows);
			$data = array();
			$data["offset"] = $offset;
			$data["size"] = $srows;
			$data["rows"] = $rows;
			$data["md5"] = md5(serialize($rows));
			$str = serialize($data);
			$clt->newChunkedPart($str);
			$offset += $srows;
			$limit -= $srows;
		}
		$clt->closeChunkedPart();
		$resp = $clt->getResponse();
		if (array_key_exists('status', $resp) && ($resp['status'] != '200')) {
			return false;
		}
		return true;
	}

	function getDynSyncTableName() {
		return $this->getFullTableName('bv_dynamic_sync');
	}
	
	function dropDynSyncTable() {
		global $wpdb;
		$table = $this->getDynSyncTableName();
		return $wpdb->query("DROP TABLE IF EXISTS $table;");
	}

	function getBvDynamincEvents($limit = 0, $filter = "") {
		global $wpdb;
		$result = array();
		$table = $this->getDynSyncTableName();
		$rows = $this->getTableContent($table, '*', $filter, $limit);
		$last_id = 0;
		foreach ($rows as $row) {
			$result[] = $row;
			$last_id = $row['id'];
		}
		$this->status['last_id'] = $last_id; 
		$this->status['events'] = $result;
		$this->status['timestamp'] = time();
		return true;
	}

	function deleteBvDynamicEvents($filter = "") {
		global $wpdb;
		$table = $this->getDynSyncTableName();
		return $wpdb->query("DELETE FROM $table $filter;");
	}

	function truncDynSyncTable() {
		global $wpdb;
		$table = $this->getDynSyncTableName();
		return $wpdb->query("TRUNCATE TABLE $table;");
	}

	function createDynSyncTable() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table = $this->getDynSyncTableName();
		$query = "CREATE TABLE $table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			site_id int NOT NULL,
			event_type varchar(40) NOT NULL DEFAULT '',
			event_tag varchar(40) NOT NULL DEFAULT '',
			event_data text NOT NULL DEFAULT '',
			PRIMARY KEY (id)
		) $charset_collate;";
		if (array_key_exists('usedbdelta', $_REQUEST)) {
			if (!function_exists('dbDelta'))
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $query );
			return $wpdb->get_var("DESCRIBE $table;");
		} else {
			return $wpdb->query($query);
		}
	}

	function getUsers($args = array(), $full) {
		global $wpdb;
		$results = array();
		$users = get_users($args);
		if ('true' == $full) {
			return $this->objectToArray($users);
		}
		$cap = $wpdb->prefix."capabilities";
		foreach( (array) $users as $user) {
			$result = array();
			$result['user_email'] = $user->user_email;
			$result['ID'] = $user->ID;
			$result['roles'] = $user->roles;
			$result['user_login'] = $user->user_login;
			$result['display_name'] = $user->display_name;
			$result['user_registered'] = $user->user_registered;
			$result['user_status'] = $user->user_status;
			$result['user_url'] = $user->url;

			$results[] = $result;
		}
		return $results;
	}

	function getWpInfo() {
		global $wp_version, $wp_db_version;
		global $wpdb, $bvVersion;
		$wp_info = array(
			'current_theme' => (string)(function_exists('wp_get_theme') ? wp_get_theme() : get_current_theme()),
			'dbprefix' => $this->dbprefix(),
			'wpmu' => $this->isMultisite(),
			'mainsite' => $this->isMainSite(),
			'name' => get_bloginfo('name'),
			'site_url' => get_bloginfo('wpurl'),
			'home_url' => get_bloginfo('url'),
			'charset' => get_bloginfo('charset'),
			'wpversion' => $wp_version,
			'dbversion' => $wp_db_version,
			'abspath' => ABSPATH,
			'uploadpath' => $this->uploadPath(),
			'uploaddir' => wp_upload_dir(),
			'contentdir' => defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : null,
			'contenturl' => defined('WP_CONTENT_URL') ? WP_CONTENT_URL : null,
			'plugindir' => defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : null,
			'dbcharset' => defined('DB_CHARSET') ? DB_CHARSET : null,
			'disallow_file_edit' => defined('DISALLOW_FILE_EDIT'),
			'disallow_file_mods' => defined('DISALLOW_FILE_MODS'),
			'bvversion' => $bvVersion,
			'locale' => get_locale()
		);
		return $wp_info;
	}

	function updateKeys($publickey, $secretkey) {
		$this->updateOption('bvPublic', $publickey);
		$this->updateOption('bvSecretKey', $secretkey);
		$this->addKeys($publickey, $secretkey);
	}
	
	function rmKeys($publickey) {
		$bvkeys = $this->getOption('bvkeys');
		if ($bvkeys && is_array($bvkeys)) {
			unset($bvkeys[$publickey]);
			$this->updateOption('bvkeys', $bvkeys);
			return true;
		}
		return false;
	}

	function addKeys($public, $secret) {
		$bvkeys = $this->getOption('bvkeys');
		if ($bvkeys && is_array($bvkeys))
			$bvkeys[$public] = $secret;
		else
			$bvkeys = array($public => $secret);
		$this->updateOption('bvkeys', $bvkeys);
	}

	function getKeys() {
		$keys = $this->getOption('bvkeys');
		if (!is_array($keys)) {
			$keys = array();
		}
		$bvpublic = $this->getOption('bvPublic');
		$bvsecret = $this->getOption('bvSecretKey');
		if ($bvpublic)
			$keys[$bvpublic] = $bvsecret;
		$keys['default'] = $bvsecret;
		return $keys;
	}

	function getAsymKey() {
		$keyName = $_REQUEST['keyname'];
		$fname = dirname(__FILE__)."/publickeys/$keyName.pub";
		if (file_exists($fname)) {
			return file_get_contents(dirname(__FILE__)."/publickeys/$keyName.pub");
		}
		return false;
	}

	function asymEncrypt($source) {
		if (function_exists('openssl_public_encrypt')) {
			$key = $this->getAsymKey();
			if (!$key)
				return false;
			$output = '';
			$blocksize = 1 + floor(($_REQUEST["keysize"] - 1) / 8) - 11;
			while ($source) {
				$input = substr($source, 0, $blocksize);
				$source = substr($source, $blocksize);
				openssl_public_encrypt($input, $encrypted, $key);

				$output .= $encrypted;
			}
			return base64_encode($output);
		}
		return false;
	}

	function updateOption($key, $value) {
		if (function_exists('update_site_option')) {
			update_site_option($key, $value);
		} else {
			if ($this->isMultisite()) {
				update_blog_option(1, $key, $value);
			} else {
				update_option($key, $value);
			}
		}
	}

	function getOption($key) {
		$res = false;
		if (function_exists('get_site_option')) {
			$res = get_site_option($key, false);
		}
		if ($res === false) {
			if ($this->isMultisite()) {
				$res = get_blog_option(1, $key, false);
			} else {
				$res = get_option($key, false);
			}
		}
		return $res;
	}

	function getAllTables() {
		global $wpdb;
		$all_tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
		$all_tables = array_map(create_function('$a', 'return $a[0];'), $all_tables);
		return $all_tables;
	}

	function getPubKeyParam() {
		if (array_key_exists('pubkey', $_REQUEST)) {
			return $_REQUEST['pubkey'];
		} else {
			return $this->getOption('bvPublic');
		}
	}

	function getSecretKeyParam() {
		$public = $this->getPubKeyParam();
		$bvkeys = $this->getKeys();
		if (array_key_exists($public, $bvkeys) && isset($bvkeys[$public]))
			return $bvkeys[$public];
		else
			return $this->getOption('bvSecretKey');
	}

	function processKeyExchange() {
		$keys = $this->getKeys();
		$keys['dbsig'] = $this->dbSig(true);
		$keys['salt'] = $this->randString(32);
		$this->addStatus("activatetime", $this->getOption('bvActivateTime'));
		$this->addStatus("currenttime", time());
		$this->addStatus("keys", $this->asymEncrypt(serialize($keys)));
	}

	/* Control Channel */
	function authenticateControlRequest() {
		if (array_key_exists('dbkey', $_REQUEST)) {
			$secret = $this->dbSig(true);
			$this->addStatus("dbkey", "true");
		} else {
			$secret = $this->getSecretKeyParam();
		}
		$method = $_REQUEST['bvMethod'];
		$sig = $_REQUEST['sig'];
		$time = intval($_REQUEST['bvTime']);
		$version = $_REQUEST['bvVersion'];
		$this->addStatus("requestedsig", $sig);
		$this->addStatus("requestedtime", $_REQUEST['bvTime']);
		$this->addStatus("requestedversion", $version);
		if ($time < intval($this->getOption('bvLastRecvTime')) - 300) {
			return false;
		}
		if (array_key_exists('sha1', $_REQUEST)) {
			$sig_match = sha1($method.$secret.$time.$version);
			$this->addStatus('sha1', $_REQUEST['sha1']);
		} else {
			$sig_match = md5($method.$secret.$time.$version);
		}
		if ($sig_match != $sig) {
			$this->addStatus("sigmatch", substr($sig_match, 0, 6));
			return false;
		}
		$this->updateOption('bvLastRecvTime', $time);
		return true;
	}

	/* New Authentication */
	function asymAuth() {
		$key = $this->getAsymKey();
		if (!$key)
			return false;
		$signature = base64_decode($_REQUEST['sig']);
		$original = $_REQUEST['orig'];
		openssl_public_decrypt($signature, $decrypted, $key);
		if ($original === substr($decrypted, 0, 32))
			return true;
		else
			return false;
	}

	function isMultisite() {
		if (function_exists('is_multisite'))
			return is_multisite();
		return false;
	}

	function isMainSite() {
		if (!function_exists('is_main_site' ) || !$this->isMultisite())
			return true;
		return is_main_site();
	}

	function uploadPath() {
		$dir = wp_upload_dir();

		return $dir['basedir'];
	}

	function processApiRequest() {
		global $wp_version, $wp_db_version;
		global $wpdb, $bvVersion, $bvmanager;
		if (array_key_exists('obend', $_REQUEST) && function_exists('ob_end_clean'))
			@ob_end_clean();
		if (array_key_exists('op_reset', $_REQUEST) && function_exists('output_reset_rewrite_vars'))
			@output_reset_rewrite_vars();
		if (array_key_exists('binhead', $_REQUEST)) {
			header("Content-type: application/binary");
			header('Content-Transfer-Encoding: binary');
		}
		if (!(array_key_exists('stripquotes', $_REQUEST)) && (get_magic_quotes_gpc() || function_exists('wp_magic_quotes'))) {
			$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
		}
		if (array_key_exists('concat', $_REQUEST)) {
			foreach ($_REQUEST['concat'] as $key) {
				$concated = '';
				$count = intval($_REQUEST[$key]);
				for ($i = 1; $i <= $count; $i++) {
					$concated .= $_REQUEST[$key."_bv_".$i];
				}
				$_REQUEST[$key] = $concated;
			}
		}
		if (array_key_exists('b64', $_REQUEST)) {
			foreach ($_REQUEST['b64'] as $key) {
				if (is_array($_REQUEST[$key])) {
					$_REQUEST[$key] = array_map('base64_decode', $_REQUEST[$key]);
				} else {
					$_REQUEST[$key] = base64_decode($_REQUEST[$key]);
				}
			}
		}
		if (array_key_exists('unser', $_REQUEST)) {
			foreach ($_REQUEST['unser'] as $key) {
				$_REQUEST[$key] = unserialize($_REQUEST[$key]);	
			}
		}
		if (array_key_exists('dic', $_REQUEST)) {
			foreach ($_REQUEST['dic'] as $key => $mkey) {
				$_REQUEST[$mkey] = $_REQUEST[$key];
				unset($_REQUEST[$key]);
			}
		}
		if (array_key_exists('clacts', $_REQUEST)) {
			foreach ($_REQUEST['clacts'] as $action) {
				remove_all_actions($action);
			}
		}
		if (array_key_exists('clallacts', $_REQUEST)) {
			global $wp_filter;
			foreach ( $wp_filter as $filter => $val ){
				remove_all_actions($filter);
			}
		}
		if (array_key_exists('memset', $_REQUEST)) {
			$val = intval(urldecode($_REQUEST['memset']));
			@ini_set('memory_limit', $val.'M');
		}
		if (array_key_exists('asymauth', $_REQUEST) && $this->asymAuth()) {
			$this->processKeyExchange();
			$this->terminate();
		}
		if (!$this->authenticateControlRequest()) {
			$this->addStatus("statusmsg", 'failed authentication');
			$this->terminate(false);
		}
		$method = urldecode($_REQUEST['bvMethod']);
		$this->addStatus("callback", $method);
		switch ($method) {
		case "sendmanyfiles":
			$files = $_REQUEST['files'];
			$offset = intval(urldecode($_REQUEST['offset']));
			$limit = intval(urldecode($_REQUEST['limit']));
			$bsize = intval(urldecode($_REQUEST['bsize']));
			$this->addStatus("status", $this->uploadFiles($files, $offset, $limit, $bsize));
			break;
		case "sendfilesmd5":
			$files = $_REQUEST['files'];
			$offset = intval(urldecode($_REQUEST['offset']));
			$limit = intval(urldecode($_REQUEST['limit']));
			$bsize = intval(urldecode($_REQUEST['bsize']));
			$this->addStatus("status", $this->uploadFilesMd5($files, $offset, $limit, $bsize));
			break;
		case "getfilesstats":
			$files = $_REQUEST['files'];
			$offset = intval(urldecode($_REQUEST['offset']));
			$limit = intval(urldecode($_REQUEST['limit']));
			$bsize = intval(urldecode($_REQUEST['bsize']));
			$this->addStatus("status", $this->getFilesStats($files, $offset, $limit, $bsize));
			break;
		case "listtables":
			$this->addStatus("status", $this->listTables());
			break;
		case "tableinfo":
			$table = urldecode($_REQUEST['table']);
			$offset = intval(urldecode($_REQUEST['offset']));
			$limit = intval(urldecode($_REQUEST['limit']));
			$bsize = intval(urldecode($_REQUEST['bsize']));
			$filter = urldecode($_REQUEST['filter']);
			$rcount = intval(urldecode($_REQUEST['rcount']));
			$tname = urldecode($_REQUEST['tname']);
			$this->addStatus("status", $this->tableInfo($table, $tname, $rcount, $offset, $limit, $bsize, $filter));
			break;
		case "uploadrows":
			$table = urldecode($_REQUEST['table']);
			$offset = intval(urldecode($_REQUEST['offset']));
			$limit = intval(urldecode($_REQUEST['limit']));
			$bsize = intval(urldecode($_REQUEST['bsize']));
			$filter = urldecode($_REQUEST['filter']);
			$rcount = intval(urldecode($_REQUEST['rcount']));
			$tname = urldecode($_REQUEST['tname']);
			$this->addStatus("status", $this->uploadRows($table, $tname, $rcount, $offset, $limit, $bsize, $filter));
			break;
		case "sendactivate":
			$this->addStatus("status", $this->activate());
			if (array_key_exists('wp', $_REQUEST)) {
				$this->addStatus("wp", $this->getWpInfo());
			}
			break;
		case "scanfilesdefault":
			$this->addStatus("status", $this->scanFiles());
			break;
		case "scanfiles":
			$initdir = urldecode($_REQUEST['initdir']);
			$offset = intval(urldecode($_REQUEST['offset']));
			$limit = intval(urldecode($_REQUEST['limit']));
			$bsize = intval(urldecode($_REQUEST['bsize']));
			$this->addStatus("status", $this->scanFiles($initdir, $offset, $limit, $bsize));
			break;
		case "scanfilesglob":
			$initdir = urldecode($_REQUEST['initdir']);
			$offset = intval(urldecode($_REQUEST['offset']));
			$limit = intval(urldecode($_REQUEST['limit']));
			$bsize = intval(urldecode($_REQUEST['bsize']));
			$regex = urldecode($_REQUEST['regex']);
			$this->addStatus("status", $this->scanFilesUsingGlob($initdir, $offset, $limit, $bsize, $regex));
			break;
		case "deletedynsyncevents":
			$filter = urldecode($_REQUEST['filter']);
			$this->addStatus("status", $this->deleteBvDynamicEvents($filter));
			break;
		case "truncdynsynctable":
			$this->addStatus("status", $this->truncDynSyncTable());
			break;
		case "dropdynsynctable":
			$this->addStatus("status", $this->dropDynSyncTable());
			break;
		case "createdynsynctable":
			$this->addStatus("status", $this->createDynSyncTable());
			break;
		case "setdynsync":
			$this->updateOption('bvDynSyncActive', $_REQUEST['dynsync']);
			break;
		case "getdynamicevents":
			$isdynsyncactive = $this->getOption('bvDynSyncActive');
			$limit = intval(urldecode($_REQUEST['limit']));
			$filter = urldecode($_REQUEST['filter']);
			if ($isdynsyncactive == 'yes') {
				$this->deleteBvDynamicEvents($_REQUEST['rmfilter']);
				$this->addStatus("status", $this->getBvDynamincEvents($limit, $filter));
			}
			break;
		case "setwoodyn":
			$this->updateOption('bvWooDynSync', $_REQUEST['woodyn']);
			break;
		case "setserverid":
			$this->updateOption('bvServerId', $_REQUEST['serverid']);
			break;
		case "updatekeys":
			$this->addStatus("status", $this->updateKeys($_REQUEST['public'], $_REQUEST['secret']));
			break;
		case "addkeys":
			$this->addStatus("status", $this->addKeys($_REQUEST['public'], $_REQUEST['secret']));
			break;
		case "rmkeys":
			$this->addStatus("status", $this->rmKeys($_REQUEST['public']));
			break;
		case "getkeys":
			$this->addStatus("keys", $this->getKeys());
			break;
		case "setignorednames":
			switch ($_REQUEST['table']) {
			case "options":
				$this->updateOption('bvIgnoredOptions', $_REQUEST['names']);
				break;
			case "postmeta":
				$this->updateOption('bvIgnoredPostmeta', $_REQUEST['names']);
				break;
			}
			break;
		case "getignorednames":
			switch ($_REQUEST['table']) {
			case "options":
				$names = $this->getOption('bvIgnoredOptions');
				break;
			case "postmeta":
				$names = $this->getOption('bvIgnoredPostmeta');
				break;
			}
			$this->addStatus("names", $names);
			break;
		case "phpinfo":
			phpinfo();
			die();
			break;
		case "getposts":
			$post_type = urldecode($_REQUEST['post_type']);
			$args = array('numberposts' => 5, 'post_type' => $post_type);
			$posts = get_posts($args);
			$keys = array('post_title', 'guid', 'ID', 'post_date');
			foreach ($posts as $post) {
				$pdata = array();
				$post_array = get_object_vars($post);
				foreach ($keys as $key) {
					$pdata[$key] = $post_array[$key];
				}
				$this->addArrayToStatus("posts", $pdata);
				$this->addArrayToStatus("post_type", $post_type);
			}
			break;
		case "getstats":
			$this->addStatus("posts", get_object_vars(wp_count_posts()));
			$this->addStatus("pages", get_object_vars(wp_count_posts("page")));
			$this->addStatus("comments", get_object_vars(wp_count_comments()));
			break;
		case "getinfo":
			if (array_key_exists('wp', $_REQUEST)) {
				$pdata = $this->getWpInfo();
				$this->addStatus("wp", $pdata);
			}
			if (array_key_exists('plugins', $_REQUEST)) {
				if (!function_exists('get_plugins'))
					require_once (ABSPATH."wp-admin/includes/plugin.php");
				$plugins = get_plugins();
				foreach ($plugins as $plugin_file => $plugin_data) {
					$pdata = array(
						'file' => $plugin_file,
						'title' => $plugin_data['Title'],
						'version' => $plugin_data['Version'],
						'active' => is_plugin_active($plugin_file),
						'network' => $plugin_data['Network']
					);
					$this->addArrayToStatus("plugins", $pdata);
				}
			}
			if (array_key_exists('themes', $_REQUEST)) {
				$themes = function_exists('wp_get_themes') ? wp_get_themes() : get_themes();
				foreach($themes as $theme) {
					$pdata = $this->serializeThemeInfo($theme);
					$this->addArrayToStatus("themes", $pdata);
				}
			}
			if (array_key_exists('currenttheme', $_REQUEST)) {
				$theme = function_exists('wp_get_theme') ? wp_get_theme() : get_current_theme();
				$pdata = $this->serializeThemeInfo($theme);
				$this->addStatus("currenttheme", $pdata);
			}
			if (array_key_exists('system', $_REQUEST)) {
				$sys_info = array(
					'serverip' => $_SERVER['SERVER_ADDR'],
					'host' => $_SERVER['HTTP_HOST'],
					'phpversion' => phpversion(),
					'uid' => getmyuid(),
					'gid' => getmygid(),
					'user' => get_current_user()
				);
				if (function_exists('posix_getuid')) {
					$sys_info['webuid'] = posix_getuid();
					$sys_info['webgid'] = posix_getgid();
				}
				$this->addStatus("sys", $sys_info);
			}
			break;
		case "setsecurityconf":
			$new_conf = $_REQUEST['secconf'];
			if (!is_array($new_conf)) {
				$new_conf = array();
			}
			$this->updateOption('bvsecurityconfig', $new_conf);
			break;
		case "getsecurityconf":
			$new_conf = $this->getOption('bvsecurityconfig');
			$this->addStatus("secconf", $new_conf);
			break;
		case "describetable":
			$table = urldecode($_REQUEST['table']);
			$this->describeTable($table);
			break;
		case "checktable":
			$table = urldecode($_REQUEST['table']);
			$type = urldecode($_REQUEST['type']);
			$this->checkTable($table, $type);
			break;
		case "repairtable":
			$table = urldecode($_REQUEST['table']);
			$this->repairTable($table);
			break;
		case "tablekeys":
			$table = urldecode($_REQUEST['table']);
			$this->tableKeys($table);
			break;
		case "gettablecreate":
			$table = urldecode($_REQUEST['table']);
			$this->addStatus("create", $this->tableCreate($table));
			break;
		case "getrowscount":
			$table = urldecode($_REQUEST['table']);
			$this->addStatus("count", $this->rowsCount($table));
			break;
		case "updatedailyping":
			$value = $_REQUEST['value'];
			$this->addStatus("bvDailyPing", $this->updateDailyPing($value));
			break;
		case "disablebadge":
			$this->addStatus("disablebadge", $this->disableBadge());
			break;
		case "upgrade":
			$this->addStatus("upgrades", $bvmanager->upgrade($_REQUEST['args']));
			break;
		case "edit":
			$this->addStatus("edit", $bvmanager->edit($_REQUEST['args']));
			break;
		case "adduser":
			$this->addStatus("adduser", $bvmanager->addUser($_REQUEST['args']));
			break;
		case "install":
			$this->addStatus("install", $bvmanager->install($_REQUEST['args']));
			break;
		case "gettablecontent":
			$table = urldecode($_REQUEST['table']);
			$fields = urldecode($_REQUEST['fields']);
			$filter = urldecode($_REQUEST['filter']);
			$limit = intval(urldecode($_REQUEST['limit']));
			$offset = intval(urldecode($_REQUEST['offset']));
			$this->addStatus("rows", $this->getTableContent($table, $fields, $filter, $limit, $offset));
			break;
		case "getusers":
			$full = false;
			if (array_key_exists('full', $_REQUEST))
				$full = true;
			$this->addStatus("users", $this->getUsers($_REQUEST['args'], $full));
			break;
		case "autologin":
			$isHttps = false;
			if (array_key_exists('https', $_REQUEST))
				$isHttps = true;
			$this->addStatus("autologin", $this->autoLogin($_REQUEST['username'], $isHttps));
			break;
		case "gettransient":
			$transient = $this->getTransient($_REQUEST['name']);
			if ($transient && array_key_exists('asarray', $_REQUEST))
				$transient = $this->objectToArray($transient);
			$this->addStatus("transient", $transient);
			break;
		case "getoption":
			$this->addStatus("option", $this->getOption($_REQUEST['name']));
			break;
		case "writeable":
			$this->addStatus("writeable", $this->isServerWritable());
			break;
		case "getpremiumupdates":
			$this->addStatus("premiumupdates", $bvmanager->getPremiumUpdates());
			break;
		case "getpremiumupgradesinfo":
			$this->addStatus("premiumupgradesinfo", $bvmanager->getPremiumUpgradesInfo());
			break;
		case "wpupdateplugins":
			$this->addStatus("wpupdateplugins", wp_update_plugins());
			break;
		case "wpupdatethemes":
			$this->addStatus("wpupdatethemes", wp_update_themes());
			break;
		default:
			$this->addStatus("statusmsg", "Bad Command");
			$this->addStatus("status", false);
			break;
		}
		$this->terminate();
	}
}