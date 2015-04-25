<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 25/04/15
 * Time: 14:19
 */

namespace Neuron\Mappers;

use Neuron\Collections\Collection;
use Neuron\Exceptions\InvalidParameter;
use Neuron\Interfaces\Model;

abstract class CachedMapper
	extends BaseMapper {

	private $models = array ();

	private $collections = array ();

	/**
	 * Check if we have already loaded the model.
	 * @param $id
	 * @return bool
	 */
	protected function hasModel ($id) {
		return isset ($this->models[$id]);
	}

	/**
	 * Return model.
	 * @param $id
	 * @return Model|null
	 */
	protected function getModel ($id) {
		return isset ($this->models[$id]) ? $this->models[$id] : null;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	protected function hasCollection ($name) {
		return isset ($this->collections[$name]);
	}

	/**
	 * @param $name
	 * @return null
	 */
	protected function getCollection ($name) {
		return isset ($this->collections[$name]) ? $this->collections[$name] : null;
	}

	protected function cache ($value, $name = null) {
		if ($value instanceof Collection) {

			if (!isset ($name)) {
				throw new InvalidParameter ("When caching a collection, you must provide a unique name.");
			}

			// Set the collection.
			$this->collections[$name] = $value;

			foreach ($value as $v) {
				$this->cache ($v);
			}
		}

		else if ($value instanceof Model) {
			$this->models[$value->getId ()] = $value;
		}

		else if ($value === null) {

			if (!isset ($name)) {
				throw new InvalidParameter ("When caching a null vlaue, you must provide a unique id.");
			}

			$this->models[$name] = $value;
		}

		else {
			throw new InvalidParameter ("You must always cache collections or models.");
		}
	}

}