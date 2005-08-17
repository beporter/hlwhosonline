<?php /*********************************************************************/
/* whosonline.class.php                                                    */
/*   - Object abstraction layer for serverspy class.                       */
/*   - Uses a heavily modified serverspy class by Daniel Luft              */
/*         <erozion@t-online.de>                                           */
/*   - Distributed under the GPL terms - see the readme for info           */
/*   - Copyright Brian Porter <beporter@users.sourceforge.net>             */
/*   - Part of HL Who's Online v1.0                                        */
/***************************************************************************/
/* $Id$ */
/***************************************************************************/

/*-------------------------------------------------------------------------*/
require_once("class_serverspy.php");

/*-------------------------------------------------------------------------*/
function printr($a, $s = '')
{
	print("<pre>" . ($s ? "{$s}: " : ""));
	print_r($a);
	print("</pre>");
}

/*-------------------------------------------------------------------------*/
function fragsort ($a, $b) {
	if($a["frags"] == $b["frags"]) return 0;
	if($a["frags"] > $b["frags"]) return -1; else return 1;
} 

/*-------------------------------------------------------------------------*/
class whosonline
{
	var $info = array();
	var $infoReady = false;

	var $players = array();
	var $playersReady = false;

	var $rules = array();
	var $rulesReady = false;

	var $serverspy;
	var $playerPos = -1;
	var $prefs = array(
			"serverStyleName" => "hlwoNormal",
			"headingStyleName" => "hlwoMid",
			"playerStyleName" => "hlwoSmall",
			"errorStyleName" => "hlwoError",
			"statsURL" => "",  // /hlstats.php?mode=search&amp;st=player&amp;q=%p
		);
	
	// -----------------------------------------
	function whosonline($serverAndPort)
	{
		$this->serverspy = new serverspy();
		return $this->serverspy->connect($serverAndPort);
	}
	
	// -----------------------------------------
	function disconnect()
	{
		$this->info = array();
		$this->infoReady = false;

		$this->players = array();
		$this->playersReady = false;

		$this->rules = array();
		$this->rulesReady = false;
		
		$this->serverspy->disconnect();
	}
	


	// -----------------------------------------
	function setStyles($server, $heading, $player, $error)
	{
		$this->prefs["serverStyleName"]  = $server;
		$this->prefs["headingStyleName"] = $heading;
		$this->prefs["playerStyleName"]  = $player;
		$this->prefs["errorStyleName"]   = $error;
	}

	// -----------------------------------------
	function setStats($url)
	// Allows HLWO to display links to player's stats pages in
	// Psyhcostats or hlStats. queryStr should be the search string
	// used to locate a player. Use %p to represent the player's
	// name in the query. Set to empty string to disable.
	{
		$this->prefs['statsURL']  = $url;
	}



	// -----------------------------------------
	function fetchInfo()
	{
		$this->info = $this->serverspy->info();
		$this->infoReady = !($this->info === false);
		return $this->infoReady;
	}

	// -----------------------------------------
	function fetchPlayers()
	{
		$this->players = $this->serverspy->player();
		$this->playersReady = !($this->players === false);
		if(!is_array($this->players))
			$this->players[0] = array("name"=>"Communication Error!",
									"frags"=>"&nbsp;",
									"time"=>"&nbsp;");		
		elseif(count($this->players) == 0)
			$this->players[0] = array("name"=>"No Players Online",
									"frags"=>"&nbsp;",
									"time"=>"&nbsp;");		
		$this->playerPos = -1;
		
		if(count($this->players) > 1) usort($this->players,"fragsort");
		reset($this->players);
		//DEBUG	printr($this->players,"returning from whosonline->fetchPlayers() with");

		return $this->playersReady;
	}

	// -----------------------------------------
	function fetchRules()
	{
		$this->rules = $this->serverspy->rules();
		$this->rulesReady = !($this->rules === false);
		return $this->rulesReady;
	}

	// -----------------------------------------
	function fetchAll()
	// Shortcut method for retrieving all the information from a server.
	{
		//$this->serverspy->getchallenge();
		$this->fetchInfo();
		$this->fetchPlayers();
		$this->fetchRules();
	}
	


	// -----------------------------------------
	function printAll()
	// Quick and dirty function for dumping all the collected data.
	{
		printr($this->info, "Info");
		printr($this->players, "Players");
		printr($this->rules, "Rules");
	}
	

	
	// -----------------------------------------
	function returnServerName($clean = true)
	// Returns the name of the server.
	{
		$str = ($this->infoReady 
				? $this->info["server_name"] 
				: "Name not available"
			);
		$style = ($this->infoReady 
				? $this->prefs["serverStyleName"] 
				: $this->prefs["errorStyleName"] 
			);

		if($clean)
			return($str);
		else
			return("<span class=\"{$style}\">{$str}</span>");
	}

	// -----------------------------------------
	function returnIsSecure($clean = true)
	// Returns whether the server is running VAC or not. Returns "1" if VAC
	// is on, and "0" if VAC is off.
	{
		$str = ($this->infoReady 
				? $this->info["secure"] 
				: "Security not available"
			);
		$style = ($this->infoReady 
				? $this->prefs["serverStyleName"] 
				: $this->prefs["errorStyleName"] 
			);

		if($clean)
			return($str);
		else
			return("<span class=\"{$style}\">{$str}</span>");
	}

	// -----------------------------------------
	function returnIsFF($clean = true)
	// Returns whether the server is friendly-fire or not. Returns "1" if FF
	// is on, and "0" if FF is off.
	{
		$str = ($this->infoReady && isset($this->info["mp_friendlyfire"]) 
				? $this->info["mp_friendlyfire"] 
				: "FF not available"
			);
		$style = ($this->infoReady 
				? $this->prefs["serverStyleName"] 
				: $this->prefs["errorStyleName"] 
			);

		if($clean)
			return($str);
		else
			return("<span class=\"{$style}\">{$str}</span>");
	}

	// -----------------------------------------
	function returnCurrentMap($clean = true)
	// Returns the map the server is currently on.
	{
		$str = ($this->infoReady 
				? $this->info["server_map"] 
				: "Map not available"
			);
		$style = ($this->infoReady 
				? $this->prefs["serverStyleName"] 
				: $this->prefs["errorStyleName"] 
			);

		if($clean)
			return($str);
		else
			return("<span class=\"{$style}\">{$str}</span>");
	}

	// -----------------------------------------
	function returnNextMap($clean = true)
	// Returns the map the next server will load. NOTE: requires AMX.
	// Returns false if the cvar doesn't exist or the rules haven't 
	// been retrieved yet.
	{
		$str = ($this->rulesReady && isset($this->rules["amx_nextmap"]) 
				? $this->rules["amx_nextmap"] 
				: false
			);
		$style = ($this->rulesReady && isset($this->rules["amx_nextmap"]) 
				? $this->prefs["serverStyleName"] 
				: $this->prefs["errorStyleName"] 
			);

		if($clean)
			return($str);
		elseif($str !== false)
			return("<span class=\"{$style}\">{$str}</span>");
		else
			return(false);
	}

	// -----------------------------------------
	function returnTimeLeft($clean = true)
	// Returns the time left on the current map. NOTE: requires AMX.
	// Returns false if the cvar doesn't exist or the rules haven't 
	// been retrieved yet.
	{
		$str = ($this->rulesReady && isset($this->rules["amx_timeleft"]) 
				? $this->rules["amx_timeleft"] 
				: false
			);
		$style = ($this->rulesReady && isset($this->rules["amx_timeleft"]) 
				? $this->prefs["serverStyleName"] 
				: $this->prefs["errorStyleName"] 
			);

		if($clean)
			return($str);
		elseif($str !== false)
			return("<span class=\"{$style}\">{$str}</span>");
		else
			return(false);
	}

	// -----------------------------------------
	function returnPlayerCount($clean = true)
	// Returns the number of players in-game and the number of player
	// slots as a fraction. I.e.: "5/20".
	{		
		$str = ($this->infoReady 
				? ($this->info["players"]  . "/" . $this->info["max_players"]) 
				: "Player summary not available"
			);
		$style = ($this->infoReady 
				? $this->prefs["serverStyleName"] 
				: $this->prefs["errorStyleName"] 
			);

		if($clean)
			return($str);
		else
			return("<span class=\"{$style}\">{$str}</span>");
	}
	


	// -----------------------------------------
	function popNextPlayer()
	// Increments the internal array pointer indexing the players array.
	// Returns true if the new index points to a new valid player,
	// and false if no more players are left. Designed for use in a while()
	// loop.
	{	
		$this->playerPos++;
		if($this->playerPos < count($this->players))
			return(true);
		else
			return(false);
	}
		
	// -----------------------------------------
	function returnNextPlayer_name($clean = true)
	// Prints 'name' in the player array stored in $nextPlayer as a hyperlink 
	//   to the hlstats search page for the player... or if hlstats is
	//   unavailable, simply the player's name.
	{
		$name = $this->players[$this->playerPos]['name'];
		
		if( strlen($this->prefs["statsURL"]) > 0 )
		{
			//DEBUG	$url = urlencode(str_replace("%p",$name,$this->prefs["statsURL"]));
			$url = str_replace("%p",$name,$this->prefs["statsURL"]);
			$str = "<a href=\"{$url}\">{$name}</a>";
		}
		else
			$str = $name; 
		
		$style = ($this->playersReady 
				? $this->prefs["playerStyleName"] 
				: $this->prefs["errorStyleName"] 
			);

		if($clean)
			return($str);
		else
			return("<span class=\"{$style}\">{$name}</span>");
	}
	
	// -----------------------------------------
	function returnNextPlayer_kills($clean = true)
	// Prints 'frags' in the player array stored in $nextPlayer.
	{
		$frags = $this->players[$this->playerPos]['frags'];
		$str = ( $clean ? $frags : "<span class=\"{$this->prefs["playerStyleName"]}\">{$frags}</span>" );
		return($str);
	}
	
	// -----------------------------------------
	function returnNextPlayer_time($clean = true)
	// Prints 'time connected' in the player array stored in $nextPlayer.
	{
		$time = $this->players[$this->playerPos]['time'];
		$str = ( $clean  ? $time : "<span class=\"{$this->prefs["playerStyleName"]}\">{$time}</span>" );
		return($str);
	}
	
}



/*######################################################################*/ ?>