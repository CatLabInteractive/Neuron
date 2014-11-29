<?php


namespace Neuron\Core;

use Neuron\Core\Text;
use Neuron\Core\Tools;
use Neuron\Exceptions\DataNotSet;


// A few ugly methods because this wasn't very well designed at first? ;-)
$catlab_template_path = '';

function set_template_path ($path)
{
	global $catlab_template_path;
	$catlab_template_path = $path;

	if (!defined ('TEMPLATE_DIR'))
	{
		define ('TEMPLATE_DIR', $path);
	}
}

function get_template_path ()
{
	global $catlab_template_path;
	return $catlab_template_path;
}

function add_to_template_path ($path, $priorize = true, $folder = null)
{
	if ($folder)
	{
		$path = $folder . '|' . $path;
	}

	if (get_template_path () == '')
	{
		set_template_path ($path);
	}

	else if ($priorize)
	{
		set_template_path ($path . PATH_SEPARATOR . get_template_path ());
	}
	else
	{
		set_template_path (get_template_path () . PATH_SEPARATOR . $path);
	}
}

// Backwards compatability stuff
if (defined ('DEFAULT_TEMPLATE_DIR'))
{
	add_to_template_path (DEFAULT_TEMPLATE_DIR, false);
}

if (defined ('TEMPLATE_DIR'))
{
	add_to_template_path (TEMPLATE_DIR, true);
}

class Template
{

	private $values = array ();
	private $lists = array ();
	
	private $sTextFile = null;
	private $sTextSection = null;
	
	private $objText = null;

	private $layoutRender = null;

	// Yea, ugly. I know.
	static $shares = array ();

	private $template;
	
	public static function load ()
	{
	
	}

	/**
	 * Create a template.
	 * @param $template
	 */
	public function __construct ($template = null)
	{
		$this->template = $template;
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
	* I believe this is a nicer way to do the directory setting.
	*/
	public static function setTemplatePath ($path)
	{
		set_template_path ($path);
	}

	/**
	* Add a folder to the template path.
	* @param $path: path to add
	* @param $prefix: only templates starting with given prefix will be loaded from this path.
	*/
	public static function addTemplatePath ($path, $prefix = '', $priorize = false)
	{
		if (substr ($path, -1) !== '/')
			$path .= '/';

		add_to_template_path ($path, $priorize, $prefix);
	}
	
	private static function getTemplatePaths ()
	{
		return explode (PATH_SEPARATOR, get_template_path ());
	}
	
	// Text function
	public function setTextSection ($sTextSection, $sTextFile = null)
	{
		$this->sTextSection = $sTextSection;
		
		if (isset ($sTextFile))
		{
			$this->sTextFile = $sTextFile;
		}
	}
	
	public function setTextFile ($sTextFile)
	{
		$this->sTextFile = $sTextFile;
	}

	public function set ($var, $value, $overwrite = false, $first = false)
	{
		$this->setVariable ($var, $value, $overwrite, $first);
	}
	
	// Intern function
	private function getText ($sKey, $sSection = null, $sFile = null, $sDefault = null)
	{
		if (!isset ($this->objText)) {
			$this->objText = Text::__getInstance ();
		}
		
		$txt = Tools::output_varchar
		(
			$this->objText->get 
			(
				$sKey, 
				isset ($sSection) ? $sSection : $this->sTextSection, 
				isset ($sFile) ? $sFile : $this->sTextFile,
				false
			)
		);

		if (!$txt)
		{
			return $sDefault;
		}

		return $txt;
	}

	public function setVariable ($var, $value, $overwrite = false, $first = false)
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

		foreach (self::getTemplatePaths () as $v) {

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
		if (isset ($this->layoutRender))
		{
			$template = new self ();

			foreach ($contents as $k => $v)
			{
				$template->set ($k, $v);
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

	private function css ($path)
	{
		return '<link rel="stylesheet" href="'. $path . '" />';
	}

	private function js ($path)
	{
		return '<script src="' . $path . '"></script>';
	}
}