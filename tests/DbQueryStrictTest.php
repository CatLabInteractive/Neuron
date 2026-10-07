<?php

namespace Neuron\Tests;

use Neuron\DB\Database;
use Neuron\DB\Query;
use Neuron\Exceptions\InvalidParameter;
use PHPUnit\Framework\TestCase;

/**
 * The query builder is strict about what it is given: tuples are validated,
 * untyped integers are quoted, a null condition can stand anywhere and
 * update/delete need a condition.
 * Runs without a real MySQL connection by injecting a TestDatabase stub.
 */
class DbQueryStrictTest extends TestCase
{
	protected function setUp (): void
	{
		Database::setInstance (new TestDatabase ());
	}

	protected function tearDown (): void
	{
		Database::setInstance (null);
	}

	// ---------------------------------------------------------------
	// WHERE tuples: [ value, type, comparator ]
	// ---------------------------------------------------------------

	public static function invalidWhereTuples ()
	{
		return array (
			'unknown comparator <>' => array (array ('x', Query::PARAM_STR, '<>')),
			'unknown comparator NOT LIKE' => array (array ('x', Query::PARAM_STR, 'NOT LIKE')),
			'comparator with spaces' => array (array ('x', Query::PARAM_STR, ' IN')),
			'comparator is not a string' => array (array ('x', Query::PARAM_STR, true)),
			'comparator is null' => array (array ('x', Query::PARAM_STR, null)),
			'associative array' => array (array ('a' => 'b')),
			'associative array with value key' => array (array ('value' => 'x', 'type' => Query::PARAM_STR)),
			'empty array' => array (array ()),
			'four elements' => array (array ('x', Query::PARAM_STR, '=', 'y')),
			'keys do not start at 0' => array (array (1 => 'x')),
			'gap in the keys' => array (array (0 => 'x', 2 => '=')),
			'type is not a constant' => array (array ('x', 99)),
			'type is zero' => array (array ('x', 0)),
			'type is a numeric string' => array (array ('x', '3')),
			'type is a word' => array (array ('x', 'STR')),
			'type is null' => array (array ('x', null)),
			'type is a boolean' => array (array ('x', true)),
			'plain list of strings' => array (array ('a', 'b', 'c')),
			'plain list of numbers' => array (array (7, 8, 9)),
			'IN with a single value' => array (array ('x', Query::PARAM_STR, 'IN')),
			'list with >' => array (array (array (1, 2), Query::PARAM_NUMBER, '>')),
			'list with LIKE' => array (array (array ('a', 'b'), Query::PARAM_STR, 'LIKE')),
			'list with NOT' => array (array (array (1, 2), Query::PARAM_NUMBER, 'NOT')),
			'nested list' => array (array (array (1, array (2, 3)), Query::PARAM_NUMBER, 'IN')),
			'nested list without comparator' => array (array (array (array (1)))),
			'object in a list' => array (array (array (new \stdClass ()), Query::PARAM_STR)),
		);
	}

	/**
	 * @dataProvider invalidWhereTuples
	 */
	public function testInvalidWhereTupleIsRejectedBySelect ($tuple)
	{
		$this->expectException (InvalidParameter::class);
		Query::select ('t', array (), array ('col' => $tuple))->getParsedQuery ();
	}

	/**
	 * @dataProvider invalidWhereTuples
	 */
	public function testInvalidWhereTupleIsRejectedByUpdate ($tuple)
	{
		$this->expectException (InvalidParameter::class);
		Query::update ('t', array ('a' => 'b'), array ('col' => $tuple))->getParsedQuery ();
	}

	/**
	 * @dataProvider invalidWhereTuples
	 */
	public function testInvalidWhereTupleIsRejectedByDelete ($tuple)
	{
		$this->expectException (InvalidParameter::class);
		Query::delete ('t', array ('col' => $tuple))->getParsedQuery ();
	}

	public function testInvalidTupleDoesNotEchoTheValue ()
	{
		try {
			Query::select ('t', array (), array ('col' => array ('private-value', Query::PARAM_STR, '<>')));
			$this->fail ('Expected InvalidParameter.');
		} catch (InvalidParameter $e) {
			$this->assertStringContainsString ('col', $e->getMessage ());
			$this->assertStringNotContainsString ('private-value', $e->getMessage ());
		}
	}

	public static function supportedComparators ()
	{
		return array (
			array ('=', "col = 'x'"),
			array ('!=', "col != 'x'"),
			array ('NOT', "col != 'x'"),
			array ('not', "col != 'x'"),
			array ('<', "col < 'x'"),
			array ('>', "col > 'x'"),
			array ('<=', "col <= 'x'"),
			array ('>=', "col >= 'x'"),
			array ('LIKE', "col LIKE 'x'"),
			array ('like', "col LIKE 'x'"),
			array ('Like', "col LIKE 'x'"),
		);
	}

	/**
	 * @dataProvider supportedComparators
	 */
	public function testSupportedComparator ($comparator, $expected)
	{
		$sql = Query::select ('t', array (), array (
			'col' => array ('x', Query::PARAM_STR, $comparator),
		))->getParsedQuery ();

		$this->assertSame ('SELECT * FROM `t` WHERE ' . $expected, $sql);
	}

	public function testInComparatorIsCaseInsensitive ()
	{
		$sql = Query::select ('t', array (), array (
			'col' => array (array (1, 2), Query::PARAM_NUMBER, 'in'),
		))->getParsedQuery ();

		$this->assertSame ('SELECT * FROM `t` WHERE col IN (1,2)', $sql);
	}

	public function testListWithEqualsIsAnInCondition ()
	{
		$sql = Query::select ('t', array (), array (
			'col' => array (array (1, 2), Query::PARAM_NUMBER, '='),
		))->getParsedQuery ();

		$this->assertSame ('SELECT * FROM `t` WHERE col IN (1,2)', $sql);
	}

	public function testListWithoutTypeIsAnInCondition ()
	{
		$sql = Query::select ('t', array (), array (
			'col' => array (array ('a', 'b')),
		))->getParsedQuery ();

		$this->assertSame ("SELECT * FROM `t` WHERE col IN ('a','b')", $sql);
	}

	public function testListMayHoldNullAndAnyKeys ()
	{
		$sql = Query::select ('t', array (), array (
			'col' => array (array (5 => 'a', 'k' => null, 9 => 'b'), Query::PARAM_STR, 'IN'),
		))->getParsedQuery ();

		$this->assertSame ("SELECT * FROM `t` WHERE col IN ('a','','b')", $sql);
	}

	public function testTupleWithOnlyAValue ()
	{
		$sql = Query::select ('t', array (), array ('col' => array ('x')))->getParsedQuery ();

		$this->assertSame ("SELECT * FROM `t` WHERE col = 'x'", $sql);
	}

	public function testDateTimeConditionWithComparator ()
	{
		$sql = Query::select ('t', array (), array (
			'created_at' => array (new \DateTime ('2020-06-15 12:30:00'), Query::PARAM_DATE, '>='),
		))->getParsedQuery ();

		$this->assertSame ("SELECT * FROM `t` WHERE created_at >= '2020-06-15 12:30:00'", $sql);
	}

	// ---------------------------------------------------------------
	// SET tuples: [ value, type, nullOnEmpty ]
	// ---------------------------------------------------------------

	public static function invalidSetTuples ()
	{
		return array (
			'associative array' => array (array ('a' => 'b')),
			'empty array' => array (array ()),
			'four elements' => array (array ('x', Query::PARAM_STR, true, 'y')),
			'keys do not start at 0' => array (array (1 => 'x')),
			'type is not a constant' => array (array ('x', 99)),
			'type is a numeric string' => array (array ('x', '3')),
			'type is null' => array (array ('x', null)),
			'plain list of strings' => array (array ('a', 'b')),
			'null flag is a comparator' => array (array ('x', Query::PARAM_STR, '!=')),
			'null flag is a number' => array (array ('x', Query::PARAM_STR, 1)),
			'value is a list' => array (array (array (1, 2), Query::PARAM_NUMBER)),
			'value is a list without type' => array (array (array ('a'))),
		);
	}

	/**
	 * @dataProvider invalidSetTuples
	 */
	public function testInvalidSetTupleIsRejectedByInsert ($tuple)
	{
		$this->expectException (InvalidParameter::class);
		Query::insert ('t', array ('col' => $tuple))->getParsedQuery ();
	}

	/**
	 * @dataProvider invalidSetTuples
	 */
	public function testInvalidSetTupleIsRejectedByReplace ($tuple)
	{
		$this->expectException (InvalidParameter::class);
		Query::replace ('t', array ('col' => $tuple))->getParsedQuery ();
	}

	/**
	 * @dataProvider invalidSetTuples
	 */
	public function testInvalidSetTupleIsRejectedByUpdate ($tuple)
	{
		$this->expectException (InvalidParameter::class);
		Query::update ('t', array ('col' => $tuple), array ('id' => 1))->getParsedQuery ();
	}

	public function testValidSetTuples ()
	{
		$sql = Query::insert ('t', array (
			'a' => array ('x'),
			'b' => array ('x', Query::PARAM_STR),
			'c' => array (null, Query::PARAM_STR, true),
			'd' => array (null, Query::PARAM_STR, false),
			'e' => array (7, Query::PARAM_NUMBER, true),
			'f' => array (null, Query::PARAM_NULL),
			'g' => array (new \DateTime ('2020-06-15 12:30:00')),
		))->getParsedQuery ();

		$this->assertSame (
			"INSERT INTO `t` SET a = 'x', b = 'x', c = NULL, d = '', e = 7, f = NULL, g = '2020-06-15 12:30:00'",
			$sql
		);
	}

	// ---------------------------------------------------------------
	// Values bound on a written query
	// ---------------------------------------------------------------

	public function testBindValueWithUnknownTypeThrows ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ?");
		$query->bindValue (1, 'x', 99);

		$this->expectException (InvalidParameter::class);
		$query->getParsedQuery ();
	}

	public function testBindValueWithNonIntegerTypeThrows ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ?");
		$query->bindValue (1, 'x', true);

		$this->expectException (InvalidParameter::class);
		$query->getParsedQuery ();
	}

	public function testBindValuesWithBareValueThrows ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ?");
		$query->bindValues (array ('x'));

		$this->expectException (InvalidParameter::class);
		$query->getParsedQuery ();
	}

	public function testBindValuesWithAssociativeTupleThrows ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ?");
		$query->bindValues (array (array ('a' => 'b')));

		$this->expectException (InvalidParameter::class);
		$query->getParsedQuery ();
	}

	public function testBoundListWithNestedListThrows ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a IN ?");
		$query->bindValue (1, array (1, array (2)));

		$this->expectException (InvalidParameter::class);
		$query->getParsedQuery ();
	}

	public function testBoundListStaysSupported ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a IN ? AND b IN ?");
		$query->bindValue (1, array (1, 2), Query::PARAM_NUMBER);
		$query->bindValue (2, array ('x', 3));

		$this->assertSame ("SELECT * FROM `t` WHERE a IN (1,2) AND b IN ('x','3')", $query->getParsedQuery ());
	}

	public function testBindValuesKeepsAcceptingATruthyThirdElement ()
	{
		// On a written query the third element is the "null on empty" flag
		// and has always been read loosely.
		$query = new Query ("SELECT * FROM `t` WHERE a IN ? AND b = ?");
		$query->bindValues (array (
			array (array ('x', 'y'), Query::PARAM_STR, 'IN'),
			array (null, Query::PARAM_STR, 0),
		));

		$this->assertSame ("SELECT * FROM `t` WHERE a IN ('x','y') AND b = ''", $query->getParsedQuery ());
	}

	public function testParamNull ()
	{
		$query = new Query ("UPDATE `t` SET a = ?, b = ?");
		$query->bindValue (1, null, Query::PARAM_NULL);
		$query->bindValue (2, null, Query::PARAM_NULL, true);

		$this->assertSame ("UPDATE `t` SET a = NULL, b = NULL", $query->getParsedQuery ());
	}

	public function testParamNullWithAValueThrows ()
	{
		$query = new Query ("UPDATE `t` SET a = ?");
		$query->bindValue (1, 'x', Query::PARAM_NULL);

		$this->expectException (InvalidParameter::class);
		$query->getParsedQuery ();
	}

	// ---------------------------------------------------------------
	// Untyped values are always quoted
	// ---------------------------------------------------------------

	public function testUntypedIntegerIsQuotedInWhere ()
	{
		$sql = Query::select ('t', array (), array ('token' => 0))->getParsedQuery ();

		$this->assertSame ("SELECT * FROM `t` WHERE token = '0'", $sql);
	}

	public function testUntypedIntegerIsQuotedInSet ()
	{
		$sql = Query::update ('t', array ('a' => 5, 'b' => -3), array ('id' => 12))->getParsedQuery ();

		$this->assertSame ("UPDATE `t` SET a = '5', b = '-3' WHERE id = '12'", $sql);
	}

	public function testUntypedIntegerIsQuotedWhenBound ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ? AND b = :b AND c IN ?");
		$query->bindValue (1, 0);
		$query->bindValue ('b', 15, Query::PARAM_UNKNOWN);
		$query->bindValue (2, array (1, 2));

		$this->assertSame (
			"SELECT * FROM `t` WHERE a = '0' AND b = '15' AND c IN ('1','2')",
			$query->getParsedQuery ()
		);
	}

	public function testTypedNumberStaysUnquoted ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ? AND b = ? AND c = ? AND d = ? LIMIT ?");
		$query->bindValue (1, 0, Query::PARAM_NUMBER);
		$query->bindValue (2, '15', Query::PARAM_NUMBER);
		$query->bindValue (3, -2.5, Query::PARAM_NUMBER);
		$query->bindValue (4, '1e3', Query::PARAM_NUMBER);
		$query->bindValue (5, 10, Query::PARAM_NUMBER);

		$this->assertSame (
			"SELECT * FROM `t` WHERE a = 0 AND b = 15 AND c = -2.5 AND d = 1e3 LIMIT 10",
			$query->getParsedQuery ()
		);
	}

	public static function untypedValues ()
	{
		return array (
			'integer' => array (42, "'42'"),
			'negative integer' => array (-42, "'-42'"),
			'float' => array (1.5, "'1.5'"),
			'whole float' => array (2.0, "'2'"),
			'true' => array (true, "'1'"),
			'false' => array (false, "''"),
			'numeric string' => array ('42', "'42'"),
			'numeric string with a space' => array (' 42', "' 42'"),
			'exponent string' => array ('1e5', "'1e5'"),
			'exponent string with sign' => array ('-1.5E-3', "'-1.5E-3'"),
			'hex-like string' => array ('0x1A', "'0x1A'"),
			'word' => array ('NULL', "'NULL'"),
			'empty string' => array ('', "''"),
		);
	}

	/**
	 * @dataProvider untypedValues
	 */
	public function testUntypedValueIsNeverWrittenBare ($value, $expected)
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ?");
		$query->bindValue (1, $value);
		$this->assertSame ("SELECT * FROM `t` WHERE a = " . $expected, $query->getParsedQuery ());

		$sql = Query::select ('t', array (), array ('a' => $value))->getParsedQuery ();
		$this->assertSame ("SELECT * FROM `t` WHERE a = " . $expected, $sql);

		$sql = Query::insert ('t', array ('a' => $value))->getParsedQuery ();
		$this->assertSame ("INSERT INTO `t` SET a = " . $expected, $sql);
	}

	public function testLargeFloatIsQuotedWhenUntypedAndNumericWhenTyped ()
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ? AND b = ?");
		$query->bindValue (1, 1.0e25);
		$query->bindValue (2, 1.0e25, Query::PARAM_NUMBER);

		$this->assertSame ("SELECT * FROM `t` WHERE a = '1.0E+25' AND b = 1.0E+25", $query->getParsedQuery ());
	}

	public static function nonFiniteNumbers ()
	{
		$out = array ();
		foreach (array ('INF' => INF, '-INF' => -INF, 'NAN' => NAN) as $name => $value) {
			$out[$name . ' untyped'] = array ($value, Query::PARAM_UNKNOWN);
			$out[$name . ' as number'] = array ($value, Query::PARAM_NUMBER);
			$out[$name . ' as string'] = array ($value, Query::PARAM_STR);
			$out[$name . ' as date'] = array ($value, Query::PARAM_DATE);
		}
		return $out;
	}

	/**
	 * @dataProvider nonFiniteNumbers
	 */
	public function testNonFiniteNumberThrows ($value, $type)
	{
		$query = new Query ("SELECT * FROM `t` WHERE a = ?");
		$query->bindValue (1, $value, $type);

		$this->expectException (InvalidParameter::class);
		$query->getParsedQuery ();
	}

	public function testNonFiniteNumberInAListThrows ()
	{
		$this->expectException (InvalidParameter::class);
		Query::select ('t', array (), array ('a' => array (array (1, INF))))->getParsedQuery ();
	}

	public function testNumbersDoNotFollowTheLocale ()
	{
		$previous = setlocale (LC_NUMERIC, '0');
		$locale = setlocale (LC_NUMERIC, array ('nl_NL.UTF-8', 'nl_NL.utf8', 'nl_BE.UTF-8', 'de_DE.UTF-8', 'de_DE.utf8', 'fr_FR.UTF-8'));

		if ($locale === false) {
			$this->markTestSkipped ('No locale with a decimal comma is installed.');
		}

		try {
			$query = new Query ("SELECT * FROM `t` WHERE a = ? AND b = ? AND c = ? AND d = ?");
			$query->bindValue (1, 1.5);
			$query->bindValue (2, 1.5, Query::PARAM_NUMBER);
			$query->bindValue (3, 1.5, Query::PARAM_STR);
			$query->bindValue (4, 1.5, Query::PARAM_DATE);
			$sql = $query->getParsedQuery ();
		} finally {
			setlocale (LC_NUMERIC, $previous);
		}

		$this->assertSame (
			"SELECT * FROM `t` WHERE a = '1.5' AND b = 1.5 AND c = '1.5' AND d = FROM_UNIXTIME(1.5)",
			$sql
		);
	}

	public function testPointCoordinatesMustBeNumbers ()
	{
		$point = new \Neuron\Models\Geo\Point (4.5, 50.5);
		$point->setLatitude ('north');

		$query = new Query ("INSERT INTO `t` SET pos = ?");
		$query->bindValue (1, $point, Query::PARAM_POINT);

		$this->expectException (InvalidParameter::class);
		$query->getParsedQuery ();
	}

	public function testPointIsWrittenWithItsCoordinates ()
	{
		$query = new Query ("INSERT INTO `t` SET pos = ?");
		$query->bindValue (1, new \Neuron\Models\Geo\Point (4.5, '50.25'), Query::PARAM_POINT);

		$this->assertSame ("INSERT INTO `t` SET pos = POINT(4.5,50.25)", $query->getParsedQuery ());
	}

	// ---------------------------------------------------------------
	// A null condition can stand anywhere
	// ---------------------------------------------------------------

	public function testNullConditionFirst ()
	{
		$sql = Query::select ('t', array (), array (
			'deleted_at' => null,
			'name' => 'x',
		))->getParsedQuery ();

		$this->assertSame ("SELECT * FROM `t` WHERE deleted_at IS NULL AND name = 'x'", $sql);
	}

	public function testNullConditionInTheMiddle ()
	{
		$sql = Query::select ('t', array (), array (
			'a' => array (1, Query::PARAM_NUMBER),
			'b' => null,
			'c' => array (null, Query::PARAM_STR),
			'd' => array (null, Query::PARAM_NUMBER, '='),
			'e' => 'x',
			'f' => array (array (1, 2), Query::PARAM_NUMBER),
		))->getParsedQuery ();

		$this->assertSame (
			"SELECT * FROM `t` WHERE a = 1 AND b IS NULL AND c IS NULL AND d IS NULL AND e = 'x' AND f IN (1,2)",
			$sql
		);
	}

	public function testNullConditionInUpdateAndDelete ()
	{
		$update = Query::update (
			't',
			array ('a' => null, 'b' => 'set'),
			array ('c' => null, 'd' => 'where')
		)->getParsedQuery ();

		$this->assertSame ("UPDATE `t` SET a = NULL, b = 'set' WHERE c IS NULL AND d = 'where'", $update);

		$delete = Query::delete ('t', array ('c' => null, 'd' => 'where'))->getParsedQuery ();

		$this->assertStringContainsString ("WHERE c IS NULL AND d = 'where'", $delete);
	}

	public function testNullConditionLastStillWorks ()
	{
		$sql = Query::select ('t', array ('id'), array ('id' => array (1, Query::PARAM_NUMBER), 'deleted_at' => null))
			->getParsedQuery ();

		$this->assertSame ("SELECT id FROM `t` WHERE id = 1 AND deleted_at IS NULL", $sql);
	}

	public function testNullWithAnotherComparatorIsStillBound ()
	{
		$sql = Query::select ('t', array (), array (
			'a' => array (null, Query::PARAM_STR, '!='),
			'b' => 'x',
		))->getParsedQuery ();

		$this->assertSame ("SELECT * FROM `t` WHERE a != NULL AND b = 'x'", $sql);
	}

	// ---------------------------------------------------------------
	// As many placeholders as values
	// ---------------------------------------------------------------

	public function testQuestionMarkInAConditionKeyThrows ()
	{
		$this->expectException (InvalidParameter::class);
		Query::select ('t', array (), array ('a = ? OR b' => 'x'));
	}

	public function testQuestionMarkInASetKeyThrows ()
	{
		$this->expectException (InvalidParameter::class);
		Query::insert ('t', array ('a = ?, b' => 'x'));
	}

	public function testQuestionMarkInUpdateKeysThrows ()
	{
		$this->expectException (InvalidParameter::class);
		Query::update ('t', array ('a' => 'x'), array ('b = ? AND c' => 'y'));
	}

	public function testWrittenQueryIsNotCounted ()
	{
		// A written query may hold a literal question mark after its last
		// placeholder: only the builders are checked.
		$query = new Query ("SELECT * FROM `t` WHERE a = ? AND b = 'really?'");
		$query->bindValue (1, 'x');

		$this->assertSame ("SELECT * FROM `t` WHERE a = 'x' AND b = 'really?'", $query->getParsedQuery ());
	}

	// ---------------------------------------------------------------
	// update() and delete() need a condition
	// ---------------------------------------------------------------

	public function testUpdateWithoutConditionThrows ()
	{
		$this->expectException (InvalidParameter::class);
		Query::update ('t', array ('a' => 'b'), array ());
	}

	public function testDeleteWithoutConditionThrows ()
	{
		$this->expectException (InvalidParameter::class);
		Query::delete ('t', array ());
	}

	public function testSelectWithoutConditionStillSelectsEverything ()
	{
		$sql = Query::select ('t', array ('id'), array ())->getParsedQuery ();

		$this->assertSame ('SELECT id FROM `t` ', $sql);
	}
}
