<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/09/14
 * Time: 21:11
 */

namespace Neuron\Models;


class Logger
	implements \Neuron\Interfaces\Logger
{
	public static function getInstance ()
	{
		static $in;
		if (!isset ($in))
		{
			$in = new self ();
		}
		return $in;
	}

	private $log = array ();
	private $output = false;
	private $start;
	private $last;

	private $temporary = array ();
	private $temporarystart = array ();

	private function __construct ()
	{
		$this->start = microtime(true);
	}

	public function setOutput ($output = true)
	{
		$this->output = $output;
	}

	public function log ($string, $replace = false, $color = null)
	{
		if (!is_string ($string))
		{
			$string = print_r ($string, true);
		}

		$td = microtime(true) - $this->start;

		$tl = null;
		if (isset ($this->last))
		{
			$tl = $td - $this->last;
		}
		$this->last = $td;

		$log = '';

		$log .= str_pad ('[' . (number_format ($td, 3)) . '] ', 12, " ", STR_PAD_LEFT);
		$log .= str_pad ('(' . (number_format ($tl, 3)) . ') ', 10, " ", STR_PAD_LEFT);
		$log .= 'MEM: ' . str_pad (number_format ((memory_get_usage () / (1024 * 1024)), 3) . 'MB ', 10, " ", STR_PAD_LEFT);
		$log .= "   " . $string;

		// Also add this log to all temporary logs.
		foreach ($this->temporary as $k => &$tmplog)
		{
			$tmplog[] = array (
				microtime (true) - $this->temporarystart[$k],
				(memory_get_usage () / (1024 * 1024)),
				$string
			);
		}

		if ($this->output)
		{
			$outlog = $log;

			if (isset ($color))
			{
				switch ($color)
				{
					case 'red':
						$outlog = "\033[31m" . $outlog . "\033[37m";
						break;

					case 'green':
						$outlog = "\033[32m" . $outlog . "\033[37m";
						break;

					case 'blue':
						$outlog = "\033[34m" . $outlog . "\033[37m";
						break;

					case 'brown':
						$outlog = "\033[33m" . $outlog . "\033[37m";
						break;

					case 'magenta':
						$outlog = "\033[35m" . $outlog . "\033[37m";
						break;

					case 'cyan':
						$outlog = "\033[36m" . $outlog . "\033[37m";
						break;

					case 'lightgray':
						$outlog = "\033[37m" . $outlog . "\033[37m";
						break;
				}
			}

			echo ($replace ? "\r" : "\n") . $outlog;
			$this->flushOutput();
		}

		$this->log[] = $log;

		return new Timer ($string);
	}

	public function replace ($string)
	{
		$this->log ($string, true);
	}

	private function flushOutput ()
	{
		//echo(str_repeat(' ', 256));
		if (@ob_get_contents())
		{
			@ob_end_flush();
		}
		flush();
	}

    /**
     * Flush everything down the drain.
     * @param bool $flushDatabase
     */
    public function flush ($flushDatabase = true)
	{
		$this->temporary = array ();
		$this->temporarystart = array ();
		$this->log = array ();
		$this->start = microtime(true);

        // Also flush database log
        \Neuron\DB\Database::getInstance ()->flushLog ();
	}

	public function getLogs ()
	{
		return $this->log;
	}

	public function startTemporaryLog ()
	{
		$this->temporary[] = array ();
		$this->temporarystart[] = microtime (true);
	}

	public function flushTemporaryLog ()
	{
		$out = array_pop ($this->temporary);
		//$this->temporary = null;

		return $out;
	}
}