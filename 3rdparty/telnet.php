<?php

class telnet_Creality_Box {
	//file handler for socket
	var $fp = NULL;

	function telnet() {
		$this->fp = NULL;
	}

	public function telnetConnect($ip, $port, &$errno, &$errstr) {
		$this->telnetDisconnect();
		$this->fp = fsockopen($ip, $port, $errno, $errstr);
		if(!$this->fp) {
			return false;
		}
		return true;
	}

	public function telnetSendCommand($command,&$response) {
		if ($this->fp) {
			fputs($this->fp,"$command\r");
			usleep(200000);
			$this->telnetReadResponse($response);
		}
		return $this->fp?1:0;
	}

	public function telnetDisconnect() {
		if ($this->fp) {
			$this->telnetSendCommand('exit',$result);
			fclose($this->fp);
			$this->fp=NULL;
		}
	}

	public function telnetReadResponse(&$response) {
		$response='';
		do {
			$response.=fread($this->fp,1000);
			$status=socket_get_status($this->fp);
		} while ($status['unread_bytes']);
	}
}

?>
