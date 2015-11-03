<?php

$fp = fopen('php://input', 'r');
echo stream_get_contents($fp);

header('HTTP/1.1 200 foobar');