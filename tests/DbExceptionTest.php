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
}
