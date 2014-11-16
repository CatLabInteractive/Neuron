<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 16/11/14
 * Time: 16:54
 */

class Module
    implements \Neuron\Interfaces\Module
{
    /**
     * Set paths
     * @return void
     */
    public function initialize()
    {
        \Neuron\Core\Template::addTemplatePath (dirname (__FILE__) . '/templates/', 'example/');
    }

    /**
     * Register the routes required for this module.
     * @param \Neuron\Router $router
     * @param $prefix
     * @return mixed
     */
    public function setRoutes (\Neuron\Router $router, $prefix)
    {
        $router->get ($prefix . '/doSomething', 'TestController@doSomething');
        $router->get ($prefix . '/foo', 'TestController@foo');
        $router->get ($prefix . '/template', 'TestController@template');

        $router->get ($prefix . '/', 'TestController@home');
    }
}