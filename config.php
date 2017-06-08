<?php
return array(
	'general' => array(
		'telegramkey' =>'' //bot api key here

	),

	'ferms' => array(  //list of your ferms
		'rig1' => array(
			'miner'=>'claymore', //claymore or ewbf
			'host'=>'192.168.0.22', //ip
			'port'=>'3333',	//miner api port
			'psw' => '', //monitoring password, leave empty if no password
			'gpu'=>5,		//number of gpu
			'critemp'=>75,	//critical temp of gpu
			'crispeed'=>20,	//alert hs speed
			'alerts' => 'true'	//watch speed and temp
		),
		'rig2' => array(
			'miner'=>'ewbf', //claymore or ewbf
			'host'=>'192.168.0.23', //ip
			'port'=>'12345',	//miner api port
			'psw' => '', //monitoring password, leave empty if no password
			'gpu'=>5,		//number of gpu
			'critemp'=>75,	//critical temp of gpu
			'crispeed'=>290,	//alert hs speed
			'alerts' => 'true'	//watch speed and temp
		)
	)


);


?>
