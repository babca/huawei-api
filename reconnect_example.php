<?php
/**
Huawei B2338-168 has an issue with band selection. In my case, after restart, is selects a band with a high internet speed. However, over time, it changes the frequency to a band with better signal, but lower maximum speeds.
Connecting to a target band directly isn't currently possible, however in my case the LTE reconnection action always selects my preferred (faster) band again.

This script will connect to specified Huawei B2338-168 and checks if it is connected to preferred band (frequency).
It performs a reconnect if not.

I have scheduled this script with cron to run every hour.
*/
require_once 'vendor/autoload.php';

$router = new HuaweiApi\Router;
$utils  = new HuaweiApi\Utils(dirname(__FILE__)."/log.txt");

// Main settings

const IP       = "192.168.1.1";
const USERNAME = "user"; // probably always 'user'
const PASSWORD = "your-password";
const PREFERRED_BAND = 20; // 1 = 2100Mhz, 3 = 1800Mhz, 7 = 2600Mhz, 20 = 800Mhz
const MAX_RETRY = 3;
const WAIT_BETWEEN_RETRIES = 60;
const MAX_EXECUTION_TIME = 300;

ini_set('max_execution_time', MAX_EXECUTION_TIME);

// Modem login

//$router->enableDebug();
$router->setAddress(IP);
$loggedIn = $router->login(USERNAME, PASSWORD);

if (!$loggedIn)
{
	printf("Connection to router unsuccesful. Check IP address, username and password.\n");
	exit();
}

// Your code

for ($retryCount = 0; $retryCount <= MAX_RETRY; $retryCount++)
{
	$currentModemStatus = $router->getModemStatus();
	$currentBand   = $currentModemStatus['modem_info']['band'];
	$currentUptime = $currentModemStatus['modem_info']['connection_time'];

	if (!$currentModemStatus)
	{
		echo "Communication error. Please enable debug mode. Is the session token valid?\n";
		break;
	}
	else if ($currentBand != PREFERRED_BAND && $retryCount >= MAX_RETRY)
	{
		$utils->logToFile(
			sprintf("Reconnection wasn't successful after %d retries. Band: %d, uptime: %s.",
			$retryCount,
			$currentBand,
			$utils->secondsToTime($currentUptime)));

		break;
	}
	else if ($currentBand != PREFERRED_BAND)
	{
		// wrong band -> perform a reconnect.
		$router->lteReconnect();

		$utils->logToFile(
			sprintf("Reconnecting%s. Band: %d, uptime: %s.",
			($retryCount > 0 ? sprintf(", retry #%d", $retryCount) : ""),
			$currentBand,
			$utils->secondsToTime($currentUptime)));

		sleep(WAIT_BETWEEN_RETRIES); // Wait a bit and check if the modem chose the preferred frequency. If not, retry N times more.
		$router->grabSessionCsrfToken(); // Token is valid for aprox. one minute. Let's grab a new token before the next cycle.
	}
	else
	{
		// preferred band
		$utils->logToFile(
			sprintf("Connected to preferred band%s. Band: %d, uptime: %s.",
			($retryCount > 0 ? sprintf(", after %d tries.", $retryCount) : ""),
			$currentBand,
			$utils->secondsToTime($currentUptime)));

		break;
	}
}
