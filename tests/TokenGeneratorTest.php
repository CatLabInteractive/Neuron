<?php


namespace Neuron\Tests;

use PHPUnit\Framework\TestCase;


class TokenGeneratorTest
	extends TestCase
{
	public function testLengthSimplified ()
	{
		for ($i = 0; $i < 100; $i ++) {
			$token = \Neuron\Tools\TokenGenerator::getSimplifiedToken (50);
			$this->assertTrue (strlen ($token) === 50);
		}
	}
}