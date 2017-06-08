<?php

class ewbf {

public $host;
public $port;
public $gpu;
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
			$this->crispeed = $params['crispeed'];
			$this->name = $params['name'];
			$this->alerts = $params['alerts'];


	}

	public function getData(){
		$tcp = new tcpRequest(array('host'=>$this->host,'port'=>$this->port));
		$result=json_decode($tcp->send('{"id":1, "method":"getstat"}'."\n"));
		$resar = array();
		if (isset($result->result)){
			$this->connecttry=0;
			foreach($result->result as $card) {
				//var_dump($card);
				$resar[$card->gpuid]=array('temp'=> $card->temperature , 'speed'=>$card->speed_sps,'gpu_power_usage'=>$card->gpu_power_usage);
				//echo $card->temperature;
			}
		} else {
			$this->connecttry++;
		}
		if (count($resar)) {

			$text=$this->name." statistic\r\n";
			for ($i=0; $i<(int)$this->gpu;$i++) {
				//var_dump( $i);
				$text.="GPU$i\t".$resar[$i]['temp']."°\t".$resar[$i]['speed']." Sol/s ".$resar[$i]['gpu_power_usage']."w\r\n";
			}

			return array('text'=>$text,'stat'=>$resar);
		} 
		return null;
		//var_dump($resar);
		//$tcp->connect;
	


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
							$text.="Looks like something is wrong with ".$this->name."!\r\n";
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
							
						if ($gpu['speed'] < $this->crispeed) {
							$stateok[]=false;
						} else {
								if ($gpu['speed'] - $this->crispeed  > 5 || (!$this->notice['speed'])) {
									$stateok[]=true;
								} else $stateok[]=false;
						}
					}

					//var_dump($stateok);
					if (in_array(false,$stateok)) {
						echo 'something wrong'."\r\n";
						if (!$this->notice['speed']) {
							$this->notice['speed']=true;
							$text.="Hashrate of ".$this->name." is too low!\r\n";
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
