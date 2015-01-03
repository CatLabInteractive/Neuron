<?php
/**
* Static class to build URLs.
* I haven't really figured out a perfect method for this.
*/

namespace Neuron;




class URLBuilder 
{
	public static function getURL ($module = '', $data = array (), $normalize = true)
	{
		if (substr ($module, 0, 1) === '/')
		{
			$module = substr ($module, 1);
		}

		$params = '';
		foreach ($data as $k => $v)
		{
			$params .= urlencode ($k) . '=' . urlencode ($v) . '&';
		}
		$params = substr ($params, 0, -1);

		if (!empty ($params))
		{
			if ($normalize)
				$url = self::normalize (Config::get ('app.url', '/') . $module) . '?' . $params;
			else
				$url = Config::get ('app.url', '/') . $module . '?' . $params;

			return $url;
		}
		else
		{
			// Google likes these.
			if ($normalize)
				return self::normalize (Config::get ('app.url', '/') . $module);
			else
				return Config::get ('app.url', '/') . $module;
		}
	}

	/**
	 * Make sure the string does not end with a /
	 * @param $path
	 * @return string
	 */
	public static function normalize ($path)
	{
		if (substr ($path, 0, -1) === '/')
		{
			return substr ($path, 0, -1);
		}
		return $path;
	}

	/**
	 * Make sure that the string ends with a slash.
	 * @param string $path
	 * @return string
	 */
	public static function partify ($path)
	{
		if (substr ($path, -1) !== '/')
		{
			return $path . '/';
		}
		return $path;
	}
}