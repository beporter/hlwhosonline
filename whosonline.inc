<?php /*********************************************************************/
/* whosonline.inc                                                          */
/*   - Function definitions for the Who's Online script                    */
/*   - uses the CounterStrike class by Henrik Schack Jensen                */
/*        <henrik@schack.dk>                                               */
/*   - Distributed under the GPL terms - see the readme for info           */
/*   - Copyright Brian Porter <beporter@users.sourceforge.net>             */
/*   - Part of HL Who's Online v0.92                                       */
/***************************************************************************/
/* $Id$ */
/***************************************************************************/

/***************************************************************************/
/* Summary                                                                 */
/***************************************************************************/
/*

These functions are designed to be embedded in an html template page. Each one
produces html output wherever the funtion call is placed. This allows vast
customization of the displayed information. See whosonline.html for a better 
idea of how this works.

printServerName()
   Prints the name of the server if available.

printCurrentMapName()
   Prints the name of current map if available.

printConnectedPlayersSummary()
   Prints the ratio of players connected to max players if available.


-- The following functions are used together. popNextOnlinePlayer() must 
   be called in between calls of the other three to populate new data.   --

popNextOnlinePlayer()
   Gets the next player from the player list and returns true. If no players
   are left, returns false.

printNextOnlinePlayer_name()
   Prints 'name' in the player array stored in $nextPlayer as
   a hyperlink to the hlstats search page for the player... or if hlstats is
   unavailable, simply the player's name.

printNextOnlinePlayer_kills()
   Prints 'kills' in the player array stored in $nextPlayer.

printNextOnlinePlayer_time()
   Prints 'time connected' in the player array stored in $nextPlayer.

*/


/***************************************************************************/
/* Sub-routines                                                            */
/***************************************************************************/

/*-------------------------------------------------------------------------*/
function popNextOnlinePlayer()
// Gets the next player from the player list and returns true. If no players
//   are left, returns false.
{
  global $csInfo;
  global $nextPlayer;
  global $status;
  
  static $alreadyRun;
  if(!isset($alreadyRun)){ $alreadyRun = false; }
  
  if($nextPlayer = @array_shift($csInfo->m_playerinfo))
  {
    $alreadyRun = true;
    return(true);
  }
  elseif(!$alreadyRun)
  {
    if(!$status)
    {
      $nextPlayer = array("name"=>"Error Communicating with Server!",
                          "kills"=>"&nbsp",
                          "time"=>"&nbsp");
    }  
    else
    {
      $nextPlayer = array("name"=>"No Players Online",
                          "kills"=>"&nbsp",
                          "time"=>"&nbsp");
    }
    $alreadyRun = true;
    return(true);
  }
  else
    return(false);
}

/*-------------------------------------------------------------------------*/
function printServerName()
// Prints the name of the server if available.
{
  global $csInfo;
  global $prefs;
  if(isset($csInfo->m_servervars["servername"]))
    print("<font class=" . $prefs["serverStyleName"] . ">"
          . $csInfo->m_servervars["servername"] . "</font>");
  else
    print("<font class=" . $prefs["errorStyleName"] 
          . ">Name not available</font>");
}

/*-------------------------------------------------------------------------*/
function printCurrentMapName()
// Prints the name of current map if available.
{
  global $csInfo;
  global $prefs;

  if(isset($csInfo->m_servervars["mapname"]))
    print("<font class=" . $prefs["serverStyleName"] . ">"
          . $csInfo->m_servervars["mapname"] . "</font>");
  else
    print("<font class=" . $prefs["errorStyleName"] 
          . ">Map not available</font>");
}

/*-------------------------------------------------------------------------*/
function printConnectedPlayersSummary()
// Prints the ratio of players connected to max players if available.
{
  global $csInfo;
  global $prefs;

  if(isset($csInfo->m_servervars["currentplayers"]) && 
     isset($csInfo->m_servervars["maxplayers"]))
    print("<font class=" . $prefs["serverStyleName"] . ">("
          . $csInfo->m_servervars["currentplayers"] . "/"
          . $csInfo->m_servervars["maxplayers"] . "</font>");
  else
    print("<font class=" . $prefs["errorStyleName"] 
          . ">Player summary not available</font>");
}

/*-------------------------------------------------------------------------*/
function printNextOnlinePlayer_name()
// Prints 'name' in the player array stored in $nextPlayer as a hyperlink 
//   to the hlstats search page for the player... or if hlstats is
//   unavailable, simply the player's name.
{
  global $nextPlayer;
  global $prefs;
  global $status;
  
  if($status)  // then print the player
  {
  	if( strlen($prefs["hlstatsLocation"]) > 0)  // hlstats location was provided
      print("<a href=\"" . $prefs["hlstatsLocation"]
            . "?mode=search&st=player&q=" . $nextPlayer["name"]
            . "\" target=_new>" . $nextPlayer["name"] . "</a>");
    else  // no hlstats
      print("<font class=\"" . $prefs["playerStyleName"]
            . "\">" . $nextPlayer["name"] . "</font>");
  }
  else  // print the error text
    print("<font class=" . $prefs["errorStyleName"] . ">" 
          . $nextPlayer["name"] . "</font>");
}

/*-------------------------------------------------------------------------*/
function printNextOnlinePlayer_kills()
// Prints 'kills' in the player array stored in $nextPlayer.
{
  global $nextPlayer;
  global $prefs;
  print("<font class=\"" . $prefs["playerStyleName"] . "\">"
         . $nextPlayer["frags"] . "</font>");
}

/*-------------------------------------------------------------------------*/
function printNextOnlinePlayer_time()
// Prints 'time connected' in the player array stored in $nextPlayer.
{
  global $nextPlayer;
  global $prefs;
  print("<font class=\"" . $prefs["playerStyleName"] . "\">"
         . $nextPlayer["time"] . "</font>");
}

/*######################################################################*/ ?>
