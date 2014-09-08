<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/04/14
 * Time: 15:52
 */

namespace Neuron\Tests;

use Neuron\Filter\Context;
use Neuron\Filter\Field;
use PHPUnit_Framework_TestCase;

use Neuron\Filter\Parser;
use Neuron\Filter\Scanner;

class FilterTest
	extends PHPUnit_Framework_TestCase
{

	public function testFilter ()
	{
		$context = new Context ();

		$filter = new Parser (new Scanner ("1 = 1"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("1 = 0"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("(1 = 1)"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("(1 = 0)"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("(1 AND 0)"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("(1 OR 0)"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("(1 OR 0) AND 1"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("(1 AND 0) AND 1"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("(1 AND (1 OR 0)) AND 1"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("(0 AND (1 OR 0)) AND 1"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("(1 AND (1 OR 0)) AND 1"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("(0 AND (1 OR 0)) AND 0"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ('"this is a test string" CONTAINS "test"'));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ('"This is a test string full of bananas" CONTAINS "pineapple"'));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ('"This is a test string full of bananas" CONTAINS "bananas"'));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ('"This is a test string full of bananas" CONTAINS "full of bananas"'));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ('"This is a test string with some bananas" CONTAINS "full of bananas"'));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ('("This is a test string with some bananas" CONTAINS "bananas") AND (1 = 1)'));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ('("This is a test string with some bananas" CONTAINS "bananas") AND (0 = 1)'));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ('"This is a test string with some bananas" CONTAINS "bananas" AND 1 = 1'));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ('"This is a test string with some bananas" CONTAINS "bananas" AND 0 = 1'));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ('"This is a test string with some bananas" CONTAINS "bananas" AND 1'));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ('\'This is a test string with some bananas\' CONTAINS "bananas"'));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ('\'This is a test string with some bananas"\' CONTAINS \'bananas"\' '));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("'This is a test string with some bananas\"' CONTAINS 'bananas\"' "));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("'This is a test string with some bananas\" & pineapples' CONTAINS 'bananas\" & pineapples' "));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("!(1 = 0)"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("NOT(1 = 0)"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("1 != 0"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("!(1 != 0)"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("NOT (1 != 0)"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("!(1 = 1)"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("NOT (1 = 1)"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("1 != 1"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("1 NOT = 1"));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("!(1 != 1)"));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("onewordstring CONTAINS string "));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("'one_word_string' CONTAINS string "));
		$this->assertTrue ($filter->validate());

		$filter = new Parser (new Scanner ("'one_word_string' !CONTAINS string "));
		$this->assertFalse ($filter->validate());

		$filter = new Parser (new Scanner ("'one_word_string' !CONTAINS string OR jill = jill"));
		$this->assertTrue ($filter->validate());
	}

	public function testContextArray ()
	{
		$context = new Context ();

		$field = new Field ('body');
		$context->setField ($field);

		$filter = new Parser (new Scanner ("body contains midget"), $context);

		// Arrays
		$arrayTrue = array ('body' => 'This object contains a midget in the body.');
		$arrayFalse = array ('body' => 'This object contains a giant in the body.');

		$this->assertTrue ($filter->validate ($arrayTrue));
		$this->assertFalse ($filter->validate ($arrayFalse));
	}

	public function testContextObject ()
	{
		$context = new Context ();

		$field = new Field ('body');
		$context->setField ($field);

		$filter = new Parser (new Scanner ("body contains midget"), $context);

		// stdObjects
		$objTrue = new \stdClass ();
		$objTrue->body = 'This object contains a midget in the body.';

		$objFalse = new \stdClass ();
		$objFalse->body = 'This object contains a giant in the body.';

		$this->assertTrue ($filter->validate ($objTrue));
		$this->assertFalse ($filter->validate ($objFalse));

	}

	public function testContextModels ()
	{
		$context = new Context ();

		$field = new Field ('body');
		$context->setField ($field);

		$filter = new Parser (new Scanner ("body contains midget"), $context);

		// Models
		$this->assertTrue ($filter->validate (new FilterTestFieldModel ("This object contains a midget in the body.")));
		$this->assertFalse ($filter->validate (new FilterTestFieldModel ("This object contains a giant in the body.")));
	}

	public function testContextModelsPublicAttributes ()
	{
		$context = new Context ();

		$field = new Field ('body');
		$context->setField ($field);

		$filter = new Parser (new Scanner ("body contains midget"), $context);

		// Models with public attributes
		$this->assertTrue ($filter->validate (new FilterTestFieldModelPublic ("This object contains a midget in the body.")));
		$this->assertFalse ($filter->validate (new FilterTestFieldModelPublic ("This object contains a giant in the body.")));
	}

	public function testContextModelsCallback ()
	{
		$context = new Context ();

		$field = new Field ('body');
		$field->setCallback (function (FilterTestFieldWeirdGetter $model) { return $model->getWeirdBody (); });
		$context->setField ($field);

		$filter = new Parser (new Scanner ("body contains midget"), $context);

		// Models with public attributes
		$this->assertTrue ($filter->validate (new FilterTestFieldWeirdGetter ("This object contains a midget in the body.")));
		$this->assertFalse ($filter->validate (new FilterTestFieldWeirdGetter ("This object contains a giant in the body.")));

		$filter = new Parser (new Scanner ("body contains body"), $context);

		// Models with public attributes
		$this->assertTrue ($filter->validate (new FilterTestFieldWeirdGetter ("Cuz theze are my united states of whatever")));
	}

}

class FilterTestFieldModel
{
	private $innerbody;

	public function __construct ($body)
	{
		$this->innerbody = $body;
	}

	public function getBody ()
	{
		return $this->innerbody;
	}
}

class FilterTestFieldWeirdGetter
{
	private $innerbody;

	public function __construct ($body)
	{
		$this->innerbody = $body;
	}

	public function getWeirdBody ()
	{
		return $this->innerbody;
	}
}

class FilterTestFieldModelPublic
{
	public $body;

	public function __construct ($body)
	{
		$this->body = $body;
	}
}