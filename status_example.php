<?php
/**
Most simple example
*/
require_once 'vendor/autoload.php';

$router = new HuaweiApi\Router;

const IP       = "192.168.1.1";
const USERNAME = "user"; // probably always 'user'
const PASSWORD = "MEzimentis96";

//$router->enableDebug();
$router->setAddress(IP);
$router->login(USERNAME, PASSWORD);

print_r($router->getModemStatus());
