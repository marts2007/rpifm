<?php
return array(
	'general' => array(
		'telegramkey' =>'' //bot api key here

	),

	'ferms' => array(  //list of your ferms
		'ferm1' => array(
			'miner'=>'claymore', //claymore or ewbf
			'host'=>'192.168.0.41', //ip
			'port'=>'3333',	//miner api port
			'gpu'=>5,		//number of gpu
			'critemp'=>75,	//critical temp of gpu
			'crispeed'=>20,	//alert hs speed
			'alerts' => 'true'	//watch speed and temp
		),

		'ferm2' => array(
            'miner'=>'ewbf',
            'host'=>'192.168.0.22',
            'port'=>'3334',
            'gpu'=>3,
			'crispeed'=>295,
            'critemp'=>75, 'alerts' => 'true' )
	)


);


?>
