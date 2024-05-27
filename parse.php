<?php

// Parse and extract as CSL-JSON, output as .jsonl

require_once (dirname(__FILE__) . '/zoobank.php');

//----------------------------------------------------------------------------------------

$filename = 'dataset-2037/taxon.txt';

$headings = array();

$row_count = 0;

$count = 1;

$file = @fopen($filename, "r") or die("couldn't open $filename");
		
$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$row = fgetcsv(
		$file_handle, 
		0, 
		"\t" 
		);
		
	$go = is_array($row);
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
		
			// print_r($obj);	
			
			if (isset($obj->namePublishedInID))
			{
				// echo "\n\n" . $obj->namePublishedInID . "\n\n";
				
				// check that we have it
				if (!zoobank_have($obj->namePublishedInID))
				{				
					zoobank_fetch($obj->namePublishedInID, false);
				}
				
				if (zoobank_have($obj->namePublishedInID))
				{	
					$csl = zoobank_to_csl($obj->namePublishedInID);
	
					echo json_encode($csl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
				}				
				
			}
		}
	}	
	$row_count++;
}
?>
