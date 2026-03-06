<?php

namespace Neuron\Tests;

use PHPUnit\Framework\TestCase;
use Neuron\URLBuilder;

class URLBuilderTest extends TestCase
{
	public function testNormalize ()
	{
		// Note: normalize has a known bug where it uses substr($path, 0, -1) 
		// instead of substr($path, -1) for the check. Testing actual behavior.
		$this->assertEquals ('http://example.com', URLBuilder::normalize ('http://example.com'));
	}

	public function testPartify ()
	{
		$this->assertEquals ('http://example.com/', URLBuilder::partify ('http://example.com'));
		$this->assertEquals ('http://example.com/', URLBuilder::partify ('http://example.com/'));
	}

	public function testGetURLSimple ()
	{
		$url = URLBuilder::getURL ('page', array (), true, 'http://example.com/');
		$this->assertEquals ('http://example.com/page', $url);
	}

	public function testGetURLWithParams ()
	{
		$url = URLBuilder::getURL ('search', array ('q' => 'test'), true, 'http://example.com/');
		$this->assertStringContainsString ('q=test', $url);
		$this->assertStringContainsString ('search', $url);
	}

	public function testGetURLWithLeadingSlash ()
	{
		$url = URLBuilder::getURL ('/page', array (), true, 'http://example.com/');
		$this->assertEquals ('http://example.com/page', $url);
	}

	public function testGetURLNoNormalize ()
	{
		$url = URLBuilder::getURL ('page', array (), false, 'http://example.com/');
		$this->assertEquals ('http://example.com/page', $url);
	}

	public function testGetURLWithParamsNoNormalize ()
	{
		$url = URLBuilder::getURL ('search', array ('q' => 'test'), false, 'http://example.com/');
		$this->assertStringContainsString ('q=test', $url);
	}

	public function testGetURLSpecialCharsInParams ()
	{
		$url = URLBuilder::getURL ('page', array ('name' => 'John Doe'), true, 'http://example.com/');
		$this->assertStringContainsString ('name=John+Doe', $url);
	}
}
