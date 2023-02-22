<?php

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
				echo "\n\n" . $obj->namePublishedInID . "\n\n";
				
				if (!zoobank_have($obj->namePublishedInID))
				{
				
					zoobank_fetch($obj->namePublishedInID, false);
	
					$csl = zoobank_to_csl($obj->namePublishedInID);
	
					echo json_encode($csl, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				
					// Give server a break every 10 items
					if (($count++ % 10) == 0)
					{
						$rand = rand(1000000, 3000000);
						echo "\n ...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
						usleep($rand);
					}
				}				
				
			}
		}
	}	
	$row_count++;
}
?>
