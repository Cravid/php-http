<?php

namespace Cravid\Http;

interface RequestFactoryInterface
{
    /**
     *
     */
    public function create($method, $uri, array $headers = array(), $body = null);
}