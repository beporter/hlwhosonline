<?php /*********************************************************************/
/* whosonline.php                                                          */
/*   - The Who's Online script for displaying players connected to a Steam */
/*        server on a webpage                                              */
/*   - Uses a heavily modified serverspy class by Daniel Luft              */
/*         <erozion@t-online.de>                                           */
/*   - Distributed under the GPL terms - see the readme for info           */
/*   - Copyright Brian Porter <beporter@users.sourceforge.net>             */
/*   - Part of HL Who's Online v1.0                                        */
/***************************************************************************/
/* $Id$ */
/***************************************************************************/

// Optional error reporting. If you're having troubles, trying uncommenting
// these two lines and see what you get.

//ini_set("display_errors",1);
//error_reporting(E_ALL);


/***************************************************************************/
/* Configuration Variables                                                 */
/***************************************************************************/
// Enter the game server IP (or hostname) and port number here.
$prefs["serverAddress"]    = "1.2.3.4";
$prefs["serverPort"]       = "27015";

// Location of stats on your web server, i.e.: "/hlstats/hlstats.php"
// (leave empty if you're not using stats.) Insert a "%p" in the string
// where you would like the player's name inserted in the URL. The following
// is an example for HLstats if hlstats.php is in your root web dir:
// $prefs["statsLocation"]  = "/hlstats.php?mode=search&amp;st=player&amp;q=%p";
// If in doubt, leave this blank.
$prefs["statsLocation"]  = "";

// File locations. Change these to point to the folder where these files are
// stored. These files need to be readable by your webserver, but do not need
// to be in your web folder. Normally you can just drop all the files from this
// package into the same folder and keep the default values here. If you would
// like to change the template used to display the data, change the first 
// entry below to the file name of the HTML template you'd like to use.
$prefs["templateFile"]     = "whosonline.html";
$prefs["classFile"]        = "whosonline.class.php";

// The script uses four CSS class styles to format text. First is the style 
//   used for displaying the server name. Second is one used for the column
//   headings. Third for players names, kills and time connected. Fourth for
//   error messages. You can define the class style names this script should
//   use when generating HTML.
/*

<style type="text/css"> <!--
 .hlwoNormal{font-family:Verdana,sans-serif;font-size:11px;color:#000000}
 .hlwoMid{font-family:Verdana,sans-serif;font-size:10px;color:#555555;}
 .hlwoSmall{font-family:Verdana,sans-serif;font-size:9px;color:#333333;}
 .hlwoError{font-family:Verdana,sans-serif;font-size:9px;color:#FF0000;}
--> </style>

*/

// PLEASE NOTE! These will not be used by default! By default, only "clean"
// output will be produced from the public functions! You must call them with
// the optional parameter set to FALSE to force them to output <span> tags!
// IE: 
//   $csInfo->returnCurrentMap(false)  -->  <span class="hlwoNormal">MyServer's Name</span>
//   $csInfo->returnCurrentMap(true)  -->  MyServer's Name
$prefs["serverStyleName"]  = "hlwoNormal";
$prefs["headingStyleName"] = "hlwoMid";
$prefs["playerStyleName"]  = "hlwoSmall";
$prefs["errorStyleName"]   = "hlwoError";


/*#########################################################################*/
/*##                                                                     ##*/
/*##                NO NEED TO EDIT BEYOND THIS POINT                    ##*/
/*##                                                                     ##*/
/*#########################################################################*/


/***************************************************************************/
/* Main                                                                    */
/***************************************************************************/
require_once($prefs["classFile"]);

// Override the default template if another calling script has set the 
// $hlwoIncFile variable. This allows the use of a single whosonline.php
// script to be included into a page multiple times with different templates
// each time. Simply set $hlwoIncFile to the relative path of your template
// file and include("whosonline.php"). (This works best with output buffering.)
if(isset($hlwoIncFile)) $prefs["templateFile"] = $hlwoIncFile;
elseif(isset($override['template'])) $prefs["templateFile"] = $override['template'];

// We want to be able to call this script from other PHP pages and show
// results for different servers each time. That means we need a way to
// specifiy the IP and port outside of the script. Use the $override[]
// array.
if(isset($override['ip'])) $prefs["serverAddress"] = $override['ip'];
if(isset($override['port'])) $prefs["serverPort"] = $override['port'];

// Handy back door to display ANY server by modifying the URL used to call
//   whosonline.php. IE: http://my.domain.com/whosonline.php?ip=64.55.197.41
if(isset($_REQUEST['ip'])) $prefs["serverAddress"] = $_REQUEST['ip'];
if(isset($_REQUEST['port'])) $prefs["serverPort"] = $_REQUEST['port'];

// Create an object to hold server info
$csInfo = new whosonline($prefs["serverAddress"].":".$prefs["serverPort"]);

// Set object prefs
$csInfo->setStyles($prefs["serverStyleName"],$prefs["headingStyleName"],
					$prefs["playerStyleName"],$prefs["errorStyleName"]);

$csInfo->setStats($prefs["statsLocation"]);

// Gather information
$csInfo->fetchAll();

// Done all the prep work, so finish by calling the html template
include($prefs["templateFile"]);

// Disconnect
$csInfo->disconnect();

/*#######################################################################*/?>