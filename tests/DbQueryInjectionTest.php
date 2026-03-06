<?php

namespace Neuron\Tests;

use DateTime;
use Neuron\DB\Database;
use Neuron\DB\Query;
use Neuron\Exceptions\InvalidParameter;
use Neuron\Models\Geo\Point;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for Neuron\DB\Query covering SQL injection defences,
 * all parameter types, all query builders, and edge cases.
 * Runs without a real MySQL connection by injecting a TestDatabase stub.
 */
class DbQueryInjectionTest extends TestCase
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
// PARAM_STR — SQL injection through string parameters
// ---------------------------------------------------------------

public function testStringParamEscapesSingleQuote ()
{
$query = new Query ("SELECT * FROM `users` WHERE name = ?");
$query->bindValue (1, "O'Reilly", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

// Single quote must be escaped as \'
$this->assertStringContainsString ("\\'", $sql);
// The value is wrapped in outer single quotes
$this->assertStringContainsString ("name = '", $sql);
}

public function testStringParamEscapesDoubleQuote ()
{
$query = new Query ("SELECT * FROM `users` WHERE name = ?");
$query->bindValue (1, 'Say "hello"', Query::PARAM_STR);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ('\\"', $sql);
}

public function testStringParamEscapesBackslash ()
{
$query = new Query ("SELECT * FROM `users` WHERE path = ?");
$query->bindValue (1, 'C:\\Users\\test', Query::PARAM_STR);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ('\\\\', $sql);
}

public function testStringParamEscapesNewline ()
{
$query = new Query ("SELECT * FROM `users` WHERE note = ?");
$query->bindValue (1, "line1\nline2", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ('\\n', $sql);
}

public function testStringParamEscapesNullByte ()
{
$query = new Query ("SELECT * FROM `users` WHERE name = ?");
$query->bindValue (1, "name\x00injected", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ('\\0', $sql);
}

public function testClassicOrInjectionInString ()
{
$query = new Query ("SELECT * FROM `users` WHERE username = ?");
$query->bindValue (1, "' OR '1'='1", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

// The dangerous ' is escaped to \' preventing context break-out
$this->assertStringContainsString ("\\'", $sql);
// Verify the whole value is inside outer quotes
$this->assertStringContainsString ("username = '", $sql);
}

public function testDropTableInjectionInString ()
{
$query = new Query ("INSERT INTO `log` SET message = ?");
$query->bindValue (1, "'; DROP TABLE users;--", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

// The leading ' is escaped so the payload cannot break out of the string context
$this->assertStringContainsString ("\\'", $sql);
$this->assertStringContainsString ("message = '", $sql);
}

public function testUnionSelectInjectionInString ()
{
$query = new Query ("SELECT * FROM `products` WHERE name = ?");
$query->bindValue (1, "' UNION SELECT username, password FROM users--", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

// The single quote is escaped; the UNION cannot be executed as SQL
$this->assertStringContainsString ("\\'", $sql);
}

public function testSleepInjectionInString ()
{
$query = new Query ("SELECT * FROM `users` WHERE id = ?");
$query->bindValue (1, "1' AND SLEEP(5)--", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

// The single quote is escaped
$this->assertStringContainsString ("\\'", $sql);
$this->assertStringContainsString ("id = '", $sql);
}

public function testStackedQueryInjection ()
{
$query = new Query ("SELECT * FROM `users` WHERE name = ?");
$query->bindValue (1, "admin'; SELECT * FROM secrets;--", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

// Quote escaped; second statement cannot execute
$this->assertStringContainsString ("\\'", $sql);
}

public function testCommentBasedInjectionInString ()
{
$query = new Query ("SELECT * FROM `users` WHERE name = ?");
$query->bindValue (1, "admin'--", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

// Single quote must be escaped
$this->assertStringContainsString ("admin\\'--", $sql);
}

public function testBlindInjectionInString ()
{
$query = new Query ("SELECT * FROM `users` WHERE name = ?");
$query->bindValue (1, "' AND 1=1--", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

// Single quote escaped
$this->assertStringContainsString ("\\'", $sql);
}

public function testNumericValueInStringParam ()
{
$query = new Query ("SELECT * FROM `t` WHERE col = ?");
$query->bindValue (1, 42, Query::PARAM_STR);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("'42'", $sql);
}

public function testFloatValueInStringParam ()
{
$query = new Query ("SELECT * FROM `t` WHERE col = ?");
$query->bindValue (1, 3.14, Query::PARAM_STR);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("'3.14'", $sql);
}

// ---------------------------------------------------------------
// PARAM_UNKNOWN — automatic type detection
// ---------------------------------------------------------------

public function testUnknownParamIntIsNotQuoted ()
{
$query = new Query ("SELECT * FROM `t` WHERE id = ?");
$query->bindValue (1, 5, Query::PARAM_UNKNOWN);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("id = 5", $sql);
$this->assertStringNotContainsString ("id = '5'", $sql);
}

public function testUnknownParamStringInjection ()
{
$query = new Query ("SELECT * FROM `t` WHERE name = ?");
$query->bindValue (1, "' OR '1'='1", Query::PARAM_UNKNOWN);
$sql = $query->getParsedQuery ();

// Single quote escaped
$this->assertStringContainsString ("\\'", $sql);
}

public function testUnknownParamCommaStringNotModified ()
{
// Comma-format "3,14" is not is_numeric(), treated as string
$query = new Query ("SELECT * FROM `t` WHERE val = ?");
$query->bindValue (1, "3,14", Query::PARAM_UNKNOWN);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("'3,14'", $sql);
}

// ---------------------------------------------------------------
// PARAM_NUMBER — numeric parameter protection
// ---------------------------------------------------------------

public function testNumberParamValidInt ()
{
$query = new Query ("SELECT * FROM `t` WHERE id = ?");
$query->bindValue (1, 42, Query::PARAM_NUMBER);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("id = 42", $sql);
}

public function testNumberParamValidFloat ()
{
$query = new Query ("SELECT * FROM `t` WHERE price = ?");
$query->bindValue (1, 9.99, Query::PARAM_NUMBER);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("price = 9.99", $sql);
}

public function testNumberParamThrowsOnNonNumericString ()
{
$this->expectException (InvalidParameter::class);

$query = new Query ("SELECT * FROM `t` WHERE id = ?");
$query->bindValue (1, "'; DROP TABLE users;--", Query::PARAM_NUMBER);
$query->getParsedQuery ();
}

public function testNumberParamThrowsOnInjectionString ()
{
$this->expectException (InvalidParameter::class);

$query = new Query ("SELECT * FROM `t` WHERE id = ?");
$query->bindValue (1, "1 OR 1=1", Query::PARAM_NUMBER);
$query->getParsedQuery ();
}

public function testNumberParamThrowsOnUnionSelect ()
{
$this->expectException (InvalidParameter::class);

$query = new Query ("SELECT * FROM `t` WHERE id = ?");
$query->bindValue (1, "1 UNION SELECT password FROM users", Query::PARAM_NUMBER);
$query->getParsedQuery ();
}

public function testNumberParamThrowsOnAlphaString ()
{
$this->expectException (InvalidParameter::class);

$query = new Query ("SELECT * FROM `t` WHERE id = ?");
$query->bindValue (1, "admin", Query::PARAM_NUMBER);
$query->getParsedQuery ();
}

// ---------------------------------------------------------------
// PARAM_DATE
// ---------------------------------------------------------------

public function testDateParamTimestamp ()
{
$ts = gmmktime (0, 0, 0, 6, 15, 2020);

$query = new Query ("SELECT * FROM `t` WHERE created = ?");
$query->bindValue (1, $ts, Query::PARAM_DATE);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("FROM_UNIXTIME($ts)", $sql);
}

public function testDateParamDateTimeObject ()
{
$dt = new DateTime ('2020-06-15 12:30:00');

$query = new Query ("SELECT * FROM `t` WHERE created = ?");
$query->bindValue (1, $dt, Query::PARAM_DATE);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("'2020-06-15 12:30:00'", $sql);
}

public function testDateParamThrowsOnInjectionString ()
{
$this->expectException (InvalidParameter::class);

$query = new Query ("SELECT * FROM `t` WHERE created = ?");
$query->bindValue (1, "' OR '1'='1", Query::PARAM_DATE);
$query->getParsedQuery ();
}

public function testDateParamThrowsOnNonNumericString ()
{
$this->expectException (InvalidParameter::class);

$query = new Query ("SELECT * FROM `t` WHERE created = ?");
$query->bindValue (1, "not-a-date", Query::PARAM_DATE);
$query->getParsedQuery ();
}

// ---------------------------------------------------------------
// PARAM_POINT
// ---------------------------------------------------------------

public function testPointParam ()
{
$point = new Point (4.3517, 50.8503);

$query = new Query ("INSERT INTO `locations` SET pos = ?");
$query->bindValue (1, $point, Query::PARAM_POINT);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("POINT(4.3517,50.8503)", $sql);
}

public function testPointParamThrowsOnNonPoint ()
{
$this->expectException (InvalidParameter::class);

$query = new Query ("INSERT INTO `locations` SET pos = ?");
$query->bindValue (1, "POINT(0,0) INJECTION", Query::PARAM_POINT);
$query->getParsedQuery ();
}

public function testPointConstructorRejectsNonNumeric ()
{
$this->expectException (InvalidParameter::class);
new Point ("x", "y");
}

// ---------------------------------------------------------------
// NULL handling
// ---------------------------------------------------------------

public function testNullValueWithCanBeNullTrue ()
{
// null value + canBeNull=true → should produce NULL in SQL
$query = new Query ("UPDATE `t` SET col = ?");
$query->bindValue (1, null, Query::PARAM_STR, true);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("col = NULL", $sql);
}

public function testNullValueWithCanBeNullFalse ()
{
// null value + canBeNull=false (default) → treated as empty string
$query = new Query ("UPDATE `t` SET col = ?");
$query->bindValue (1, null, Query::PARAM_STR, false);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("col = ''", $sql);
}

public function testNullInWhereProducesIsNull ()
{
$query = Query::select ('users', array ('id'), array ('deleted_at' => null));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("deleted_at IS NULL", $sql);
}

public function testNullViaStaticBuilderProducesNull ()
{
// Via static builder, no explicit type → $v[2] not set → defaults to true
// in getParsedQuery → null produces NULL
$query = Query::insert ('t', array ('col' => null));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("col = NULL", $sql);
}

// ---------------------------------------------------------------
// Auto-detection of DateTime and Point (via bindValues without type)
// ---------------------------------------------------------------

public function testAutoDetectDateTimeViaBindValues ()
{
$dt = new DateTime ('2023-01-15 08:00:00');

$query = new Query ("INSERT INTO `t` SET created = ?");
$query->bindValues (array (array ($dt)));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("'2023-01-15 08:00:00'", $sql);
}

public function testAutoDetectPointViaBindValues ()
{
$point = new Point (10.5, 20.3);

$query = new Query ("INSERT INTO `t` SET pos = ?");
$query->bindValues (array (array ($point)));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("POINT(10.5,20.3)", $sql);
}

// ---------------------------------------------------------------
// Array (IN clause) values
// ---------------------------------------------------------------

public function testArrayValueForInClause ()
{
$query = Query::select ('users', array ('id', 'name'), array (
'id' => array (array (1, 2, 3), Query::PARAM_NUMBER),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("(1,2,3)", $sql);
}

public function testArrayStringValuesEscaped ()
{
$query = Query::select ('users', array ('name'), array (
'name' => array (array ("admin", "' OR '1'='1"), Query::PARAM_STR),
));
$sql = $query->getParsedQuery ();

// The dangerous single quote in the injection payload must be escaped
$this->assertStringContainsString ("\\'", $sql);
}

// ---------------------------------------------------------------
// WHERE comparators
// ---------------------------------------------------------------

public function testWhereNotEqualsPrefix ()
{
$query = Query::select ('t', array (), array (
'status' => array ('!active', Query::PARAM_STR),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("status != ", $sql);
$this->assertStringContainsString ("'active'", $sql);
}

public function testWhereLike ()
{
$query = Query::select ('t', array (), array (
'name' => array ('%test%', Query::PARAM_STR, 'LIKE'),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("name LIKE ", $sql);
$this->assertStringContainsString ("'%test%'", $sql);
}

public function testWhereNot ()
{
$query = Query::select ('t', array (), array (
'type' => array ('admin', Query::PARAM_STR, 'NOT'),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("type != ", $sql);
}

public function testWhereGreaterThan ()
{
$query = Query::select ('t', array (), array (
'age' => array (18, Query::PARAM_NUMBER, '>'),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("age > ", $sql);
$this->assertStringContainsString ("18", $sql);
}

public function testWhereLessThan ()
{
$query = Query::select ('t', array (), array (
'age' => array (65, Query::PARAM_NUMBER, '<'),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("age < 65", $sql);
}

public function testWhereGreaterOrEqual ()
{
$query = Query::select ('t', array (), array (
'score' => array (100, Query::PARAM_NUMBER, '>='),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("score >= 100", $sql);
}

public function testWhereLessOrEqual ()
{
$query = Query::select ('t', array (), array (
'score' => array (50, Query::PARAM_NUMBER, '<='),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("score <= 50", $sql);
}

public function testWhereNotEqualsOperator ()
{
$query = Query::select ('t', array (), array (
'status' => array (0, Query::PARAM_NUMBER, '!='),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("status != 0", $sql);
}

public function testWhereInOperator ()
{
$query = Query::select ('t', array (), array (
'id' => array (array (1, 2, 3), Query::PARAM_NUMBER, 'IN'),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("id IN ", $sql);
$this->assertStringContainsString ("(1,2,3)", $sql);
}

public function testWhereArrayImplicitIn ()
{
$query = Query::select ('t', array (), array (
'id' => array (array (5, 10, 15), Query::PARAM_NUMBER),
));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("id IN ", $sql);
}

// ---------------------------------------------------------------
// SELECT builder
// ---------------------------------------------------------------

public function testSelectAllColumns ()
{
$query = Query::select ('users', array (), array ('active' => array (1, Query::PARAM_NUMBER)));
$sql = $query->getParsedQuery ();

$this->assertStringStartsWith ("SELECT *", $sql);
$this->assertStringContainsString ("FROM `users`", $sql);
$this->assertStringContainsString ("WHERE active = 1", $sql);
}

public function testSelectSpecificColumns ()
{
$query = Query::select ('users', array ('id', 'email'), array ());
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("SELECT id, email", $sql);
$this->assertStringContainsString ("FROM `users`", $sql);
}

public function testSelectNoWhere ()
{
$query = Query::select ('users', array ('id'));
$sql = $query->getParsedQuery ();

$this->assertStringNotContainsString ("WHERE", $sql);
}

public function testSelectWithOrder ()
{
$query = Query::select ('users', array ('id'), array (), array ('name ASC', 'created DESC'));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("ORDER BY name ASC, created DESC", $sql);
}

public function testSelectWithLimit ()
{
$query = Query::select ('users', array ('id'), array (), array (), '10, 20');
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("LIMIT 10, 20", $sql);
}

public function testSelectWithOrderAndLimit ()
{
$query = Query::select ('users', array ('id'), array (), array ('id ASC'), '0, 5');
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("ORDER BY id ASC", $sql);
$this->assertStringContainsString ("LIMIT 0, 5", $sql);
}

// ---------------------------------------------------------------
// INSERT builder
// ---------------------------------------------------------------

public function testInsertBasic ()
{
$query = Query::insert ('users', array (
'name' => 'Alice',
'age'  => array (30, Query::PARAM_NUMBER),
));
$sql = $query->getParsedQuery ();

$this->assertStringStartsWith ("INSERT INTO `users`", $sql);
$this->assertStringContainsString ("name = 'Alice'", $sql);
$this->assertStringContainsString ("age = 30", $sql);
}

public function testInsertEscapesSingleQuote ()
{
$query = Query::insert ('users', array ('name' => "O'Brien"));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("\\'", $sql);
}

public function testInsertWithInjectionPayload ()
{
$query = Query::insert ('log', array ('msg' => "'; DROP TABLE users;--"));
$sql = $query->getParsedQuery ();

// Single quote is escaped — the payload cannot break out of the string context
$this->assertStringContainsString ("\\'", $sql);
$this->assertStringContainsString ("msg = '", $sql);
}

public function testInsertWithNullValue ()
{
$query = Query::insert ('users', array ('id' => 1, 'bio' => null));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("bio = NULL", $sql);
}

// ---------------------------------------------------------------
// REPLACE builder
// ---------------------------------------------------------------

public function testReplaceBasic ()
{
$query = Query::replace ('users', array (
'id'   => array (1, Query::PARAM_NUMBER),
'name' => 'Bob',
));
$sql = $query->getParsedQuery ();

$this->assertStringStartsWith ("REPLACE INTO `users`", $sql);
$this->assertStringContainsString ("id = 1", $sql);
$this->assertStringContainsString ("name = 'Bob'", $sql);
}

public function testReplaceWithInjectionPayload ()
{
$query = Query::replace ('users', array ('name' => "'; DROP TABLE users;--"));
$sql = $query->getParsedQuery ();

// Single quote is escaped
$this->assertStringContainsString ("\\'", $sql);
}

// ---------------------------------------------------------------
// UPDATE builder
// ---------------------------------------------------------------

public function testUpdateBasic ()
{
$query = Query::update (
'users',
array ('name' => 'Alice'),
array ('id'   => array (1, Query::PARAM_NUMBER))
);
$sql = $query->getParsedQuery ();

$this->assertStringStartsWith ("UPDATE `users`", $sql);
$this->assertStringContainsString ("SET name = 'Alice'", $sql);
$this->assertStringContainsString ("WHERE id = 1", $sql);
}

public function testUpdateWithInjectionInSet ()
{
$query = Query::update (
'users',
array ('bio' => "'; DROP TABLE secrets;--"),
array ('id'  => array (42, Query::PARAM_NUMBER))
);
$sql = $query->getParsedQuery ();

// Single quote escaped in the SET clause
$this->assertStringContainsString ("\\'", $sql);
}

public function testUpdateWithInjectionInWhere ()
{
$query = Query::update (
'users',
array ('bio' => 'safe value'),
array ('name' => "' OR '1'='1")
);
$sql = $query->getParsedQuery ();

// Single quote in WHERE value must be escaped
$this->assertStringContainsString ("\\'", $sql);
}

// ---------------------------------------------------------------
// DELETE builder
// ---------------------------------------------------------------

public function testDeleteBasic ()
{
$query = Query::delete ('users', array ('id' => array (5, Query::PARAM_NUMBER)));
$sql = $query->getParsedQuery ();

$this->assertStringStartsWith ("DELETE FROM `users`", $sql);
$this->assertStringContainsString ("WHERE id = 5", $sql);
}

public function testDeleteWithInjectionInWhere ()
{
$query = Query::delete ('users', array ('name' => "' OR '1'='1"));
$sql = $query->getParsedQuery ();

// Single quote escaped
$this->assertStringContainsString ("\\'", $sql);
}

public function testDeleteNoWhere ()
{
$query = Query::delete ('t', array ());
$sql = $query->getParsedQuery ();

$this->assertStringStartsWith ("DELETE FROM `t`", $sql);
$this->assertStringNotContainsString ("WHERE", $sql);
}

// ---------------------------------------------------------------
// Named parameters injection
// ---------------------------------------------------------------

public function testNamedParamInjection ()
{
$query = new Query ("SELECT * FROM `users` WHERE name = :name AND role = :role");
$query->bindValue ('name', "' OR '1'='1");
$query->bindValue ('role', 'admin');
$sql = $query->getParsedQuery ();

// Single quote in named param must be escaped
$this->assertStringContainsString ("\\'", $sql);
$this->assertStringContainsString ("role = 'admin'", $sql);
}

public function testNamedParamDoesNotReplaceItselfInValue ()
{
$query = new Query ("INSERT INTO `t` SET a = :a, b = :b");
$query->bindValue ('a', 'value with :b placeholder');
$query->bindValue ('b', 'real b');
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("a = 'value with :b placeholder'", $sql);
$this->assertStringContainsString ("b = 'real b'", $sql);
}

// ---------------------------------------------------------------
// Positional parameters with injection
// ---------------------------------------------------------------

public function testPositionalParamsWithInjection ()
{
$query = new Query ("SELECT * FROM `t` WHERE a = ? AND b = ?");
$query->bindValue (1, "' OR 1=1--");
$query->bindValue (2, "'; DROP TABLE t;--");
$sql = $query->getParsedQuery ();

// Both single quotes must be escaped
$this->assertStringContainsString ("\\'", $sql);
}

public function testQuestionMarkInValueDoesNotBreakParsing ()
{
$query = new Query ("INSERT INTO `t` SET msg = ?, other = ?");
$query->bindValue (1, "Is this a question?");
$query->bindValue (2, "yes");
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("msg = 'Is this a question?'", $sql);
$this->assertStringContainsString ("other = 'yes'", $sql);
}

// ---------------------------------------------------------------
// bindValue chaining
// ---------------------------------------------------------------

public function testBindValueChaining ()
{
$query = (new Query ("SELECT * FROM `t` WHERE a = ? AND b = ?"))
->bindValue (1, 'foo')
->bindValue (2, 'bar');
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ("a = 'foo'", $sql);
$this->assertStringContainsString ("b = 'bar'", $sql);
}

// ---------------------------------------------------------------
// Table name escaping
// ---------------------------------------------------------------

public function testTableNameIsBacktickEscaped ()
{
$query = Query::select ('my_table', array ('id'));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ('`my_table`', $sql);
}

public function testInsertTableNameIsBacktickEscaped ()
{
$query = Query::insert ('log_entries', array ('msg' => 'test'));
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ('`log_entries`', $sql);
}

// ---------------------------------------------------------------
// Edge cases with special characters
// ---------------------------------------------------------------

public function testCarriageReturnEscaped ()
{
$query = new Query ("INSERT INTO `t` SET data = ?");
$query->bindValue (1, "line1\rline2", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ('\\r', $sql);
}

public function testSubstituteCharacterEscaped ()
{
$query = new Query ("INSERT INTO `t` SET data = ?");
$query->bindValue (1, "data\x1amore", Query::PARAM_STR);
$sql = $query->getParsedQuery ();

$this->assertStringContainsString ('\\Z', $sql);
}

public function testMultipleInjectionVectorsInSingleQuery ()
{
$query = Query::insert ('audit_log', array (
'user'    => "' OR '1'='1",
'action'  => "'; DROP TABLE audit_log;--",
'payload' => "' UNION SELECT password FROM users--",
));
$sql = $query->getParsedQuery ();

// All dangerous single quotes must be escaped
$this->assertGreaterThanOrEqual (3, substr_count ($sql, "\\'"));
}
}
