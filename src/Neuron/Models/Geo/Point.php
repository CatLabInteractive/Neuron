<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 11/12/13
 * Time: 17:57
 */

namespace Neuron\Models\Geo;

use Neuron\Exceptions\InvalidParameter;

class Point {

	private $lat, $lng;

	public function __construct ($lng, $lat)
	{
		if (!is_numeric ($lng) || !is_numeric ($lat))
		{
			throw new InvalidParameter ("Latitude and longtitude must be numeric values, (" . $lng . "," . $lat . ") received.");
		}

		$this->setLatitude ($lat);
		$this->setLongtitude ($lng);
	}

	public function setLongtitude ($lng)
	{
		$this->lng = $lng;
	}

	public function getLongtitude ()
	{
		return $this->lng;
	}

	public function setLatitude ($lat)
	{
		$this->lat = $lat;
	}

	public function getLatitude ()
	{
		return $this->lat;
	}

	public function getData ()
	{
		return array (
			'coordinates' => array (
				$this->lng,
				$this->lat
			),
			'type' => 'Point'
		);
	}
} 