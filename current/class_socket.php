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

class socket
{
	var $host;
	var $port;
	var $handle;
	var $connect_timeout;
	var $timeout;
	var $errno;
	var $errstr;
	
	// -----------------------------------------
	function socket($connect_timeout=5.0, $timeout=1.0)
	{
		$this->host = NULL;
		$this->port = NULL;
		$this->handle = NULL;
		$this->connect_timeout = $connect_timeout;
		$this->timeout = $timeout;		
		$this->errno = 0;		
		$this->errstr = "";		
	}

	// -----------------------------------------
	function connect($hostAndPort, $protocol="udp")
	{
		if(isset($this->handle))
		{
			$this->disconnect();
		}
		
		list($this->host, $this->port) = explode(":", $hostAndPort);
		
		$this->handle = fsockopen("{$protocol}://{$this->host}", $this->port, $errno, $errstr);
		
		if(!$this->handle)
		{
			$this->errno = $errno;
			$this->errstr = $errstr;
			return false;
		}
		
		if(function_exists("stream_set_timeout"))
		{
			stream_set_timeout($this->handle, $this->timeout);
		}
		else 
		{
			socket_set_timeout($this->handle, $this->timeout);
		}
		
		return true;
	}
	
	// -----------------------------------------
	function disconnect()
	{
		$this->errno = 0;		
		$this->errstr = "";		
		fclose($this->handle);
	}
	
	// -----------------------------------------
	function geterror()
	{
		return array($this->errno, $this->errstr = "");
	}
	
	// -----------------------------------------
	function unread_bytes()
	{
		$status = socket_get_status($this->handle);
		return $status['unread_bytes'];
	}
	
	// -----------------------------------------
	function send($command)
	{
		fwrite($this->handle, $command, strlen($command));
	}
	
	// -----------------------------------------
	function read($length=NULL)
	{
		if(!is_null($length))
		{
			return fread($this->handle, $length);
		}
		
		$buffer = "";
		
		do
		{
			$buffer .= fread($this->handle, 1);
		}while($this->unread_bytes());
		
		return $buffer;
	}
}
?>