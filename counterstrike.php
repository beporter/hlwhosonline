<?php

/* $Id$ */

// ****************************************************************************
// Class CounterStrike
// Author : Henrik Schack Jensen (henrik@schack.dk)
// Modified: Brian Porter <beporter@users.sourceforge.net> (v1.02)
//
// Changelog:
// Version 1.02 	03/16/2002	Internalized connection vars and added a method
//								  to set them: setConnectionVars()
//								Supressed PHP version warning about socket_set_timeout()
//                Added RCS ID tag
// Version 1.01 	03/11/2001	Removed ASP style tags
//								Removed usort warning
//								Fixed error on empty server
// Version 1.00 	03/05/2001	Initial (& a bit messy) release
// 
// A utilityclass (PHP4 only) to do serverstatus-queries against 
// Halflife/Counterstrike servers
//
// The following functions are available:
// 
// Function setConnectionVars(server_address,server_port,refresh_time)
// This function initializes the connection variables for the object. Default
// values are 127.0.0.1 (localhost), 27015, and 60 secs.
//
// Function getServerInfo(serveraddress,serverport)
// Get info about servername,serveraddress,mapname,currentplayers & maxplayers.
//
// Function getServerPlayers(serveraddress,serverport)
// Get info about players currently playing on the server.
// Players are sortet by frags.
//	
// Function getServerRules(serveraddress,serverport)
// Get info about serverrules/settings.
//
// All results are returned in membervariables:
//
//
// Demosource is available at http://www.gameserver.dk/
// ****************************************************************************
// 
// Function used to sort players by frags
// Needs to be defined globally in order for usort to call it
//	

function fragsort ($a, $b) {	 
	if ($a["frags"] == $b["frags"]) return 0;
	if ($a["frags"] > $b["frags"]) {
		return -1;
	} else {
		return 1;
	}
} 


class CounterStrike {
	var $m_playerinfo 	= ""; 	 // Info about players
	var $m_servervars 	= ""; 	 // Info about the server current map, players etc
	var $m_serverrules	= ""; 	 // Serverrules
	
	// Set these using setConnectionVars()-- don't just change them here!
	var $server_address = "127.0.0.1"; // Default IP of game server
	var $server_port		= "27015";		 // Default Port of game server
	var $refresh_time 	= "60"; 			 // Default refresh rate

	//
	// Set server connection variables
	//
	function setConnectionVars($server_address, $server_port, $refresh_time) {
		$this->server_address = $server_address;
		$this->server_port		= $server_port;
		$this->refresh_time 	= $refresh_time;
	}

	//
	// Get exact time, used for timeout counting
	//
	function timenow() {
		return doubleval(ereg_replace('^0\.([0-9]*) ([0-9]*)$','\\2.\\1',microtime()));
	}
	
	//
	// Read raw data from server
	//
	function getServerData($command,$serveraddress,$portnumber,$waittime) {
		$serverdata 	="";
		$serverdatalen=0;
		
		if ($waittime< 500) $waittime= 500;
		if ($waittime>2000) $waittime=2000;
		$waittime=doubleval($waittime/1000.0);

			
		if (!$cssocket=fsockopen("udp://".$serveraddress,$portnumber,$errnr)) {
			$this->errmsg="No connection";
			return "";
		}
		
		socket_set_blocking($cssocket,true);
		@socket_set_timeout($cssocket,0,500000);
		fwrite($cssocket,$command,strlen($command));	
		// Mark
		$starttime=$this->timenow();
		do {
			$serverdata.=fgetc($cssocket);
			$serverdatalen++;
			$socketstatus=socket_get_status($cssocket);
			if ($this->timenow()>($starttime+$waittime)) {
				$this->errmsg="Connection timed out";
				fclose($cssocket);
				return "";
			}
		} while ($socketstatus["unread_bytes"] );
		fclose($cssocket);
		return $serverdata; 	
	}
	
	function getnextstring(&$data) {
		$temp="";
		$counter=0;
		while (ord($data[$counter++])!=0) $temp.=$data[$counter-1];
		$data=substr($data,strlen($temp)+1);
		return $temp;
	}

	function getnextbytevalue(&$data) {
		$temp=ord($data[0]);
		$data=substr($data,1);
		return $temp;
	}

	function getnextfragvalue(&$data) {
		$frags=ord($data[0])+(ord($data[1])<<8)+(ord($data[2])<<16)+(ord($data[3])<<24);
		if ($frags>=4294967294) $frags-=4294967296;
		$data=substr($data,4);
		return $frags;
	}

	function getnextplaytime(&$data) {
		$decnumber = ord($data[0])+(ord($data[1])<<8) +
								(ord($data[2])<<16)+(ord($data[3])<<24);
		$binnumber=base_convert($decnumber,10,2);
		while (strlen($binnumber) < 32) $binnumber="0".$binnumber;
		$exp=abs(base_convert(substr($binnumber,1,8),2,10))-127;
		if (substr($binnumber,0,1)=="1") $exp=0-$exp;
		$man=1;$manadd=0.5;
		for ($counter=9;$counter<32;$counter++) {
			if (substr($binnumber,$counter,1)=="1") $man+=$manadd;
			$manadd=$manadd/2;
		}
		$time=round(pow(2,$exp)*$man);
		$playtime="";
		if ($time>3600) {
			$playtime=sprintf("%2dh",$time/3600);
		} 
		$time%=3600;
		$playtime=$playtime.sprintf("%2dm",$time/60); 	 
		$time%=60;
		$playtime=$playtime.sprintf("%2ds",$time);
		$data=substr($data,5);
		return $playtime;
	}

	// **********************************************************************
	// getServerRules
	// Read rules/setup from the gameserver into m_serverrules
	// Return true if successful
	// **********************************************************************
	function getServerRules($serveraddress,$portnumber,$waittime) {
		$cmd="\xFF\xFF\xFF\xFFrules\x00"; 	
		$serverdata=$this->getServerData($cmd,$serveraddress,$portnumber,$waittime) ;
		// Check length of returned data, if < 5 something went wrong
		if (strlen($serverdata)<5) return false;			
		// Figure out how many rules there are
		$rules=(ord($serverdata[5]))+(ord($serverdata[6])*256);
		if ($rules!=0) {
			// Strip OOB data 			
			$serverdata=substr($serverdata,7);
			for ($i=1;$i<=$rules;$i++) {
				$rulename 	=$this->getnextstring($serverdata);
				$rulevalue	=$this->getnextstring($serverdata);
				$this->m_serverrules[$rulename]=$rulevalue;
			}
			return true;
		} else {
			return false;
		}
	} 

	
	// **********************************************************************
	// getServerinfo
	// Read information about the gameserver into m_servervars
	// Serveraddress,servername,current map etc etc
	// Return true if successful
	// **********************************************************************
	function getServerInfo($serveraddress,$portnumber,$waittime) {
		$cmd="\xFF\xFF\xFF\xFFinfo\x00";		
		$serverdata=$this->getServerData($cmd,$serveraddress,$portnumber,$waittime) ;
		// Check length of returned data, if < 5 something went wrong
		if (strlen($serverdata)<5) return false;		
		// Strip OOB data 			
		$serverdata=substr($serverdata,5);
		$this->m_servervars["serveraddress"]	= $this->getnextstring($serverdata);
		$this->m_servervars["servername"] = $this->getnextstring($serverdata);
		$this->m_servervars["mapname"]		= $this->getnextstring($serverdata);
		$this->m_servervars["game"] 	= $this->getnextstring($serverdata);
		$this->m_servervars["gamename"] 	= $this->getnextstring($serverdata);
		$this->m_servervars["currentplayers"] = $this->getnextbytevalue($serverdata);
		$this->m_servervars["maxplayers"] 		=$this->getnextbytevalue($serverdata);	
		return true;
} 


	// **********************************************************************
	// Get Playerinfo
	// Read information about the players into m_playerinfo
	// Name,frags,playtime
	// Return true if successful
	// **********************************************************************
	function getServerPlayers($serveraddress,$portnumber,$waittime) {
		// Servercommand
		$cmd="\xFF\xFF\xFF\xFFplayers\x00";
		$serverdata=$this->getServerData($cmd,$serveraddress,$portnumber,$waittime);
		
		// Check length of returned data, if < 5 something went wrong
		if (strlen($serverdata)<5) return false;
		
		// Check number of players to read data for
		$players=ord($serverdata[5]);
		
		 // Strip OOB data and other stuff
		$serverdata=substr($serverdata,7);
		for ($i=1;$i<=$players;$i++) {
			$playername = htmlspecialchars($this->getnextstring($serverdata));
			$frags		= $this->getnextfragvalue($serverdata);
			$playtime = $this->getnextplaytime($serverdata);
			$this->m_playerinfo[$i] = 
										array("name"=>$playername,"frags"=>$frags,"time"=>$playtime);
		}
		// Sort players in fragorder
		if ($players>1) usort($this->m_playerinfo,"fragsort");
		return true;
	}
}
?>
