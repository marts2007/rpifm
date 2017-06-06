<?php

class tcpRequest {
	private $ip;
	private $port;
	private $socket;

	public function __construct($settings){
		$this->ip = gethostbyname($settings['host']);
		$this->port = $settings['port'];

	}

	public function send($msg){
		if (!$this->socket) {
			$this->connect();
		}
		@socket_write($this->socket, $msg, strlen($msg));
		$out = '';
		$packet='';
		while ($out = @socket_read($this->socket, 2048)) {
			$packet.=$out;
		}
		//echo "Закрываем сокет...";
		socket_close($this->socket);
		return $packet;
	}

	private function connect(){
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($this->socket === false) {
			echo "Не удалось выполнить socket_create(): причина: " . socket_strerror(socket_last_error()) . "\n";
		}	
		$result = @socket_connect($this->socket, $this->ip, $this->port);
		if ($result === false) {
			//echo "Не удалось выполнить socket_connect().\nПричина: ($result) " . socket_strerror(@socket_last_error($socket)) . "\n";
		}
	}

}
?>
