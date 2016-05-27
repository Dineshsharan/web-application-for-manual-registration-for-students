<?php

namespace Google\Cloud\Samples\Bookshelf\PubSub;

use Psr\Http\Message\RequestInterface;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Cloud\PubSub\Connection\Rest;
use Google\Cloud\RequestWrapper;
use Google\Cloud\RestTrait;

/**
*
*/
class AsyncConnection extends Rest
{
    use RestTrait;

    private $promise;
    private $asyncRequestWrapper;

    public function __construct(array $config = [], $client)
    {
        $this->asyncRequestWrapper = new RequestWrapper($config + [
            'httpHandler' => function (RequestInterface $request, array $options = []) use ($client) {
                return $client->sendAsync($request, $options);
            },
            'authHttpHandler' => HttpHandlerFactory::build(),
        ]);

        parent::__construct($config);
    }

    /**
     * Delivers a request built from the service definition.
     *
     * @param string $resource The resource type used for the request.
     * @param string $method The method used for the request.
     * @param array $options Options used to build out the request.
     * @return array
     */
    public function sendAsync($resource, $method, array $options = [])
    {
        $requestOptions = array_intersect_key($options, [
            'httpOptions' => null,
            'retries' => null
        ]);

        $request = $this->requestBuilder->build($resource, $method, $options);

        return $this->asyncRequestWrapper->send(
            $request,
            $requestOptions
        );
    }

    /**
     * @param array $args
     */
    public function pull(array $args)
    {
        if (!$this->promiseIsPending()) {
            $this->promise = $this->sendAsync('subscriptions', 'pull', $args);
            $this->promise->then($args['then']);
        }

        // hack to prevent error in Google\Cloud\Pubsub\Subscription
        return ['receivedMessages' => []];
    }

    private function promiseIsPending()
    {
        return $this->promise && $this->promise->getState() == 'pending';
    }
}
