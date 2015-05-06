<?php


namespace Neuron\Tests;

use PHPUnit_Framework_TestCase;
use Neuron\Core\Tools;


class ToolsTest
	extends PHPUnit_Framework_TestCase
{
	public function testEmailInputCheck ()
	{
		// Valid email addresses
		$this->assertTrue (Tools::checkInput ('thijs@catlab.be', 'email'));
		$this->assertTrue (Tools::checkInput ('thijs.vanderschaeghe@catlab.be', 'email'));

		// Invalid email address
		$this->assertFalse (Tools::checkInput (0, 'email'));
		$this->assertFalse (Tools::checkInput (null, 'email'));
		$this->assertFalse (Tools::checkInput (false, 'email'));
		$this->assertFalse (Tools::checkInput ('thijs', 'email'));
		$this->assertFalse (Tools::checkInput ('@catlab.be', 'email'));
		$this->assertFalse (Tools::checkInput ('thijs@home@catlab.be', 'email'));
	}

	public function testURLInputCheck ()
	{
		//$this->assertTrue (Tools::checkInput ('huffingtonpost.com/2014/06/13/iraq-defend-country_n_5491357.html?1402661760', 'url'));

		$this->assertTrue (Tools::checkInput ('http://www.catlab.eu/', 'url'));
		$this->assertTrue (Tools::checkInput ('http://www.catlab.eu', 'url'));
		$this->assertTrue (Tools::checkInput ('http://www.catlab.eu?foo=bar&bla=bam', 'url'));
		$this->assertTrue (Tools::checkInput ('http://www.catlab.eu/?foo=bar&bla=bam', 'url'));
		$this->assertTrue (Tools::checkInput ('http://www.catlab.eu/index.html?foo=bar&bla=bam', 'url'));
		$this->assertTrue (Tools::checkInput ('http://www.catlab.eu/index.php?foo=bar&bla=bam', 'url'));

		$this->assertTrue (Tools::checkInput ('https://www.catlab.eu/', 'url'));
		$this->assertTrue (Tools::checkInput ('https://www.catlab.eu', 'url'));
		$this->assertTrue (Tools::checkInput ('https://www.catlab.eu?foo=bar&bla=bam', 'url'));
		$this->assertTrue (Tools::checkInput ('https://www.catlab.eu/?foo=bar&bla=bam', 'url'));
		$this->assertTrue (Tools::checkInput ('https://www.catlab.eu/index.html?foo=bar&bla=bam', 'url'));
		$this->assertTrue (Tools::checkInput ('https://www.catlab.eu/index.php?foo=bar&bla=bam', 'url'));
		$this->assertTrue (Tools::checkInput ('http://socialmouths.com/blog/2014/01/24/google-plus-features/?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed%3A+Socialmouths+%28SocialMouths%29', 'url'));
		$this->assertTrue (Tools::checkInput ('http://www.business2community.com/social-media/social-media-strategy-wont-work-without-one-thing-0911103#!YluUO', 'url'));
		$this->assertTrue (Tools::checkInput ('http://www.latimes.com/world/middleeast/la-fg-obama-iraq-20140613-story.html#page=1', 'url'));
		$this->assertTrue (Tools::checkInput ('http://www.huffingtonpost.com/2014/06/13/iraq-defend-country_n_5491357.html?1402661760', 'url'));
		$this->assertTrue (Tools::checkInput ('ww.link.be', 'url'));

		$this->assertTrue (Tools::checkInput ('www.huffingtonpost.com/2014/06/13/iraq-defend-country_n_5491357.html?1402661760', 'url'));

		$this->assertFalse (Tools::checkInput ('this is not an url.', 'url'));
		$this->assertFalse (Tools::checkInput ('thisisalsonotanurl.', 'url'));
		$this->assertFalse (Tools::checkInput ('.neitheristhis', 'url'));
		$this->assertFalse (Tools::checkInput ('.or this', 'url'));

		//$this->assertFalse (Tools::checkInput ('iwouldliketobeanurl.but im not', 'url'));

		$this->assertFalse (Tools::checkInput ('test', 'url'));
//		$this->assertFalse (Tools::checkInput ('w.test', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('w.test.com', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('ftp://user:password@domain.com/path/', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('https://www.test.subdomain.domain.xyz/', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('domain.test/#anchor', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('domain.co/?query=123', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('mailto://user@unkwn.com', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('http://www.domain.co/path/to/index.ext', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('http://www.domain.co\path\to\stuff.txt', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('http://www.domain.co\path@to#stuff$txt', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('www.test.com/file[/]index.html', 'url')); // @TODO
//		$this->assertFalse (Tools::checkInput ('www.test.com/file{/}index.html', 'url')); // @TODO
		$this->assertFalse (Tools::checkInput ('www."test".com', 'url'));
	}

	public function testNumberInput ()
	{
		$this->assertTrue (Tools::checkInput (5, 'number'));
		$this->assertTrue (Tools::checkInput (5.0, 'number'));
		$this->assertTrue (Tools::checkInput ('5.0', 'number'));

		$this->assertFalse (Tools::checkInput ('five', 'number'));
		$this->assertFalse (Tools::checkInput ('23,5', 'number'));
		$this->assertFalse (Tools::checkInput ('foobaaaar', 'number'));
	}

	public function testIntInput ()
	{
		$this->assertTrue (Tools::checkInput (5, 'int'));
		$this->assertTrue (Tools::checkInput (5.0, 'int'));
		$this->assertTrue (Tools::checkInput ('5', 'int'));
		$this->assertTrue (Tools::checkInput ('5.0', 'int'));

		$this->assertFalse (Tools::checkInput (5.1, 'int'));
		$this->assertFalse (Tools::checkInput ('5.1', 'int'));
		$this->assertFalse (Tools::checkInput ('foobar', 'int'));
		$this->assertFalse (Tools::checkInput ('23,5', 'int'));

	}

	public function testDateInput () {
		$this->assertTrue (Tools::checkInput ('2015-06-01T10:00', 'datetime'));
		$this->assertFalse (Tools::checkInput ('06-01-2015T10:00', 'datetime'));
	}
}