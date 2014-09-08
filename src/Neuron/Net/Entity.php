<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 12/08/14
 * Time: 14:45
 */

namespace Neuron\Net;


use Neuron\Exceptions\InvalidParameter;
use Neuron\MapperFactory;
use Neuron\Models\User;

abstract class Entity {

	const CHECK_SIGNATURE = false;
	
	/** @var User */
	private $user;
	private $body;
	private $path;
	private $post;
	private $headers;
	private $session;
	private $data;
	private $cookies;
	
	/** @var integer $status */
	private $status;

	private $application;

	/**
	 * Serialize & deserialize requests
	 * @param $data
	 * @return \Neuron\Net\Request
	 * @throws \Neuron\Exceptions\InvalidParameter
	 */
	public function setFromData ($data)
	{
		// Check signature
		$model = $this;
		
		$chk = self::CHECK_SIGNATURE;

		if ($chk && !isset ($data['signature']))
		{
			throw new InvalidParameter ("All decoded requests must have a signature.");
		}

		if ($chk && $data['signature'] != self::calculateSignature ($data))
		{
			throw new InvalidParameter ("Leave now, and Never come Back! *gollem, gollem* (Decoded request signature mismatch).");
		}

		if (isset ($data['session']) && isset ($data['session']['user']))
		{
			$user = MapperFactory::getUserMapper ()->getFromId ($data['session']['user']);
			if ($user)
			{
				$model->setUser ($user);
			}
		}

		// The body. If data is found, body is not used.
		if (isset ($data['data']) && !empty ($data['data']))
		{
			$model->setData ($data['data']);
		}

		else if (isset ($data['body']))
		{
			$model->setBody ($data['body']);
		}

		if (isset ($data['session']))
		{
			$model->setSession ($data['session']);
		}

		if (isset ($data['headers']))
		{
			$model->setHeaders ($data['headers']);
		}

		if (isset ($data['cookies']))
		{
			$model->setCookies ($data['cookies']);
		}

		if (isset ($data['post']))
		{
			$model->setPost ($data['post']);
		}
		
		if (isset ($data['status']))
		{
			$model->setStatus ($data['status']);
		}

		return $model;
	}

	/**
	 * @return array
	 */
	public function getJSONData ()
	{
		$data = array ();

		if ($this->getData ())
		{
			$plaindata = $this->getData ();
			if (!empty ($plaindata))
			{
				$data['data'] = $this->getData ();
			}
		}
		
		$data['body'] = $this->getBody ();

		$data['session'] = $this->getSession ();
		$data['headers'] = $this->getHeaders ();
		$data['cookies'] = $this->getCookies ();
		$data['post'] = $this->getPost ();
		$data['status'] = $this->getStatus ();

		return $data;
	}

	/**
	 * @param array $data
	 * @return string
	 */
	private static function calculateSignature (array $data)
	{
		$txt = self::calculateBaseString ($data);
		return md5 ($txt);
	}

	private static function calculateBaseString (array $data)
	{
		unset ($data['signature']);

		$txt = '\(^-^)/ !Stupid Rainbow Tables! \(^-^)/ ';
		foreach ($data as $k => $v)
		{
			$txt .= $k . ":" . json_encode ($v) . "|";
		}

		$txt .= APP_SECRET_KEY;

		return $txt;
	}

	/**
	 * @return string json
	 */
	public function toJSON ()
	{
		$data = $this->getJSONData ();
		$data['random'] = mt_rand ();
		$data['time'] = gmdate ('c');
		$data['signature'] = $this->calculateSignature ($data);
		return json_encode ($data);
	}

	/**
	 * @param array|string $key
	 * @param mixed $value
	 */
	public function setSession ($key, $value = null)
	{
		if (is_array ($key) && is_null ($value))
		{
			$this->session = $key;
		}
		else
		{
			if (!isset ($this->session))
			{
				$this->session = array ();
			}
			$this->session[$key] = $value;
		}
	}

	/**
	 * @return mixed
	 */
	public function getSession ()
	{
		return $this->session;
	}

	/**
	 * @param User $user
	 */
	public function setUser (User $user)
	{
		$this->user = $user;
		$this->setSession ('user', $user->getId ());
	}

	/**
	 * @return User
	 */
	public function getUser ()
	{
		return $this->user;
	}

	/**
	 * @param $body
	 */
	public function setBody ($body)
	{
		$this->body = $body;
	}

	/**
	 * @return mixed
	 */
	public function getBody ()
	{
		return $this->body;
	}

	/**
	 * @param $id
	 */
	public function setApplication ($id)
	{
		$this->setSession ('application', $id);
	}

	/**
	 * @return mixed
	 */
	public function getApplication ()
	{
		return $this->application;
	}

	/**
	 * @param $path
	 */
	public function setPath ($path)
	{
		$this->path = $path;
	}

	/**
	 * @return mixed
	 */
	public function getPath ()
	{
		return $this->path;
	}

	/**
	 * @param array $post
	 */
	public function setPost ($post)
	{
		$this->post = $post;
	}

	/**
	 * @return array
	 */
	public function getPost ()
	{
		return $this->post;
	}

	/**
	 * @param mixed $data
	 */
	public function setData ($data)
	{
		$this->data = $data;
	}

	/**
	 * @return mixed
	 */
	public function getData ()
	{
		return $this->data;
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function setHeader ($name, $value = null)
	{
		if (!isset ($this->headers))
		{
			$this->headers = array ();
		}
		$this->headers[$name] = $value;
	}

	/**
	 * @param array $headers
	 */
	public function setHeaders ($headers)
	{
		$this->headers = $headers;
	}

	/**
	 * @return array
	 */
	public function getHeaders ()
	{
		return $this->headers;
	}

	public function setCookies ($cookies)
	{
		$this->cookies = $cookies;
	}

	public function getCookies ()
	{
		return $this->cookies;
	}

	public function setStatus ($status)
	{
		$this->status = $status;
	}

	public function isStatusSet ()
	{
		return isset ($this->status);
	}

	public function getStatus ()
	{
		if (isset ($this->status))
		{
			return $this->status;
		}
		return 200;
	}

} 