<?php


namespace Neuron;

use Neuron\Interfaces\FrontController as NeuronInterfacesFrontController;
use Neuron\Net\Request;
use Neuron\Net\Response;
use Neuron\Tracking\Tracker;

class FrontController
	implements \Neuron\Interfaces\FrontController
{
	/** @var \Neuron\Interfaces\FrontController[]  */
	private $controllers = array ();
	private $input = array ();
	private $page;
	
	/** @var Request $request */
	private $request;
	
	/** var Response $response */
	private $response;

	private static $instance;

	public static function getInstance ()
	{
		if (!isset (self::$instance))
		{
			self::$instance = new self ();
		}
		return self::$instance;
	}
	
	public function __construct ()
	{
		
	}

	public static function destroy ()
	{
		self::$instance = null;
	}

	public function addController (NeuronInterfacesFrontController $controller)
	{
		$this->controllers[] = $controller;
		$controller->setParentController ($this);
	}

	public function canDispatch ()
	{
		return true;
	}

	public function setInput (array $fields)
	{
		$this->input = $fields;
	}

	public function getInput ($id = null)
	{
		if ($id !== null)
		{
			if (isset ($this->input[$id]))
			{
				return $this->input[$id];
			}
			return null;
		}
		return $this->input;
	}

	public function setPage (Page $page)
	{
		$this->page = $page;
	}

	public function getPage ()
	{
		if (!isset ($this->page))
		{
			$this->page = new Page ();
		}
		return $this->page;
	}

	public function dispatch (Page $page = null)
	{
		Tracker::getInstance ()->setModule ('Neuron');

		foreach ($this->controllers as $v)
		{
			if ($v->canDispatch ())
			{
				return $v->dispatch ($this->getPage ());
			}
		}

		// Nothing found? Last one it is.
		if (count ($this->controllers) > 0)
		{
			return $this->controllers[count ($this->controllers) - 1]->dispatch ($this->getPage ());
		}
		else
		{
			return Response::error ('No controllers set.');
		}
	}

	public function setParentController (NeuronInterfacesFrontController $input)
	{
		// Nothing to do here.
	}

	public function getName ()
	{
		return 'Neuron front controller';
	}

	/**
	 * @param Request $request
	 */
	public function setRequest (Request $request)
	{
		$this->request = $request;
	}

	/**
	 * @return Request
	 */
	public function getRequest ()
	{
		return $this->request;
	}

	/**
	 * @param Response $response
	 */
	public function setResponse (Response $response)
	{
		$this->response = $response;
	}

	/**
	 * @return Response
	 */
	public function getResponse ()
	{
		if (!isset ($this->response))
		{
			$this->response = new Response ();
		}
		return $this->response;
	}
}