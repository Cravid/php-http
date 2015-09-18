<?php

namespace Cravid\Http;

/**
 *
 */
interface ClientInterface
{
    /**
     *
     *
     * @param string $target Target URI.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get($target);

    /**
     *
     *
     * @param string $target Target URI.
     * @param mixed[] $data Data to send.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($target, array $data);

    /**
     *
     *
     * @param \Psr\Http\Message\RequestInterface $request Specific request instance.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send(\Psr\Http\Message\RequestInterface $request);
}