<?php
class claymore {


public $host;
public $port;
public $gpu;
public $psw;
public $critemp;
public $crispeed;
public $name;
public $alerts;
public $connecttry=0;
public $notice=array('temp'=>false,'speed'=>false,'connect'=>false);


	public function __construct($params=array()){
			$this->host = $params['host'];
			$this->port = $params['port'];
			$this->gpu = $params['gpu'];
			$this->critemp = $params['critemp'];
			$this->name = $params['name'];
			$this->alerts = $params['alerts'];
			$this->crispeed = $params['crispeed'];
			$this->psw = $params['psw'];
			if ($this->psw)
				$this->psw=',"psw":"'.$this->psw.'"';
	}

	public function getData(){

		$tcp = new tcpRequest(array('host'=>$this->host,'port'=>$this->port));

		$request = ('{"id":0,"jsonrpc":"2.0","method":"miner_getstat1"'.$this->psw.'}'."\n");
		//var_dump($request);

		$result=json_decode($tcp->send($request));

		if (isset($result->result)){
			$this->connecttry=0;
			//var_dump($result);
			$temps=explode(';',$result->result['6']);
			$ethhashrates=explode(';',$result->result['3']);
			$dcrhashrates=explode(';',$result->result['5']);
			//var_dump($temps);
			$text="/".$this->name." ".PHP_EOL;
			for($i=0;$i<$this->gpu;$i++) {
				$gpu[$i]=array('temp'=>$temps[$i*2],'cooler'=>$temps[$i+2],'erate'=>round($ethhashrates[$i]/1000,2),'drate'=>$dcrhashrates[$i]);
				$text.="GPU".$i."\t".$gpu[$i]['temp']."°(".$gpu[$i]['cooler']."%)\t".$gpu[$i]['erate']." hs / ".$gpu[$i]['drate']." hs\r\n";
			}
			//var_dump($result);

			//$gpu[0]=array('temp'=>$temps[0],'cooler'=>$temps[$i+2],'erate'=>round($ethhashrates[$i]/1000,2),'drate'=>$dcrhashrates[$i]);



			/*
			$text.="GPU0\t".$gpu[0]['temp']."°(".$gpu[0]['cooler']."%)\t".$gpu[0]['erate']." hs / ".$gpu[0]['drate']." hs\r\n";
			$text.="GPU1\t".$gpu[1]['temp']."°(".$gpu[1]['cooler']."%)\t".$gpu[1]['erate']." hs / ".$gpu[1]['drate']." hs\r\n";
			$text.="GPU2\t".$gpu[2]['temp']."°(".$gpu[2]['cooler']."%)\t".$gpu[2]['erate']." hs / ".$gpu[2]['drate']." hs\r\n";
			$text.="GPU3\t".$gpu[3]['temp']."°(".$gpu[3]['cooler']."%)\t".$gpu[3]['erate']." hs / ".$gpu[3]['drate']." hs\r\n";	*/
			return array('text'=>$text,'stat'=>$gpu);
			/*$request=('http://srv2.inshell.ru/temp/farm6.php?t0='.(float)$resar[0]['t'].
			'&t0s='.(float)$resar[0]['speed'].
			'&t1='.(float)$resar[1]['t'].
			'&t1s='.(float)$resar[1]['speed'].
			'&t2='.(float)$resar[2]['t'].
			'&t2s='.(float)$resar[2]['speed'].
			'&t3='.(float)$resar[3]['t'].
			'&t3s='.(float)$resar[3]['speed']);
			file_get_contents($request);*/
		} else {
				$this->connecttry++;
		}
		return null;


	}


	public function checkTemp(){
		$data = $this->getData();

		$text='';
		if ($this->connecttry >= 5 && !$this->notice['connect']) {
				//не смогли подключиться к майнеру, нет данных :(
				$text.="Can not connect to ".$this->name.", looks like it`s down!\r\n";
				$this->notice['connect']=true;
		} else {
				if (is_array($data)) {
					if ($this->notice['connect']) {
						$this->notice['connect']=false;
						$text.='Connection to the '.$this->name.' established, it`s online!';
					}

					//проверка температур
					$stateok=array();

					foreach($data['stat'] as $gpu) {

						if ($gpu['temp'] > $this->critemp) {
							$stateok[]=false;
						} else {
								if ($this->critemp - $gpu['temp'] > 3 || (!$this->notice['temp'])) {
									$stateok[]=true;
								} else $stateok[]=false;
						}
					}

					//var_dump($stateok);
					if (in_array(false,$stateok)) {
						echo 'something wrong'."\r\n";
						if (!$this->notice['temp']) {
							$this->notice['temp']=true;
							$text.="Too high temperature on  ".$this->name."!(>".$this->critemp.")\r\n";
							$text.=$data['text']."\r\n";
						}
					} else {
						if ($this->notice['temp']==true){
							$this->notice['temp']=false;
							$text.=$this->name." is fine again!\r\n";
							$text.=$data['text']."\r\n";
						}

					}

				}
		}
		return $text;
	}



	public function checkSpeed(){
		$data = $this->getData();

		$text='';
		if ($this->connecttry >= 5 && !$this->notice['connect']) {
				//не смогли подключиться к майнеру, нет данных :(
				$text.="Can not connect to ".$this->name.", looks like it`s down!\r\n";
				$this->notice['connect']=true;
		} else {
				if (is_array($data)) {
					if ($this->notice['connect']) {
						$this->notice['connect']=false;
						$text.='Connection to the '.$this->name.' established, it`s online!';
					}

					//проверка температур
					$stateok=array();

					foreach($data['stat'] as $gpu) {

						if ($gpu['erate'] < $this->crispeed) {
							$stateok[]=false;
						} else {
								if ($gpu['erate']-$this->crispeed > 3 || (!$this->notice['speed'])) {
									$stateok[]=true;
								} else $stateok[]=false;
						}
					}

					//var_dump($stateok);
					if (in_array(false,$stateok)) {
						echo 'something wrong '.$this->name." \r\n";
						if (!$this->notice['speed']) {
							$this->notice['speed']=true;
							$text.="Hashrate of ".$this->name." is too low! (<".$this->crispeed.")\r\n";
							$text.=$data['text']."\r\n";
						}
					} else {
						if ($this->notice['speed']==true){
							$this->notice['speed']=false;
							$text.=$this->name." is fine again!\r\n";
							$text.=$data['text']."\r\n";
						}

					}

				}
		}
		return $text;
	}

}


?>
