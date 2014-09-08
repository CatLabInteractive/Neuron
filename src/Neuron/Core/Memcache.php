<?php

namespace Neuron\Core;

if (!defined ('MEMCACHE_PREFIX'))
{
	define ('MEMCACHE_PREFIX', '');
}

/**
 * Class Memcache
 * @package Neuron\Core\Memcache
 */
class Memcache
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

	private $objCache;

	// Small cache to speed up issues
	private $sLastCache;
	private $sLastValue;

	private function __construct ()
	{
		if (defined ('MEMCACHE_IP') && defined ('MEMCACHE_PORT') && MEMCACHE_IP)
		{
			try
			{
				$this->objCache = new \Memcache ();
				$this->objCache->connect (MEMCACHE_IP, MEMCACHE_PORT);
			}
			catch (Exception $e)
			{
				$this->objCache = false;
			}
		}
		else
		{
			$this->objCache = false;
		}
	}

	public function hasCache ($sKey)
	{
		// Check if this object even exists
		if (!$this->objCache)
		{
			return false;
		}

		$this->sLastCache = $sKey;
		$this->sLastValue = @$this->objCache->get (MEMCACHE_PREFIX . $sKey);

		return $this->sLastValue !== false;
	}

	public function setCache ($sKey, $sContent)
	{
		// Check if this object even exists
		if (!$this->objCache)
		{
			return false;
		}

		return @$this->objCache->set (MEMCACHE_PREFIX . $sKey, $sContent, 0, 0);
	}

	public function getCache ($sKey)
	{
		// Check if this object even exists
		if (!$this->objCache)
		{
			return false;
		}

		if ($this->sLastCache == $sKey)
		{
			return $this->sLastValue;
		}
		else
		{
			return $this->objCache->get (MEMCACHE_PREFIX . $sKey);
		}
	}

	public function removeCache ($sKey)
	{
		if (!$this->objCache)
		{
			return false;
		}

		//customMail ('daedelson@gmail.com', 'deleting cache '.$sKey, 'test');

		$this->objCache->delete (MEMCACHE_PREFIX . $sKey);
	}

	public function flush ()
	{
		// Check if this object even exists
		if (!$this->objCache)
		{
			return false;
		}

		//customMail ('daedelson@gmail.com', 'clearing all cache', 'test');

		$this->objCache->flush ();
	}

	public function getExtendedStats ()
	{
		return $this->objCache->getExtendedStats ();
	}

	public function __toString ()
	{
		if ($this->objCache)
		{
			return print_r ($this->objCache->getExtendedStats (), true);
		}
		else
		{
			return 'No memcache support configured.';
		}
	}
}