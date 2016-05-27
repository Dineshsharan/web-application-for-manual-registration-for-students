<?php

namespace Google\Cloud\Samples\Bookshelf\PubSub;

use Google\Cloud\Samples\Bookshelf\DataModel\DataModelInterface;
use Google_Client;
use Google_Service_Books;

/**
*
*/
class LookupBookDetailsJob
{
    protected $model;
    protected $client;

    public function __construct(DataModelInterface $model, Google_Client $client)
    {
        $this->model = $model;
        $this->client = $client;
    }

    public function work($id)
    {
        if ($book = $this->model->read($id)) {
            $service = new Google_Service_Books($this->client);
            $options = array('orderBy' => 'relevance');
            $results = $service->volumes->listVolumes($book['title'], $options);

            foreach ($results as $result) {
                $volumeInfo = $result->getVolumeInfo();
                $imageInfo = $volumeInfo->getImageLinks();
                if ($thumbnail = $imageInfo->getThumbnail()) {
                    $book['imageUrl'] = $thumbnail;
                    return $this->model->update($book);
                }
            }
        }
    }
}
