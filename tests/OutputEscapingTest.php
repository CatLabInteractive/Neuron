<?php

namespace Neuron\Tests;

use PHPUnit\Framework\TestCase;
use Neuron\Net\Outputs\HTML;
use Neuron\Net\Outputs\PrintR;
use Neuron\Net\Outputs\Table;
use Neuron\Net\Response;
use Neuron\Tools\TokenGenerator;

/**
 * The HTML data outputs show values; a value never becomes markup.
 */
class OutputEscapingTest extends TestCase
{
	private function render ($output, $data)
	{
		$response = new Response ();
		$response->setData ($data);

		ob_start ();
		try {
			$output->outputContent ($response);
		} finally {
			$out = ob_get_clean ();
		}

		return $out;
	}

	public function testTableEscapesKeysAndValues ()
	{
		$out = $this->render (new Table (), array ('<i>key</i>' => '<b>value</b>'));

		$this->assertStringNotContainsString ('<i>key</i>', $out);
		$this->assertStringNotContainsString ('<b>value</b>', $out);
		$this->assertStringContainsString ('&lt;i&gt;key&lt;/i&gt;', $out);
		$this->assertStringContainsString ('&lt;b&gt;value&lt;/b&gt;', $out);
	}

	public function testTableEscapesNestedAndDebugValues ()
	{
		$out = $this->render (new Table (), array (
			'row' => array ('name' => "it's <b>x</b>"),
			'debug' => array ('log' => '<b>line</b>'),
		));

		$this->assertStringNotContainsString ('<b>x</b>', $out);
		$this->assertStringNotContainsString ('<b>line</b>', $out);
		$this->assertStringContainsString ('it&#039;s &lt;b&gt;x&lt;/b&gt;', $out);
		$this->assertStringContainsString ('&lt;b&gt;line&lt;/b&gt;', $out);
	}

	public function testTableKeepsItsOwnMarkup ()
	{
		$out = $this->render (new Table (), array ('a' => 1));

		$this->assertStringContainsString ('<table class="api-data-table">', $out);
		$this->assertStringContainsString ('<th>a</th>', $out);
	}

	public function testTableEscapesStringData ()
	{
		$out = $this->render (new Table (), '<b>text</b>');

		$this->assertSame ('&lt;b&gt;text&lt;/b&gt;', $out);
	}

	public function testPrintREscapesAndPrintsTheData ()
	{
		$out = $this->render (new PrintR (), array ('name' => '<b>value</b>'));

		$this->assertStringStartsWith ('<pre>', $out);
		$this->assertStringEndsWith ('</pre>', $out);
		$this->assertStringContainsString ('[name] =&gt; &lt;b&gt;value&lt;/b&gt;', $out);
		$this->assertStringNotContainsString ('<b>value</b>', $out);
	}

	public function testHtmlOutputEscapesADataStructure ()
	{
		$out = $this->render (new HTML (), array ('message' => '<b>value</b>'));

		$this->assertStringNotContainsString ('<b>value</b>', $out);
		$this->assertStringContainsString ('&lt;b&gt;value&lt;/b&gt;', $out);
	}

	public function testHtmlOutputLeavesABodyAlone ()
	{
		$response = new Response ();
		$response->setBody ('<p>page</p>');

		ob_start ();
		(new HTML ())->outputContent ($response);
		$out = ob_get_clean ();

		$this->assertSame ('<p>page</p>', $out);
	}

	public function testTokensKeepTheirAlphabetAndLength ()
	{
		for ($i = 0; $i < 50; $i ++) {
			$this->assertMatchesRegularExpression ('/\A[a-zA-Z0-9]{40}\z/', TokenGenerator::getToken (40));
			$this->assertMatchesRegularExpression ('/\A[A-Z0-9]{12}\z/', TokenGenerator::getSimplifiedToken (12));
		}
	}

	public function testTokenGeneratorUsesTheSecureRandomSource ()
	{
		$reflection = new \ReflectionClass (TokenGenerator::class);
		$source = file_get_contents ($reflection->getFileName ());

		$this->assertStringContainsString ('random_int', $source);
		$this->assertStringNotContainsString ('mt_rand', $source);
	}
}
