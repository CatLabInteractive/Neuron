<?php

namespace Neuron\Net;

/**
 * Class QueryTrackingParameters
 * @package Neuron\Net
 */
class QueryTrackingParameters
{
	/**
	 * @return QueryTrackingParameters
	 */
	public function instance()
	{
		static $in;
		if (!isset($in)) {
			$in = new self();
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
		'pk_vid'
	];
}
