<?php

namespace Google\Cloud\Samples\Bookshelf\PubSub;

use Google\Cloud\PubSub\Subscription;

class Worker
{
    const TOPIC_NAME = 'fill-book-details';
    const SUB_NAME = 'book-worker-sub';

    private $subscription;
    private $job;

    public function __construct(Subscription $subscription, $job)
    {
        $this->subscription = $subscription;
        $this->job = $job;
    }

    public function __invoke($timer)
    {
        $job = $this->job;
        $subscription = $this->subscription;

        $thenFunc = function ($response) use ($job, $subscription) {
            $ackIds = [];
            $messages = json_decode($response->getBody(), true);
            foreach ($messages['receivedMessages'] as $message) {
                $attributes = $message['message']['attributes'];
                $job->work($attributes['id']);
                $ackIds[] = $message['ackId'];
            }

            if (!empty($ackIds)) {
                $subscription->acknowledgeBatch($ackIds);
            }
        };

        // this is a hack to call the yield function
        foreach ($subscription->pull(['then' => $thenFunc]) as $ret);
    }
}
