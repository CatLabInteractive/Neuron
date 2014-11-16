<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 16/11/14
 * Time: 14:35
 */

namespace Neuron\Interfaces;


interface Module
{
    /**
     * Set template paths, config vars, etc
     * @return void
     */
    public function initialize ();

    /**
     * Register the routes required for this module.
     * @param \Neuron\Router $router
     * @param $prefix
     * @return mixed
     */
    public function setRoutes (\Neuron\Router $router, $prefix);

}