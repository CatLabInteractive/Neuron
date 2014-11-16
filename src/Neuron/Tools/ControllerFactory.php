<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 16/11/14
 * Time: 17:20
 */

namespace Neuron\Tools;


use Neuron\Exceptions\DataNotFound;

class ControllerFactory {

    static $in;

    public static function getInstance ()
    {
        if (!isset (self::$in))
        {
            self::$in = new self ();
        }
        return self::$in;
    }

    private $controllers = array ();

    private function __construct ()
    {

    }

    /**
     * @param $name
     * @return mixed
     * @throws DataNotFound
     */
    public function getController ($name)
    {
        if (class_exists ($name))
        {
            $this->controllers[$name] = new $name ();
        }
        else {
            throw new DataNotFound ("Controller not found: " . $name);
        }

        return $this->controllers[$name];
    }
}