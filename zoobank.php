<?php

// Harvest from ZooBank
	
require_once (dirname(__FILE__) . '/HtmlDomParser.php');

use Sunra\PhpSimple\HtmlDomParser;

$cache = dirname(__FILE__) . '/zoobank';

//----------------------------------------------------------------------------------------
function get($url)
{
	$data = null;
	
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	return $data;
}

//----------------------------------------------------------------------------------------
//http://www.php.net/manual/en/function.rmdir.php#107233
function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
 }

//----------------------------------------------------------------------------------------
// http://stackoverflow.com/questions/247678/how-does-mediawiki-compose-the-image-paths
function sha1_to_path_array($sha1)
{
	preg_match('/^(..)(..)(..)/', $sha1, $matches);
	
	$sha1_path = array();
	$sha1_path[] = $matches[1];
	
	//$sha1_path[] = $matches[2];
	//$sha1_path[] = $matches[3];

	return $sha1_path;
}

//----------------------------------------------------------------------------------------
// Return path for a sha1
function sha1_to_path_string($sha1)
{
	$sha1_path_parts = sha1_to_path_array($sha1);
	
	$sha1_path = '/' . join("/", $sha1_path_parts);

	return $sha1_path;
}

//----------------------------------------------------------------------------------------
// Create nested folders in folder "root" based on sha1
function create_path_from_sha1($sha1, $root = '.')
{	
	$sha1_path_parts 	= sha1_to_path_array($sha1);
	$sha1_path 			= sha1_to_path_string($sha1);
	$filename 			= $root . $sha1_path;
				
	// If we dont have file, create directory structure for it	
	if (!file_exists($filename))
	{
		$path = $root;
		$path .= '/' . $sha1_path_parts[0];
		if (!file_exists($path))
		{
			$oldumask = umask(0); 
			mkdir($path, 0777);
			umask($oldumask);
		}
		
		/*
		$path .= '/' . $sha1_path_parts[1];
		if (!file_exists($path))
		{
			$oldumask = umask(0); 
			mkdir($path, 0777);
			umask($oldumask);
		}
		$path .= '/' . $sha1_path_parts[2];
		if (!file_exists($path))
		{
			$oldumask = umask(0); 
			mkdir($path, 0777);
			umask($oldumask);
		}
		*/
		
	}
	
	return $filename;
}

//----------------------------------------------------------------------------------------
// Clean a DOI
function clean_doi($doi)
{
	$doi = preg_replace('/https?:\/\/(dx\.)?doi.org\//i', '', $doi);
	
	$doi = strtolower($doi);
	
	return $doi;
}

//----------------------------------------------------------------------------------------
function zoobank_fetch($uuid, $force = false, $debug = false)
{
	global $cache;
	
	$skip_list = array(
		'91bd42d4-90f1-4b45-9350-eef175b1727a'
	);	
	
	$basedir = $cache;
	
	$uuid = strtolower($uuid);
	
	if (in_array($uuid, $skip_list) && $debug)
	{
		echo "\n\n*** skip ***\n\n";	
		return;
	}
	
	$uuid_path = create_path_from_sha1($uuid, $basedir);
		
	$json_filename = $uuid_path . '/' . $uuid . '.json';
	$html_filename = $uuid_path . '/' . $uuid . '.html';

	$go = true;
	
	if (file_exists($json_filename) && !$force)
	{
		if ($debug)
		{
			echo "File exists\n";
		}
		$go = false;
	}
	
	if ($go)
	{
		// JSON
		$url = 'https://zoobank.org/References.json/' . strtolower($uuid);	
		$json = get($url);	
		
		if ($json != '')
		{
		
			// Remove array
			$json = preg_replace('/^\[/u', '', $json);				
			$json = preg_replace('/\]$/u', '', $json);

			file_put_contents($json_filename, $json);
		}
		
		// HTML
		$url = 'https://zoobank.org/References/' . strtolower($uuid);	
		$html = get($url);	
		
		if ($html != '')
		{
			file_put_contents($html_filename, $html);
		}		
	}	
}


//----------------------------------------------------------------------------------------
function zoobank_retrieve($uuid)
{
	global $cache;
	
	$files = array();
		
	$basedir = $cache;
	
	$uuid = strtolower($uuid);
	
	$uuid_path = create_path_from_sha1($uuid, $basedir);
		
	$json_filename = $uuid_path . '/' . $uuid . '.json';
	$html_filename = $uuid_path . '/' . $uuid . '.html';

	$go = true;
	
	if (!file_exists($json_filename) || !file_exists($html_filename))
	{
		zoobank_fetch($uuid);
	}
	
	if (file_exists($json_filename) || file_exists($html_filename))
	{
		$files['json'] = file_get_contents($json_filename);
		$files['html'] = file_get_contents($html_filename);
	}
	
	
	return $files;
}

//----------------------------------------------------------------------------------------
function zoobank_have($uuid)
{
	global $cache;
	
	$have = true;
	
	$files = array();
		
	$basedir = $cache;
	
	$uuid = strtolower($uuid);
	
	$uuid_path = create_path_from_sha1($uuid, $basedir);
		
	$json_filename = $uuid_path . '/' . $uuid . '.json';
	$html_filename = $uuid_path . '/' . $uuid . '.html';

	$go = true;
	
	if (!file_exists($json_filename) || !file_exists($html_filename))
	{
		$have = false;
	}

	return $have;	
}



//----------------------------------------------------------------------------------------
function zoobank_to_csl($uuid)
{
	$csl = null;
	
	$files = zoobank_retrieve($uuid);

	//print_r($files);

	if (count($files) == 2)
	{
		$obj = json_decode($files['json']);

		//print_r($obj);
		
		$csl = null;
		
		if ($obj)
		{

			$csl = new stdclass;

			foreach ($obj as $k => $v)
			{
				if ($v != '')
				{
					switch ($k)
					{
						case 'referenceuuid':
							$csl->id = $v;
							$csl->ZOOBANK = strtoupper($v);
							$csl->URL = 'https://zoobank.org/References/' . $obj->referenceuuid;
							break;
			
						case 'volume':
							$csl->{$k} = $v;
							break;

						case 'title':
							$csl->{$k} = strip_tags($v);
							break;

						case 'number':
							$csl->issue = $v;
							break;

						case 'startpage':
							$csl->{'page-first'} = $v;
					
							if (!isset($csl->page))
							{
								$csl->page = $v;
							}
							else
							{
								$csl->page = $v . '-' . $csl->page;
							}
							break;

						case 'endpage':
							if (!isset($csl->page))
							{
								$csl->page = $v;
							}
							else
							{
								$csl->page .= '-' . $v;
							}
							break;

						case 'parentreference':
							$csl->{'container-title'} = $v;
							break;

						case 'lsid':
							$csl->LSID = $v;
							break;
					
						case 'year':
							if (!isset($csl->issued))
							{
								$csl->issued = new stdclass;
								$csl->issued->{'date-parts'} = array();
								$csl->issued->{'date-parts'}[0] = array();						
							}
							$csl->issued->{'date-parts'}[0][] = (Integer)$v;
							break;
						
						case 'authors':
							foreach ($v as $author_array)
							{
								$author = new stdclass;
							
								if (isset($author_array[0]->familyname))
								{
									$author->family = $author_array[0]->familyname;
								}

								if (isset($author_array[0]->givenname))
								{
									$author->given = $author_array[0]->givenname;
								}

								if (isset($author_array[0]->gnubuuid))
								{
									$author->ZOOBANK = $author_array[0]->gnubuuid;
								}
							
								$csl->author[] = $author;
							}
							break;
			
						default:
							break;
					}
				}
			}
		}
		
		if ($csl)
		{

			// HTML has some additional stuff such as DOI and a more precise date
			$dom = HtmlDomParser::str_get_html($files['html']);

			if ($dom)
			{	
				foreach ($dom->find('tr th[class=entry_label]') as $th)
				{
					switch (trim($th->plaintext))
					{
						case 'Journal:':
							$value = trim($th->next_sibling()->plaintext);
							if (preg_match_all('/(?<issn>[0-9]{4}-[0-9]{3}([0-9]|X))/', $value, $m))
							{
								foreach ($m['issn'] as $issn)
								{
									$csl->ISSN[] = $issn;
								}
								$csl->ISSN = array_unique($csl->ISSN);
								$csl->ISSN = array_values($csl->ISSN);
							}
							break;
				
						case 'Date Published:':
							$value = trim($th->next_sibling()->plaintext);
						
							// date
							if (preg_match('/(?<date>\d+\s+[A-Z]\w+\s+[0-9]{4})/', $value))
							{
								$value = preg_replace('/\s\s+/', ' ', $value);
								$dateTime = date_create_from_format('d F Y', $value);
								$Ymd = date_format($dateTime, 'Y-m-d');
							
								if (!isset($csl->issued))
								{
									$csl->issued = new stdclass;
									$csl->issued->{'date-parts'} = array();													
								}
								$csl->issued->{'date-parts'}[0] = array();	
							
								$parts = explode("-", $Ymd);
								foreach ($parts as $part)
								{
									$csl->issued->{'date-parts'}[0][] = (Integer)$part;
								}
							}
							break;

						case 'DOI:':
							$doi = trim($th->next_sibling()->plaintext);
							$doi = clean_doi($doi);
							if ($doi != '')
							{
								$csl->DOI = $doi;
							}
							break;
		
						default:
							break;
					}

				}
			}
		}

	}

	return $csl;
	
}




//----------------------------------------------------------------------------------------

if (0)
{
	$uuids = array(
	'0AB9F97C-399D-484D-B878-E7B569E3ED3C',
	'592A67E2-F023-4D77-AF2D-82636E9087C6',
	);

	$force = false;
	$debug = false;

	$count = 1;

	foreach ($uuids as $uuid)
	{
		if ($debug)
		{
			echo $uuid  . "\n";
		}

		zoobank_fetch($uuid, $force, $debug);
	
		$csl = zoobank_to_csl($uuid);
	
		// print_r($csl);
	
		// echo json_encode($csl);
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

?>
