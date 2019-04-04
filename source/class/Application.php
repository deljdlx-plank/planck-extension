<?php


namespace Planck\Extension;


use Phi\Routing\Request;
use Planck\Exception\DoesNotExist;
use Planck\Routing\Route;

class Application extends \Planck\Application\Application
{



    /**
     * @var Extension[]
     */
    protected $extensions =array();
    protected $extensionsRoutePrefix =array();

    /**
     * @var Loader
     */
    protected $extensionLoader;



    public function __construct($path = null, $instanceName = null, $autobuild = true)
    {
        parent::__construct($path, $instanceName, $autobuild);


        $this->extensionLoader = new Loader();
        $this->extensionLoader->setApplication($this);
    }



    public function initialize()
    {

        parent::initialize();
        $this->loadExtensions();
    }







    public function addExtension($extensionName, $routeValidator = null)
    {
        $this->extensions[$extensionName] = false;
        if($routeValidator !== null) {
            $this->extensionsRoutePrefix[$extensionName] = $routeValidator;
        }
        return $this;
    }


    public function loadExtensions()
    {
        foreach ($this->extensions as $extensionName => $value) {
            if($value === false) {
                $this->extensions[$extensionName] = $this->loadExtension($extensionName);
            }
        }

        return $this;

    }

    protected function loadExtension($extensionName)
    {
        $extension= $this->extensionLoader->loadExtension($extensionName);

        $pattern = null;
        if(array_key_exists($extensionName, $this->extensionsRoutePrefix)) {
            $pattern = $this->extensionsRoutePrefix[$extensionName];
        }
        $this->registerExtension($extension, $pattern);
        return $extension;
    }


    //=======================================================

    public function registerExtension(Extension $extension, $routeValidator = null)
    {
        //$this->extensions[$extension->getName()] = $extension;


        if($routeValidator) {
            $extension->setURLPattern($routeValidator);
        }


        $extension->setApplication($this);


        foreach ($extension->getModules() as $module) {

            $routers = $module->getRouters();
            foreach ($routers as $router) {

                $router->setApplication($this);

                if(is_string($routeValidator) && $routeValidator !== '') {
                    $router->addValidator(function (Request $request) use ($routeValidator) {

                        if (strpos($request->getURI(), $routeValidator) !== false) {
                            return true;
                        }
                        return false;
                    });
                }
                $this->addRouter($router, get_class($router));
            }
        }


        return $this;
    }




    //=======================================================
    /**
     * @return Route[]
     */
    public function getRoutes()
    {
        $routes = array();
        $registeredRouters = [];

        foreach ($this->extensions as $extension) {
            $extensionRoutes = $extension->getRoutes();
            foreach ($extensionRoutes as $routeName => $route) {

                $registeredRouters[get_class($route->getRouter())] = true;

                $key = '/'.$routeName;
                $routes[$key] = $route;
            }
        }


        $routes = array_merge(
            $routes,
            parent::getRoutes()

        );

        return $routes;

    }

    public function buildRoute($routeName, array $parameters = array())
    {

        $route = $this->getRouteByFingerPrint($routeName);

        if(!$route) {
            throw new DoesNotExist('No route with name '.$routeName.' registered');
        }

        $url = $route->buildURL($parameters);


        if($route->getRouter()->hasExtension()) {
            $extensionName = $route->getRouter()->getExtension()->getName();
            if(array_key_exists($extensionName, $this->extensionsRoutePrefix)) {
                $url = $this->extensionsRoutePrefix[$extensionName].$url;
            }

        }

        return $url;
    }





}






