<?php

// Process Zoobank records, generate CSL, extract data

require_once (dirname(__FILE__) . '/zoobank.php');

$basedir =  dirname(__FILE__) . '/zoobank';

$files1 = scandir($basedir);

$files1 = array('ca');


$since = 0;

// yesterday
//$since = strtotime('-1 day');

foreach ($files1 as $directory)
{
	if (preg_match('/^[a-z0-9]+$/', $directory))
	{	
		// echo "\n$directory\n";
		
		$files2 = scandir($basedir . '/' . $directory);
		
		foreach ($files2 as $filename)
		{
			if (preg_match('/(?<uuid>.*)\.json/', $filename, $m))
			{
				$uuid = $m['uuid'];
		
				$csl = zoobank_to_csl($uuid);
			
				echo json_encode($csl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
			}
		}
	}
}

?>
