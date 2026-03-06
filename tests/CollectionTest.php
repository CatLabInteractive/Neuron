<?php

namespace Neuron\Tests;

use PHPUnit\Framework\TestCase;
use Neuron\Collections\Collection;

class CollectionTest extends TestCase
{
	public function testAddAndCount ()
	{
		$collection = new Collection ();
		$this->assertCount (0, $collection);

		$collection->add ('a');
		$collection->add ('b');
		$collection->add ('c');
		$this->assertCount (3, $collection);
	}

	public function testFirstAndLast ()
	{
		$collection = new Collection ();
		$collection->add ('first');
		$collection->add ('middle');
		$collection->add ('last');

		$this->assertEquals ('first', $collection->first ());
		$this->assertEquals ('last', $collection->last ());
	}

	public function testFirstAndLastEmpty ()
	{
		$collection = new Collection ();
		$this->assertNull ($collection->first ());
		$this->assertNull ($collection->last ());
	}

	public function testIterator ()
	{
		$collection = new Collection ();
		$collection->add ('a');
		$collection->add ('b');
		$collection->add ('c');

		$values = [];
		foreach ($collection as $key => $value) {
			$values[$key] = $value;
		}

		$this->assertEquals ([0 => 'a', 1 => 'b', 2 => 'c'], $values);
	}

	public function testRewind ()
	{
		$collection = new Collection ();
		$collection->add ('a');
		$collection->add ('b');

		// Iterate to end
		foreach ($collection as $v) {}

		// Rewind and verify
		$collection->rewind ();
		$this->assertEquals ('a', $collection->current ());
	}

	public function testArrayAccess ()
	{
		$collection = new Collection ();
		$collection[] = 'value1';
		$collection[] = 'value2';

		$this->assertTrue (isset ($collection[0]));
		$this->assertTrue (isset ($collection[1]));
		$this->assertFalse (isset ($collection[2]));

		$this->assertEquals ('value1', $collection[0]);
		$this->assertEquals ('value2', $collection[1]);
	}

	public function testOffsetSet ()
	{
		$collection = new Collection ();
		$collection[5] = 'value5';

		$this->assertTrue (isset ($collection[5]));
		$this->assertEquals ('value5', $collection[5]);
	}

	public function testOffsetUnset ()
	{
		$collection = new Collection ();
		$collection->add ('a');
		$collection->add ('b');

		unset ($collection[0]);
		$this->assertFalse (isset ($collection[0]));
	}

	public function testRemove ()
	{
		$collection = new Collection ();
		$collection->add ('a');
		$collection->add ('b');
		$collection->add ('c');

		$result = $collection->remove ('b');
		$this->assertTrue ($result);
		$this->assertCount (2, $collection);
	}

	public function testRemoveNonExistent ()
	{
		$collection = new Collection ();
		$collection->add ('a');

		$result = $collection->remove ('nonexistent');
		$this->assertFalse ($result);
	}

	public function testClear ()
	{
		$collection = new Collection ();
		$collection->add ('a');
		$collection->add ('b');

		$collection->clear ();
		$this->assertCount (0, $collection);
	}

	public function testPeek ()
	{
		$collection = new Collection ();
		$collection->add ('a');
		$collection->add ('b');
		$collection->add ('c');

		$collection->rewind ();
		$this->assertEquals ('a', $collection->current ());
		$this->assertEquals ('b', $collection->peek ());
		// Position should not have changed
		$this->assertEquals ('a', $collection->current ());
	}

	public function testPeekAtEnd ()
	{
		$collection = new Collection ();
		$collection->add ('a');

		$collection->rewind ();
		$this->assertNull ($collection->peek ());
	}

	public function testReverse ()
	{
		$collection = new Collection ();
		$collection->add ('a');
		$collection->add ('b');
		$collection->add ('c');

		$collection->reverse ();
		$this->assertEquals ('c', $collection->first ());
		$this->assertEquals ('a', $collection->last ());
	}

	public function testCurrentAtInvalidPosition ()
	{
		$collection = new Collection ();
		$this->assertNull ($collection->current ());
	}

	public function testValid ()
	{
		$collection = new Collection ();
		$collection->add ('a');

		$collection->rewind ();
		$this->assertTrue ($collection->valid ());
		$collection->next ();
		$this->assertFalse ($collection->valid ());
	}

	public function testKey ()
	{
		$collection = new Collection ();
		$collection->add ('a');
		$collection->add ('b');

		$collection->rewind ();
		$this->assertEquals (0, $collection->key ());
		$collection->next ();
		$this->assertEquals (1, $collection->key ());
	}
}
