<?php

namespace Cravid\Http;

class Configuration
{
    private $config = array(
        'username'      => 'goldkiwi',
        'password'      => 'jb39S5Pj',
        'serviceCode'   => 'AT020060',
        'uri'           => 'http://premium.mobile-gw.com:9000',
        'price'         => 1000,
        'operatorCodes' => array(
            23201           => 'Mobilkom',
            23209           => 'Tele2 Mobil',
            23210           => 'H3G',
            23205           => 'Orange',
            23207           => 'TeleRing',
            23203           => 'T-Mobile',
        ),
    );

    public function __get($key)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        throw new Exception('key ' . $key . ' not found');
    }
}