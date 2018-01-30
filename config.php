<?php
return array(
	'general' => array(
		'telegramkey' =>'530253851:AAECfTQUi1DyuJU_ZtT1TZWJHauwQj3dNeI' //bot api key here

	),

	'farms' => array(  //list of your farms
		'rig0' => array(
			'miner'=>'ewbf', //claymore or ewbf
			'host'=>'x194529b98.51mypc.cn', //ip
			'port'=>'42000',	//miner api port
			'psw' => '', //monitoring password, leave empty if no password
			'gpu'=>6,		//number of gpu
			'critemp'=>60,	//critical temp of gpu
			'crispeed'=>500,	//alert hs speed
			'alerts' => 'true'	//watch speed and temp
		),
		'rig1' => array(
			'miner'=>'ewbf', //claymore or ewbf
			'host'=>'x194529b99.51mypc.cn', //ip
			'port'=>'42000',	//miner api port
			'psw' => '', //monitoring password, leave empty if no password
			'gpu'=>6,		//number of gpu
			'critemp'=>60,	//critical temp of gpu
			'crispeed'=>500,	//alert hs speed
			'alerts' => 'true'	//watch speed and temp
		),

	)

);

?>
