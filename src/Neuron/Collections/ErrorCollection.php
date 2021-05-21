<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 20/02/15
 * Time: 15:10
 */

namespace Neuron\Collections;

use Neuron\Models\Error;

/**
 * Class ErrorCollection
 * @package Neuron\Collections
 */
class ErrorCollection extends Collection {

    /**
     * Add an error message in vsprintf syntax
     * @param $errorMessage
     * @param array $arguments
     * @return Error
     */
    public function addError($errorMessage, $arguments = [])
    {
        $error = new Error($errorMessage, $arguments);
        $this->add($error);

        return $error;
    }

    /**
     * @return array
     */
    public function getDetailedData()
    {
        $out = array ();
        foreach ($this as $v) {
            if ($v instanceof Error) {
                $out[] = [
                    'message' => $v->getMessage(),
                    'template' => $v->getTemplate(),
                    'arguments' => $v->getArguments(),
                    'subject' => $v->getSubject(),
					'code' => $v->getCode()
                ];
            } else {
                $out[] = [
                    'message' => $v->getMessage(),
                    'template' => $v->getMessage(),
                    'arguments' => [],
                    'subject' => null,
					'code' => null
                ];
            }
        }
        return $out;
    }

    /**
     * @return string[]
     */
	public function getData ()
	{
		$out = array ();
		foreach ($this as $v)
			$out[] = (string) $v;

		return $out;
	}
}
