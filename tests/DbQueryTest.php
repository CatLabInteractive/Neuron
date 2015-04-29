<?php


namespace Neuron\Tests;

use PHPUnit_Framework_TestCase;
use Neuron\DB\Query;


class DbQueryTest
	extends PHPUnit_Framework_TestCase
{
	public function testQueryBuilder ()
	{
		// Regular query.
		$query = new Query ("SELECT * FROM `table` WHERE m_date = ? AND m_id = ? AND m_string = ?");
		$query->bindValue (1, gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE);
		$query->bindValue (2, 15, Query::PARAM_NUMBER);
		$query->bindValue (3, "This is a test with ' and \"", Query::PARAM_STR);

		$sql = $query->getParsedQuery ();
		$expected = 'SELECT * FROM `table` WHERE m_date = FROM_UNIXTIME(1379289600) AND m_id = 15 AND m_string = \'This is a test with \\\' and \\"\'';

		$this->assertEquals ($expected, $sql);

		// Regular query with NULL values
		$query = new Query ("SELECT * FROM `table` WHERE m_date = ? AND m_id = ? AND m_string = ?");
		$query->bindValue (1, gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE);
		$query->bindValue (2, 15, Query::PARAM_NUMBER);
		$query->bindValue (3, "", Query::PARAM_STR, true);

		$sql = $query->getParsedQuery ();
		$expected = 'SELECT * FROM `table` WHERE m_date = FROM_UNIXTIME(1379289600) AND m_id = 15 AND m_string = NULL';

		$this->assertEquals ($expected, $sql);
	}

	public function testQueryBuilderSelect ()
	{
		$expected1 = "SELECT column1, column2, column3 FROM `table` WHERE m_id = 1 AND m_date = FROM_UNIXTIME(1379289600) AND m_string = 'Value 1'";
		$expected2 = "SELECT * FROM `table` WHERE m_id = 1 AND m_date = FROM_UNIXTIME(1379289600) AND m_string = 'Value 1'";

		$fields = array ('column1', 'column2', 'column3');
		$where = array 
		(
			'm_id' => array (1, Query::PARAM_NUMBER), 
			'm_date' => array (gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE),
			'm_string' => 'Value 1'
		);

		$query = Query::select ('table', $fields, $where);
		$selectsql = $query->getParsedQuery ();
		$this->assertEquals ($expected1, $selectsql);

		// Select everything.
		$query = Query::select ('table', array (), $where);
		$selectsql = $query->getParsedQuery ();
		$this->assertEquals ($expected2, $selectsql);
	}

	public function testQueryBuilderInsert ()
	{
		// Insert query with only strings
		$query = Query::insert ('table', array ('column1' => 'Value 1', 'column2' => 'Value 2', 'column3' => 'Value \' "'));
		$sql = $query->getParsedQuery ();
		$expected = 'INSERT INTO `table` SET column1 = \'Value 1\', column2 = \'Value 2\', column3 = \'Value \\\' \\"\'';
		$this->assertEquals ($expected, $sql);

		// Insert query with two dimensions, should return the same.
		$query = Query::insert 
		(
			'table', 
			array 
			(
				'column1' => array ('Value 1', Query::PARAM_STR), 
				'column2' => array ('Value 2', Query::PARAM_STR),
				'column3' => array ('Value \' "', Query::PARAM_STR)
			)
		);
		$sql = $query->getParsedQuery ();
		$this->assertEquals ($expected, $sql);

		// And mix it up.
		$query = Query::insert 
		(
			'table', 
			array 
			(
				'column1' => array ('Value 1', Query::PARAM_STR), 
				'column2' => 'Value 2',
				'column3' => array ('Value \' "', Query::PARAM_STR)
			)
		);
		$sql = $query->getParsedQuery ();
		$this->assertEquals ($expected, $sql);
	}

	public function testQueryBuilderUpdate ()
	{
		// And do the same with update
		$expected = 'UPDATE `table` SET column1 = \'Value 1 \\\' \\" \\\\ dum dum dum.\', column2 = 98, column3 = FROM_UNIXTIME(1379289600) WHERE column_string = \'CatLab \\\' \" \\\\ dum dum dum.\' AND column_id = 15 AND column_date = FROM_UNIXTIME(1379289600)';

		$query = Query::update 
		(
			'table', 
			array 
			(
				'column1' => array ('Value 1 \' " \\ dum dum dum.', Query::PARAM_STR), 
				'column2' => array (98, Query::PARAM_NUMBER),
				'column3' => array (gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE)
			),
			array 
			(
				'column_string' => array ('CatLab \' " \\ dum dum dum.', Query::PARAM_STR), 
				'column_id' => array (15, Query::PARAM_NUMBER),
				'column_date' => array (gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE)
			)
		);
		$sql = $query->getParsedQuery ();
		$this->assertEquals ($expected, $sql);

		// Mixed up update
		$query = Query::update 
		(
			'table', 
			array 
			(
				'column1' => 'Value 1 \' " \\ dum dum dum.',
				'column2' => array (98, Query::PARAM_NUMBER),
				'column3' => array (gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE)
			),
			array 
			(
				'column_string' => 'CatLab \' " \\ dum dum dum.', 
				'column_id' => array (15, Query::PARAM_NUMBER),
				'column_date' => array (gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE)
			)
		);
		$sql = $query->getParsedQuery ();
		$this->assertEquals ($expected, $sql);
	}

	public function testQueryOrder ()
	{
		$expected1 = "SELECT column1, column2, column3 FROM `table` WHERE m_id = 1 AND m_date = FROM_UNIXTIME(1379289600) AND m_string = 'Value 1' ORDER BY m_id ASC";

		$fields = array ('column1', 'column2', 'column3');
		$where = array 
		(
			'm_id' => array (1, Query::PARAM_NUMBER), 
			'm_date' => array (gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE),
			'm_string' => 'Value 1'
		);

		$order = array ('m_id ASC');

		$query = Query::select ('table', $fields, $where, $order);
		$selectsql = $query->getParsedQuery ();
		$this->assertEquals ($expected1, $selectsql);
	}

	public function testQueryLimit ()
	{
		$expected1 = "SELECT column1, column2, column3 FROM `table` WHERE m_id = 1 AND m_date = FROM_UNIXTIME(1379289600) AND m_string = 'Value 1' LIMIT 0, 10";

		$fields = array ('column1', 'column2', 'column3');
		$where = array 
		(
			'm_id' => array (1, Query::PARAM_NUMBER), 
			'm_date' => array (gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE),
			'm_string' => 'Value 1'
		);

		$limit = "0, 10";

		$query = Query::select ('table', $fields, $where, array (), $limit);
		$selectsql = $query->getParsedQuery ();
		$this->assertEquals ($expected1, $selectsql);
	}

	public function testQueryOrderAndLimit ()
	{
		$expected1 = "SELECT column1, column2, column3 FROM `table` WHERE m_id = 1 AND m_date = FROM_UNIXTIME(1379289600) AND m_string = 'Value 1' ORDER BY m_id ASC LIMIT 0, 10";

		$fields = array ('column1', 'column2', 'column3');
		$where = array 
		(
			'm_id' => array (1, Query::PARAM_NUMBER), 
			'm_date' => array (gmmktime (0, 0, 0, 9, 16, 2013), Query::PARAM_DATE),
			'm_string' => 'Value 1'
		);

		$limit = "0, 10";
		$order = array ('m_id ASC');

		$query = Query::select ('table', $fields, $where, $order, $limit);
		$selectsql = $query->getParsedQuery ();
		$this->assertEquals ($expected1, $selectsql);
	}

	public function testStupidQuestionmarkReplace ()
	{
		$values = array 
		(
			'm_id' => 1,
			'm_test' => 'test string with a random ? in it.',
			'm_next' => 'another parameter'
		);

		// Insert
		$query = Query::insert ('table', $values);

		$expected = "INSERT INTO `table` SET m_id = '1', m_test = 'test string with a random ? in it.', m_next = 'another parameter'";

		$this->assertEquals ($expected, $query->getParsedQuery ());
	}

	public function testStupidNamedParameterReplace ()
	{
		// Insert
		$query = new Query ("INSERT INTO `table` SET m_id = :m_id, m_test = :m_test, m_next = :m_next");
		
		$query->bindValue ('m_test', 'test string with a random :m_next parameter in it.');
		$query->bindValue ('m_next', 'another parameter');
		$query->bindValue ('m_id', 1);

		$expected = "INSERT INTO `table` SET m_id = '1', m_test = 'test string with a random :m_next parameter in it.', m_next = 'another parameter'";

		$this->assertEquals ($expected, $query->getParsedQuery ());
	}

	public function testStupidMixedNameAndQuestionmarks ()
	{
		// Insert
		$query = new Query ("INSERT INTO `table` SET m_id = :m_id, m_test = ?, m_next = ?");
		
		$query->bindValue (1, 'test string with a random :m_next parameter in it.');
		$query->bindValue (2, 'another parameter');
		$query->bindValue ('m_id', 1);

		$expected = "INSERT INTO `table` SET m_id = '1', m_test = 'test string with a random :m_next parameter in it.', m_next = 'another parameter'";

		$this->assertEquals ($expected, $query->getParsedQuery ());
	}

	public function testStupidMixedNameAndQuestionmarksReversed ()
	{
		// Insert
		$query = new Query ("INSERT INTO `table` SET m_id = ?, m_test = :m_test, m_next = ?");
		
		$query->bindValue (1, 1);
		$query->bindValue ('m_test', 'test string with a random :m_next parameter in it.');
		$query->bindValue (2, 'another parameter');

		$expected = "INSERT INTO `table` SET m_id = '1', m_test = 'test string with a random :m_next parameter in it.', m_next = 'another parameter'";

		$this->assertEquals ($expected, $query->getParsedQuery ());
	}

	public function testStupidZeroBasedQuestionmarks ()
	{
		// Insert
		$query = new Query ("INSERT INTO `table` SET m_id = ?, m_test = ?, m_next = ?");
		
		$query->bindValue (0, 1);
		$query->bindValue (1, 'test string with a random :m_next parameter in it.');
		$query->bindValue (2, 'another parameter');

		$expected = "INSERT INTO `table` SET m_id = '1', m_test = 'test string with a random :m_next parameter in it.', m_next = 'another parameter'";

		$this->assertEquals ($expected, $query->getParsedQuery ());
	}
}