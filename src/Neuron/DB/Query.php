<?php

namespace Neuron\DB;

use DateTime;
use Neuron\Exceptions\InvalidParameter;
use Neuron\Models\Geo\Point;

/**
 * Class Query
 * @package Neuron\DB
 */
class Query
{
	const PARAM_NUMBER = 1;
	const PARAM_DATE = 2;
	const PARAM_STR = 3;
	const PARAM_STRING = 3;
	const PARAM_NULL = 4;
    const PARAM_UNKNOWN = 5;

	const PARAM_POINT = 10;

	private $query;
	private $values = array ();

	/**
	 * @var bool
	 */
	private static $legacyNegationPrefix = false;

	/**
	 * Before 3.3.1 a WHERE value starting with '!' turned the comparison into
	 * `!=` and lost its first character. A value that came from outside the
	 * application could therefore change what a query matched, so the value
	 * is now always compared as it is.
	 *
	 * To negate a comparison, pass the comparator as the third element:
	 * `array($value, Query::PARAM_STR, '!=')`.
	 *
	 * A project that still relies on the prefix can switch it back on here
	 * until its queries are migrated. Do not switch it on where WHERE values
	 * can come from a request.
	 *
	 * @param bool $enabled
	 */
	public static function setLegacyNegationPrefix($enabled)
	{
		self::$legacyNegationPrefix = (bool) $enabled;
	}

	/**
	 * @return bool
	 */
	public static function usesLegacyNegationPrefix()
	{
		return self::$legacyNegationPrefix;
	}

	/**
	 * The comparators a WHERE tuple may name, and what they are written as.
	 * @var string[]
	 */
	private static $comparators = array (
		'=' => '=',
		'!=' => '!=',
		'NOT' => '!=',
		'<' => '<',
		'>' => '>',
		'<=' => '<=',
		'>=' => '>=',
		'LIKE' => 'LIKE',
		'IN' => 'IN'
	);

	/**
	 * Generate an insert query
	 * @param string $table: table to insert data to
	 * @param mixed[] $set: a 2 dimensional array with syntax: { column_name : [ value, type, nullOnEmpty ]}
	 * @return Query
	 * @throws InvalidParameter
	 */
	public static function insert($table, array $set)
	{
		$values = array ();
		$assignments = self::processSet($set, $values);

		return self::build(
			'INSERT INTO ' . self::escapeTableName($table) . ' SET ' . $assignments,
			$assignments,
			$values
		);
	}

	/**
	 * Generate an replace query
	 * @param string $table: table to insert data to
	 * @param mixed[] $set: a 2 dimensional array with syntax: { column_name : [ value, type, nullOnEmpty ]}
	 * @return Query
	 * @throws InvalidParameter
	 */
	public static function replace($table, array $set)
	{
		$values = array ();
		$assignments = self::processSet($set, $values);

		return self::build(
			'REPLACE INTO ' . self::escapeTableName($table) . ' SET ' . $assignments,
			$assignments,
			$values
		);
	}

	/**
	 * Generate an update query
	 * @param $table: table to update
	 * @param $set: a 2 dimensional array with syntax: { column_name : [ value, type, nullOnEmpty ]}
	 * @param $where: a 2 dimensional array with syntax: { column_name : [ value, type, comparator ]}
	 * type, nullOnEmpty and comparator may be omitted.
	 *
	 * $where must hold at least one condition: an update of every row is
	 * written as a query (`new Query('UPDATE ...')`).
	 * @return Query
	 * @throws InvalidParameter
	 */
	public static function update($table, array $set, array $where)
	{
		if (count($where) === 0) {
			throw new InvalidParameter("Query::update on " . $table . " needs at least one condition.");
		}

		$values = array();
		$assignments = self::processSet($set, $values);
		$conditions = self::processWhere($where, $values);

		return self::build(
			'UPDATE ' . self::escapeTableName($table) . ' SET ' . $assignments . ' ' . $conditions,
			$assignments . ' ' . $conditions,
			$values
		);
	}

	/**
	 * Turn { column_name : value } or { column_name : [ value, type, nullOnEmpty ]}
	 * into `column = ?, column = ?` and collect the values.
	 * @param mixed[] $set
	 * @param mixed[] $values
	 * @return string
	 * @throws InvalidParameter
	 */
	private static function processSet(array $set, &$values)
	{
		$assignments = array ();

		foreach ($set as $k => $v) {
			$type = null;
			$nullOnEmpty = true;

			// No array? Then it's a simple value.
			if (is_array($v)) {
				$what = 'The value for column ' . $k;
				self::assertTuple($v, $what);

				if (array_key_exists(1, $v)) {
					$type = self::assertType($v[1], $what);
				}

				if (array_key_exists(2, $v)) {
					if (!is_bool($v[2])) {
						throw new InvalidParameter($what . ' has a "null on empty" flag that is not a boolean.');
					}
					$nullOnEmpty = $v[2];
				}

				$v = $v[0];

				if (is_array($v)) {
					throw new InvalidParameter($what . ' cannot be a list.');
				}
			}

			$assignments[] = $k . ' = ?';
			$values[] = array($v, $type, $nullOnEmpty);
		}

		return implode(', ', $assignments);
	}

    /**
     * Turn { column_name : value } or { column_name : [ value, type, comparator ]}
     * into `WHERE column = ? AND column > ?` and collect the values.
     * @param mixed[] $where
     * @param mixed[] $values
     * @return string
     * @throws InvalidParameter
     */
	private static function processWhere(array $where, &$values)
	{
		$conditions = array ();

		foreach ($where as $k => $v) {
			$type = self::PARAM_UNKNOWN;
			$comparator = '=';

			// No array? Then it's a simple value.
			if (is_array($v)) {
				$what = 'The condition on column ' . $k;
				self::assertTuple($v, $what);

				$type = null;
				if (array_key_exists(1, $v)) {
					$type = self::assertType($v[1], $what);
				}

				if (array_key_exists(2, $v)) {
					// Only the third element selects the comparator, and only
					// from the list above.
					if (!is_string($v[2]) || !isset(self::$comparators[strtoupper($v[2])])) {
						throw new InvalidParameter($what . ' has an unknown comparator.');
					}
					$comparator = self::$comparators[strtoupper($v[2])];
				}

				$v = $v[0];
			}

			// A value is data and never selects the comparator. The legacy
			// '!' value prefix is honoured only when a project has switched
			// it on (see setLegacyNegationPrefix()), and then only for
			// strings.
			if (self::$legacyNegationPrefix && is_string($v) && substr($v, 0, 1) === '!') {
				$comparator = '!=';
				$v = substr($v, 1);
			}

			if (is_array($v)) {
				// A list of values: IN.
				if ($comparator !== 'IN' && $comparator !== '=') {
					throw new InvalidParameter('The condition on column ' . $k . ' compares a list: only IN can do that.');
				}

				self::assertList($v, 'The condition on column ' . $k);

				$conditions[] = $k . ' IN ?';
				$values[] = array($v, $type);
			} elseif ($comparator === 'IN') {
				throw new InvalidParameter('The condition on column ' . $k . ' uses IN and needs a list of values.');
			} elseif ($v === null && $comparator === '=') {
				// No placeholder, so no value either: every value that is
				// collected must have its own placeholder.
				$conditions[] = $k . ' IS NULL';
			} else {
				$conditions[] = $k . ' ' . $comparator . ' ?';
				$values[] = array($v, $type);
			}
		}

		if (count($conditions) === 0) {
			return '';
		}

		return 'WHERE ' . implode(' AND ', $conditions);
	}

	/**
	 * A tuple is a list with 1 to 3 elements: [ value ], [ value, type ] or
	 * [ value, type, third ]. Anything else (an associative array, a longer
	 * list) is not read as one.
	 * @param array $tuple
	 * @param string $what
	 * @throws InvalidParameter
	 */
	private static function assertTuple(array $tuple, $what)
	{
		$count = count($tuple);

		if ($count < 1 || $count > 3 || array_keys($tuple) !== range(0, $count - 1)) {
			throw new InvalidParameter(
				$what . ' is an array that is not a [ value, type, ... ] tuple. '
				. 'Pass a list of values as [ $list, Query::PARAM_..., \'IN\' ].'
			);
		}
	}

	/**
	 * @param mixed $type
	 * @param string $what
	 * @return int
	 * @throws InvalidParameter
	 */
	private static function assertType($type, $what)
	{
		$types = array (
			self::PARAM_NUMBER,
			self::PARAM_DATE,
			self::PARAM_STR,
			self::PARAM_NULL,
			self::PARAM_UNKNOWN,
			self::PARAM_POINT
		);

		if (in_array($type, $types, true)) {
			return $type;
		}

		throw new InvalidParameter($what . ' has a type that is not one of the Query::PARAM_ constants.');
	}

	/**
	 * A list of values (IN) holds scalars and nulls, nothing nested.
	 * @param array $list
	 * @param string $what
	 * @throws InvalidParameter
	 */
	private static function assertList(array $list, $what)
	{
		foreach ($list as $v) {
			if ($v !== null && !is_scalar($v)) {
				throw new InvalidParameter($what . ' has a list that holds something else than plain values.');
			}
		}
	}

	/**
	 * @param string $sql The whole query.
	 * @param string $generated The part of it that was built from $set and $where.
	 * @param mixed[] $values
	 * @return Query
	 * @throws InvalidParameter
	 */
	private static function build($sql, $generated, array $values)
	{
		// Values are bound by position. One placeholder too many or too few
		// (a '?' in a column name, for example) and every value after it
		// would end up in another condition.
		if (substr_count($generated, '?') !== count($values)) {
			throw new InvalidParameter(
				'The query has ' . substr_count($generated, '?') . ' placeholders for '
				. count($values) . ' values: ' . $sql
			);
		}

		$query = new self($sql);
		$query->bindValues($values);

		return $query;
	}

	/**
	 * Select data from a message
	 * @param $table
	 * @param array $data : array of column names [ column1, column2 ]
	 * @param array $where : a 2 dimensional array with syntax: { column_name : [ value, type, comparator ]}
	 * @param array $order
	 * @param null $limit
	 * @return Query
	 * @throws InvalidParameter
	 */
	public static function select (
        $table,
        array $data = array(),
        array $where = array(),
        $order = array(),
        $limit = null
    ) {
		$query = 'SELECT ';
		$values = array ();

		if (count ($data) > 0) {
			foreach ($data as $v) {
				$query .= $v . ', ';
			}
			$query = substr ($query, 0, -2) . ' ';
		} else {
			$query .= '* ';
		}

		$conditions = self::processWhere ($where, $values);

		$query .= 'FROM ' . self::escapeTableName($table) . ' ';
		$query .= $conditions;

		// Order
		if (count ($order) > 0) {
			$query .= " ORDER BY ";
			foreach ($order as $v) {
				$query .= $v . ", ";
			}
			$query = substr ($query, 0, -2);
		}

		// Limit
		if ($limit) {
			$query .= " LIMIT " . $limit;
		}

		return self::build($query, $conditions, $values);
	}

	/**
	 * $where must hold at least one condition: a delete of every row is
	 * written as a query (`new Query('DELETE FROM ...')`).
	 * @param $table
	 * @param array $where
	 * @return Query
	 * @throws InvalidParameter
	 */
	public static function delete($table, array $where)
	{
		if (count($where) === 0) {
			throw new InvalidParameter("Query::delete on " . $table . " needs at least one condition.");
		}

		$values = array();
		$conditions = self::processWhere($where, $values);

		return self::build(
			'DELETE FROM ' . self::escapeTableName($table) . '' . $conditions,
			$conditions,
			$values
		);
	}

	/**
	 * And construct.
	 */
	public function __construct ($query)
	{
		$this->query = $query;
	}

    /**
     * @param $values
     * @return Query
     */
	public function bindValues ($values)
	{
		$this->values = $values;
        return $this;
	}

    /**
     * @param string $index
     * @param mixed $value
     * @param int $type
     * @param bool $canBeNull
     * @return Query
     */
	public function bindValue(
        $index,
        $value,
        $type = self::PARAM_UNKNOWN,
        $canBeNull = false
    ) {
		$this->values[$index] = array ($value, $type, $canBeNull);

		// Chaining
		return $this;
	}

    /**
     * @return string
     * @throws InvalidParameter
     */
	public function getParsedQuery ()
	{
		$keys = array();
		$values = array();

		foreach ($this->values as $k => $v) {
			if (!is_array ($v)) {
				throw new InvalidParameter ("Parameter " . $k . " is not a [ value, type, nullOnEmpty ] tuple in query " . $this->query);
			}

			self::assertTuple ($v, "Parameter " . $k);

			// Column type?
			if (!isset ($v[1])) {
				// Check for known "special types"
				if ($v[0] instanceof Point) {
					$v[1] = self::PARAM_POINT;
				} elseif ($v[0] instanceof DateTime) {
					$v[1] = self::PARAM_DATE;
				} else {
					$v[1] = self::PARAM_UNKNOWN;
				}
			} else {
				self::assertType ($v[1], "Parameter " . $k);
			}

			// NULL on empty?
			if (!isset($v[2])) {
				$v[2] = true;
			}

            // Empty and should set NULL?
            if ($v[2] && $v[0] === null) {
                $value = "NULL";
            } else {
                $value = $this->getValues($k, $v);
            }

			$values[$k] = $value;

			// Replace question marks or tokens?
			if (is_string ($k)) {
				$keys[] = '/:'.$k.'/';
			} else {
				$keys[] = '/[?]/';
			}
		}

		// Put a marker where each value goes, then swap all markers for
		// their values in ONE pass. strtr() never looks at text it has
		// already substituted, so nothing inside a value (not a '?', not a
		// ':name', not marker-like text) can be taken for a placeholder.
		// The markers also carry a random part, so a value cannot contain
		// one in the first place.
		$nonce = bin2hex (random_bytes (8));

		$fakeValues = array ();
		$replacements = array ();
		foreach ($values as $k => $v) {
			$fakeValues[$k] = '{{{ctlb-' . $nonce . '-placeholder-' . $k . '}}}';
			$replacements[$fakeValues[$k]] = (string) $v;
		}

		// And replace
		$query = preg_replace ($keys, $fakeValues, $this->query, 1);

		if (count ($replacements) > 0) {
			$query = strtr ($query, $replacements);
		}

		return $query;
	}

    /**
     * @param mixed $value
     * @param string $type
     * @param string $parameterName
     * @return string
     * @throws InvalidParameter
     */
	private function getValue ($value, $type, $parameterName)
    {
		$db = Database::getInstance ();

		if (is_float ($value) && !is_finite ($value)) {
			throw new InvalidParameter ("Parameter " . $parameterName . " should be a finite number in query " . $this->query);
		}

		switch ($type) {
			case self::PARAM_NUMBER:
				if (!is_numeric ($value)) {
					throw new InvalidParameter ("Parameter " . $parameterName . " should be numeric in query " . $this->query);
				}
				return self::numberToSql ($value);

			case self::PARAM_DATE:

				if ($value instanceof DateTime) {
					return "'" . $value->format ('Y-m-d H:i:s') . "'";
				}

				else if (is_numeric ($value)) {
					return "FROM_UNIXTIME(" . self::numberToSql ($value) . ")";
				}
				else {
					throw new InvalidParameter ("Parameter " . $parameterName . " should be a valid timestamp in query " . $this->query);
				}

			case self::PARAM_POINT:
				if (! ($value instanceof Point))
				{
					throw new InvalidParameter ("Parameter " . $parameterName . " should be a valid \\Neuron\\Models\\Point " . $this->query);
				}
				$longitude = $value->getLongtitude();
				$latitude = $value->getLatitude();

				if (
					!is_numeric ($longitude) || !is_numeric ($latitude)
					|| !is_finite ((float) $longitude) || !is_finite ((float) $latitude)
				) {
					throw new InvalidParameter ("Parameter " . $parameterName . " should be a point with numeric coordinates in query " . $this->query);
				}

				return "POINT(" . self::numberToSql ($longitude) . "," . self::numberToSql ($latitude) .")";

			case self::PARAM_NULL:
				if ($value !== null) {
					throw new InvalidParameter ("Parameter " . $parameterName . " should be null in query " . $this->query);
				}
				return "NULL";

			case self::PARAM_STR:
			case self::PARAM_UNKNOWN:
				// Always a quoted string, whatever PHP type the value has:
				// a bare number next to a string column would make the
				// database compare numbers instead of strings. A quoted
				// number is still read as a number by a numeric column.
				if (is_float ($value)) {
					$value = self::numberToSql ($value);
				}

				return "'" . $db->escape (strval ($value)) . "'";

			default:
				throw new InvalidParameter ("Parameter " . $parameterName . " has an unknown type in query " . $this->query);
		}
	}

	/**
	 * @param int|float|string $value A value that passed is_numeric().
	 * @return string
	 */
	private static function numberToSql ($value)
	{
		// A float is written with the decimal separator of the locale
		// before PHP 8.
		return trim ((string) str_replace (',', '.', (string) $value));
	}

    /**
     * @return Result|int
     */
	public function execute ()
	{
		$db = Database::getInstance();
		$query = $this->getParsedQuery();
		return $db->query($query);
	}

    /**
     * @param $k
     * @param $v
     * @return string
     * @throws InvalidParameter
     */
    private function getValues ($k, $v)
    {
        if (is_array ($v[0])) {
            self::assertList ($v[0], "Parameter " . $k);

            $tmp = array ();

            foreach ($v[0] as $kk => $vv) {
                $tmp[] = $this->getValue($vv, $v[1], $k . '[' . $kk . ']');
            }

            return '(' . implode (',', $tmp) . ')';
        } else {
            return $this->getValue($v[0], $v[1], $k);
        }
    }

    /**
     * @param string $table
     * @return string
     */
    private static function escapeTableName($table)
    {
        return "`$table`";
    }
}