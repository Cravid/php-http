<?php

error_reporting(-1);

require_once('vendor/autoload.php');

$client = new \Cravid\Http\Client();
$data = array('foo' => 'bar');
//$data = json_encode($data);
$response = $client->post('http://localhost:8888/foobar.php?hallo=welt', $data);
//echo $response->getStatusCode() . PHP_EOL;
//echo $response->getReasonPhrase() . PHP_EOL;
echo $response->getBody() . PHP_EOL;