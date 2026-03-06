<?php


namespace Neuron\Tests;

use PHPUnit\Framework\TestCase;
use Neuron\Core\Tools;


class ToolsTest
	extends TestCase
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

	// ---------------------------------------------------------------
	// Date validation tests (the bug fix)
	// ---------------------------------------------------------------

	public function testDateCheckInputValidDates ()
	{
		$this->assertTrue (Tools::checkInput ('2015-06-01', 'date'));
		$this->assertTrue (Tools::checkInput ('2000-01-01', 'date'));
		$this->assertTrue (Tools::checkInput ('1999-12-31', 'date'));
		$this->assertTrue (Tools::checkInput ('2024-02-29', 'date')); // leap year
	}

	public function testDateCheckInputInvalidNonIntegerParts ()
	{
		// The original bug: "a-b-c" would pass
		$this->assertFalse (Tools::checkInput ('a-b-c', 'date'));
		$this->assertFalse (Tools::checkInput ('foo-bar-baz', 'date'));
		$this->assertFalse (Tools::checkInput ('20xx-01-01', 'date'));
		$this->assertFalse (Tools::checkInput ('2015-ab-01', 'date'));
		$this->assertFalse (Tools::checkInput ('2015-01-cd', 'date'));
	}

	public function testDateCheckInputInvalidDateValues ()
	{
		$this->assertFalse (Tools::checkInput ('2015-13-01', 'date')); // month 13
		$this->assertFalse (Tools::checkInput ('2015-00-01', 'date')); // month 0
		$this->assertFalse (Tools::checkInput ('2015-02-30', 'date')); // Feb 30
		$this->assertFalse (Tools::checkInput ('2023-02-29', 'date')); // non-leap year
		$this->assertFalse (Tools::checkInput ('2015-06-32', 'date')); // day 32
		$this->assertFalse (Tools::checkInput ('0000-01-01', 'date')); // year 0
	}

	public function testDateCheckInputInvalidFormats ()
	{
		$this->assertFalse (Tools::checkInput ('', 'date'));
		$this->assertFalse (Tools::checkInput ('2015', 'date'));
		$this->assertFalse (Tools::checkInput ('2015-06', 'date'));
		$this->assertFalse (Tools::checkInput ('2015/06/01', 'date'));
		$this->assertFalse (Tools::checkInput ('01-06-2015', 'date')); // wrong order but valid checkdate would pass
		$this->assertFalse (Tools::checkInput ('2015-06-01-extra', 'date'));
	}

	public function testDateGetInputValidDate ()
	{
		$dat = array ('date' => '2015-06-01');
		$result = Tools::getInput ($dat, 'date', 'date');
		$this->assertIsInt ($result);
		$this->assertEquals ('2015-06-01', date ('Y-m-d', $result));
	}

	public function testDateGetInputInvalidDate ()
	{
		$dat = array ('date' => 'a-b-c');
		$result = Tools::getInput ($dat, 'date', 'date');
		$this->assertNull ($result);
	}

	public function testDateGetInputMissing ()
	{
		$dat = array ();
		$result = Tools::getInput ($dat, 'date', 'date');
		$this->assertNull ($result);
	}

	public function testDateGetInputDefault ()
	{
		$dat = array ('date' => 'invalid');
		$result = Tools::getInput ($dat, 'date', 'date', 'default_value');
		$this->assertEquals ('default_value', $result);
	}

	// ---------------------------------------------------------------
	// Datetime validation tests
	// ---------------------------------------------------------------

	public function testDatetimeCheckInputValid ()
	{
		$this->assertTrue (Tools::checkInput ('2015-06-01T10:00', 'datetime'));
		$this->assertTrue (Tools::checkInput ('2024-12-31T23:59', 'datetime'));
	}

	public function testDatetimeCheckInputInvalid ()
	{
		$this->assertFalse (Tools::checkInput ('06-01-2015T10:00', 'datetime'));
		$this->assertFalse (Tools::checkInput ('not-a-datetime', 'datetime'));
		$this->assertFalse (Tools::checkInput ('2015-06-01 10:00', 'datetime'));
		$this->assertFalse (Tools::checkInput ('', 'datetime'));
	}

	public function testDatetimeGetInputValid ()
	{
		$dat = array ('dt' => '2015-06-01T10:00');
		$result = Tools::getInput ($dat, 'dt', 'datetime');
		$this->assertInstanceOf (\DateTime::class, $result);
	}

	// ---------------------------------------------------------------
	// Text and varchar type tests
	// ---------------------------------------------------------------

	public function testTextCheckInput ()
	{
		$this->assertTrue (Tools::checkInput ('anything', 'text'));
		$this->assertTrue (Tools::checkInput ('', 'text'));
		$this->assertTrue (Tools::checkInput ('<script>alert(1)</script>', 'text'));
	}

	public function testVarcharCheckInput ()
	{
		$this->assertTrue (Tools::checkInput ('valid text', 'varchar'));
		$this->assertTrue (Tools::checkInput ('valid text', 'string'));
		$this->assertTrue (Tools::checkInput ('valid <b>html</b>', 'html'));
	}

	public function testNameCheckInput ()
	{
		$this->assertTrue (Tools::checkInput ('John Doe', 'name'));
		$this->assertFalse (Tools::checkInput ('<b>John</b>', 'name'));
		$this->assertFalse (Tools::checkInput ('<script>alert(1)</script>', 'name'));
	}

	// ---------------------------------------------------------------
	// Bool type tests
	// ---------------------------------------------------------------

	public function testBoolCheckInput ()
	{
		$this->assertTrue (Tools::checkInput (1, 'bool'));
		$this->assertTrue (Tools::checkInput ('true', 'bool'));
		$this->assertFalse (Tools::checkInput (0, 'bool'));
		$this->assertFalse (Tools::checkInput ('false', 'bool'));
		$this->assertFalse (Tools::checkInput ('', 'bool'));
	}

	public function testBoolGetInput ()
	{
		// When checkInput passes (value is 1 or 'true'), getInput returns strip_tags(value)
		$dat = array ('flag' => 1);
		$this->assertEquals ('1', Tools::getInput ($dat, 'flag', 'bool'));

		$dat = array ('flag' => 'true');
		$this->assertEquals ('true', Tools::getInput ($dat, 'flag', 'bool'));

		// When bool validation fails, getInput returns false (special case)
		$dat = array ('flag' => 0);
		$this->assertFalse (Tools::getInput ($dat, 'flag', 'bool'));

		$dat = array ('flag' => 'no');
		$this->assertFalse (Tools::getInput ($dat, 'flag', 'bool'));
	}

	// ---------------------------------------------------------------
	// Password type tests
	// ---------------------------------------------------------------

	public function testPasswordCheckInput ()
	{
		$this->assertTrue (Tools::checkInput ('password123', 'password'));
		$this->assertTrue (Tools::checkInput ('12345678', 'password'));
		$this->assertFalse (Tools::checkInput ('short', 'password'));
		$this->assertFalse (Tools::checkInput ('1234567', 'password')); // 7 chars
		$this->assertFalse (Tools::checkInput (str_repeat ('a', 257), 'password')); // too long
		$this->assertTrue (Tools::checkInput (str_repeat ('a', 256), 'password')); // max allowed
	}

	// ---------------------------------------------------------------
	// Username type tests
	// ---------------------------------------------------------------

	public function testUsernameCheckInput ()
	{
		$this->assertTrue (Tools::checkInput ('john_doe', 'username'));
		$this->assertTrue (Tools::checkInput ('User123', 'username'));
		$this->assertTrue (Tools::checkInput ('abc', 'username')); // min 3 chars
		$this->assertFalse (Tools::checkInput ('ab', 'username')); // too short
		$this->assertFalse (Tools::checkInput ('', 'username'));
		$this->assertFalse (Tools::checkInput ('user name', 'username')); // space
		$this->assertFalse (Tools::checkInput ('user@name', 'username')); // special chars
		$this->assertFalse (Tools::checkInput (str_repeat ('a', 21), 'username')); // too long
	}

	// ---------------------------------------------------------------
	// MD5 type tests
	// ---------------------------------------------------------------

	public function testMd5CheckInput ()
	{
		$this->assertTrue (Tools::checkInput (md5 ('test'), 'md5'));
		$this->assertTrue (Tools::checkInput ('d41d8cd98f00b204e9800998ecf8427e', 'md5'));
		$this->assertFalse (Tools::checkInput ('tooshort', 'md5'));
		$this->assertFalse (Tools::checkInput ('', 'md5'));
		$this->assertFalse (Tools::checkInput (str_repeat ('a', 33), 'md5'));
	}

	// ---------------------------------------------------------------
	// Base64 type tests
	// ---------------------------------------------------------------

	public function testBase64CheckInput ()
	{
		$this->assertTrue (Tools::checkInput (base64_encode ('test'), 'base64'));
		$this->assertTrue (Tools::checkInput (base64_encode ('hello world'), 'base64'));
		$this->assertFalse (Tools::checkInput ('not valid base64!!!', 'base64'));
	}

	public function testBase64GetInput ()
	{
		$dat = array ('data' => base64_encode ('hello world'));
		$result = Tools::getInput ($dat, 'data', 'base64');
		$this->assertEquals ('hello world', $result);
	}

	// ---------------------------------------------------------------
	// Raw type tests
	// ---------------------------------------------------------------

	public function testRawCheckInput ()
	{
		$this->assertTrue (Tools::checkInput ('anything', 'raw'));
		$this->assertTrue (Tools::checkInput ('<script>alert(1)</script>', 'raw'));
	}

	public function testRawGetInput ()
	{
		$dat = array ('data' => '<script>alert(1)</script>');
		$result = Tools::getInput ($dat, 'data', 'raw');
		$this->assertEquals ('<script>alert(1)</script>', $result);
	}

	public function testHtmlGetInput ()
	{
		$dat = array ('data' => '<b>bold</b>');
		$result = Tools::getInput ($dat, 'data', 'html');
		$this->assertEquals ('<b>bold</b>', $result);
	}

	// ---------------------------------------------------------------
	// Unknown type tests
	// ---------------------------------------------------------------

	public function testUnknownTypeReturnsFalse ()
	{
		$this->assertFalse (Tools::checkInput ('value', 'nonexistent_type'));
	}

	// ---------------------------------------------------------------
	// getInput default behavior tests
	// ---------------------------------------------------------------

	public function testGetInputMissingKey ()
	{
		$dat = array ();
		$this->assertNull (Tools::getInput ($dat, 'missing', 'text'));
	}

	public function testGetInputMissingKeyWithDefault ()
	{
		$dat = array ();
		$this->assertEquals ('fallback', Tools::getInput ($dat, 'missing', 'text', 'fallback'));
	}

	public function testGetInputStripsTagsByDefault ()
	{
		$dat = array ('name' => '<b>John</b> Doe');
		$result = Tools::getInput ($dat, 'name', 'varchar');
		$this->assertEquals ('John Doe', $result);
	}

	// ---------------------------------------------------------------
	// SQL injection patterns via checkInput
	// ---------------------------------------------------------------

	public function testSqlInjectionInEmailField ()
	{
		$this->assertFalse (Tools::checkInput ("' OR '1'='1", 'email'));
		$this->assertFalse (Tools::checkInput ("admin@example.com' OR 1=1--", 'email'));
		$this->assertFalse (Tools::checkInput ("admin@example.com'; DROP TABLE users;--", 'email'));
		$this->assertFalse (Tools::checkInput ("' UNION SELECT * FROM users--", 'email'));
	}

	public function testSqlInjectionInUsernameField ()
	{
		$this->assertFalse (Tools::checkInput ("' OR '1'='1", 'username'));
		$this->assertFalse (Tools::checkInput ("admin'; DROP TABLE users;--", 'username'));
		$this->assertFalse (Tools::checkInput ("admin' UNION SELECT * FROM users--", 'username'));
		$this->assertFalse (Tools::checkInput ("1; DROP TABLE users", 'username'));
	}

	public function testSqlInjectionInDateField ()
	{
		$this->assertFalse (Tools::checkInput ("'; DROP TABLE users;--", 'date'));
		$this->assertFalse (Tools::checkInput ("2015-01-01' OR '1'='1", 'date'));
		$this->assertFalse (Tools::checkInput ("2015-01-01; DROP TABLE users;--", 'date'));
		$this->assertFalse (Tools::checkInput ("1 OR 1=1", 'date'));
		$this->assertFalse (Tools::checkInput ("' UNION SELECT * FROM users--", 'date'));
	}

	public function testSqlInjectionInDatetimeField ()
	{
		$this->assertFalse (Tools::checkInput ("'; DROP TABLE users;--", 'datetime'));
		$this->assertFalse (Tools::checkInput ("not-a-datetime", 'datetime'));
	}

	public function testSqlInjectionInIntField ()
	{
		$this->assertFalse (Tools::checkInput ("1; DROP TABLE users", 'int'));
		$this->assertFalse (Tools::checkInput ("1 OR 1=1", 'int'));
		$this->assertFalse (Tools::checkInput ("' OR '1'='1", 'int'));
		$this->assertFalse (Tools::checkInput ("1 UNION SELECT * FROM users", 'int'));
	}

	public function testSqlInjectionInNumberField ()
	{
		$this->assertFalse (Tools::checkInput ("1; DROP TABLE users", 'number'));
		$this->assertFalse (Tools::checkInput ("1 OR 1=1", 'number'));
		$this->assertFalse (Tools::checkInput ("' OR '1'='1", 'number'));
	}

	public function testSqlInjectionInMd5Field ()
	{
		$this->assertFalse (Tools::checkInput ("' OR '1'='1' --", 'md5'));
		$this->assertFalse (Tools::checkInput ("'; DROP TABLE users;--", 'md5'));
	}

	public function testSqlInjectionInPasswordField ()
	{
		// These pass checkInput because password only checks length/UTF-8
		// But they should still be properly escaped before use in queries
		$this->assertTrue (Tools::checkInput ("password' OR '1'='1", 'password'));
	}

	public function testSqlInjectionInUrlField ()
	{
		$this->assertFalse (Tools::checkInput ("'; DROP TABLE users;--", 'url'));
		$this->assertFalse (Tools::checkInput ("' OR '1'='1", 'url'));
	}

	// ---------------------------------------------------------------
	// XSS injection patterns via getInput
	// ---------------------------------------------------------------

	public function testXssStrippedByGetInputDefault ()
	{
		$dat = array ('name' => '<script>alert("xss")</script>');
		$result = Tools::getInput ($dat, 'name', 'varchar');
		$this->assertStringNotContainsString ('<script>', $result);
	}

	public function testXssStrippedByGetInputName ()
	{
		// The 'name' type rejects values with HTML tags
		$dat = array ('name' => '<img src=x onerror=alert(1)>');
		$result = Tools::getInput ($dat, 'name', 'name');
		$this->assertNull ($result);
	}

	// ---------------------------------------------------------------
	// UTF-8 validation tests
	// ---------------------------------------------------------------

	public function testIsValidUTF8 ()
	{
		$this->assertTrue (Tools::isValidUTF8 ('hello'));
		$this->assertTrue (Tools::isValidUTF8 ('héllo wörld'));
		$this->assertTrue (Tools::isValidUTF8 ('日本語'));
		$this->assertTrue (Tools::isValidUTF8 (''));
		$this->assertFalse (Tools::isValidUTF8 ("\xFF\xFE"));
	}

	// ---------------------------------------------------------------
	// isValidBase64 tests
	// ---------------------------------------------------------------

	public function testIsValidBase64 ()
	{
		$this->assertTrue (Tools::isValidBase64 (base64_encode ('test')));
		$this->assertTrue (Tools::isValidBase64 (base64_encode ('hello world')));
		$this->assertFalse (Tools::isValidBase64 ('not valid!!!'));
	}

	// ---------------------------------------------------------------
	// putIntoText tests
	// ---------------------------------------------------------------

	public function testPutIntoText ()
	{
		$result = Tools::putIntoText ('Hello @@name, you are @@age years old.', array ('name' => 'John', 'age' => 30));
		$this->assertEquals ('Hello John, you are 30 years old.', $result);
	}

	public function testPutIntoTextRemovesUnmatched ()
	{
		$result = Tools::putIntoText ('Hello @@name, your @@missing value.', array ('name' => 'John'));
		$this->assertEquals ('Hello John, your  value.', $result);
	}

	public function testPutIntoTextWithObject ()
	{
		$obj = new class {
			public function __toString () { return 'StringObj'; }
		};
		$result = Tools::putIntoText ('Value: @@val', array ('val' => $obj));
		$this->assertEquals ('Value: StringObj', $result);
	}

	public function testPutIntoTextThrowsOnInvalidValue ()
	{
		$this->expectException (\Exception::class);
		Tools::putIntoText ('Hello @@val', array ('val' => array ('nested')));
	}

	// ---------------------------------------------------------------
	// output_text tests
	// ---------------------------------------------------------------

	public function testOutputText ()
	{
		$result = Tools::output_text ("Hello\nWorld");
		$this->assertStringContainsString ('<br', $result);
	}

	// ---------------------------------------------------------------
	// output_datepicker tests
	// ---------------------------------------------------------------

	public function testOutputDatepicker ()
	{
		$timestamp = mktime (0, 0, 0, 6, 15, 2020);
		$result = Tools::output_datepicker ($timestamp);
		$this->assertEquals ('2020-06-15', $result);
	}

	public function testOutputDatepickerEmpty ()
	{
		$result = Tools::output_datepicker (null);
		$this->assertEquals ('', $result);
	}

	// ---------------------------------------------------------------
	// splitLongWords tests
	// ---------------------------------------------------------------

	public function testSplitLongWords ()
	{
		$long = str_repeat ('a', 40);
		$result = Tools::splitLongWords ($long);
		$this->assertStringContainsString (' ', $result);
	}

	public function testSplitLongWordsShortInput ()
	{
		$result = Tools::splitLongWords ('short');
		$this->assertEquals ('short', $result);
	}

	// ---------------------------------------------------------------
	// output_form tests
	// ---------------------------------------------------------------

	public function testOutputForm ()
	{
		$result = Tools::output_form ('<script>alert("xss")</script>');
		$this->assertStringNotContainsString ('<script>', $result);
		$this->assertStringContainsString ('&lt;script&gt;', $result);
	}

	public function testOutputFormQuotes ()
	{
		$result = Tools::output_form ("He said \"hello\" and it's fine");
		$this->assertStringNotContainsString ('"', $result);
		$this->assertStringContainsString ('&quot;', $result);
	}

	// ---------------------------------------------------------------
	// output_varchar tests
	// ---------------------------------------------------------------

	public function testOutputVarchar ()
	{
		$result = Tools::output_varchar ('<b>bold</b>');
		$this->assertStringContainsString ('&lt;b&gt;', $result);
	}
}