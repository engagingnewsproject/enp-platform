<?php

class BVHttpClient {
	var $user_agent = 'BVHttpClient';
	var $host;
	var $port;
	var $timeout = 20;
	var $errormsg = "";
	var $conn;
	var $mode;

	/**
	 * PHP5 constructor.
	 */
	function __construct() {
		global $blogvault;
		$sno = "";
		if (array_key_exists('svrno', $_REQUEST)) {
			$sno = intval($_REQUEST['svrno']);
			if (array_key_exists('mode', $_REQUEST)) {
				$this->mode = $_REQUEST['mode'];
			} else {
				$this->mode = "req";
			}
			if ($this->mode === "resp") {
				$blogvault->addStatus("mode", "resp");
				return;
			}
		} else {
			$this->timeout = 5;
			$sno = $blogvault->getOption('bvServerId');
			if (empty($sno)) {
				$sno = "1";
			}
		}
		$this->host = "pluginapi".$sno.".blogvault.net";
		$this->port = 80;
		if (defined('BV_APP_HOST')) {
			$this->host = BV_APP_HOST;	
		}
		if (defined('BV_APP_PORT')) {
			$this->port = BV_APP_PORT;	
		}
		if (array_key_exists('ssl', $_REQUEST)) {
			$this->port = 443;
			$this->host = $_REQUEST['ssl']."://".$host;
		}
		if (!$this->conn = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout)) {
			$this->errormsg = "Cannot Open Connection to Host";
			$blogvault->addStatus("httperror", "Cannot Open Connection to Host");
			return;
		}
		socket_set_timeout($this->conn, $this->timeout);
	}

	/**
	 * PHP4 constructor.
	 */
	function BVHttpClient() {
		BVHttpClient::__construct();
	}

	function streamedPost($url, $headers = array()) {
		$headers['Transfer-Encoding'] = "chunked";
		$this->sendRequest("POST", $url, $headers);
	}

	function sendChunk($data) {
		if ($this->mode === "resp") {
			echo("bvchunk:");
		}
		$this->write(sprintf("%x\r\n", strlen($data)));
		$this->write($data);
		$this->write("\r\n");
	}

	function closeChunk() {
		$this->sendChunk("");
	}

	function uploadChunkedFile($url, $field, $filename)
	{
		$this->multipartChunkedPost($url, array("Content-Disposition" => "form-data; name=\"".$field."\"; filename=\"".$filename."\"", "Content-Type" => "application/octet-stream"));
	}

	function multipartChunkedPost($url, $mph = array(), $headers = array()) {
		$rnd = rand(100000, 999999);
		$this->boundary = "----".$rnd;
		$prologue = "--".$this->boundary."\r\n";
		foreach($mph as $key=>$val) {
			$prologue .= $key.":".$val."\r\n";
		}
		$prologue .= "\r\n";
		$headers['Content-Type'] = "multipart/form-data; boundary=".$this->boundary;
		$this->streamedPost($url, $headers);
		$this->sendChunk($prologue);
	}

	function newChunkedPart($data) {
		if (strlen($data) > 0) {
			$chunk = "";
			if (isset($_REQUEST['checksum'])) {
				if ($_REQUEST['checksum'] == 'crc32') {
					$chunk = "CRC32" . ":" . crc32($data) . ":";
				} else if ($_REQUEST['checksum'] == 'md5') {
					$chunk = "MD5" . ":" . md5($data) . ":";
				}
			}
			$chunk .= (strlen($data) . ":" . $data);
			$this->sendChunk($chunk);
		}
	}

	function closeChunkedPart() {
		$epilogue = "\r\n\r\n--".$this->boundary."--\r\n";
		$this->sendChunk($epilogue);
		$this->closeChunk();
	}

	function write($data) {
		if ($this->mode === "resp") {
			echo($data);
		} else {
			fwrite($this->conn, $data);
		}
	}

	function get($url, $headers = array()) {
		return $this->request("GET", $url, $headers);
	}

	function post($url, $headers = array(), $body = "") {
		if(is_array($body)) {
			$b = "";
			foreach($body as $key=>$val) {
				$b .= $key."=".urlencode($val)."&";
			}
			$body = substr($b, 0, strlen($b) - 1);
		}
		if ($this->mode === "resp") {
			$this->sendChunk("bvpost:".$body);
		}
		return $this->request("POST", $url, $headers, $body);
	}

	function request($method, $url, $headers = array(), $body = null) {
		if (array_key_exists('bvapicheck', $_REQUEST)) {
			$url = $url."&bvapicheck=".$_REQUEST['bvapicheck'];
		} else {
			$url = $url."&bvdirect=true";
		}
		$this->sendRequest($method, $url, $headers, $body);
		return $this->getResponse();
	}

	function sendRequest($method, $url, $headers = array(), $body = null) {
		if ($this->mode === "resp") {
			return;
		}
		$def_hdrs = array("Connection" => "keep-alive",
			"Host" => $this->host);
		$headers = array_merge($def_hdrs, $headers);
		$request = strtoupper($method)." ".$url." HTTP/1.1\r\n";
		if (null != $body) {
			$headers["Content-length"] = strlen($body);
		}
		foreach($headers as $key=>$val) {
			$request .= $key.":".$val."\r\n";
		}
		$request .= "\r\n";
		if (null != $body) {
			$request .= $body;
		}
		$this->write($request);
		return $request;
	}

	function getResponse() {
		global $blogvault;
		$response = array();
		$response['headers'] = array();
		$state = 1;
		$conlen = 0;
		if ($this->mode === "resp") {
			return $response;
		}
		stream_set_timeout($this->conn, 300);
		while (!feof($this->conn)) {
			$line = fgets($this->conn, 4096);
			if (1 == $state) {
				if (!preg_match('/HTTP\/(\\d\\.\\d)\\s*(\\d+)\\s*(.*)/', $line, $m)) {
					$response['errormsg'] = "Status code line invalid: ".htmlentities($line);
					return $response;
				}
				$response['http_version'] = $m[1];
				$response['status'] = $m[2];
				$response['status_string'] = $m[3];
				$state = 2;
				$blogvault->addStatus("respstatus", $response['status']);
				$blogvault->addStatus("respstatus_string", $response['status_string']);
			} else if (2 == $state) {
				# End of headers
				if (2 == strlen($line)) {
					if ($conlen > 0)
						$response['body'] = fread($this->conn, $conlen);
					return $response;
				}
				if (!preg_match('/([^:]+):\\s*(.*)/', $line, $m)) {
					// Skip to the next header
					continue;
				}
				$key = strtolower(trim($m[1]));
				$val = trim($m[2]);
				$response['headers'][$key] = $val;
				if ($key == "content-length") {
					$conlen = intval($val);
				}
			}
		}
		return $response;
	}
}