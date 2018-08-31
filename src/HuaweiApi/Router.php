<?php
namespace HuaweiApi;

/**
* This class handles getting security keys, login, and translates API calls to GET/POST queries
* TO-DO: feel free to apply best practices, clean the code and add more comments
* TO-DO: token auto-expiration & auto-refresh feature.
*/
class Router
{
	private $http = null; // our custom HTTP provider
	private $routerAddress = 'https://192.168.1.1'; // default IP address
	private $debugMode = false;
	
	const TOKEN_LENGTH = 64;
	const OTP_LENGTH = 8;
	const PUBLIC_KEY_MAX_EXPECTED_LENGTH = 500;

	public function __construct()
	{
		$this->http = new CustomHttpClient();
	}

	public function enableDebug()
	{	
		$this->debugMode = true;
		$this->http->enableDebug();
	}

	/**
	* Main API functions. Feel free to add more functions, I didn't need more than that.
	*/

	public function getRouterStatus()
	{
		$status = json_decode($this->http->ajaxGet('/cgi-bin/sysconf.cgi?page=status.asp&action=update_index_status'), true);
		
		if (json_last_error() !== JSON_ERROR_NONE) // && $this->debugMode)
		{
			echo "ERROR in getRouterStatus(): Probably because of an invalid or expired session token, router returned garbage HTML instead of JSON. Grab a new session token first.\n";
		}

		return (json_last_error() === JSON_ERROR_NONE) ? $status : false;
	}

	public function getLoginStatus()
	{
		$status = $this->getRouterStatus();
		return ($status['session'] == "pass");
	}

	public function getModemStatus()
	{
		$status = json_decode($this->http->ajaxGet('/cgi-bin/sysconf.cgi?page=status_modem_status.asp'), true);

		if (json_last_error() !== JSON_ERROR_NONE) // && $this->debugMode)
		{
			echo "ERROR in getModemStatus(): Probably because of an invalid or expired session token, router returned garbage HTML instead of JSON. Grab a new session token first.\n";
		}

		return (json_last_error() === JSON_ERROR_NONE) ? $status : false;
	}

	public function getLteBand()
	{
		$modem_status = $this->getModemStatus();
		return $modem_status['modem_info']['band'];
	}

	public function lteReconnect()
	{
		$postFields = [
			"page" => "command.asp",
			"action" => "lte_reconnect",
			"btn" => "reconnect",
			"time" => "1534793579592",
			];	
		
		$this->http->post($this->http->getUrl('/cgi-bin/sysconf.cgi'), $postFields, Array("X-Requested-With: XMLHttpRequest", "X-CSRFToken: ".$this->http->getSessionCsrfToken(), "Referer: https://192.168.1.1/index.asp"), true);

		// router will respond with empty body
	}

	/**
	* Performs a non-standard Huawei-style login procedure
	* @return true if login was probably successful (we got session CSRF and a cookie)
	*         you can check login status by another API call getLoginStatus() if you need to be sure
	*/

	public function login($username, $password)
	{
		$this->http->resetSession();

		if (!$this->grabPubkeyAndOtp())   return false;
		if (!$this->grabLoginCsrfToken()) return false;

		$csrf_token = $this->http->getCsrfToken();
		$otp        = $this->http->getOtp();
		$pubkey     = $this->http->getPubkey();
		
		$encrypted_username = "";
		openssl_public_encrypt ($username, $encrypted_username, $pubkey);

		$encrypted_password = "";
		openssl_public_encrypt ($password.$otp, $encrypted_password, $pubkey);

		$login_post_fields = [
			'action' => 'login',
			'page'   => 'login.asp',
			'csrftoken' => $csrf_token,
			'user_lang' => 'en',
			'user_name' => base64_encode($encrypted_username),
			'user_name_plaintext' => '',
			'user_passwd' => base64_encode($encrypted_password),
			'user_passwd_plaintext' => ''
			];

		$this->http->post($this->http->getUrl('/cgi-bin/sysconf.cgi'), $login_post_fields);
		
		// if we got *any* cookie && then successfuly grabbed csrf token for the current session, we consider the state as successful login
		return ($this->http->getSessionCookie() && $this->grabSessionCsrfToken());
	}

	/**
	* Sets the router address.
	*/
	public function setAddress($address)
	{
		// Remove trailing slash
		$address = rtrim($address, '/');

		// HTTPS is default type of connection
		if (strpos($address, 'http') !== 0) $address = 'https://'.$address;

		$this->routerAddress = $address;
		$this->http->setAddress($address);
	}

	/**
	* Grab security keys
	*/
	private function grabPubkeyAndOtp()
	{
		$login_pubkey_otp_json = $this->http->ajaxGet('/cgi-bin/sysconf.cgi?page=status.asp&action=get_pubkey');
		$login_pubkey_otp = json_decode($login_pubkey_otp_json, true);
		
		if (json_last_error() === JSON_ERROR_NONE && 
			strlen($login_pubkey_otp['pubkey']) <= self::PUBLIC_KEY_MAX_EXPECTED_LENGTH && 
			strlen($login_pubkey_otp['otp'])    == self::OTP_LENGTH)
		{
			printf("We got a PUB key and OTP for login:\n%s\n", $login_pubkey_otp_json);

			$this->http->setPubkey($login_pubkey_otp['pubkey']);
			$this->http->setOtp($login_pubkey_otp['otp']);
			return true;
		}
		
		printf("Getting public key and login one-time password failed.\n");
		return false;	
	}

	private function grabLoginCsrfToken()
	{
		// The router returns different response if 'X-Requested-With' is not used. For example, getting CSRF token without XMLHttpRequest header will retun javascript code instead of token. (WTF?)
		$login_csrf_token = $this->http->ajaxGet('/cgi-bin/sysconf.cgi?page=login.asp&action=get_csrf_token_file');
		
		if (strlen($login_csrf_token) == self::TOKEN_LENGTH && ctype_alnum($login_csrf_token))
		{
			printf("We got a CSRF token for login:\n%s\n", $login_csrf_token);
			$this->http->setLoginCsrfToken($login_csrf_token);
			return true;
		}
		
		printf("Getting CSRF token for login failed.\n");
		return false;
	}
	
	public function grabSessionCsrfToken()
	{
		$session_csrf_token = $this->http->ajaxGet('/cgi-bin/sysconf.cgi?page=status.asp&action=get_csrf_token');
		
		if (strlen($session_csrf_token) == self::TOKEN_LENGTH && ctype_alnum($session_csrf_token))
		{
			printf("We got a session CSRF token:\n%s\n", $session_csrf_token);
			$this->http->setSessionCsrfToken($session_csrf_token);
			return true;
		}
		
		printf("Getting session CSRF token failed.\n");
		return false;
	}
}