<?php
/***
 * ReloadLab PHP XML Sitemap Generator
 * Based on iProDev PHP XML Sitemap Generator 
 * (http://iprodev.github.io/PHP-XML-Sitemap-Generator/)
 * Simple site crawler to create a search engine XML Sitemap.
 * Version 1.1.0
 * Free to use, without any warranty.
 * Written by Reload (Domenico Gigante) https://www.reloadlab.it 24/Feb/2021.
 ***/

// To scan large sites (more than 1,000 pages) it can take more than 30 seconds. 
// This avoids "PHP Fatal error: Maximum execution time of 30 seconds exceeded"
set_time_limit(0);

// To scan large sites (more than 1,000 pages) can require a lot of memory. 
// Change memory_limit so that the script does not hang before the scan is finished 
// with "PHP Fatal error: Allowed memory size of x bytes exhausted"
ini_set('memory_limit', '256M');

// This script use PHP Simple HTML DOM Parser (https://simplehtmldom.sourceforge.io/) 
// version 1.7 o 1.9, if PHP 5.6+ is available
require_once('simple_html_dom.php');

// Set true or false to define how the script is used.
// true:  As CLI script.
// false: As Website script.
define('CLI', true);
define('NL', CLI? "\n": '<br>');

define('VERSION', '1.1.0');

// Default config ==========================
// Set the start URL. Here is http used, use https:// for 
// SSL websites.
$start_url = 'https://www.example.com/';       

// Set the output file name.
$file = 'sitemap.xml';

// Set the output folder
$folder = '';

// Scan frequency
$frequency = 'weekly';

// Page priority
$priority = '1';

// Number of urls scanned before updating xml file
$max_url_write = 100;

// Number of old sitemap xml files to keep before deleting them.
// 0 for none rotation
$num_rotation = 4;

// CURL debug: Set to 1 for verbose
$debug = false;

// Define here the URLs to skip. All URLs that start with 
// the defined URL will be skipped too.
// Example: 'https://www.reloadlab.it/print' will also skip
// https://www.reloadlab.it/print/bootmanager.html
$skip = array(
	//
);

// Define what file types should be scanned.
$extension = array(
	'.html', 
	'.php',
	'/',
); 

// Pass parameters as cli arguments or GET/POST variables
if(PHP_SAPI == 'cli'){
	
	$short_options = 'u::f::d::';
	$long_options = array(
		'url::', 
		'file::', 
		'folder::', 
		'frequency::', 
		'priority::', 
		'max_url_write::',
		'num_rotation::',
		'debug',
		'skip::',
		'extension::'
	);
	$options = getopt($short_options, $long_options);
	
	// start_url
	if(isset($options['u']) || isset($options['url'])){
		
		$tmp1 = isset($options['u'])? $options['u']: $options['url'];
		if(is_string($tmp1)){
			
			$start_url = $tmp1;
		}
	}
	
	// file
	if(isset($options['f']) || isset($options['file'])){
		
		$tmp2 = isset($options['f'])? $options['f']: $options['file'];
		if(is_string($tmp2)){
			
			$file = $tmp2;
		}
	}
	
	// folder
	if(isset($options['d']) || isset($options['folder'])){
		
		$tmp3 = isset($options['d'])? $options['d']: $options['folder'];
		if(is_string($tmp3)){
			
			$folder = $tmp3;
		}
	}
	
	// frequency
	if(isset($options['frequency'])){
		
		if(is_string($options['frequency']) 
			&& in_array($options['frequency'], array(
				'Always',
				'Hourly',
				'Daily',
				'Weekly',
				'Monthly',
				'Yearly',
				'Never'
			))
		){
			$frequency = $options['frequency'];
		}
	}
	
	// priority
	if(isset($options['priority'])){
		
		if(is_numeric($options['priority']) 
			&& $options['priority'] >= 0 
			&& $options['priority'] <= 1
		){
			$priority = $options['priority'];
		}
	}
	
	// max_url_write
	if(isset($options['max_url_write'])){
		
		if(is_numeric($options['max_url_write']) 
			&& $options['max_url_write'] > 0
		){
			$max_url_write = (int) $options['max_url_write'];
		}
	}
	
	// num_rotation
	if(isset($options['num_rotation'])){
		
		if(is_numeric($options['num_rotation']) 
			&& $options['num_rotation'] >= 0
		){
			$num_rotation = (int) $options['num_rotation'];
		}
	}
	
	// debug
	if(isset($options['debug'])){
		
		if(is_bool($options['debug'])){
			
			$debug = true;
		}
	}
	
	// skip
	if(isset($options['skip'])){
		
		if(is_string($options['skip'])){
			
			$skip = array_map('trim', explode(',', $options['skip']));
		}
	}
	
	// extension
	if(isset($options['extension'])){
		
		if(is_string($options['extension'])){
			
			$extension = array_map('trim', explode(',', $options['extension']));
		}
	}
} else{
	
	// start_url
	if(isset($_REQUEST['u']) || isset($_REQUEST['url'])){
		
		$tmp1 = isset($_REQUEST['u'])? $_REQUEST['u']: $_REQUEST['url'];
		if(is_string($tmp1)){
			
			$start_url = $tmp1;
		}
	}
	
	// file
	if(isset($_REQUEST['f']) || isset($_REQUEST['file'])){
		
		$tmp2 = isset($_REQUEST['f'])? $_REQUEST['f']: $_REQUEST['file'];
		if(is_string($tmp2)){
			
			$file = $tmp2;
		}
	}
	
	// folder
	if(isset($_REQUEST['d']) || isset($_REQUEST['folder'])){
		
		$tmp3 = isset($_REQUEST['d'])? $_REQUEST['d']: $_REQUEST['folder'];
		if(is_string($tmp3)){
			
			$folder = $tmp3;
		}
	}
	
	// frequency
	if(isset($_REQUEST['frequency'])){
		
		if(is_string($_REQUEST['frequency']) 
			&& in_array($_REQUEST['frequency'], array(
				'Always',
				'Hourly',
				'Daily',
				'Weekly',
				'Monthly',
				'Yearly',
				'Never'
			))
		){
			$frequency = $_REQUEST['frequency'];
		}
	}
	
	// priority
	if(isset($_REQUEST['priority'])){
		
		if(is_numeric($_REQUEST['priority']) 
			&& $_REQUEST['priority'] >= 0 
			&& $_REQUEST['priority'] <= 1
		){
			$priority = $_REQUEST['priority'];
		}
	}
	
	// max_url_write
	if(isset($_REQUEST['max_url_write'])){
		
		if(is_numeric($_REQUEST['max_url_write']) 
			&& $_REQUEST['max_url_write'] > 0
		){
			$max_url_write = (int) $_REQUEST['max_url_write'];
		}
	}
	
	// num_rotation
	if(isset($_REQUEST['num_rotation'])){
		
		if(is_numeric($_REQUEST['num_rotation']) 
			&& $_REQUEST['num_rotation'] >= 0
		){
			$num_rotation = (int) $_REQUEST['num_rotation'];
		}
	}
	
	// debug
	if(isset($_REQUEST['debug'])){
		
		if($_REQUEST['debug'] == 1){
			
			$debug = true;
		}
	}
	
	// skip
	if(isset($_REQUEST['skip'])){
		
		if(is_array($_REQUEST['skip'])){
			
			$skip = (array) $_REQUEST['skip'];
		}
	}
	
	// extension
	if(isset($_REQUEST['extension'])){
		
		if(is_array($_REQUEST['extension'])){
			
			$extension = (array) $_REQUEST['extension'];
		}
	}
}

// start xml
$start_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
	"<?xml-stylesheet type=\"text/xsl\" href=\"https://raw.githubusercontent.com/Reload-Lab/PHP-XML-Sitemap-Generator/main/xml-sitemap.xsl\"?>\n".
	"<!-- Created with ReloadLab PHP XML Sitemap Generator ".VERSION." https://www.reloadlab.it -->\n".
	"<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n".
	"        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n".
	"        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n".
	"        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n".
	"  <url>\n".
	"    <loc>".htmlentities($start_url, ENT_QUOTES, 'UTF-8', true)."</loc>\n".
	"    <changefreq>$frequency</changefreq>\n".
	"    <priority>$priority</priority>\n".
	"  </url>\n";

// end xml
$end_xml = "</urlset>\n";

// Init end ==========================

function rel2abs($rel, $base)
{
	if(strpos($rel, '//') === 0){
		
		return 'http:'.$rel;
	}
	
	// return if already absolute URL
	if(parse_url($rel, PHP_URL_SCHEME) != ''){
		
		return $rel;
	}
	
	$first_char = substr($rel, 0, 1);

	// queries and anchors
	if($first_char == '#' || $first_char == '?'){
		
		return $base.$rel;
	}

	// parse base URL and convert to local variables:
	// $scheme, $host,  $path
	extract(parse_url($base));

	// remove non-directory element from path
	$path = preg_replace('#/[^/]*$#', '', $path);

	// destroy path if relative url points to root
	if($first_char ==  '/'){
		
		$path = '';
	}
	
	// dirty absolute URL
	$abs =  $host.$path.'/'.$rel;
	
	// replace '//' or '/./' or '/foo/../' with '/'
	$re =  array('#(/.?/)#', '#/(?!..)[^/]+/../#');
	for($n = 1; $n > 0;  $abs = preg_replace($re, '/', $abs, -1, $n)){
		
	}
	
	// absolute URL is ready!
	return  $scheme.'://'.$abs;
}

function GetUrl($url)
{
	global $debug;
	
	$agent = 'Mozilla/5.0 (compatible; ReloadLab PHP XML Sitemap Generator/'.VERSION.', https://www.reloadlab.it)';

	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_VERBOSE, $debug);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);

	$data = curl_exec($ch);

	curl_close($ch);

	return $data;
}

function Scan($url, &$count_url, &$str_xml)
{
	global $file, $folder, $start_url, $max_url_write, $end_xml;
	global $scanned, $extension, $skip, $frequency, $priority;
	global $callStartTime, $total_url;
	
	if($count_url == 0){
		
		$str_xml = '';
	}

	echo $url.NL;
	
	$total_url++;

	$url = filter_var($url, FILTER_SANITIZE_URL);
	
	$key_url = str_replace($start_url, '/', $url);

	if(!filter_var($url, FILTER_VALIDATE_URL) || isset($scanned[$key_url])){

		return;
	}

	$scanned[$key_url] = true;
	
	if($html = str_get_html(GetUrl($url))){
		
		$a1 = $html->find('a');
		
		// Clear to prevent Memory leak!
		$html->clear(); 
		unset($html);
	
		foreach($a1 as $val){
			
			$next_url = $val->href or '';
	
			$fragment_split = explode('#', $next_url);
			$next_url = $fragment_split[0];
	
			if((substr($next_url, 0, 7) != 'http://') && 
				(substr($next_url, 0, 8) != 'https://') &&
				(substr($next_url, 0, 6) != 'ftp://') &&
				(substr($next_url, 0, 7) != 'mailto:')){
				
				$next_url = @rel2abs($next_url, $url);
			}
	
			$next_url = filter_var($next_url, FILTER_SANITIZE_URL);
			
			$next_key_url = str_replace($start_url, '/', $next_url);
	
			if(substr($next_url, 0, strlen($start_url)) == $start_url){
				
				$ignore = false;
	
				if(!filter_var($next_url, FILTER_VALIDATE_URL)){
					
					$ignore = true;
				}
	
				if(isset($scanned[$next_key_url])){
					
					$ignore = true;
				}
	
				if(isset($skip) && !$ignore){
					
					foreach($skip as $v){
						
						if(substr($next_url, 0, strlen($v)) == $v){
							
							$ignore = true;
						}
					}
				}
				
				if(!$ignore){
					
					foreach($extension as $ext){
						
						if(strrpos($next_url, $ext) > 0){
							
							$pr = number_format(round($priority / count(explode('/', trim(str_ireplace(array('http://', 'https://'), '', $next_url), '/'))) + 0.5, 3), 1);
							
							$str_xml .= "  <url>\n".
								"    <loc>".htmlentities($next_url, ENT_QUOTES, 'UTF-8', true)."</loc>\n".
								"    <changefreq>$frequency</changefreq>\n".
								"    <priority>$pr</priority>\n".
								"  </url>\n";
								
							$count_url++;
							
							// update sitemap xml file
							if($count_url >= $max_url_write){
								
								if(smwrite($folder.$file, $str_xml.$end_xml)){
									
									$count_url = 0;
									
									$callEndTime = microtime(true); 
									$callTime = $callEndTime - $callStartTime;
									
									echo 'Total url scanned: '.$total_url.NL;
									echo 'Time elapsed: '.secondsToTime($callTime).' seconds'.NL;
									echo 'Current memory usage: '.(memory_get_usage(true) / 1024 / 1024).' MB'.NL;
								}
							}
							
							Scan($next_url, $count_url, $str_xml);
						}
					}
				}
			}
		}
	}
}

// Remove The Last Line From A File In PHP
// Thanks to Philip Norton (https://www.hashbangcode.com/article/remove-last-line-file-php)
function trimfile($filename)
{ 
	// File size
	$filesize = filesize($filename);

	if(!$filesize){
		
		return '';
	}
	
	// Open file
	$file_handle = fopen($filename, 'r');
	
	// Set up loop variables
	$linebreak  = false;
	$file_start = false;

	// Number of bytes to look at
	$bite = 50;
	
	// Put pointer to the end of the file.
	fseek($file_handle, 0, SEEK_END);
 
	while($linebreak === false && $file_start === false){

		// Get the current file position.
		$pos = ftell($file_handle);
	 
		if($pos < $bite){
			
			// If the position is less than a bite then go to the start of the file
			rewind($file_handle);
		} else{ 
			
			// Move back $bite characters into the file
			fseek($file_handle, -$bite, SEEK_CUR);
		}
	 
		// Read $bite characters of the file into a string.
		$string = fread($file_handle, $bite) or die('Can\'t read from file '.$filename.'.');
	 
		// If we happen to have read to the end of the file then we need to ignore 
		// the lastfile line as this will be a new line character.
		if($pos + $bite >= $filesize){
			
			$string = substr_replace($string, '', -1);
		}
	 
		// Since we fread() forward into the file we need to back up $bite characters. 
		if($pos < $bite){
			
			// If the position is less than a bite then go to the start of the file
			rewind($file_handle);
		} else{ 
			
			// Move back $bite characters into the file
			fseek($file_handle, -$bite, SEEK_CUR);
		}
	 
		// Is there a line break in the string we read?
		if(is_integer($lb = strrpos($string, "\n"))){
			
			// Set $linebreak to true so that we break out of the loop
			$linebreak = true;
			
			// The last line in the file is right after the linebreak
			$line_end = ftell($file_handle) + $lb + 1; 
		}
	 
		if(ftell($file_handle) == 0){
			
			// Break out of the loop if we are at the beginning of the file. 
			$file_start = true;
		}
	}

	if($linebreak === true){

		// If we have found a line break then read the file into a string to writing without the last line.
		rewind($file_handle);
		
		$file_minus_lastline = fread($file_handle, $line_end - 1);
		
		fclose($file_handle);

		return $file_minus_lastline;
	}
	
	$content = fread($file_handle, $filesize);
	
	fclose($file_handle);
	
	return $content;
}

function smwrite($file, $str, $start_new_sitemap = false)
{
	if($start_new_sitemap){
		
		$newstr = '';
	} else{
		
		$newstr = trimfile($file).NL;
	}
	
	$pf = fopen($file, 'w+');
	if(!$pf){
		
		echo 'Cannot create '.$file.'!'.NL;
		return false;
	}
	
	fwrite($pf, $newstr.$str);

	fclose($pf);
	
	return true;
}

function rotate($sitemap, $folder = null)
{	
	global $num_rotation;
	
	if($num_rotation <= 0){
		
		return;
	}
	
	$fileremove = '';
	$lastfile = date('YmdHis');
	$num = 0;
	
	$info_sm = explode('.', $sitemap);
	$ext_sm = array_pop($info_sm);
	$filename_sm = implode('.', $info_sm);
	
	$cur_dir = rtrim($folder != ''? $folder: getcwd(), '/').'/';
	$files = array_diff(scandir($cur_dir), array('.', '..'));
	
	if($files){
		
		foreach($files as $file){
			
			if(strrpos($file, $ext_sm) > 0){
				
				if($file == $sitemap){
					
					$filename = $filename_sm.'.'.date('YmdHis').'.'.$ext_sm;
					@rename($cur_dir.$sitemap, $cur_dir.$filename);
					$num++;
				} else{
				
					$info = explode('.', $file);
					$ext = array_pop($info);
					$timestamp = array_pop($info);
					$filename = implode('.', $info);
					
					if($filename_sm == $filename && is_numeric($timestamp)){
						
						if((int) $timestamp < (int) $lastfile){
							
							$lastfile = $timestamp;
							$fileremove = $file;
						}
						
						$num++;
					}
				}
			}
		}
		
		if($fileremove != '' && $num >= $num_rotation){
			
			@unlink($cur_dir.$fileremove);
		}
	}
}

// pass in the number of seconds elapsed to get hours:minutes:seconds returned
function secondsToTime($s)
{
	$h = floor($s / 3600);
	$s -= $h * 3600;
	$m = floor($s / 60);
	$s -= $m * 60;
	return $h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
}

// shutdown
function check_for_fatal()
{
	$error = error_get_last();
    if($error['type'] == E_ERROR){
			
		echo '['.date('Y-m-d H:i:s').'] PHP Fatal error: '.$error['message'].' in '.$error['file'].' on line '.$error['line'].NL;
	}
}
register_shutdown_function('check_for_fatal');


//--------------
$callStartTime = microtime(true);
echo date('Y-m-d H:i:s').' Start to scan '.$start_url.NL;

// First rotate old Sitemap xml files
rotate($file, $folder);

// Start writing xml file
if(smwrite($folder.$file, $start_xml.$end_xml, true)){
	
	// Internal variables
	$scanned = array();
	$count_url = 0;
	$total_url = 0;
	$str_xml = 0;
	
	$start_url = filter_var($start_url, FILTER_SANITIZE_URL);
	
	// Start scan
	Scan($start_url, $count_url, $str_xml);
	
	// End writing xml file
	if($count_url > 0 && $count_url < $max_url_write){
		
		smwrite($folder.$file, $str_xml.$end_xml);
	}
}

$callEndTime = microtime(true); 
$callTime = $callEndTime - $callStartTime;

echo date('Y-m-d H:i:s').' End to scan '.$start_url.NL;
echo 'Total url scanned: '.$total_url.NL;
echo 'Time elapsed: '.secondsToTime($callTime).' seconds'.NL;
echo 'Peak memory usage: '.(memory_get_peak_usage(true) / 1024 / 1024).' MB'.NL;
echo 'Done.'.NL;
echo $file.' created.'.NL;