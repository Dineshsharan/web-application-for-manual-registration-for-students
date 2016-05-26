<?php

require_once __DIR__ . '/../vendor/autoload.php';

/** @var Silex\Application $app */
$app = require __DIR__ . '/../src/app.php';

/** @var Google_Client $client */
$client = $app['google_client'];

/** @var DataModelInterface $model */
$model = $app['bookshelf.model'];

