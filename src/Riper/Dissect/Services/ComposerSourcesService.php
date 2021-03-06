<?php

namespace Riper\Dissect\Services;

use JMS\Serializer\SerializerInterface;

class ComposerSourcesService
{

    /**
     * @var String
     */
    protected $composerLockContent;
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var array
     */
    private $composerLockData = null;

    /**
     * @param $composerLockContent
     * @param $serializer
     */
    public function __construct($composerLockContent, SerializerInterface $serializer)
    {
        $this->composerLockContent = $composerLockContent;
        $this->serializer          = $serializer;
    }


    /**
     * Deserialize the content of the composer.lock
     *
     * @return array
     */
    private function getComposerLockData()
    {
        if (!$this->composerLockData) {
            $this->composerLockData = $this->serializer->deserialize($this->composerLockContent, 'array', 'json');
        }

        return $this->composerLockData;
    }

    public function getSources($type = null)
    {
        $composerLockData = $this->getComposerLockData();
        $sources          = array();
        foreach ($composerLockData['packages'] as $package) {
            if ($type === null || $package['source']['type'] = strtolower($type)) {
                $sources[] = array(
                    'url' => $package['source']['url'],
                    'packageName' => $package['name']
                );
            }
        }

        return $sources;
    }

    public function getDevSources($type=null){
        $composerLockData = $this->getComposerLockData();
        $sources          = array();
        foreach ($composerLockData['packages-dev'] as $package) {
            if ($type === null || $package['source']['type'] = strtolower($type)) {
                $sources[] = array(
                    'url' => $package['source']['url'],
                    'packageName' => $package['name']
                );
            }
        }

        return $sources;
    }
}