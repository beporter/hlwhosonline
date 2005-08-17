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

class parse
{
	function getchar(&$data)
	{
		return sprintf("%c", parse::getbyte($data));
	}
	
	function getbyte(&$data)
	{
		if(empty($data)) return "";
		$result = substr($data, 0, 1);
		$data = substr($data, 1);
		return ord($result);
	}
	
	function getint16(&$data)
	{
		if(empty($data)) return "";
		$lower = parse::getbyte($data);
		$higher = parse::getbyte($data);
		return ($higher << 8) | $lower;
	}
	
	function getint32(&$data)
	{
		if(empty($data)) return "";
		$lower = parse::getint16($data);
		$higher = parse::getint16($data);
		return ($higher << 16) | $lower;
	}
	
	function getfloat32(&$data)
	{
		if(empty($data)) return "";
		$result = unpack("ffloat", $data);
		$data = substr($data, 4);
		return $result['float'];
	}
	
	function getstring(&$data)
	{
		if(empty($data)) return "";
		$terminated = strpos($data, chr(0));
		$result = substr($data, 0, $terminated);
		$data = substr($data, $terminated + 1);
		return htmlentities($result, ENT_COMPAT, "UTF-8");
	}
}
?>