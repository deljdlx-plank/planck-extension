<?php

namespace Planck\Extension;

use Phi\Core\Autoloader;
use Planck\Traits\IsApplicationObject;

class Loader
{
    use IsApplicationObject;

    protected $extentionFilepath;


    /**
     * @var Autoloader
     */
    protected $autoloader;


    /**
     * @var Extension[]
     */
    protected $loadedExtensions = [];



    public function __construct()
    {

    }



    public function isExtensionLoaded($extensionName)
    {
        if(array_key_exists($extensionName, $this->loadedExtensions)) {
            return true;
        }
        return false;
    }


    public function getExtension($extensionName)
    {
        if(!array_key_exists($extensionName, $this->loadedExtensions)) {
            throw new Exception('Extension '.$extensionName.' is not loaded');
        }
        return $this->loadedExtensions[$extensionName];
    }


    /**
     * @param $extensionName
     * @param $extensionPath
     * @param string $pattern
     * @return Extension
     * @throws Exception
     */
    public function loadExtension($extensionName)
    {

        if(array_key_exists($extensionName, $this->loadedExtensions)) {
            throw new Exception('Extension '.$extensionName.' already loaded');
        }



        $extension = new $extensionName(
            $this->getApplication()
        );



        $this->loadedExtensions[$extensionName] = $extension;


        return $extension;

    }




}
