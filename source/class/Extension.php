<?php

namespace Planck\Extension;

use Phi\HTML\CSSFile;
use Phi\HTML\JavascriptFile;
use Phi\Traits\Introspectable;
use Planck\Application\Aspect;
use Planck\Exception\DoesNotExist;
use Planck\Extension\FrontVendor\Package\Planck;
use Planck\Helper\File;
use Planck\Routing\Route;
use Planck\Traits\HasLocalResource;
use Planck\Traits\IsApplicationObject;




class Extension
{

    use IsApplicationObject;
    use HasLocalResource;
    use Introspectable;


    const ENTITY_FILEPATH = 'source/class/Model/Entity';


    protected $namespace;
    protected $path;
    protected $sourcePath;

    protected $autoloader;



    /**
     * @var Module[]
     */
    protected $modules = [];

    /**
     * @var Application
     */
    //protected $application;



    protected $urlPattern;


    /**
     * @var Aspect[]
     */
    protected $aspects = [];


    public function __construct(Application $application = null)
    {

        $classDefinitionPath = $this->getDefinitionFolder();

        $this->sourcePath = $classDefinitionPath;
        $this->path = File::normalize(realpath($this->sourcePath.'/../..'));

        //the extension class name is the same as the extension namespace
        $this->namespace = get_class($this);



        $this->setApplication($application);
        $this->loadAspects($application);
        $this->loadModules();




        $this->addFrontPackage(
            new Planck()
        );
    }


    public function setApplication(Application $application)
    {
        $this->application = $application;
        return $this;
    }



    public function getFilepath()
    {

        return File::normalize($this->path);
    }

    public function getAssetsFilepath($normalize = true)
    {
        $path = realpath($this->getFilepath().'/asset');
        if(!$normalize) {
            return $path;
        }
        else {
            return str_replace('\\', '/', $path);
        }
    }


    public function getJavascriptsFilepath()
    {
        return $this->getAssetsFilepath().'/javascript';
    }





    public function getName()
    {
        return $this->namespace;
    }

    public function getBaseName()
    {
        return basename(str_replace('\\', '/', get_class($this)));
    }


    /**
     * @return Route[]
     */
    public function getRoutes()
    {
        $routes = [];
        foreach ($this->getModules() as $module) {
            $moduleRoutes = $module->getRoutes();
            $routes = array_merge($routes, $moduleRoutes);
        }
        return $routes;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        $entities = [];
        if(is_dir($this->getFilepath().'/'.static::ENTITY_FILEPATH)) {
            $files = File::rglob($this->getFilepath().'/'.static::ENTITY_FILEPATH.'/*.php');

            foreach ($files as $file) {

                $className = str_replace($this->getFilepath().'/'.static::ENTITY_FILEPATH.'/', '', File::normalize($file));

                $className = str_replace('.php', '', $className);
                $className = get_class($this).'\Model\Entity\\'.str_replace('/', '\\', $className);


                if(class_exists($className)) {
                    $entities[] = $className;
                }
            }
        }

        return $entities;
    }



    public function setURLPattern($pattern)
    {
        $this->urlPattern = $pattern;
        return $this;
    }


    /**
     * @param Application $application
     * @return $this
     */
    public function loadAspects(Application $application)
    {
        $aspectFilepath = $this->sourcePath.'/Aspect';


        if(!is_dir($aspectFilepath)) {
            return $this;
        }

        $aspects = glob($aspectFilepath.'/*');


        foreach ($aspects as $path) {


            $aspectName = str_replace('.php', '', basename($path));
            $className = $this->namespace.'\Aspect\\'.$aspectName;


            $aspect = new $className($application);


            /**
             * @var Aspect $aspect
             */
            $application->addAspect($aspect, $aspect->getName());

            $this->aspects[$aspect->getName()] = $aspect;


        }

        return $this;
    }




    /**
     * @return $this
     */
    public function loadModules()
    {
        $moduleFilepath = $this->sourcePath.'/Module';

        $modules = glob($moduleFilepath.'/*');

        foreach ($modules as $path) {

            $moduleName = basename($path);
            $namespace = $this->namespace.'\Module\\'.$moduleName;



            $module = new Module($this->application, $namespace, $this, $path);
            $this->modules[$moduleName] = $module;
        }

        return $this;
    }

    /**
     * @return Module[]
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * @param $moduleName
     * @return Module
     * @throws DoesNotExist
     */
    public function getModule($moduleName)
    {

        if(array_key_exists($moduleName, $this->modules)) {
            return $this->modules[$moduleName];
        }

        throw new DoesNotExist('Module '.$moduleName.' does not exists');
    }


    public function buildURL($moduleName, $routerName, $routeName, $parameters = array())
    {

        return $this->urlPattern.
            $this->getModule($moduleName)
                ->getRouter($routerName)
                ->getRouteByName($routeName)
                ->buildURL($parameters)
            ;
    }


    public function getCSS($toObject = true)
    {
        $assets = [];

        $assetPath = $this->path.'/asset';

        $css = File::rglob($assetPath.'/css/*.css');
        foreach ($css as $cssPath) {
            $cssBasename = str_replace($this->getAssetsFilepath(), '', $cssPath);
            if($toObject) {
                $assets[$cssPath] = $this->getExtensionCSS($cssBasename);
            }
            else {
                $assets[$cssPath] = $cssPath;
            }
        }
        return $assets;
    }

    public function getCSSRequirements()
    {
        $assets = [];

        foreach ($this->getFrontPackages() as $package) {
            $descriptor = $package->getCSSPackageDescriptor();
            foreach ($descriptor as $filepath => $css) {
                $assets[$filepath] = $css;
            }
        }

        return $assets;

    }


    //=======================================================

    public function getJavascriptRequirements()
    {
        $assets = [];

        foreach ($this->getFrontPackages() as $package) {
            $descriptor = $package->getJavascriptPackageDescriptor();
            foreach ($descriptor as $filepath => $javascript) {
                $assets[$filepath] = $javascript;
            }
        }

        return $assets;

    }

    public function getJavascripts($toObject = true)
    {
        $assets = [];

        //=======================================================

        $assetPath = $this->path.'/asset';

        $javascripts = glob($assetPath.'/javascript/*.js');
        foreach ($javascripts as $javascript) {

            $javascript = File::normalize($javascript);

            if($toObject) {
                $javascriptBasename = str_replace($this->getAssetsFilepath(), '', $javascript);
                $assets[$javascript] = $this->getExtensionJavascript($javascriptBasename);
            }
            else {
                $assets[$javascript] = $javascript;
            }
        }


        $javascripts = File::rglob($assetPath.'/javascript/class/*.js');
        foreach ($javascripts as $javascript) {
            $javascriptBasename = str_replace($this->getAssetsFilepath(), '', $javascript);
            if($toObject) {
                $assets[$javascript] = $this->getExtensionJavascript($javascriptBasename);
            }
            else {
                $assets[$javascript] = $javascript;
            }

        }

        return $assets;
    }


    public function getAssets($toObject = true)
    {


        return array_merge(
            $this->getJavascripts($toObject),
            $this->getCSS($toObject)
        );

    }



    public function getExtensionCSS($css)
    {

        $loaderURL = $this->getFromContainer('extension-css-loader-url' );

        $url = $loaderURL.'&css='.$css.'&extension='.rawurlencode($this->getName());

        $data = null;

        $cssInstance = new CSSFile($url, $data);

        $cssInstance->setKey($css);
        return $cssInstance;
    }



    public function getExtensionJavascript($javascript)
    {

        $loaderURL = $this->getFromContainer('extension-javascript-loader-url' );

        $url = $loaderURL.'&javascript='.$javascript.'&extension='.rawurlencode($this->getName());


        $data = null;
        $javascriptInstance = new JavascriptFile($url, $data);

        $javascriptInstance->setKey($javascript);
        return $javascriptInstance;
    }







}


