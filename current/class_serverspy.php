<?php
/**
*
* @author       Daniel Luft <erozion@t-online.de>
* @copyright    GPL 15/05/2005
*
*				Part of HL Who's Online v1.0
* 				Modified by Brian Porter <beporter@users.sourceforge.net>
*				$Id$
*
**/

require_once("class_parse.php");
require_once("class_socket.php");

define("A2S_SERVERQUERY_GETCHALLENGE", "W"); // request challenge

define("A2S_INFO", "TSource Engine Query\0"); // server info request
define("A2S_PLAYER", "U"); // request player list
define("A2S_RULES", "V"); // request rules list

define("S2C_CHALLENGE", "A"); // challenge response (HL2)

define("S2A_INFO", "I"); // info response (HL2)
define("S2A_INFO_DETAILED", "m"); // info response (HL1)
define("S2A_PLAYER", "D"); // player response
define("S2A_RULES", "E"); // rules response

class serverspy extends socket
{
	var $challenge = -1;
	
	
	// -----------------------------------------
	function serverspy()
	{
		$this->socket(); // Propogate the constructors down to the base class.
	}
	
	// -----------------------------------------
	function communicate($command)
	{
		$command = pack("N", 0xFFFFFFFF).$command;
		$this->send($command); // function 'send' from 'socket'
		
		$packets = array();
		
		do
		{
			$cache = $this->read(); // function 'read' from 'socket'
			
			if(empty($cache))
			{// if 'cache' is empty, try it again
				$this->send($command); // function 'send' from 'socket'
				$total_packets = 1;
				continue;
			}
			
			// 4 byte packet header
			$header = unpack("Nint", substr($cache, 0, 4));
			$packet_type = sprintf("%u", $header['int']);
			
			if($packet_type == 0xFFFFFFFF)
			{// single packet
				$packets[0] = $cache;
				$total_packets = 0;
			}
			elseif($packet_type == 0xFEFFFFFF)
			{// multi packets
				$cache = substr($cache, 4); // strip off the 4 byte packet header
				
				// byte 9 tells us two things. the low 4 bits holds the total number of packets sent
				// by the server and the high 4 bits holds the index of the packet we just received. 
				$packet_info = unpack("Cbyte", substr($cache, 4, 1));
				
				// total number of packets
				if(!isset($total_packets)) $total_packets = $packet_info['byte'] & 0x0F;
				$total_packets--;
				
				// index of received packet
				$index = (int)($packet_info['byte'] >> 4);
				$packets[$index] = substr($cache, 5);
			}
		}while($total_packets);
		
		ksort($packets);
		return substr(implode("", $packets), 4);
	}
	
	// -----------------------------------------
	function getchallenge()
	{
		//DEBUG	echo "getting challenge: ";
		if($this->challenge != -1) // Don't get a new challenge if we already have one.
		{
			//DEBUG	echo "returning cached challenge ({$this->challenge}). ";
			return true;
		}
		
		$response = $this->communicate(A2S_SERVERQUERY_GETCHALLENGE);
		$control_byte = parse::getchar($response);
		
		if($control_byte != S2C_CHALLENGE)
		{
			//DEBUG	echo "bad response ({$this->challenge}:{$control_byte}). ";
			return false;
		}
		
		$this->challenge = $response;
		//DEBUG	echo "success ({$this->challenge}). ";
		return true;
	}
	
	// -----------------------------------------
	function info()
	{
		$response = $this->communicate(A2S_INFO);
		$control_byte = parse::getchar($response);	
		$result = array();
		
		//DEBUG	echo "control byte for info is: {$control_byte}. ";
		if($control_byte != S2A_INFO && $control_byte != S2A_INFO_DETAILED)
		{
			return false;
		}
		
		if($control_byte == S2A_INFO)
		{
			$result['protocol_version'] = parse::getbyte($response);
			
			$result['server_name'] = parse::getstring($response);
			$result['server_map'] = parse::getstring($response);
			$result['game_directory'] = parse::getstring($response);
			$result['game_description'] = parse::getstring($response);
			
			$result['application_id'] = parse::getint16($response);
			
			$result['players'] = parse::getbyte($response);
			$result['max_players'] = parse::getbyte($response);
			$result['bot_players'] = parse::getbyte($response);
			
			$result['dedicated'] = parse::getchar($response);
			$result['operating_system'] = parse::getchar($response);
			$result['password'] = parse::getbyte($response);
			$result['secure'] = parse::getbyte($response);
			
			$result['server_version'] = parse::getstring($response);
			
			if(!$this->getchallenge())
			{
				return false;
			}
		}
		elseif($control_byte == S2A_INFO_DETAILED)
		{
			$result['server_address'] = parse::getstring($response);
			$result['server_name'] = parse::getstring($response);
			$result['server_map'] = parse::getstring($response);
			$result['game_directory'] = parse::getstring($response);
			$result['game_description'] = parse::getstring($response);
			
			$result['players'] = parse::getbyte($response);
			$result['max_players'] = parse::getbyte($response);
			
			$result['protocol_version'] = parse::getbyte($response);
			
			$result['dedicated'] = parse::getchar($response);
			$result['operating_system'] = parse::getchar($response);
			$result['password'] = parse::getbyte($response);
			
			$result['modded'] = parse::getbyte($response);
			
			if($result['modded'])
			{
				$result['mod_website'] = parse::getstring($response);
				$result['mod_download_server'] = parse::getstring($response);
				
				$result['mod_unused'] = parse::getstring($response);
				
				$result['mod_version'] = parse::getint32($response);
				$result['mod_size'] = parse::getint32($response);
				
				$result['mod_serverside_only'] = parse::getbyte($response);
				$result['mod_custom_dll'] = parse::getbyte($response);
			}
			
			$result['secure'] = parse::getbyte($response);
			$result['bot_players'] = parse::getbyte($response);
		}
		
		return $result;
	}
	
	// -----------------------------------------
	function player()
	{
		$this->getchallenge();
		$response = $this->communicate(A2S_PLAYER.$this->challenge);
		$control_byte = parse::getchar($response);
		
		//DEBUG	echo "control byte for players is: {$control_byte}. ";
		if($control_byte != S2A_PLAYER)
		{
			return false;
		}
		
		$result = array();
		
		$count = parse::getbyte($response);
		for($i = 0; $i < $count; $i++)
		{
			if(empty($response)) continue;
			
			$index = parse::getbyte($response);
			$name =  parse::getstring($response);
			$frags = parse::getint32($response);
			$rawtime = unpack("Vstr",substr($response,0,4));
			$time = parse::getfloat32($response);
			
			$result[] = array(
					"index" => $index,
					"name" => $name,
					"frags" => $frags,
					"rawtime" =>sprintf("%b",$rawtime['str']),
					"time" => (($time != -1) ? (date("H:i:s", mktime(0, 0, $time))) : ("BOT-Player"))
				);				
		}
		//DEBUG	printr($result,"returning from serverspy->player() with");
		return $result;
	}
	
	// -----------------------------------------
	function rules($rule="")
	{
		$this->getchallenge();
		$response = $this->communicate(A2S_RULES.$this->challenge);
		$control_byte = parse::getchar($response);
		
		//DEBUG	echo "control byte for rules is: {$control_byte}. ";
		if($control_byte != S2A_RULES)
		{
			return FALSE;
		}
		
		$result = array();
		
		$count = parse::getint16($response);
		for($i = 0; $i < $count; $i++)
		{
			$key = parse::getstring($response);
			$value = parse::getstring($response);
			$result[$key] = $value;
		}
		
		if(!empty($rule))
		{
			return $result[$rule];
		}
		
		return $result;
	}
}
?>