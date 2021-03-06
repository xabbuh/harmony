<?php
namespace WoohooLabs\Harmony\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OutputBufferResponderMiddleware
{
    /**
     * @var bool
     */
    protected $onlyClearBuffer;

    /**
     * @param bool $clearBuffer
     */
    public function __construct($clearBuffer = false)
    {
        $this->onlyClearBuffer = $clearBuffer;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->onlyClearBuffer === true) {
            ob_end_clean();
        } else {
            ob_end_flush();
        }

        return $next();
    }

    /**
     * @return bool
     */
    public function isOnlyClearBuffer()
    {
        return $this->onlyClearBuffer;
    }

    /**
     * @param bool $onlyClearBuffer
     */
    public function setOnlyClearBuffer($onlyClearBuffer)
    {
        $this->onlyClearBuffer = $onlyClearBuffer;
    }
}
