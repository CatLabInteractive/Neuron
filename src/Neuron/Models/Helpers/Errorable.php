<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/05/14
 * Time: 11:27
 */

namespace Neuron\Models\Helpers;

use Neuron\Collections\ErrorCollection;
use Neuron\Models\Error;

/**
 * Class Errorable
 *
 * Provide some default methods to set and return errors.
 *
 * @package Neuron\Models\Errorable
 */
abstract class Errorable
{

    /**
     * @var ErrorCollection array
     */
    private $errors = null;

    /**
     *
     */
    private function touchErrors()
    {
        if (!isset ($this->errors)) {
            $this->setErrors($this->createErrorCollection());
        }
    }

    /**
     * @return ErrorCollection
     */
    protected function createErrorCollection()
    {
        return new ErrorCollection ();
    }

    /**
     * @param string $error
     */
    public function setError($error)
    {
        return call_user_func_array(array($this, 'addError'), func_get_args());
    }

    /**
     * Set the error array. By reference!
     */
    public function setErrors(ErrorCollection $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return string|null
     */
    public function getError()
    {
        $this->touchErrors();
        if (count($this->errors) > 0) {
            return end($this->errors);
        }
        return null;
    }

    /**
     * @param $error
     * @return Error
     */
    public function addError($error)
    {
        $args = func_get_args();
        array_shift($args);

        $this->touchErrors();
        return $this->errors->addError($error, $args);
    }

    /**
     * @return ErrorCollection
     */
    public function getErrors()
    {
        $this->touchErrors();
        return $this->errors;
    }

} 
