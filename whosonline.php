<?php /*********************************************************************/
/* whosonline.php                                                          */
/*   - the Who's Online script for displaying players connected to a HL    */
/*        server on a webpage                                              */
/*   - uses the CounterStrike class by Henrik Schack Jensen                */
/*        <henrik@schack.dk>                                               */
/*   - Distributed under the GPL terms - see the readme for info           */
/*   - Copyright Brian Porter <beporter@users.sourceforge.net>             */
/***************************************************************************/
/* $Id$ */
/***************************************************************************/

/***************************************************************************/
/* Configuration Variables                                                 */
/***************************************************************************/
// Global server connection information used by the CounterStrike class
$prefs["serverAddress"]    = "64.55.197.41";
$prefs["serverPort"]       = "27015";
$prefs["refreshTime"]      = "60";

// Location of hlstats on your web server (leave empty if you're not using
//   hlstats.)
$prefs["hlstatsLocation"]  = "";

// File locations
$prefs["includeFile"]      = "whosonline.inc";
$prefs["templateFile"]     = "whosonline_embedded.html";
$prefs["classFile"]        = "counterstrike.php";

// The script uses four CSS class styles to format text. First is the style 
//   used for displaying the server name. Second is one used for the column
//   headings. Third for players names, kills and time connected. Fourth for
//   error messages. 
// You can define the class style names this script should use when
//   generating HTML. This allows the script to create html using stylesheet
//   definitions already in your site... -OR- you could add the block commented
//   below to your template webpage inside its <HEAD> tag. (The template that
//   is distributed with this package already has class styles defined.)
/*

<style type="text/css"> <!--
 .fontNormal{font-family:Verdana,Arial,sans-serif;font-size:11px;color:#000000}
 .fontMid{font-family:Verdana,Arial,sans-serif;font-size:10px;color:#555555;}
 .fontSmall{font-family:Verdana,Arial,sans-serif;font-size:9px;color:#333333;}
 .fontError{font-family:Verdana,Arial,sans-serif;font-size:9px;color:#FF0000;}
--> </style>

*/
$prefs["serverStyleName"]  = "fontNormal";
$prefs["headingStyleName"] = "fontMid";
$prefs["playerStyleName"]  = "fontSmall";
$prefs["errorStyleName"]   = "fontError";


/***************************************************************************/
/* Main                                                                    */
/***************************************************************************/
require_once($prefs["classFile"]);
require_once($prefs["includeFile"]);

// Handy back door to display ANY server by modifying the URL used to call
//   whosonline.php. IE: http://my.domain.com/whosonline.php?ip=64.55.197.41
if(isset($ip))
  $prefs["serverAddress"] = $ip;

// Create an object to hold server info
$csInfo = new CounterStrike;
$csInfo->setConnectionVars($prefs["serverAddress"], 
                           $prefs["serverPort"], 
                           $prefs["refreshTime"]);

// Create an array to hold the 'current' player
$nextPlayer = array();

// Gather information
$status = 
   ($csInfo->getServerPlayers($prefs["serverAddress"],$prefs["serverPort"],1000) && 
    $csInfo->getServerInfo($prefs["serverAddress"],$prefs["serverPort"],1000) );
           
// Done all the prep work, so finish by calling the html template
include_once($prefs["templateFile"]);

/*#######################################################################*/?>
