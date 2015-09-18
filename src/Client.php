<?php

namespace Cravid\Http;

/**
 *
 */
class Client
{
   /**
    * Request factory instance.
    *
    * @var RequestFactoryInterface
    */
    private $factory;

    /**
     *
     */
    const GET = 'GET';
    const POST = 'POST';


    /**
     *
     */
    public function __construct(RequestFactoryInterface $factory = null)
    {
        if ($factory === null) {
            $factory = new RequestFactory();
        }
        $this->factory = $factory;
    }

    /**
     *
     *
     * @param string $target Target URI.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get($target, $data = null)
    {
        $separator = (parse_url($target, PHP_URL_QUERY) == null) ? '?' : '&';
        $target .= $separator . http_build_query($data);

        $request = $this->factory->create(self::GET, $target, array(
            'Content-Type'  => 'text/plain',
        ));

        return $this->send($request);
    }

    /**
     *
     *
     * @param string $target Target URI.
     * @param mixed $data Data to send.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($target, $data = null)
    {
        $request = $this->factory->create(self::POST, $target, array(
            'Content-Type'  => 'application/x-www-form-urlencoded',
            //'Content-Type'  => 'application/json',
        ), $data);

        return $this->send($request);
    }

    /**
     *
     *
     * @param \Psr\Http\Message\RequestInterface $request Specific request instance.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send(\Psr\Http\Message\RequestInterface $request)
    {
        $header = '';
        foreach ($request->getHeaders() as $key => $value)
        {
            $header .= $key . ': ' . $request->getHeaderLine($key) . "\r\n";
        }

        $context = stream_context_create(array(
            $request->getUri()->getScheme()   => array(
                'method'            => $request->getMethod(),
                'content'           => $request->getBody()->getContents(),
                'request_fulluri'   => true,
                'protocol_version'  => (float)$request->getProtocolVersion(),
                'header'            => $header,
            ),
        ));

        $stream = new Stream((string)$request->getUri(), 'r', false, $context);
        
        $meta = $stream->getMetaData('wrapper_data');
        $status = $meta[0];
        $result = explode(' ', $status);
        
        $response = (new Response())
            ->withStatus($result[1], $result[2])
            ->withBody(new StringStream($stream->getContents()))
        ;

        foreach ($meta as $value)
        {
            $result = explode(': ', $value);
            if (count($result) === 2) {
                $response = $response->withHeader($result[0], $result[1]);
            }
        }

        return $response;
    }
}