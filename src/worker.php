<?php
/*
 * Copyright 2015 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Ratchet\Server\IoServer;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\Samples\Bookshelf\PubSub\AsyncConnection;
use Google\Cloud\Samples\Bookshelf\PubSub\LookupBookDetailsJob;
use Google\Cloud\Samples\Bookshelf\PubSub\Worker;
use Google\Cloud\Samples\Bookshelf\PubSub\HealthCheckListener;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../src/app.php';
$pubsub = $app['pubsub_client'];

// Listen to port 8080 for our health checker
$server = IoServer::factory(
    new HealthCheckListener(),
    8080
);

// set the Curl Multi handler
$handler = new CurlMultiHandler;
$server->loop->addPeriodicTimer(0, function () use ($handler) {
    $handler->tick();
});
$client = new Client([
    'handler' => HandlerStack::create($handler)
]);
$pubsub->connection = new AsyncConnection([], $client);

// create the topic/subscription if they do not exist.
$topic = $pubsub->topic(Worker::TOPIC_NAME);
if (!$topic->exists()) {
    $topic->create();
}
$subscription = $topic->subscription(Worker::SUB_NAME);
if (!$subscription->exists()) {
    $subscription->create();
}

// add our job to the event loop
$job = new LookupBookDetailsJob($app['bookshelf.model'], $app['google_client']);
$worker = new Worker($subscription, $job);
$server->loop->addPeriodicTimer(0, $worker);
$server->run();
