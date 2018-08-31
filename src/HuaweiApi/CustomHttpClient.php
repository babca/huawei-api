<?php
namespace HuaweiApi;

/**
* HTTP-related stuff
* TO-DO: feel free to apply best practices, clean the code and add more comments
*/
class CustomHttpClient
{
	private $debugMode = false;
	private $connectionTimeout = 3;
	private $responseTimeout = 5;
	private $baseUrl = '';

	private $loginOtp = '';
	private $loginPubkey = '';
	private $loginCsrfToken = '';
	private $sessionCsrfToken = '';
	private $sessionCookies = '';

	public function enableDebug()
	{	
		$this->debugMode = true;
	}
	
	/**
	* Set tokens and keys
	*/

	public function setOtp($otp)
	{	
		$this->loginOtp = $otp;
	}

	public function setPubkey($pubkey)
	{
		$this->loginPubkey = $pubkey;
	}

	public function setLoginCsrfToken($csrf_token)
	{
		$this->loginCsrfToken = $csrf_token;
	}

	public function setSessionCsrfToken($csrf_token)
	{
		$this->sessionCsrfToken = $csrf_token;
	}

	private function setSessionCookie($cookie_data)
	{
		$this->sessionCookies = $cookie_data;
	}

	/**
	* Get token and keys
	*/

	public function getOtp()
	{
		return $this->loginOtp;
	}

	public function getPubkey()
	{
		return $this->loginPubkey;
	}

	public function getCsrfToken()
	{
		return $this->loginCsrfToken;
	}

	public function getSessionCsrfToken()
	{
		return $this->sessionCsrfToken;
	}

	public function getSessionCookie()
	{
		return $this->sessionCookies;
	}

	/**
	* Logout by forgetting the session cookie and session CSRF token
	*/
	public function resetSession()
	{
		$this->setSessionCookie('');
		$this->setSessionCsrfToken('');
	}
	
	/**
	* Builds common curl object
	*/
	private function getCurlObject($url, $headerFields = array())
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_VERBOSE, $this->debugMode);

		$header = array(
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:61.0) Gecko/20100101 Firefox/61.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8;charset=UTF-8',
			'Accept-Language: en,cs;q=0.8,en-US;q=0.5,sk;q=0.3',
			'Accept-Charset: utf-8;q=0.7,*;q=0.7',
			'Keep-Alive: 115',
			'Connection: keep-alive',
			'Pragma: no-cache',
			'Cache-Control: no-cache'
		);
		
		if ($this->sessionCookies)
		$header[] = 'Cookie: '.$this->sessionCookies;
			
		foreach($headerFields as $h)
		{
			$header[] = $h;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->responseTimeout);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_ENCODING , "gzip");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'HeaderHandler'));
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // removes HTTPS security. The cert is invalid anyway. 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Hint: the router uses encryption on application level

		//if ($reset_session)
		//curl_setopt($ch,CURLOPT_COOKIESESSION, 1);
		//curl_setopt($ch,CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookies.txt');
    	//curl_setopt($ch,CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookies.txt');

		return $ch;
	}

	/**
	* Handles the HTTP response header lines from cURL requests, 
	* it currently extracts only cookie
	*/
	public function HeaderHandler($ch, $header_line)
	{    
	    if (strpos($header_line, 'Set-Cookie:') === 0)
	    {
	    	$cookie = trim(substr($header_line, strlen('Set-Cookie:')));
	    	$this->sessionCookies = $cookie;
	    }
	    return strlen($header_line);
	}

	/**
	* Performs a HTTP GET
	*/
	public function get($url, $headerFields = array())
	{
		$ch = $this->getCurlObject($url, $headerFields);

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	} 

	/**
	* Performs an AJAX GET, a shortcut function for API calls in Router
	*/

	public function ajaxGet($simplified_url)
	{
		return $this->get($this->getUrl($simplified_url), Array('X-Requested-With: XMLHttpRequest'));
	}

	/**
	* Performs HTTP POST
	*/
	public function post($url, $postFields = array(), $headerFields = array())
	{	
		if ($this->debugMode) {
		echo "PREPARING POST REQUEST:\n";
		print_r($postFields); }

		$ch = $this->getCurlObject($url, $headerFields);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	} 

	/**
	* Builds a complete URL to a given route in the API
	*/
	public function getUrl($route)
	{
		return $this->baseUrl.$route;
	}

	/**
	* Sets the base url for building full URL. It's a router IP address basically.
	*/
	public function setAddress($address)
	{
		$this->baseUrl = $address;
	}

}