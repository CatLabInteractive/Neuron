<?php

namespace Neuron\Tests;

use Neuron\Exceptions\DbException;
use PHPUnit\Framework\TestCase;

class DbExceptionTest extends TestCase
{
	public function testIsDuplicateEntry ()
	{
		$this->assertTrue ((new DbException ('dup'))->setErrorCode (1062)->isDuplicateEntry ());
		$this->assertTrue ((new DbException ('dup'))->setErrorCode ('1062')->isDuplicateEntry ());
		$this->assertFalse ((new DbException ('missing table'))->setErrorCode (1146)->isDuplicateEntry ());
		$this->assertFalse ((new DbException ('no code'))->isDuplicateEntry ());
	}

	public function testFromMysqliExceptionKeepsCodeQueryAndPrevious ()
	{
		if (!class_exists ('mysqli_sql_exception')) {
			$this->markTestSkipped ('mysqli extension not loaded');
		}

		$original = new \mysqli_sql_exception ("Duplicate entry 'a' for key 'u'", 1062);
		$e = DbException::fromMysqliException ($original, 'INSERT INTO t VALUES (1)');

		$this->assertSame ("MySQL Error: Duplicate entry 'a' for key 'u'", $e->getMessage ());
		$this->assertSame (1062, $e->getErrorCode ());
		$this->assertSame (1062, $e->getCode ());
		$this->assertSame ('INSERT INTO t VALUES (1)', $e->getQuery ());
		$this->assertSame ($original, $e->getPrevious ());
		$this->assertTrue ($e->isDuplicateEntry ());
	}

	public function testConnectionFailedHasAFixedMessageAndKeepsTheCause ()
	{
		$e = DbException::connectionFailed ("Can't connect to MySQL server on 'db.internal'", 2002);

		$this->assertSame ('Database connection failed.', $e->getMessage ());
		$this->assertSame (DbException::CONNECTION_FAILED, $e->getMessage ());
		$this->assertTrue ($e->isConnectionError ());
		$this->assertSame (2002, $e->getErrorCode ());
		$this->assertSame (2002, $e->getCode ());
		$this->assertNull ($e->getQuery ());
		$this->assertSame ("Can't connect to MySQL server on 'db.internal'", $e->getPrevious ()->getMessage ());
		$this->assertSame (2002, $e->getPrevious ()->getCode ());
	}

	public function testConnectionFailedAcceptsANonNumericCode ()
	{
		$e = DbException::connectionFailed ('driver missing', 'HY000');

		$this->assertSame (0, $e->getCode ());
		$this->assertSame ('Database connection failed.', $e->getMessage ());
	}

	public function testOtherErrorsAreNotConnectionErrors ()
	{
		$this->assertFalse ((new DbException ('dup'))->setErrorCode (1062)->isConnectionError ());
	}
}
