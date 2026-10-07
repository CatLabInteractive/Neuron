<?php

namespace Neuron\Tests;

use Neuron\Core\Template;
use PHPUnit\Framework\TestCase;

/**
 * Pages and partials (loaded with template() or combine()) get the same
 * variables with the same escaping, templates are rendered in a scope of
 * their own, and template names are validated before they are looked up.
 */
class TemplateHardeningTest extends TestCase
{
	const TEXT = "<b>x</b> it's";
	const ESCAPED = '&lt;b&gt;x&lt;/b&gt; it&#039;s';

	protected function setUp (): void
	{
		Template::clearShares ();
		Template::setPath (__DIR__ . '/templates/');
	}

	protected function tearDown (): void
	{
		Template::clearShares ();
	}

	/**
	 * Every way a template gets rendered: as the page itself, or as a
	 * partial through each of the two helpers.
	 */
	public static function renderModes ()
	{
		return [
			'page' => [ null ],
			'template() partial' => [ 'hardening/viaTemplate.phpt' ],
			'combine() partial' => [ 'hardening/viaCombine.phpt' ],
		];
	}

	public static function partialHelpers ()
	{
		return [
			'template()' => [ 'Template' ],
			'combine()' => [ 'Combine' ],
		];
	}

	/**
	 * Render $name directly, or through the given wrapper template.
	 */
	private function template ($name, $wrapper)
	{
		if ($wrapper === null) {
			return new Template ($name);
		}

		$template = new Template ($wrapper);
		$template->set ('partial', $name);
		return $template;
	}

	/**
	 * @dataProvider renderModes
	 */
	public function testStringVariableIsEscaped ($wrapper)
	{
		$template = $this->template ('hardening/value.phpt', $wrapper);
		$template->set ('value', self::TEXT);

		$this->assertSame ('[' . self::ESCAPED . ']', $template->parse ());
	}

	/**
	 * @dataProvider renderModes
	 */
	public function testSharedStringVariableIsEscaped ($wrapper)
	{
		Template::share ('value', self::TEXT);

		$template = $this->template ('hardening/value.phpt', $wrapper);

		$this->assertSame ('[' . self::ESCAPED . ']', $template->parse ());
	}

	/**
	 * @dataProvider renderModes
	 */
	public function testInvalidUtf8IsSubstitutedInsteadOfDropped ($wrapper)
	{
		$template = $this->template ('hardening/value.phpt', $wrapper);
		$template->set ('value', "a\xC3(b");

		$this->assertSame ("[a\u{FFFD}(b]", $template->parse ());
	}

	/**
	 * @dataProvider renderModes
	 */
	public function testRawVariableStaysRaw ($wrapper)
	{
		$template = $this->template ('hardening/value.phpt', $wrapper);
		$template->setRaw ('value', self::TEXT);

		$this->assertSame ('[' . self::TEXT . ']', $template->parse ());
	}

	/**
	 * @dataProvider renderModes
	 */
	public function testUnderscoreVariantHoldsTheUnescapedString ($wrapper)
	{
		$template = $this->template ('hardening/rawVariant.phpt', $wrapper);
		$template->set ('value', self::TEXT);

		$this->assertSame ('[' . self::TEXT . ']', $template->parse ());
	}

	/**
	 * @dataProvider renderModes
	 */
	public function testNonStringVariablesAreLeftAlone ($wrapper)
	{
		$template = $this->template ('hardening/readNamed.phpt', $wrapper);
		$template->set ('name', 'list');
		$template->set ('list', array ('<b>x</b>', 'y'));

		$this->assertSame ('[<b>x</b>,y]', $template->parse ());
	}

	/**
	 * A partial parameter is passed on as it is given: a template that hands
	 * one of its own (already escaped) variables to a partial must not get
	 * it escaped a second time.
	 *
	 * @dataProvider partialHelpers
	 */
	public function testParameterIsNotEscapedTwice ($helper)
	{
		$template = new Template ('hardening/passOn' . $helper . '.phpt');
		$template->set ('value', self::TEXT);

		$this->assertSame ('[' . self::ESCAPED . ']', $template->parse ());
	}

	public static function internalNames ()
	{
		$other = __DIR__ . '/templates/hardening/other.phpt';

		return [
			'template' => [ 'template', 'hardening/other.phpt', 'hardening/other.phpt' ],
			'ctlbtmpltfiles' => [ 'ctlbtmpltfiles', array ($other), $other ],
			'ctlbtmpltfile' => [ 'ctlbtmpltfile', $other, $other ],
			'parameters' => [ 'parameters', array ('template' => 'hardening/other.phpt'), 'hardening/other.phpt' ],
			'val' => [ 'val', 'other', 'other' ],
		];
	}

	/**
	 * A variable that happens to be named like something the engine uses
	 * is just a variable: the requested template is rendered, and it can
	 * read the variable.
	 *
	 * @dataProvider internalNames
	 */
	public function testVariableNamedLikeAnInternalInAPage ($name, $value, $printed)
	{
		$template = new Template ('hardening/readNamed.phpt');
		$template->set ('name', $name);
		$template->set ($name, $value);

		$this->assertSame ('[' . $printed . ']', $template->parse ());
	}

	/**
	 * @dataProvider internalNames
	 */
	public function testVariableNamedLikeAnInternalInATemplatePartial ($name, $value, $printed)
	{
		$template = new Template ('hardening/viaTemplate.phpt');
		$template->set ('partial', 'hardening/readNamed.phpt');
		$template->set ('name', $name);
		$template->set ($name, $value);

		$this->assertSame ('[' . $printed . ']', $template->parse ());
	}

	/**
	 * @dataProvider internalNames
	 */
	public function testVariableNamedLikeAnInternalInACombinePartial ($name, $value, $printed)
	{
		$template = new Template ('hardening/viaCombine.phpt');
		$template->set ('partial', 'hardening/readNamed.phpt');
		$template->set ('name', $name);
		$template->set ($name, $value);

		$this->assertSame ('[' . $printed . ']', $template->parse ());
	}

	/**
	 * @dataProvider partialHelpers
	 */
	public function testParameterNamedLikeAnInternal ($helper)
	{
		foreach (self::internalNames () as $case) {
			list ($name, $value) = $case;

			$template = new Template ('hardening/parameter' . $helper . '.phpt');
			$template->set ('value', 'kept');
			$template->set ('name', $name);
			$template->set ('injected', $value);

			$this->assertSame ('[kept]', $template->parse (), $name);
		}
	}

	public function testVariableNamedThisDoesNotReplaceTheTemplate ()
	{
		$template = new Template ('hardening/viaTemplate.phpt');
		$template->set ('partial', 'hardening/value.phpt');
		$template->set ('value', 'kept');
		$template->set ('this', 'not a template');

		$this->assertSame ('[kept]', $template->parse ());
	}

	public static function rejectedNames ()
	{
		return [
			'parent segment' => [ 'hardening/../hardening/value.phpt' ],
			'leading parent segment' => [ '../templates/hardening/value.phpt' ],
			'backslash parent segment' => [ 'hardening\\..\\hardening/value.phpt' ],
			'trailing parent segment' => [ 'hardening/..' ],
			'NUL byte' => [ "hardening/value.phpt\0" ],
			'NUL byte in the middle' => [ "hardening/value.phpt\0.txt" ],
			'absolute path' => [ __DIR__ . '/templates/hardening/value.phpt' ],
			'leading slash' => [ '/hardening/value.phpt' ],
			'leading backslash' => [ '\\hardening/value.phpt' ],
			'drive letter' => [ 'C:\\hardening\\value.phpt' ],
		];
	}

	/**
	 * @dataProvider rejectedNames
	 */
	public function testUnsafeNameIsNotATemplate ($name)
	{
		$this->assertFalse (Template::hasTemplate ($name));
	}

	/**
	 * @dataProvider rejectedNames
	 */
	public function testUnsafeNameIsNotRenderedAsAPage ($name)
	{
		$template = new Template ($name);
		$template->set ('value', 'rendered');

		$out = $template->parse ();

		$this->assertStringContainsString ('Template not found', $out);
		$this->assertStringNotContainsString ('[rendered]', $out);
	}

	/**
	 * @dataProvider rejectedNames
	 */
	public function testUnsafeNameIsNotRenderedAsAPartial ($name)
	{
		foreach (array ('hardening/viaTemplate.phpt', 'hardening/viaCombine.phpt') as $wrapper) {
			$template = new Template ($wrapper);
			$template->setRaw ('partial', $name);
			$template->set ('value', 'rendered');

			$this->assertSame ('', $template->parse (), $wrapper);
		}
	}

	public function testNamesWithDotsThatAreNotParentSegmentsStillWork ()
	{
		$this->assertTrue (Template::hasTemplate ('hardening/value.phpt'));
		$this->assertTrue (Template::hasTemplate ('hardening/./value.phpt'));
		$this->assertFalse (Template::hasTemplate ('hardening/..value.phpt'));
		$this->assertFalse (Template::hasTemplate ('hardening/missing.phpt'));
	}

	public function testNotFoundMessageEscapesTheName ()
	{
		$out = (new Template ("<b>x</b> it's.phpt"))->parse ();

		$this->assertStringContainsString ('Template not found', $out);
		$this->assertStringContainsString (self::ESCAPED . '.phpt', $out);
		$this->assertStringNotContainsString ('<b>', $out);
	}

	public function testRenderingLeavesTheOutputBufferLevelAlone ()
	{
		$level = ob_get_level ();

		foreach (self::renderModes () as $mode) {
			$template = $this->template ('hardening/value.phpt', $mode[0]);
			$template->set ('value', 'x');
			$template->parse ();
		}

		$this->assertSame ($level, ob_get_level ());
	}
}
