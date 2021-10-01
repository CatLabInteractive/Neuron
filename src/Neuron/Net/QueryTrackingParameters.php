<?php

namespace Neuron\Net;

use Neuron\Config;

/**
 * Class QueryTrackingParameters
 * @package Neuron\Net
 */
class QueryTrackingParameters
{
	/**
	 * @return QueryTrackingParameters
	 */
	public static function instance()
	{
		static $in;
		if (!isset($in)) {
			$in = new self();

			// Can we set these from config?
            if (Config::get('tracking.queryParameters')) {
                $in->queryParameters = Config::get('tracking.queryParameters');
            }
		}
		return $in;
	}

	public $queryParameters = [
		'utm_abversion',
		'utm_referrer',
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_term',
		'utm_content',
		'_ga',
		'_gac',
		'pk_vid',
        '_gl',
		'_cc'
	];
}
