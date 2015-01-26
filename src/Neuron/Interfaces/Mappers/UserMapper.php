<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 26/01/15
 * Time: 11:54
 */

namespace Neuron\Interfaces\Mappers;


interface UserMapper {

	/**
	 * @param $id
	 * @return \Neuron\Interfaces\Models\User
	 */
	public function getFromId ($id);

}