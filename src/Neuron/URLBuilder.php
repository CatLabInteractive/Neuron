<?php
/**
* Static class to build URLs.
* I haven't really figured out a perfect method for this.
*/

namespace Neuron;




class URLBuilder 
{
	public static function getURL ($module = '', $data = array ())
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
			return Config::get ('app.url', '/') . $module . '?' . $params;
		}
		else
		{
			// Google likes these.
			return Config::get ('app.url', '/') . $module;
		}
	}
}