<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 30/11/14
 * Time: 21:26
 */

namespace Neuron\Mappers;


use Neuron\Config;

abstract class BaseMapper {

	/**
	 * @param $name
	 * @return string
	 */
	protected function getTableName ($name)
	{
		return Config::get ('database.mysql.prefix') . $name;
	}

	/**
	 * @param $data
	 * @return null|mixed
	 */
	protected function getSingle ($data)
	{
		if (count ($data) > 0)
		{
			return $this->getObjectFromData ($data[0]);
		}
		return null;
	}

	/**
	 * @param $data
	 * @return array|mixed[]
	 */
	protected function getObjectsFromData ($data)
	{
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = $this->getObjectFromData ($v);
		}
		return $out;
	}

	protected abstract function getObjectFromData ($data);

}