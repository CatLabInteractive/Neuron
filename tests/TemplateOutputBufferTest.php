<?php

namespace Neuron\Tests;

use Neuron\Core\Template;
use PHPUnit\Framework\TestCase;

/**
 * A template that throws must not leave its output buffer open: the leaked
 * buffer would swallow everything the caller prints afterwards (and makes
 * PHPUnit re-print its whole output as the failing test's output).
 */
class TemplateOutputBufferTest extends TestCase
{
	protected function setUp (): void
	{
		Template::setPath (__DIR__ . '/templates/');
	}

	public static function throwingTemplates ()
	{
		return [
			'parse' => [ 'buffer/throws.phpt' ],
			'template() inside a template' => [ 'buffer/viaTemplate.phpt' ],
			'combine() inside a template' => [ 'buffer/viaCombine.phpt' ],
		];
	}

	/**
	 * @dataProvider throwingTemplates
	 */
	public function testThrowingTemplateClosesItsBuffers ($name)
	{
		$level = ob_get_level ();

		try {
			(new Template ($name))->parse ();
			$this->fail ('Expected the template exception to propagate');
		} catch (\RuntimeException $e) {
			$this->assertSame ('template failed', $e->getMessage ());
		}

		$this->assertSame ($level, ob_get_level ());
	}

	public function testRenderingStillReturnsTheOutput ()
	{
		$level = ob_get_level ();

		$template = new Template ('buffer/fine.phpt');
		$template->set ('name', 'world');

		$this->assertSame ('hello world', trim ($template->parse ()));
		$this->assertSame ($level, ob_get_level ());
	}
}
