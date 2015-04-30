<?php


namespace Neuron\Tests;

use PHPUnit_Framework_TestCase;


class TokenGeneratorTest
	extends PHPUnit_Framework_TestCase
{
	public function testLengthSimplified ()
	{
		for ($i = 0; $i < 100; $i ++) {
			$token = \Neuron\Tools\TokenGenerator::getSimplifiedToken (50);
			$this->assertTrue (strlen ($token) === 50);
		}
	}
}