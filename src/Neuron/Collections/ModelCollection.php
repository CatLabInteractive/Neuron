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

    /**
     * @param Model|null $model
     * @param null $offset
     */
	protected function onAdd (Model $model = null, $offset = null)
	{
	    if ($model) {
            $this->map[$model->getId ()] = $model;
        }
	}

    /**
     * @param Model|null $model
     * @param null $offset
     */
	protected function onUnset (Model $model = null, $offset = null)
	{
		if ($model) {
            unset ($this->map[$model->getId()]);
        }
	}

	/**
	 * Return all ids.
	 * @return array
	 */
	public function getIds ()
    {
		return array_keys ($this->map);
	}

    /**
     * @param $id
     * @return mixed|null
     */
	public function getFromId ($id)
	{
		if (isset ($this->map[$id])) {
			return $this->map[$id];
		}
		return null;
	}
}