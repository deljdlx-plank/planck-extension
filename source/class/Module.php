<?php

namespace Planck\Extension;


use Planck\Exception\DoesNotExist;
use Planck\Helper\StringUtil;
use Planck\Routing\Route;
use Planck\Routing\Router;
use Planck\Traits\HasLocalResource;
use Planck\Traits\IsApplicationObject;

class Module
{


    use IsApplicationObject;
    use HasLocalResource;

    /**
     * @var Extension
     */
    protected $extension;


    /**
     * @var Application;
     */
    protected $application;


    /**
     * @var Router[]
     */
    protected $routers;


    protected $namespace;
    protected $filepath;




    public function __construct(Application $application, $namespace, Extension $extension, $filepath)
    {

        $this->application = $application;



        $this->namespace = $namespace;
        $this->extension = $extension;
        $this->filepath = $filepath;
    }


    public function getExtension()
    {
        return $this->extension;
    }



    /**
     * @return Route[]
     */
    public function getRoutes()
    {

        $routes = [];

        foreach ($this->getRouters() as $routerName => $router) {

            foreach ($router->getRoutes() as $routeName => $route) {
                $key =
                    ''.
                    StringUtil::camelCaseToSeparated($this->extension->getBaseName()).'/'.
                    StringUtil::camelCaseToSeparated($this->getBaseName()).'/'.
                    strtolower($routerName).
                    '['.$routeName.']';
                $routes[$key] = $route;
            }
        }
        return $routes;
    }


    public function getName()
    {
        return $this->extension->getName().'\\'.$this->getBaseName();
    }


    public function getBaseName()
    {
        return basename(str_replace('\\', '/', $this->namespace));
    }


    public function loadRouters()
    {


        $this->routers = array();

        $routerFilepath = $this->filepath.'/Router';

        if(!is_dir($routerFilepath)) {
            return $this;
        }

        $routers = glob($routerFilepath.'/*.php');


        foreach ($routers as $path) {
            include($path);

            $routerName = strtolower(basename(str_replace('.php', '', $path)));

            $routerClassName = $this->namespace.'\Router\\'.$routerName;

            $router = new $routerClassName($this->application);


            $this->routers[$routerName] = $router;
        }

        return $this;
    }


    /**
     * @return Router[]
     */
    public function getRouters()
    {
        if($this->routers === null) {
            $this->loadRouters();
        }

        return $this->routers;
    }


    /**
     * @param $routerName
     * @return Router
     * @throws DoesNotExist
     */
    public function getRouter($routerName)
    {

        $routerName = strtolower($routerName);

        if(array_key_exists($routerName, $this->routers)) {
            return $this->routers[$routerName];
        }

        throw new DoesNotExist('Router '.$routerName.' does not exists');
    }

    public function buildURL($routerName, $routeName, $parameters = array())
    {
        $router = $this->getRouter($routerName);
        return $router->build($routeName, $parameters);
    }


    /*
    public function getAssets($getExtensionAssets = true)
    {
        $assets = [];

        if($getExtensionAssets) {
            $assets = $this->getExtension()->getAssets();
        }


        $assetPath = $this->filepath.'/asset';

        $javascripts = File::rglob($assetPath.'/javascript/class/*.js');
        foreach ($javascripts as $javascript) {
            $script = $this->getLocalJavascriptFile($javascript);
            $assets[] = $script;
        }

        $javascripts = glob($assetPath.'/javascript/*.js');
        foreach ($javascripts as $javascript) {
            $script = $this->getLocalJavascriptFile($javascript);
            $assets[] = $script;
        }

        $css = File::rglob($assetPath.'/css/*.css');
        foreach ($css as $cssPath) {
            $cssFile = $this->getLocalCSSFile($cssPath);
            $assets[] = $cssFile;
        }
        return $assets;
    }
    */






}
