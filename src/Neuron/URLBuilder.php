<?php
/**
* Static class to build URLs.
* I haven't really figured out a perfect method for this.
*/

namespace Neuron;




class URLBuilder 
{
	/**
	 * @param string $module Path name
	 * @param array $data Query string
	 * @param bool $normalize Should the url be normalized?
	 * @param null $appurl Override the app url
	 * @return string
	 */
	public static function getURL ($module = '', $data = array (), $normalize = true, $appurl = null)
	{
		if (!isset ($appurl))
			$appurl = Config::get ('app.url', '/');

		if (substr ($module, 0, 1) === '/')
		{
			$module = substr ($module, 1);
		}

		$params = '';
		if (isset ($data)) {
			foreach ($data as $k => $v) {
				$params .= urlencode ($k) . '=' . urlencode ($v) . '&';
			}
		}
		$params = substr ($params, 0, -1);

		if (!empty ($params))
		{
			if ($normalize)
				$url = self::normalize ($appurl . $module) . '?' . $params;
			else
				$url = $appurl . $module . '?' . $params;

			return $url;
		}
		else
		{
			// Google likes these.
			if ($normalize)
				return self::normalize ($appurl . $module);
			else
				return $appurl . $module;
		}
	}

	public static function getAbsoluteURL ($module = '', $data = array ()) {

		if (self::isAbsolute (Config::get ('app.url'))) {
			return self::getURL ($module, $data, true);
		}
		else {
			return self::getURL ($module, $data, true, self::guessAbsoluteURL ());
		}
	}

	private static function guessAbsoluteURL () {
		return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/';
	}

	private static function isAbsolute ($url) {
		if (substr (strtolower ($url), 0, 4) == 'http') {
			return true;
		}
		return false;
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