<?php

namespace Cravid\Http;

/**
 *
 */
class Client implements ClientInterface
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
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     *
     */
    const REDIRECT_TEMPORARY = 1;
    const REDIRECT_PERMANENT = 2;
    const REDIRECT_PROXY = 3;
    const REDIRECT_HTML = 3;
    const REDIRECT_JS = 4;
    const REDIRECT_BLOCK = 5;



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
     */
    public function setRequestFactory(RequestFactoryInterface $factory = null)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     *
     *
     * @param string $target Target URI.
     * @param mixed[] $data Associative array of data to send.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get($target, array $data = array())
    {
        $separator = (parse_url($target, PHP_URL_QUERY) == null) ? '?' : '&';
        $target .= $separator . http_build_query($data);

        $request = $this->factory->create(self::METHOD_GET, $target, array(
            'Content-Type'  => 'text/plain',
        ));
        
        return $this->send($request);
    }

    /**
     *
     *
     * @param string $target Target URI.
     * @param mixed[] $data Associative array of data to send.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($target, array $data = array())
    {
        $request = $this->factory->create(self::METHOD_POST, $target, array(
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ), http_build_query($data));

        return $this->send($request);
    }

    /**
     *
     *
     * @param string $target Target URI.
     * @param mixed[] $data Associative array of data to send.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json($target, array $data = array())
    {
        $request = $this->factory->create(self::METHOD_POST, $target, array(
            'Content-Type'  => 'application/json',
        ), json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));

        return $this->send($request);
    }

    /**
     *
     *
     * @param string $target Target URI.
     * @param mixed[] $data Associative array of data to send with.
     * @param int $type Redirect type to use.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function redirect($target, array $data = array(), $type = self::REDIRECT_TEMPORARY)
    {
        $separator = (parse_url($target, PHP_URL_QUERY) == null) ? '?' : '&';
        $target .= $separator . http_build_query($data);
        
        $response = new Response();

        switch ($type)
        {
            case self::REDIRECT_TEMPORARY:
                $response->withStatus(Response::HTTP_TEMPORARY_REDIRECT);
                $response->withHeader('Location', $target);
                $response->withBody(new StringStream());
                break;
            case self::REDIRECT_PERMANENT:
                $response->withStatus(Response::HTTP_PERMANENTLY_REDIRECT);
                $response->withHeader('Location', $target);
                $response->withBody(new StringStream());
                break;
            case self::REDIRECT_PROXY:
                $response->withStatus(Response::HTTP_USE_PROXY);
                $response->withHeader('Location', $target);
                $response->withBody(new StringStream());
                break;
            case self::REDIRECT_HTML:
                $response->withStatus(Response::HTTP_TEMPORARY_REDIRECT);
                $response->withBody(new StringStream(sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="1;url=%1$s" />

        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($target, ENT_QUOTES, 'UTF-8'))));
                break;
            case self::REDIRECT_JS:
                $response->withStatus(Response::HTTP_TEMPORARY_REDIRECT);
                $response->withBody(new StringStream(sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />

        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.

        <script>
            setTimeout(function(){
                window.location.href = "{{ url }}"
            }, 10);
        </script>
    </body>
</html>', htmlspecialchars($target, ENT_QUOTES, 'UTF-8'))));
                break;
            case self::REDIRECT_BLOCK:
                $response->withStatus(Response::HTTP_TEMPORARY_REDIRECT);
                $response->withBody(new StringStream(sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />

        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.

        <script>
            setTimeout(function(){
                window.location.replace("{{ url }}")
            }, 10);

            history.pushState(null,null,location.href);
            window.onpopstate = function() {
                history.pushState(null,null,location.href);
            }
        </script>
    </body>
</html>', htmlspecialchars($target, ENT_QUOTES, 'UTF-8'))));
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Redirect type is not valid, given %s.', $type));
        }

        return $response;
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