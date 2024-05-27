<?php

// Parse and extract as CSL-JSON, output as .jsonl

require_once (dirname(__FILE__) . '/zoobank.php');

//----------------------------------------------------------------------------------------

$uuids = array(

'04e60525-4141-4037-b739-394f07318f8b',
'0a0bbf5d-87a7-4ddc-9241-bdd2e2a069ab',
'0f159313-4b16-488b-acd4-0db95a8354fb',
'29fc2c47-247c-47b6-9d86-753a8f7071d4',
'2c31276a-f274-479d-a19b-69afc1d47730',
'3137d245-49fb-43cc-a9f0-da95adaffa34',
'3eebbf20-5023-4f0e-baaf-2dbbe3b7282d',
'48867f27-c53f-46c7-a6f8-ac2744f6a34a',
'7637b923-c8ac-4924-926c-8ea8d8543d8e',
'9f67a870-56d3-4924-ad75-49585d9c0cb5',
'A103E58E-7DCA-413E-BFC3-1F422B04169F',
'be7942eb-edac-4afc-a511-80053eca63ca',
'beee74c8-0f4b-4e25-9bad-bc2bcd6cfa3e',
'd293a506-6640-4181-b37b-179e18eecce2',
'f17ec277-1027-4510-b547-f1c4d71494ea',
'f2f765d5-e3cc-4299-b412-097c40871c10',
);

foreach ($uuids as $uuid)
{				
	// check that we have it
	if (!zoobank_have($uuid))
	{				
		zoobank_fetch($uuid, false);
	}
	
	if (zoobank_have($uuid))
	{	
		$csl = zoobank_to_csl($uuid);

		echo json_encode($csl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
	}				
}

?>
