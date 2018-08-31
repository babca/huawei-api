<?php
namespace HuaweiApi;

date_default_timezone_set('Europe/Prague');

/**
* This class includes helper functions
*/
class Utils
{
	private $logfilePath = "log.txt";

	public function __construct($logpath) {
		$this->logfilePath = $logpath;
	}
	
	public function logToFile($msg) 
	{  
		// open file
		$fd = fopen($this->logfilePath, "a"); 
		// append date/time to message 
		$str = "[" . date("Y/m/d H:i:s", mktime()) . "] " . $msg;  
		// write string 
		fwrite($fd, $str . "\n"); 
		// close file 
		fclose($fd); 
	} 

	public function secondsToTime($seconds)
	{
		$dtF = new \DateTime('@0');
		$dtT = new \DateTime("@$seconds");
		return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
	}
}