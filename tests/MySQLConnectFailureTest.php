<?php

namespace Neuron\Tests;

use Neuron\Exceptions\DbException;
use PHPUnit\Framework\TestCase;

class MySQLConnectFailureTest extends TestCase
{
	const DRIVER_ERROR = "Access denied for user 'app_user'@'db.internal' (using password: YES)";

	public function testConnectionFailureThrowsDbExceptionWithFixedMessage ()
	{
		$db = new FailingMySQL ();
		$db->openError = new \RuntimeException (self::DRIVER_ERROR, 1045);

		ob_start ();
		try {
			$db->connect ();
			$thrown = null;
		} catch (DbException $e) {
			$thrown = $e;
		}
		$output = ob_get_clean ();

		$this->assertSame ('', $output);
		$this->assertInstanceOf (DbException::class, $thrown);
		$this->assertSame (DbException::CONNECTION_FAILED, $thrown->getMessage ());
		$this->assertSame ('Database connection failed.', $thrown->getMessage ());
		$this->assertTrue ($thrown->isConnectionError ());
		$this->assertNull ($thrown->getQuery ());
	}

	public function testOriginalErrorStaysAvailableForLogging ()
	{
		$db = new FailingMySQL ();
		$db->openError = new \RuntimeException (self::DRIVER_ERROR, 1045);

		try {
			$db->connect ();
			$this->fail ('Expected a DbException');
		} catch (DbException $e) {
			$this->assertSame (1045, $e->getErrorCode ());
			$this->assertNotNull ($e->getPrevious ());
			$this->assertSame (self::DRIVER_ERROR, $e->getPrevious ()->getMessage ());
			$this->assertSame (1045, $e->getPrevious ()->getCode ());
			$this->assertNotSame ($db->openError, $e->getPrevious ());
		}
	}

	public function testErrorsThatAreNotExceptionsAreWrappedToo ()
	{
		$db = new FailingMySQL ();
		$db->openError = new \Error ('Class "mysqli" not found');

		$this->expectException (DbException::class);
		$this->expectExceptionMessage (DbException::CONNECTION_FAILED);
		$db->connect ();
	}

	public function testFailedConnectionIsNotKept ()
	{
		$db = new FailingMySQL ();
		$db->openError = new \RuntimeException (self::DRIVER_ERROR, 1045);

		try {
			$db->connect ();
		} catch (DbException $e) {
		}

		$this->assertNull ($db->getConnection ());

		$db->openError = null;
		$db->fakeConnection = new FakeMysqliConnection ();
		$db->connect ();

		$this->assertSame (2, $db->attempts);
		$this->assertSame ($db->fakeConnection, $db->getConnection ());
	}

	public function testCharsetIsSetThroughTheDriver ()
	{
		$db = new FailingMySQL ();
		$db->fakeConnection = new FakeMysqliConnection ();

		$db->connect ();
		$db->connect ();

		// example/config/database.php
		$this->assertSame (array ('utf8'), $db->fakeConnection->charsets);
		$this->assertSame (1, $db->attempts);
	}

	public function testRejectedCharsetIsAConnectionFailure ()
	{
		$db = new FailingMySQL ();
		$db->fakeConnection = new FakeMysqliConnection ();
		$db->fakeConnection->acceptCharset = false;
		$db->fakeConnection->error = 'Unknown character set';
		$db->fakeConnection->errno = 2019;

		try {
			$db->connect ();
			$this->fail ('Expected a DbException');
		} catch (DbException $e) {
			$this->assertSame (DbException::CONNECTION_FAILED, $e->getMessage ());
			$this->assertSame (2019, $e->getErrorCode ());
			$this->assertSame ('Unknown character set', $e->getPrevious ()->getMessage ());
			$this->assertNull ($db->getConnection ());
		}
	}
}
