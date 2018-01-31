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
      $start_date = new DateTime("@$result->start_time");
      $now_date=new DateTime("now");
      $difference = $now_date->diff($start_date);
      $uptime = $difference->format("%a").'d'
                .str_pad($difference->h, 2, '0', STR_PAD_LEFT).':'
                .str_pad($difference->i, 2, '0', STR_PAD_LEFT).':'
                .str_pad($difference->s, 2, '0', STR_PAD_LEFT);

			foreach($result->result as $card) {
				//var_dump($card);
				$resar[$card->gpuid]=array('temp'=> $card->temperature
                                  , 'speed'=>$card->speed_sps
                                  ,'gpu_power_usage'=>$card->gpu_power_usage);
			}
		} else {
			$this->connecttry++;
		}

		if (count($resar)) {
			$max_temp=0;
			$tot_speed=0;
			$tot_power=0;
			$text="/".$this->name." Uptime: ".$uptime."\r\n";
			for ($i=0; $i<(int)$this->gpu;$i++) {
				//var_dump( $i);
				$text.="GPU$i\t".$resar[$i]['temp']."°\t"
                                  .$resar[$i]['speed']." Sol/s "
                                  .$resar[$i]['gpu_power_usage']."W\r\n";
        if ($resar[$i]['temp']>$max_temp)
				{
					$max_temp=$resar[$i]['temp'];
				}
				$tot_speed+=$resar[$i]['speed'];
				$tot_power+=$resar[$i]['gpu_power_usage'];
			}
			$text.="Total:\t".(int)($max_temp)."°\t"
			  .$tot_speed." Sol/s "
				.$tot_power."W\r\n";

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
				$text.="Can not connect to /".$this->name.", looks like it`s down!".PHP_EOL;
				$this->notice['connect']=true;
		} else {
				if (is_array($data)) {
					if ($this->notice['connect']) {
						$this->notice['connect']=false;
						$text.='Connection to the /'.$this->name.' established, it`s online!'.PHP_EOL;
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
						echo "Temperature of ".$this->name." is too high!".PHP_EOL;
						if (!$this->notice['temp']) {
							$this->notice['temp']=true;
							$text.="Temperature of /".$this->name." is too high!".PHP_EOL;
							//$text.=$data['text']."\r\n";
						}
					} else {
						if ($this->notice['temp']==true){
							$this->notice['temp']=false;
							$text.="/".$this->name." is fine again!".PHP_EOL;
							//$text.=$data['text']."\r\n";
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
				$text.="Can not connect to /".$this->name.", looks like it`s down!".PHP_EOL;
				$this->notice['connect']=true;
		} else {
				if (is_array($data)) {
					if ($this->notice['connect']) {
						$this->notice['connect']=false;
						$text.='Connection to the /'.$this->name.' established, it`s online!'.PHP_EOL;
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
						echo "Hashrate of ".$this->name." is too low!".PHP_EOL;
						if (!$this->notice['speed']) {
							$this->notice['speed']=true;
							$text.="Hashrate of /".$this->name." is too low!".PHP_EOL;
							//$text.=$data['text']."\r\n";
						}
					} else {
						if ($this->notice['speed']==true){
							$this->notice['speed']=false;
							$text.="/".$this->name." is fine again!".PHP_EOL;
							//$text.=$data['text']."\r\n";
						}
					}
				}
		}
		return $text;
	}

}

?>
