<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 30/11/14
 * Time: 18:49
 */

namespace Neuron\Collections;
use Neuron\Interfaces\Model;

/**
 * Class TokenizedCollection
 *
 * @package Neuron\Collections
 */
class ModelCollection
	extends Collection {

	private $map = array ();

	public function __construct ()
	{
		$this->on ('add', array ($this, 'onAdd'));
		$this->on ('set', array ($this, 'onAdd'));
		$this->on ('unset', array ($this, 'onUnset'));
	}

	protected function onAdd (Model $model, $offset)
	{
		$this->map[$model->getId ()] = $model;
	}

	protected function onUnset (Model $model = null, $offset = null)
	{
		if ($model)
			unset ($this->map[$model->getId ()]);
	}

	public function getFromId ($id)
	{
		if (isset ($this->map[$id]))
		{
			return $this->map[$id];
		}
		return null;
	}
}