<?php


namespace Neuron\Core;

use Neuron\Core\Text as NeuronCoreText;
use Neuron\Exceptions\Deprecated;
use Neuron\Core\Tools;



class Text
{

	protected $cache;
	protected $root_dir;
	protected $backup = false;
	protected $inFile, $inSection;
	protected $tag;
	private $pathname;

	/*
		Return an instance
	*/
	public static function __getInstance ()
	{
		static $in;
		if (empty ($in)) 
		{
			$in = new NeuronCoreText ();
		}
		return $in;
	}

	/**
	* Tiny bit cleaner approach.
	*/
	public static function setLanguagePath ($path)
	{
		define ('LANGUAGE_DIR', $path);
		define ('CATLAB_LANGUAGE_PATH', $path);
	}
	
	public static function getInstance ()
	{
		return self::__getInstance ();
	}

	public function __construct ($language = null, $baseText = 'en', $pathname = null)
	{
		if (!isset ($language) && defined ('LANGUAGE_TAG'))
		{
			$language = LANGUAGE_TAG;
		}

		if (!isset ($pathname))
		{
			$pathname = LANGUAGE_DIR;
			$this->backup = new NeuronCoreText ($language, $baseText, CATLAB_LANGUAGE_PATH);
		}

		else if ($baseText && $baseText != $language)
		{
			$this->backup = new NeuronCoreText ($baseText, false);
		}
		
		// Take text
		if (isset ($language))
		{
			$this->root_dir = $pathname.$language;
			$this->tag = $language;
		}

		else 
		{
			echo 'Language directory not defined.';
			exit ();
		}

	}
	
	public function setLanguage ($language)
	{
		$this->root_dir = LANGUAGE_DIR.$language;
		$this->tag = $language;
		
		// Remove cache
		$this->cache = array ();
	}
	
	public function setFile ($file)
	{
	
		$this->inFile = $file;
	
	}
	
	public function setSection ($section)
	{
		$this->inSection = $section;
	}

	public function get ($id, $section = null, $file = null, $alternative = null)
	{
		throw new Deprecated ("Nope. Use gettext.");

		// Section & fill
		if (empty ($section))
		{
			$section = $this->inSection;
		}
		
		if (empty ($file))
		{
			$file = $this->inFile;
		}

		// Check if the file is loaded already
		if (empty ($this->cache[$file])) 
		{
			$this->load_file ($file);
		}

		// Check if the id exist
		if (empty ($this->cache[$file][$section][$id])) 
		{
			if ($this->backup && $this->backup->get ($id, $section, $file, false))
			{
				return $this->backup->get ($id, $section, $file, false);
			}
			elseif ($alternative === null) 
			{
				return 'Text Not Found: '.$id.' ('.$file.', '.$section.', '.$this->root_dir.')';
			}
			else 
			{
				return $alternative;
			}
		}
		else {
			return $this->cache[$file][$section][$id];
		}
	}
	
	public function getSection ($section, $file)
	{
	
		// Check if the file is loaded already
		if (empty ($this->cache[$file]))
		{
			$this->load_file ($file);
		}
		
		$output = array ();
		
		if (!empty ($this->cache[$file][$section])) 
		{
			foreach ($this->cache[$file][$section] as $k => $v) 
			{
				if (!empty ($v)) {
					$output[$k] = $v;
				}
			}
		}
		
		if (count ($output) == 0)
		{
			$output = $this->backup->getSection ($section, $file);
		}
		
		return $output;
	}
	
	public function getRandomLine ($section, $file, $iNumber = false)
	{
		$section = array_values ($this->getSection ($section, $file));
		
		$total = count ($section);
		
		if ($total < 1)
		{
			return null;
		}
		
		if (!$iNumber)
		{
			$iNumber = mt_rand (0, $total);
		}
		
		return $section[$iNumber % $total];
	}
	
	public function getFile ($inFile, $return = 'error')
	{
	
		$file = $this->root_dir.'/templates/'.$inFile.'.txt';
		
		if (is_readable ($file)) 
		{
			return file_get_contents ($file);
		}
		
		elseif ($this->backup && $this->backup->getFile ($inFile, false))
		{
			return $this->backup->getFile ($inFile);
		}
		
		else {
			if ($return === 'error')
			{
				return ('404: Unable to load file '.$file.'.');
			}
			
			else return $return;
		}
	}
	
	public function getTemplate ($file, $fields = array ())
	{
		return Tools::putIntoText
		(
			$this->getFile ($file),
			$fields
		);
	}

	protected function load_file ($file)
	{
		$f = $this->root_dir.'/'.$file.'.lng';
		
		if (is_readable ($f)) 
		{
			$this->cache[$file] = parse_ini_file ($f, true);
		}
	}
	
	/*
		Return the abasolute path to the
		text file.
	*/
	public function getPath ()
	{
		return $this->root_dir . '/';
	}
	
	public function getClickto ($txt)
	{
		return Array
		(
			$this->get ('clickto1', 'main', 'main').' ',
			$this->get ('clickto2', 'main', 'main'),
			' '.$txt
		);
	}

	public static function getLanguages ()
	{
		$o = array ();
		$dir = scandir (LANGUAGE_DIR);
		foreach ($dir as $file)
		{
			if ($file != '.' && $file != '..' && strlen ($file) == 2)
			{
				$o[] = $file;
			}
		}
		return $o;
	}
	
	public function getCurrentLanguage ()
	{
		return $this->tag;
	}
}
