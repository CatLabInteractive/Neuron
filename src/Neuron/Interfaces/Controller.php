<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 16/11/14
 * Time: 14:35
 */

namespace Neuron\Interfaces;


use Neuron\Net\Request;

interface Controller
{
    /**
     * Controllers must know what module they are from.
     * @param Module $module
     */
    public function __construct (Module $module = null);

    /**
     * Set (or clear) the request object.
     * @param Request $request
     * @return void
     */
    public function setRequest (Request $request = null);
}