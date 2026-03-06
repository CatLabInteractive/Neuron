<?php

namespace Neuron\Tests;

use PHPUnit\Framework\TestCase;
use Neuron\Collections\ErrorCollection;

class ErrorCollectionTest extends TestCase
{
	public function testAddError ()
	{
		$collection = new ErrorCollection ();
		$error = $collection->addError ('Something went wrong');

		$this->assertCount (1, $collection);
		$this->assertInstanceOf (\Neuron\Models\Error::class, $error);
	}

	public function testGetData ()
	{
		$collection = new ErrorCollection ();
		$collection->addError ('Error %s', ['one']);
		$collection->addError ('Error %s', ['two']);

		$data = $collection->getData ();
		$this->assertCount (2, $data);
		$this->assertEquals ('Error one', $data[0]);
		$this->assertEquals ('Error two', $data[1]);
	}

	public function testGetDetailedData ()
	{
		$collection = new ErrorCollection ();
		$error = $collection->addError ('Error %s in %s', ['field', 'form']);
		$error->setSubject ('test_subject');
		$error->setCode ('ERR001');

		$detailed = $collection->getDetailedData ();
		$this->assertCount (1, $detailed);
		$this->assertEquals ('Error field in form', $detailed[0]['message']);
		$this->assertEquals ('Error %s in %s', $detailed[0]['template']);
		$this->assertEquals (['field', 'form'], $detailed[0]['arguments']);
		$this->assertEquals ('test_subject', $detailed[0]['subject']);
		$this->assertEquals ('ERR001', $detailed[0]['code']);
	}

	public function testAddErrorWithNoArguments ()
	{
		$collection = new ErrorCollection ();
		$collection->addError ('Simple error message');

		$data = $collection->getData ();
		$this->assertEquals ('Simple error message', $data[0]);
	}
}
