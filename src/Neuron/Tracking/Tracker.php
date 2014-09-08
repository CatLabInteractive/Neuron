<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 6/11/13
 * Time: 13:11
 */

namespace Neuron\Tracking;

use Exception;

class Tracker
{
	private $appname = 'PHP App';
	private $module;
	private $controller;
	private $action;
	private $started = true;
	private $background = false;
	private $peakMemoryUsage = 0;

	private $output = false;

	/**
	 * @return Tracker
	 */
	public static function getInstance ()
	{
		static $in;
		if (!isset ($in))
		{
			$in = new self ();
		}
		return $in;
	}

	public function setAppName ($name)
	{
		$this->appname = $name;

		if (extension_loaded('newrelic')) {
			newrelic_set_appname($name);
		}
	}

	/**
	 * Check if this transaction generated a bigger peak usage
	 * then the previous transaction. If so, report it.
	 */
	private function updatePeakMemoryUsage ()
	{
		$memory = memory_get_peak_usage ();
		if ($memory > $this->peakMemoryUsage)
		{
			$this->peakMemoryUsage = $memory;
			$this->setParameter ('Memory/PeakMemoryUsage', $memory);

			if (extension_loaded ('newrelic'))
			{
				newrelic_custom_metric ('Custom/PeakMemoryUsage', $memory);
			}
		}
	}

	public function startTransaction ()
	{
		if ($this->started)
		{
			$this->endTransaction ();
		}

		if (extension_loaded ('newrelic')) {
			newrelic_start_transaction ($this->appname);
			newrelic_background_job ($this->background);

			if ($this->output)
				echo "\nStarting transaction " . $this->appname . ' ' . ($this->background ? '(background)' : '(frontground)') . "\n";
		}

		$this->started = true;
	}

	public function endTransaction ()
	{
		$this->updatePeakMemoryUsage ();

		if (extension_loaded ('newrelic')) {
			newrelic_end_transaction ();

			if ($this->output)
				echo "\nend transaction\n";
		}

		$this->started = false;
	}

	public function setModule ($module)
	{
		$this->module = $module;
		$this->updateTransactionName ();
	}

	public function setController ($controller)
	{
		$this->controller = $controller;
		$this->updateTransactionName ();
	}

	public function setAction ($action)
	{
		$this->action = $action;
		$this->updateTransactionName ();
	}

	private function updateTransactionName ()
	{
		$name = $this->module;

		if (!empty ($this->controller))
		{
			$name .= '/' . $this->controller;
		}

		if (!empty ($this->action))
		{
			$name .= '/' . $this->action;
		}

		$this->nameTransaction ($name);
	}

	public function setParameters (array $parameters)
	{
		foreach ($parameters as $k => $v)
		{
			$this->setParameter ($k, $v);
		}
	}

	public function setParameter ($key, $value)
	{
		if (extension_loaded ('newrelic'))
		{
			newrelic_add_custom_parameter ($key, $value);
		}
	}

	public function nameTransaction ($action)
	{
		if (extension_loaded ('newrelic'))
		{
			newrelic_name_transaction ($action);

			if ($this->output)
				echo "\nSet name to " . $action . "\n";
		}
	}

	public function setBackground ($bool)
	{
		$this->background = $bool;
	}

	public function warning ($message, Exception $e)
	{
		if (extension_loaded ('newrelic'))
		{
			newrelic_notice_error ($message, $e);
		}
	}
} 