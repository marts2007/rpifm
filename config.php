<?php
return array(
	'general' => array(
		'telegramkey' =>'376150922:AAGwFFHRo6yww1Lh6KaLjhmt7wwhb-WWKo4' //bot api key here

	),

	'ferms' => array(  //list of your ferms
		'rig1' => array(
			'miner'=>'claymore', //claymore or ewbf
			'host'=>'', //ip
			'port'=>'',	//miner api port
			'psw' => '', //monitoring password, leave empty if no password
			'gpu'=>5,		//number of gpu
			'critemp'=>75,	//critical temp of gpu
			'crispeed'=>20,	//alert hs speed
			'alerts' => 'true'	//watch speed and temp
		)
	)


);


?>
