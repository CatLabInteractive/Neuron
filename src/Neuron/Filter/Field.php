<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 28/04/14
 * Time: 15:05
 */

namespace Neuron\Filter;


use Neuron\Exceptions\InvalidParameter;

class Field
{
	private $name;
	private $valueCallback;
	private $callbacks;
	private $getter;

	public function __construct ($name)
	{
		$this->name = $name;
		$this->setGetter ('get' . ucfirst (strtolower ($this->name)));
	}

	public function setCallback ($valueCallback)
	{
		if (!is_callable ($valueCallback))
		{
			throw new InvalidParameter ("Callback must be callable.");
		}

		$this->valueCallback = $valueCallback;
	}

	public function setGetter ($getter)
	{
		$this->getter = $getter;
	}

	public function getName ()
	{
		return $this->name;
	}

	public function setOperation ($operation, $callback)
	{
		if (!is_callable ($callback))
		{
			throw new InvalidParameter ("Callback must be callable.");
		}

		$this->callbacks[$operation] = $callback;
	}

	public function operate ($operation, $model)
	{
		$arguments = func_get_args ();
		$operation = array_shift ($arguments);
		$model = array_shift ($arguments);

		foreach ($arguments as $k => $v)
		{
			// At this point, all arguments must be values.
			if ($v instanceof self)
			{
				$v[$k] = $v->getValue ($model);
			}
		}

		if (isset ($this->callbacks[$operation]))
		{
			return call_user_func_array ($this->callbacks[$operation], array ($model, $arguments[0]));
		}
		else
		{
			return $this->defaultOperation ($operation, $model, $arguments);
		}
	}

	public function getValue ($object)
	{
		if (isset ($this->valueCallback))
		{
			return call_user_func ($this->valueCallback, $object);
		}

		/** getName() */
		else if (is_object ($object) && is_callable (array ($object, $this->getter)))
		{
			return call_user_func (array ($object, $this->getter));
		}

		/** stdObject */
		else if (is_object ($object) && property_exists ($object, $this->getName ()))
		{
			return $object->{$this->getName ()};
		}

		/** assoc array */
		else if (is_array ($object) && isset ($object[$this->getName ()]))
		{
			return $object[$this->getName ()];
		}

		else
		{
			throw new InvalidParameter ("Could not get field " . $this->getName () . " from object");
		}
	}

	private function defaultOperation ($operation, $model, $arguments)
	{
		switch ($operation)
		{
			case Parser::OP_EQUALS:
				return $this->getValue ($model) == $arguments[0];
				break;

			case Parser::OP_CONTAINS:
				return strpos (strtolower ($this->getValue ($model)), strtolower ($arguments[0])) !== false;
				break;
		}
		return null;
	}
}
