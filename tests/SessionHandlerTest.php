<?php

namespace Neuron\Tests;

use Neuron\SessionHandlers\SessionHandler;
use Neuron\Tests\SessionHandlers\RecordingSessionHandler;
use PHPUnit\Framework\TestCase;

/**
 * Which session id a request may bring, and what is logged about it.
 * Nothing here starts a session or sends a header.
 */
class SessionHandlerTest extends TestCase
{
	const ID = 'abcdef0123456789abcdef0123456789';
	const OTHER_ID = 'ZYXWVUTSRQPONMLKJIHGFEDCBA-,9876';

	protected function tearDown (): void
	{
		SessionHandler::setQueryParameterEnabled (false);
	}

	public function testIdsInTheFormatPhpGeneratesAreValid ()
	{
		$this->assertTrue (SessionHandler::isValidSessionId (self::ID));
		$this->assertTrue (SessionHandler::isValidSessionId (self::OTHER_ID));
		$this->assertTrue (SessionHandler::isValidSessionId (str_repeat ('a', 22)));
		$this->assertTrue (SessionHandler::isValidSessionId (str_repeat ('a', 256)));
	}

	/**
	 * @dataProvider invalidIds
	 */
	public function testOtherIdsAreInvalid ($id)
	{
		$this->assertFalse (SessionHandler::isValidSessionId ($id));
	}

	public static function invalidIds ()
	{
		return array (
			'empty' => array (''),
			'null' => array (null),
			'array' => array (array (self::ID)),
			'number' => array (1234567890123456789012),
			'too short' => array (str_repeat ('a', 21)),
			'too long' => array (str_repeat ('a', 257)),
			'slash' => array ('../../../../etc/passwd-aaaaaaaaaa'),
			'dot' => array ('abcdef0123456789.abcdef012345678'),
			'quote' => array ("abcdef0123456789'abcdef012345678"),
			'space' => array ('abcdef0123456789 abcdef012345678'),
			'trailing newline' => array (self::ID . "\n"),
			'nul byte' => array (self::ID . "\0"),
			'non-ascii' => array ('abcdef0123456789abcdef012345678é'),
		);
	}

	public function testAnIdGivenByTheApplicationComesFirst ()
	{
		$resolved = SessionHandler::resolveSessionId (
			self::ID,
			array ('PHPSESSID' => self::OTHER_ID),
			array ('PSID' => self::OTHER_ID)
		);

		$this->assertSame (array ('id' => self::ID, 'source' => SessionHandler::SOURCE_PROVIDED), $resolved);
	}

	public function testAnInvalidIdGivenByTheApplicationIsIgnored ()
	{
		$resolved = SessionHandler::resolveSessionId ('not a session id', array (), array ());
		$this->assertSame (array ('id' => null, 'source' => SessionHandler::SOURCE_NEW), $resolved);
	}

	public function testTheCookieIdIsUsed ()
	{
		$resolved = SessionHandler::resolveSessionId (null, array ('PHPSESSID' => self::ID), array ());
		$this->assertSame (array ('id' => self::ID, 'source' => SessionHandler::SOURCE_COOKIE), $resolved);
	}

	public function testAnInvalidCookieIdIsIgnored ()
	{
		$resolved = SessionHandler::resolveSessionId (null, array ('PHPSESSID' => 'abc/../def'), array ());
		$this->assertSame (array ('id' => null, 'source' => SessionHandler::SOURCE_NEW), $resolved);

		$resolved = SessionHandler::resolveSessionId (null, array ('PHPSESSID' => array (self::ID)), array ());
		$this->assertSame (array ('id' => null, 'source' => SessionHandler::SOURCE_NEW), $resolved);
	}

	public function testTheQueryParameterIsIgnoredByDefault ()
	{
		$this->assertFalse (SessionHandler::isQueryParameterEnabled ());

		$resolved = SessionHandler::resolveSessionId (null, array (), array ('PSID' => self::ID));
		$this->assertSame (array ('id' => null, 'source' => SessionHandler::SOURCE_NEW), $resolved);
	}

	public function testTheQueryParameterIsUsedOnceEnabled ()
	{
		SessionHandler::setQueryParameterEnabled (true);
		$this->assertTrue (SessionHandler::isQueryParameterEnabled ());

		$resolved = SessionHandler::resolveSessionId (null, array (), array ('PSID' => self::ID));
		$this->assertSame (array ('id' => self::ID, 'source' => SessionHandler::SOURCE_QUERY), $resolved);
	}

	public function testAnInvalidQueryParameterIdIsIgnored ()
	{
		SessionHandler::setQueryParameterEnabled (true);

		$resolved = SessionHandler::resolveSessionId (null, array (), array ('PSID' => "abc'; --"));
		$this->assertSame (array ('id' => null, 'source' => SessionHandler::SOURCE_NEW), $resolved);
	}

	public function testTheCookieWinsOverTheQueryParameter ()
	{
		SessionHandler::setQueryParameterEnabled (true);

		$resolved = SessionHandler::resolveSessionId (
			null,
			array ('PHPSESSID' => self::ID),
			array ('PSID' => self::OTHER_ID)
		);
		$this->assertSame (array ('id' => self::ID, 'source' => SessionHandler::SOURCE_COOKIE), $resolved);
	}

	public function testAnInvalidCookieFallsBackToTheEnabledQueryParameter ()
	{
		SessionHandler::setQueryParameterEnabled (true);

		$resolved = SessionHandler::resolveSessionId (
			null,
			array ('PHPSESSID' => 'nope'),
			array ('PSID' => self::OTHER_ID)
		);
		$this->assertSame (array ('id' => self::OTHER_ID, 'source' => SessionHandler::SOURCE_QUERY), $resolved);
	}

	public function testTheStartLogMessageNamesTheSourceAndNeverTheId ()
	{
		$sources = array (
			SessionHandler::SOURCE_PROVIDED,
			SessionHandler::SOURCE_COOKIE,
			SessionHandler::SOURCE_QUERY,
			SessionHandler::SOURCE_NEW,
		);

		$messages = array ();
		foreach ($sources as $source) {
			$message = SessionHandler::getStartLogMessage ($source);
			$this->assertStringContainsString ('session', $message);
			$messages[] = $message;
		}

		// One distinct message per source.
		$this->assertCount (4, array_unique ($messages));

		// The message is built from the source alone.
		$resolved = SessionHandler::resolveSessionId (null, array ('PHPSESSID' => self::ID), array ());
		$this->assertStringNotContainsString (self::ID, SessionHandler::getStartLogMessage ($resolved['source']));
	}

	public function testValidateIdRejectsAnInvalidFormat ()
	{
		$handler = new SessionHandler ();
		$this->assertFalse ($handler->validateId ('../not-a-session-id'));
	}

	public function testTheSessionFileOfTheFilesHandlerIsLocated ()
	{
		$this->assertSame ('/var/sessions/sess_' . self::ID, SessionHandler::getSessionFilePath ('/var/sessions', self::ID));
		$this->assertSame ('/var/sessions/sess_' . self::ID, SessionHandler::getSessionFilePath ('/var/sessions/', self::ID));
		$this->assertSame ('/var/sessions/sess_' . self::ID, SessionHandler::getSessionFilePath ('0;0600;/var/sessions', self::ID));
		$this->assertSame ('/var/sessions/a/b/sess_' . self::ID, SessionHandler::getSessionFilePath ('2;/var/sessions', self::ID));
		$this->assertSame ('/var/sessions/a/sess_' . self::ID, SessionHandler::getSessionFilePath ('1;0600;/var/sessions', self::ID));
		$this->assertSame (
			rtrim (sys_get_temp_dir (), '/\\') . '/sess_' . self::ID,
			SessionHandler::getSessionFilePath ('', self::ID)
		);
	}

	public function testUpdateTimestampWritesTheSession ()
	{
		$handler = new RecordingSessionHandler ();
		$this->assertTrue ($handler->updateTimestamp (self::ID, 'data'));
		$this->assertSame (array (array (self::ID, 'data')), $handler->writes);
	}

	public function testItImplementsTheInterfaceStrictModeNeeds ()
	{
		$this->assertInstanceOf (\SessionUpdateTimestampHandlerInterface::class, new SessionHandler ());
	}
}
