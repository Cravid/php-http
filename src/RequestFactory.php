<?php

namespace Cravid\Http;

class RequestFactory implements RequestFactoryInterface
{
    /**
     *
     */
    public function create($method, $uri, array $headers = array(), $body = null)
    {
        $request = new Request();

        $request = $request->withMethod($method);

        if (!$uri instanceof \Psr\Http\Message\UriInterface) {
            $uri = new Uri($uri);
        }
        $request = $request->withUri($uri);

        foreach ($headers as $key => $value)
        {
            $request = $request->withHeader($key, $value);
        }

        if ($body) {
            if (is_array($body)) {
                $body = new StringStream(http_build_query($body));
            } else {
                $body = new StringStream($body);
            }
        } else {
            $body = new StringStream('');
        }
        $request = $request->withBody($body);

        return $request;
    }
}