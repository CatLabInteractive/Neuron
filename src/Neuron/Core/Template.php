<?php

namespace Neuron\Core;

use Neuron\Core\Text;
use Neuron\Core\Tools;
use Neuron\Exceptions\DataNotSet;

class Template
{

	private $values = array ();
	
	private $sTextFile = null;
	private $sTextSection = null;
	
	private $objText = null;

	private $layoutRender = null;

	// Yea, ugly. I know.
	static $shares = array ();

	/** @var string[] */
	static $paths = array ();

	/** @var int[] */
	static $pathpriorities = array ();

	/** @var string $template */
	private $template;

	/** @var array $helpers */
	static $helpers = array ();
	
	public static function load ()
	{
	
	}

	/**
	 * Create a template.
	 * @param $template
	 */
	public function __construct ($template = null, $values = array ())
	{
		$this->template = $template;

		foreach ($values as $name => $value) {
			$this->set ($name, $value);
		}
	}

	/**
	 * Clear all shared variables.
	 */
	public static function clearShares ()
	{
		self::$shares = array ();
	}

	/**
	 * Set a variable that will be shared across all templates.
	 * @param $name
	 * @param $value
	 */
	public static function share ($name, $value)
	{
		self::$shares[$name] = $value;
	}

	/**
	 * Add a helper that is available inside the templates.
	 * @param $name
	 * @param $helper
	 */
	public static function addHelper ($name, $helper)
	{
		self::$helpers[$name] = $helper;
	}

	/**
	* I believe this is a nicer way to do the directory setting.
	*/
	public static function setPath ($path)
	{
		self::$paths = array ();
		self::$pathpriorities = array ();

		self::addPath ($path, '', 0);
	}

	/**
	 * Add a folder to the template path.
	 * @param $path: path to add
	 * @param $prefix: only templates starting with given prefix will be loaded from this path.
	 * @param $priority
	*/
	public static function addPath ($path, $prefix = '', $priority = 0)
	{
		if (substr ($path, -1) !== '/')
			$path .= '/';

		if ($prefix) {
			$name = $prefix . '|' . $path;
		}
		else {
			$name = $path;
		}

		// Set priority
		self::$pathpriorities[$name] = $priority;

		// Calculate the position based on priority.
		$position = 0;
		foreach (self::$paths as $path)
		{
			if (self::$pathpriorities[$path] < $priority)
			{
				break;
			}
			$position ++;
		}

		array_splice (self::$paths, $position, 0, array ($name));
	}

	/**
	 * @return string[]
	 */
	public static function getPaths ()
	{
		return self::$paths;
	}

	/**
	 * @param $sTextSection
	 * @param null $sTextFile
	 * @return $this
	 */
	private function setTextSection ($sTextSection, $sTextFile = null)
	{
		$this->sTextSection = $sTextSection;
		
		if (isset ($sTextFile))
		{
			$this->sTextFile = $sTextFile;
		}

		return $this;
	}

	/**
	 * @param $sTextFile
	 * @return $this
	 */
	private function setTextFile ($sTextFile)
	{
		$this->sTextFile = $sTextFile;
		return $this;
	}

	/**
	 * @param $var
	 * @param $value
	 * @param bool $overwrite
	 * @param bool $first
	 * @return $this
	 */
	public function set ($var, $value, $overwrite = false, $first = false)
	{
		$this->setVariable ($var, $value, $overwrite, $first);
		return $this;
	}

	/**
	 * @param $var
	 * @param $value
	 * @param bool $overwrite
	 * @param bool $first
	 */
	private function setVariable ($var, $value, $overwrite = false, $first = false)
	{
		if ($overwrite) {
			$this->values[$var] = $value;
		}
		
		else {
			if (isset ($this->values[$var])) {
				if ($first) {
					$this->values[$var] = $value.$this->values[$var];
				}
				
				else {
					$this->values[$var].= $value;
				}
			}
			
			else {
				$this->values[$var] = $value;
			}
		}
	}

	/**
	 * Return an array of all filenames, or FALSE if none are found.
	 * @param $template
	 * @param bool $all
	 * @return bool|string
	 */
	private static function getFilenames ($template, $all = false)
	{
		$out = array ();

		foreach (self::getPaths () as $v) {

			// Split prefix and folder
			$split = explode ('|', $v);

			if (count ($split) === 2)
			{
				$prefix = array_shift ($split);
				$folder = implode ('|', $split);
				$templatefixed = substr ($template, 0, strlen ($prefix));

				if ($templatefixed == $prefix)
				{
					$templaterest = substr ($template, strlen ($templatefixed));
					if (is_readable ($folder . $templaterest))
					{
						$out[] = $folder . $templaterest;

						if (!$all)
							return $out;
					}
				}
			}
			else
			{
				if (is_readable ($v . $template))
				{
					$out[] = $v . $template;

					if (!$all)
						return $out;
				}
			}
		}

		if (count ($out) > 0)
		{
			return $out;
		}
		return false;	
	}
	
	public static function hasTemplate ($template)
	{
		return self::getFilenames ($template) ? true : false;
	}

	public function parse ($template = null, $text = null)
	{
		if (!isset ($template))
		{
			if (isset ($this->template))
			{
				$template = $this->template;
			}
			else {
				throw new DataNotSet ("You must define a template name in constructor or as parse method parameter.");
			}
		}

		if (! $ctlbtmpltfiles = $this->getFilenames ($template))
		{
			$out = '<h1>Template not found</h1>';
			$out .= '<p>The system could not find template "'.$template.'"</p>';
			return $out;
		}

		ob_start ();

		foreach (self::$shares as $k => $v)
		{
			${$k} = $v;
		}

		foreach ($this->values as $k => $v) {
			${$k} = $v;
		}

		include $ctlbtmpltfiles[0];
		
		$val = ob_get_contents();

		ob_end_clean();

		return $this->processRenderQueue (array ('content' => $val));
	}

	private function processRenderQueue ($contents = array ())
	{
		if (isset ($this->layoutRender)) {
			$template = new self ();

			// Set the variables that have been set here.
			foreach ($this->values as $k => $v) {
				$template->set ($k, $v);
			}

			// And now set the content blocks.
			// This might overwrite other sets.
			foreach ($contents as $k => $v) {
				$template->set ($k, $v, true);
			}

			return $template->parse ($this->layoutRender);
		}
		else {
			return $contents['content'];
		}
	}

	/**
	 * Extend a parent theme.
	 * @param $layout
	 */
	private function layout ($layout)
	{
		$this->layoutRender = $layout;
	}

	/**
	 * Go trough all set template directories and search for
	 * a specific template. Concat all of them.
	 */
	private function combine ($template)
	{
		ob_start();

		foreach (self::$shares as $k => $v) {
			${$k} = $v;
		}

		foreach ($this->values as $k => $v) {
			${$k} = $v;
		}

		if ($ctlbtmpltfiles = $this->getFilenames($template, true)) {
			foreach ($ctlbtmpltfiles as $ctlbtmpltfile) {
				include $ctlbtmpltfile;
			}
		}

		$val = ob_get_contents();
		ob_end_clean();

		return $val;
	}

	/**
	 * Include a single template inside another template.
	 */
	private function template ($template)
	{
		ob_start();

		foreach (self::$shares as $k => $v) {
			${$k} = $v;
		}

		foreach ($this->values as $k => $v) {
			${$k} = $v;
		}

		if ($ctlbtmpltfiles = $this->getFilenames($template)) {
			foreach ($ctlbtmpltfiles as $ctlbtmpltfile) {
				include $ctlbtmpltfile;
			}
		}

		$val = ob_get_contents();
		ob_end_clean();

		return $val;
	}

	/**
	 * @param string $name
	 * @param string $method
	 * @return string
	 */
	private function help ($name, $method = 'helper')
	{
		$args = func_get_args ();
		array_shift ($args);
		array_shift ($args);

		if (isset (self::$helpers[$name]))
		{
			$call = array (self::$helpers[$name], $method);
			if (is_callable ($call))
			{
				$out = call_user_func_array ($call, $args);
				if ($out instanceof Template)
				{
					return $out->parse ();
				}
				else {
					return $out;
				}
			}
			else {
				return '<p class="error">Method ' . $method . ' on helper ' . $name . ' is not callable.</p>';
			}
		}
		else {
			return '<p class="error">Could not find helper ' . $name . '</p>';
		}
	}

	private function css ($path)
	{
		return '<link rel="stylesheet" href="'. $path . '" />';
	}

	private function js ($path)
	{
		return '<script src="' . $path . '"></script>';
	}

	private function textdomain ($domain)
	{
		\Neuron\Tools\Text::getInstance ()->setDomain ($domain);
	}

	private function gettext ($message1)
	{
		return \Neuron\Tools\Text::getInstance ()->getText ($message1);
	}

	private function ngettext ($message1, $message2 = null, $n = null)
	{
		return \Neuron\Tools\Text::getInstance ()->getText ($message1, $message2, $n);
	}

	/**
	 * @param $path
	 * @param array $params
	 * @param bool $normalize
	 * @param null $appurl
	 * @return string
	 */
	private function getURL ($path, array $params = null, $normalize = true, $appurl = null) {
		return \Neuron\URLBuilder::getURL ($path, $params, $normalize, $appurl);
	}

	public function __toString () {
		return $this->parse ();
	}
}