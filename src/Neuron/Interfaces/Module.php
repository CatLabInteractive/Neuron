<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 16/11/14
 * Time: 14:35
 */

namespace Neuron\Interfaces;


use Neuron\Router;

interface Module
{
    /**
     * Set template paths, config vars, etc
     * @param string $routepath The prefix that should be added to all route paths.
     * @return void
     */
    public function initialize ($routepath);

    /**
     * Register the routes required for this module.
     * @param Router $router
     * @return void
     */
    public function setRoutes (Router $router);

}