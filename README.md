# Huawei C2338-168 API

This project will let you interface with your Huawei C2238-168 router.

## Features

 - Query router status (uptime, LTE frequency used)
 - Query traffic statistics
 - Query modem status
 - Perform a LTE reconnect
 - Much more is not programmed yet, but now it is very easy

## Documentation

How do I use this library?

Perform `composer install`. Include the composer autoloader into your project and proceed to make a HuaweiApi\Router object. Set the address of your router and login. For available commands, please take a look at the source code.

```php
require_once 'vendor/autoload.php';

$router = new HuaweiApi\Router;

const IP       = "192.168.1.1";
const USERNAME = "user"; // probably always 'user'
const PASSWORD = "your-password";

$router->setAddress(IP);
$router->login(USERNAME, PASSWORD);

print_r($router->getModemStatus());
```

Answer:

```
Array
(
    [modem_info] => Array
        (
            [connection_status] => 3
            [connection_time] => 32307
            [op_name] => T-Mobile
            [plmn] => 32001
            [ecgi] => 3200123C8A70
            [eci_hex] => 23C8A70
            [cell_id] => 35
            [enb_id] => 3009A
            [imei] => 862021010171259
            [imsi] => 731251010912262
            [iccid] => 86540036009861221C
            [sim_status] => 1
            [pin_retries] => 3
            [puk_retries] => 10
            [rx_bit_rate] => 2146
            [tx_bit_rate] => 6911
            [rssi] => -54
            [sinr] => 20
            [rsrp] => -77
            [rsrq] => -4
            [rat] => 7
            [roaming] => 0
            [tx_power] => 
            [pci] => 187
            [cqi] => 15
            [band] => 1
            [tx_mcs] => 
            [ber] => 
            [bw_mhz] => 15
            [earfcn] => 473
            [rank] => 2
            [dl_ca] => 0
            [ul_ca] => 0
        )

    [traffic] => Array
        (
            [data_tx] => 10383423896
            [data_rx] => 37459290022
            [voip_tx] => 0
            [voip_rx] => 0
        )

)
```