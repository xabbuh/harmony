<?php
namespace WoohooLabs\Harmony;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use WoohooLabs\Harmony\Exception\MiddlewareReturnTypeException;

class Harmony
{
    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var int
     */
    protected $currentMiddleware = -1;

    /**
     * @var int
     */
    protected $currentFinalMiddleware = -1;

    /**
     * @var bool
     */
    protected $stopped = false;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->response = $response;
        $this->request = $request;
    }

    /**
     *  Executes the final middlewares.
     */
    public function __destruct()
    {
        if ($this->stopped === false) {
            $this->stopped = true;
            $this->__invoke();
        }
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        if ($request !== null) {
            $this->request = $request;
        }

        if ($response !== null) {
            $this->response = $response;
        }

        // Retrieving the key of the next middleware of the appropriate type
        if ($this->stopped === true) {
            $nextKey = &$this->currentFinalMiddleware;
        } else {
            $nextKey = &$this->currentMiddleware;
        }
        $nextKey = $this->getNextMiddlewareKey($nextKey + 1, $this->stopped);

        // Stopping if there aren't any executable middlewares remaining
        if ($nextKey === null) {
            return $this->response;
        }

        // Executing the next middleware
        $this->executeMiddleware($this->middlewares[$nextKey]["callable"]);

        return $this->response;
    }

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $id
     * @return callable|null
     */
    public function getMiddleware($id)
    {
        $position = $this->findMiddleware($id);

        if ($position === null) {
            return null;
        }

        return $this->middlewares[$position]["callable"];
    }

    /**
     * @param string $id
     * @param callable $middleware
     * @return $this
     */
    public function addMiddleware($id, callable $middleware)
    {
        $this->middlewares[] = ["id" => $id, "callable" => $middleware, "final" => false];

        return $this;
    }

    /**
     * @param string $id
     * @param callable $middleware
     * @return $this
     */
    public function addFinalMiddleware($id, callable $middleware)
    {
        $this->middlewares[] = ["id" => $id, "callable" => $middleware, "final" => true];

        return $this;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function removeMiddleware($id)
    {
        $position = $this->findMiddleware($id);

        if ($position !== null) {
            unset($this->middlewares[$position]);
        }

        return $this;
    }

    /**
     * @param int $fromKey
     * @param bool $isFinal
     * @return int|null
     */
    protected function getNextMiddlewareKey($fromKey, $isFinal)
    {
        for (; isset($this->middlewares[$fromKey]); $fromKey++) {
            if ($this->middlewares[$fromKey]["final"] === $isFinal) {
                return $fromKey;
            }
        }

        return null;
    }

    /**
     * @param string $id
     * @return int|null
     */
    protected function findMiddleware($id)
    {
        foreach ($this->middlewares as $k => $middleware) {
            if ($middleware["id"] === $id) {
                return $k;
            }
        }

        return null;
    }

    /**
     * @param callable $middleware
     * @throws \WoohooLabs\Harmony\Exception\MiddlewareReturnTypeException
     */
    protected function executeMiddleware(callable $middleware)
    {
        $response = $middleware($this->getRequest(), $this->getResponse(), $this);
        if ($response instanceof ResponseInterface) {
            $this->response = $response;
        } elseif ($response === null) {
            trigger_error(
                "Middlewares must return a \\Psr\\Http\\Message\\ResponseInterface instance!",
                E_USER_DEPRECATED
            );
        } else {
            throw new MiddlewareReturnTypeException();
        }
    }
}
