<?php

error_reporting(-1);

require_once('vendor/autoload.php');

$context = stream_context_create(array(
    'http' => array(
        // values from the request
        'method'           => 'POST',
        'header'           => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: 185\r\nAccept: */*\r\nHost: premium.mobile-gw.com:9000",
        'content'          => "username=wetterstein&password=rg9SN52s&serviceCode=DE011144&command=wapIdentifyUser&userIp=81.210.128.18&callbackUrl=http%3A%2F%2Fsingle.de.premium-billing.info%2Fjs%2Fidentify_check.js",
        'protocol_version' => 1.1,
        // values from the current client
        'ignore_errors'    => true,
        'follow_location'  => true,
        'max_redirects'    => 6,
        'timeout'          => 5,
    ),
));
var_dump($context);
die();