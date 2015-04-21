<?php

// a simple script to create Google Sitemaps
// contact RJ Softwares at info@rjsoftwares.com

// this script is free for modifications and changes. just two requests:
// 1. E-Mail us at info@rjsoftwares.com if you are using this. We will add a link to your site on ours.
// 2. Link to our site from yours.
// hope this is helpful!

// Changes, add-ons March 2007
// Tested with php 5.2
		// write to file
		// Deploy URL handling (set deploy URL different from development URL)
		// 'Deploy directory separator' handling (in case you stage on windows and deploy on Unix)
		// more exclusion rules (files, frontpage, ...
		// in/exclude self (sitemap.php, sitemap_init.php): Default excluded, call $sm->includeSelf() to include in map
		// separation of class construction and parsing (to allow multiple sitemaps)
		// handling of .robots file: Call $sm->considerRobotsTxt($rf="robots.txt") to add directories in $rf to the ignore list
		// by Peter Pircher info@merope.com
		// let me know if you download it from merope.com

// sitemap parse root, default is current directory

$smroot="..";


// Directory separator, default is to use the system separator, but if you test
// on Windows (sep = \) and deploy on Unix (sep = /) this might cause problems


$d_separator=DIRECTORY_SEPARATOR;

// File name to write sitemap to (in addition to echo)
$smfilename="";

// Path we want prepended for deployed web site if we generate the map on a test site
$deployedPath="";


// list the directories you do not want to include
// in the sitemap. Relative to the browser path
// for example, if you are on the domain www.yourdomain.com
// and you do not want to list any files in www.yourdomain.com/images/
// then enter /images in the array below
$ignoreArray = array ();

// file names to ignore, comparison is case insensitive as all compares (suffix, dir-ignore, ..)
// the script files (sitemap.php and sitemap_vars.php as well as the output file $smfilename
// are ignored per default
// Note this is different from the directory ignores, as matching names are discarded at any level
// for example robots.txt or internal debugging files would be good candidates.

$ignoreFiles = array ();

// file extenstions to ignore

$ignoreExtensions = array ();

// Overwrite default priority, value must be between 0 (least) - 0.5 (default) - 1.0 (most)
// filePriority["filename"] = "0.7"

$filePriority = array() ;


// Overwrite Change frequency
		// always 	(dynamically generated)
		// hourly
		// daily
		// weekly
		// monthly
		// yearly
		// never	(archived pages)
// fileChangeFreq["filename"] = "hourly"


$fileChangeFreq = array() ;


// get all localizations from an included file so we can redeploy and enhance the base ...




if (file_exists("sitemap_init.php"))
{
	include("sitemap_init.php");
}



// no need to change anything below
$sitemap = new RJGoogleSiteMap($smroot, $ignoreArray, $ignoreFiles, $ignoreExtensions,
				$filePriority, $fileChangeFreq,
				 $d_separator, $deployedPath);

// include a second time, this time the objects initialzed part will be executed


if (function_exists("setlocalSitemapDefaults"))
{
	setlocalSitemapDefaults($sitemap, $smfilename);
}

// write it at least once
// if already written from the setlocalSitemapDefaults function then we don't write it a second time

if (!$sitemap->hasSitemapBeenWritten())
{
	$sitemap->processAndWriteXML($smfilename, TRUE);
}



class RJGoogleSiteMap {



	// some variables for general use
	var $browserPath;
	var $browserDeployedPath;
	var $primaryFolder;
	var $ignoreArray;
	var $ignoreExtensions;
	var	$ignoreFiles;
	var	$smfile;
	var	$dsep;
	var	$filePrty;
	var	$fileFreq;
	var	$topFolder;
	var	$defaultPriority;
	var	$defaultFrequency;
	var	$echoWriteToo;
	var	$writtenOnce;
	var	$validFrequencies;
	var	$robotsExclude;


	// this is the initializing function
	// defaults to current directory

	function RJGoogleSiteMap($folder = ".", $ignoreArray = "", $ignoreFiles = "", $ignoreExtensions = "",
								$filePriority = "", $fileFrequency = "",
					 			$dseparator=DIRECTORY_SEPARATOR, $dpath="")
	{
		$this->defaultPriority = "0.5";
		$this->defaultFrequency = "monthly";
		$this->robotsExclude = array();

		$this->primaryFolder = $folder;
		$this->writtenOnce = FALSE;
		$this->validFrequencies = array("always", "hourly", "daily", "weekly", "monthly", "yearly", "never");


		if (is_array($ignoreArray))
		{
			$this->ignoreArray = $ignoreArray;
		} else {
			$this->ignoreArray = array ();
		}


		if (is_array($filePriority))
		{
			$this->filePrty = $filePriority;
		} else {
			$this->filePrty = array ();
		}

		if (is_array($fileFrequency))
		{
			$this->fileFreq = $fileFrequency;
		} else {
			$this->fileFreq = array ();
		}

		if (is_array($ignoreFiles))
		{
			$this->ignoreFiles = $ignoreFiles;
		} else {
			$this->ignoreFiles = array ();
		}

		// we always ignore - except if includeSelf() is called later ...
		//		Output file (if set)
		//		Self script (sitemap.php)
		//		Init File (sitemap_init.php)


		$this->ignoreFiles[] = "sitemap.php";
		$this->ignoreFiles[] = "sitemap_init.php";


		$this->excludeFrontpageDirs = FALSE;


		$this->ignoreExtensions = $ignoreExtensions;


		// set the browser path
		$this->getBrowserPath();
		$this->browserDeployedPath = $dpath;

		if (!isset($dseparator) || empty($dseparator) || $dseparator=="")
			$this->dsep = DIRECTORY_SEPARATOR;
		else
			$this->dsep = $dseparator;


		// start processing the specified folder
		$this->topFolder = $folder;
}

public	function	includeSelf()
{
	// we un-ignore (what a creative word) the self files we usually ignore
	//		Self script (sitemap.php)
	//		Init File (sitemap_init.php)

	$r = array();
	foreach($this->ignoreFiles as $ig)
	{
		if ($ig != "sitemap.php" && $ig != "sitemap_init.php")
		{
			$r[] = $ig;
		}
	}

	$this->ignoreFiles = $r;
}

public	function	excludeFrontpage()
{
	$this->excludeFrontpageDirs = array("_derived", "_vti_cnf", "_vti_pvt", "_private");
}


private	function	excludeThisFPDir($d)
{
	if (is_array($this->excludeFrontpageDirs) && in_array($d, $this->excludeFrontpageDirs))
		return(TRUE);
	return(FALSE);
}

public	function	setDefaultPriority($p)
{
	$r = $this->defaultPriority;

	$this->defaultPriority = $this->validatedPriority($p);
	return($r);

}


private	function	validatedFrequency($p)
{
	$p = strtolower($p);
	if (in_array($p, $this->validFrequencies))
		return($p);
	return($this->defaultFrequency);
}


public	function	setDefaultFrequency($f)
{
	$r = $this->defaultFrequency;
	$this->defaultFrequency = $this->validatedFrequency($f);
	return($r);
}

private	function	validatedPriority($p)
{

	$f = floatval($p);

	if ($f >= 0.0 && $f <= 1.0)
		return(strval($f));
	if ($f < 0.0)
		return("0.0");
	if ($f > 1.0)
		return("1.0");
	return($this->defaultFrequency);
}



public	function	hasSitemapBeenWritten()
{
	return($this->writtenOnce);
}

// read robots file and add disallow lines to exclusion

// User-Agent: AAAAAAA
// Disallow: BBBBBBB
// Disallow: CCCCCC


public	function	considerRobotsTxt($rf="robots.txt")
{
	$robfile = file($rf);



	foreach ($robfile as $rline)
	{
		list($keep, $disc) = explode("#", $rline);

		list($tag, $dir) = explode(":", $keep);

		$tag = trim($tag);
		// add Disallow tag lines to exclude directory list
		if (0==strcasecmp($tag,"Disallow"))
		{
			$this->robotsExclude[] = $this->substitureDirSep(trim($dir));
		}
	}
}

// From http://www.robotstxt.org/wc/norobots.html
// The value of this field specifies a partial URL that is not to be visited.
// This can be a full path, or a partial path; any URL that starts with this value will not be retrieved.
// For example,
// Disallow: /help disallows both /help.html and /help/index.html
// whereas
// Disallow: /help/ would disallow /help/index.html but allow /help.html.


private	function	robotDisallowFile($f)
{
	$t = $this->substitureDirSep($f);

	foreach ($this->robotsExclude as $re)
	{
		if ($re == $t)
			return(TRUE);
		if (strpos($t, $re) != FALSE)
			return(TRUE);
	}
	return(FALSE);
}

public	function	processAndWriteXML($smfname, $echoToo=TRUE)
{
		$this->writtenOnce = TRUE;
		$this->echoWriteToo = $echoToo;

		$sv_ignoreArray = $this->ignoreArray;
		$sv_ignoreFiles = $this->ignoreFiles;



		if (!isset($smfname) || empty($smfname) || $smfname=="")
			$this->smfile = FALSE;
		else
		{
			$this->smfile = fopen($smfname, 'w');
			if ($this->smfile == FALSE)
				genError("File open returned ERROR", $smfname);
			else // ignore output name from map
				$this->ignoreFiles[] = $smfname;
		}


		// generate the header

		$this->writeXML($this->createXMLHeader());

		$folders[] = $this->topFolder;


		while ($current_folder = array_pop($folders)) {

			$folder = opendir($current_folder);

			// open directory and start reading all files
			while ($file = readdir($folder)) {

				// skip if this is the current directory or, the parent
				if ($file == "." || $file == "..") {
					continue;
				}

				$filename = $current_folder.DIRECTORY_SEPARATOR.$file;

				// check for ignore lists
				$flag = 0;
				$toremove = "";

				if ($this->robotDisallowFile($filename))
				{
					$flag = 1;
				}


				if(is_array($this->ignoreFiles))
				{
					foreach ($this->ignoreFiles as $item)
					{
						if (strtolower($file) == strtolower($item))
						{
							$flag = 1;
						}
					}

				}


				$tmpfile =  $this->substitureDirSep($this->getURL($filename));

				if(is_array($this->ignoreArray))
				{
					$this->tmpArray = array();

					foreach ($this->ignoreArray as $item)
					{
						if ($tmpfile == $this->substitureDirSep($this->browserPath.$item))
						{
							$flag = 1;
						}
						else // we exclude directories that already matched from further processing, a small optimiziation
							array_push($this->tmpArray, $item);
					}

					$this->ignoreArray = $this->tmpArray;
				}



				if(is_array($this->ignoreExtensions))
				{
					foreach($this->ignoreExtensions as $item)
					{
						$length = strlen($item) * -1;

						if(strtolower(substr($filename, $length)) == strtolower($item))
						{
							$flag = 1;
							break;
						}
					}
				}

				if (is_dir($filename) && $this->excludeThisFPDir($file))
				{
					$flag = 1;
				}

				// check if the current file is a directory or not
				if ($flag == 0)
				{
					if (is_dir($filename))
					{
						// add to array for further processing
						array_push($folders, $filename);
						continue;
					} else
						$this->makeXML($filename);
				}
			}

			closedir($folder);
		}

		// generate the footer


	    $this->writeXML($this->createXMLFooter());

	    // close file if open ...


	    if ($this->smfile)
		{
			fflush($this->smfile);
			fclose($this->smfile);
		}

		// need to restore as function modifies the arrays as side-effect
		// original design of RJ SW only had one pass in mind

		$this->ignoreArray = $sv_ignoreArray;
		$this->ignoreFiles = $sv_ignoreFiles;


	}



	function invalidName($fn)
	{
		$pc = explode(DIRECTORY_SEPARATOR, $fn);
		$lf = end($pc);
		$lfs = strtr($lf, "äëïöüàèìoùáéíóúâêîôûç", "aeiouaeiouaeiouaeiouc");
		// return ($lfs != $lf);

		return(FALSE);

	}




	function	getFilePriority($f)
	{
		$r = $this->filePrty[$f];
		if (isset($r) &&!empty($r))
		{
			return($this->validatedPriority($r));
		}
		return($this->defaultPriority);
	}


	function	getFileFrequency($f)
	{
		$r = $this->fileFreq[$f];
		if (isset($r) &&!empty($r))
		{
			return($this->validatedFrequency($r));
		}
		return($this->defaultFrequency);
	}


	function	genError($msg, $tok)
	{
		$s = "\n<!--".$msg.": ".$tok."-->\n";

		$this->writeXML($s, TRUE);
	}

	function substitureDirSep($fn)
	{
		if (DIRECTORY_SEPARATOR != $this->dsep)
		{
			$pc = explode(DIRECTORY_SEPARATOR, $fn);
			$fn = implode($this->dsep, $pc);
		}
		return($fn);
	}


	function makeXML($file)
	{
		// get the last part of the full path name

		$fcomp = end(explode(DIRECTORY_SEPARATOR, $file));


		// now we do the actual processing here
		// and generate the xml for this file

		// generate last modification date/time
		$file_info = stat($file);
		$mod1 = $file_info[9];
		$lastmod = date('Y-m-d\TH:i:s', $mod1);

		// get the time difference
		$timediff = gmdate('Y-m-d\TH:i:s', $mod1);
		$timediff = (substr($lastmod, 12, 2) - substr($timediff, 12, 2));
		if (strlen($timediff == 1)) $timediff = '0'.$timediff;
		if (strpos($timediff,'-') === false) $timediff = '+'.$timediff;
		$lastmod .= $timediff.':00';


		if ($this->invalidName($file))
		{
			$this->genError("Invalid Name, can cause xml errors", $file);
		}


		$file = $this->substitureDirSep($this->getDeployedURL($file));



		$str = "<url>";
		$str .= "<loc>".$file."</loc>";
		$str .= "<lastmod>$lastmod</lastmod>";
		$str .= "<priority>".$this->getFilePriority($fcomp)."</priority>";
		$str .= "<changefreq>".$this->getFileFrequency($fcomp)."</changefreq>";
		$str .= "</url>";


		$this->writeXML($str);
	}

	function getBrowserPath()
	{
		$scriptlocation = split('/', $_SERVER["REQUEST_URI"]);
		array_pop($scriptlocation);
		$this->browserPath = join('/', $scriptlocation);
		$this->browserPath = "http://".$_SERVER["HTTP_HOST"].$this->browserPath;
	}

	function createXMLHeader()
	{

		header("Content-type: text/xml");
		$str = '<?xml version="1.0" encoding="UTF-8"?>';
		$str .= '<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">';
		return $str;
	}

	function getURL($filename) {

		// now make sure the primary folder is removed from the path
		// and ensure this is done just once

		$pos = strpos($filename, $this->primaryFolder);
		if (!($pos === false))
			$filename = substr_replace($filename, "", $pos, strlen($this->primaryFolder));

		return $this->browserPath.$filename;
	}

	function getDeployedURL($filename) {

		if ($this->browserDeployedPath=="")
		{
			return($this->getURL($filename));
		}

		// now make sure the primary folder is removed from the path
		// and ensure this is done just once

		$pos = strpos($filename, $this->primaryFolder);
		if (!($pos === false))
			$filename = substr_replace($filename, "", $pos, strlen($this->primaryFolder));

		return $this->browserDeployedPath.$filename;
	}


	function createXMLFooter() {
		return "</urlset>";
	}

	function	writeXML($s, $forceEcho=FALSE)
	{
		if ($this->echoWriteToo || $forceEcho)
			echo $s;

		if ($this->smfile)
			fwrite($this->smfile, $s."\n");
	}
}
?>
