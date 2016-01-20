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
	 * Generate an insert query
	 * @param string $table: table to insert data to
	 * @param mixed[] $set: a 2 dimensional array with syntax: { column_name : [ value, type, nullOnEmpty ]}
	 * @return Query
	 */
	public static function insert($table, array $set)
	{
		$query = 'INSERT INTO ' . self::escapeTableName($table) . ' SET ';
		$values = array ();
		foreach ($set as $k => $v) {
			$query .= $k . ' = ?, ';

			// No array? Then it's a simple string.
			if (is_array ($v)) {
				$values[] = $v;
			} else {
				$values[] = array($v);
			}
		}

		$query = substr($query, 0, -2);

		$query = new self($query);
		$query->bindValues($values);

		return $query;
	}

	/**
	 * Generate an replace query
	 * @param string $table: table to insert data to
	 * @param mixed[] $set: a 2 dimensional array with syntax: { column_name : [ value, type, nullOnEmpty ]}
	 * @return Query
	 */
	public static function replace($table, array $set)
	{
		$query = 'REPLACE INTO ' . self::escapeTableName($table) . ' SET ';
		$values = array ();
		foreach ($set as $k => $v)
		{
			$query .= $k . ' = ?, ';

			// No array? Then it's a simple string.
			if (is_array($v))
			{
				$values[] = $v;
			}
			else
			{
				$values[] = array($v);
			}
		}

		$query = substr($query, 0, -2);

		$query = new self($query);
		$query->bindValues($values);

		return $query;
	}

	/**
	 * Generate an insert query
	 * @param $table: table to insert data to
	 * @param $set: a 2 dimensional array with syntax: { column_name : [ value, type, nullOnEmpty ]}
	 * @param $where: a 2 dimensional array with syntax: { column_name : [ value, type, nullOnEmpty ]}
	 * nullOnEmpty may be omitted.
	 * @return Query
	 */
	public static function update($table, array $set, array $where)
	{
		$query = 'UPDATE ' . self::escapeTableName($table) . ' SET ';
		$values = array();
		foreach ($set as $k => $v) {
			$query .= $k . ' = ?, ';

			// No array? Then it's a simple string.
			if (is_array($v)) {
				$values[] = $v;
			} else {
				$values[] = array($v);
			}
		}

		$query = substr($query, 0, -2) . ' ';
		$query .= self::processWhere($where, $values);

		$query = new self($query);
		$query->bindValues($values);

		return $query;
	}

    /**
     * @param mixed[] $where
     * @param mixed[] $values
     * @return string
     */
	private static function processWhere(array $where, &$values)
	{
		$query = '';

		if (count($where) > 0) {
			$query .= 'WHERE ';
			foreach ($where as $k => $v) {
				// No array? Then it's a simple string.
				if (is_array($v)) {
					$tmp = $v;
				} else {
					$tmp = array($v, self::PARAM_UNKNOWN);
				}

                // Parse comparators
				if (!is_array($tmp[0]) && substr($tmp[0], 0, 1) === '!') {
					$query .= $k . ' != ? AND ';
					$tmp[0] = substr($tmp[0], 1);
				} elseif (isset($tmp[2]) && strtoupper($tmp[2]) === 'LIKE') {
					$query .= $k . ' LIKE ? AND ';
					$tmp = array($tmp[0], $tmp[1]);
				} elseif (isset ($tmp[2]) && strtoupper($tmp[2]) === 'NOT') {
					$query .= $k . ' != ? AND ';
					$tmp = array($tmp[0], $tmp[1]);
				} elseif (
                    isset ($tmp[2])
					&& (
						strtoupper ($tmp[2]) === '>'
						|| strtoupper ($tmp[2]) === '<'
						|| strtoupper ($tmp[2]) === '>='
						|| strtoupper ($tmp[2]) === '<='
						|| strtoupper ($tmp[2]) === '!='
					)
				) {
					$query .= $k . ' ' . $tmp[2] . ' ? AND ';
					$tmp = array($tmp[0], $tmp[1]);
				} elseif (isset($tmp[2]) && strtoupper($tmp[2]) == 'IN') {
					$query .= $k . ' ' . $tmp[2] . ' ? AND ';
					$tmp = array ($tmp[0], $tmp[1]);
				} elseif (is_array($tmp[0])) {
					$query .= $k . ' IN ? AND ';
				} elseif ($tmp[0] === null) {
                    $query .= $k . ' IS NULL AND ';
                } else {
					$query .= $k . ' = ? AND ';
				}

				$values[] = $tmp;
			}

			$query = substr($query, 0, -5);
		}

		return $query;
	}

	/**
	 * Select data from a message
	 * @param $table
	 * @param array $data : array of column names [ column1, column2 ]
	 * @param array $where : a 2 dimensional array with syntax: { column_name : [ value, type, nullOnEmpty ]}
	 * @param array $order
	 * @param null $limit
	 * @return Query
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

		$query .= 'FROM ' . self::escapeTableName($table) . ' ';
		$query .= self::processWhere ($where, $values);

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

		$query = new self($query);
		$query->bindValues($values);

		return $query;
	}

	/**
	 * @param $table
	 * @param array $where
	 * @return Query|string
	 */
	public static function delete($table, array $where)
	{
		$query = 'DELETE FROM ' . self::escapeTableName($table) . '';

		$values = array();
		$query .= self::processWhere($where, $values);

		$query = new self($query);
		$query->bindValues($values);

		return $query;
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
     */
	public function getParsedQuery ()
	{
		$keys = array();
		$values = array();

		foreach ($this->values as $k => $v) {
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

		// First we make a list with placeholders which we will later repalce with values
		$fakeValues = array ();
		foreach ($values as $k => $v) {
			$fakeValues[$k] = '{{{ctlb-custom-placeholder-' . $k . '}}}';
		}

		// And replace
		$query = preg_replace ($keys, $fakeValues, $this->query, 1);

		// And now replace the tokens with the actual values
		foreach ($values as $k => $v) {
			$query = str_replace ($fakeValues[$k], $v, $query);
		}

		return $query;
	}

    /**
     * @param mixed $value
     * @param string $type
     * @param string $parameterName
     * @return int|string
     * @throws InvalidParameter
     */
	private function getValue ($value, $type, $parameterName)
    {
		$db = Database::getInstance ();

		switch ($type) {
			case self::PARAM_NUMBER:
				if (!is_numeric ($value)) {
					throw new InvalidParameter ("Parameter " . $parameterName . " should be numeric in query " . $this->query);
				}
				return (string)str_replace (',', '.', $value);

			case self::PARAM_DATE:

				if ($value instanceof DateTime) {
					return "'" . $value->format ('Y-m-d H:i:s') . "'";
				}

				else if (is_numeric ($value)) {
					return "FROM_UNIXTIME(" . $value . ")";
				}
				else {
					throw new InvalidParameter ("Parameter " . $parameterName . " should be a valid timestamp in query " . $this->query);
				}

			case self::PARAM_POINT:
				if (! ($value instanceof Point))
				{
					throw new InvalidParameter ("Parameter " . $parameterName . " should be a valid \\Neuron\\Models\\Point " . $this->query);
				}
				return $value = "POINT(" . $value->getLongtitude() . "," . $value->getLatitude() .")";

			case self::PARAM_STR:
                if (is_numeric ($value)) {
					$value = (string)str_replace (',', '.', $value);
				}

				return "'" . $db->escape (strval ($value)) . "'";

            case self::PARAM_UNKNOWN:
                if (is_int($value)) {
                    return intval($value);
                } elseif (is_numeric ($value)) {
                    $value = (string)str_replace (',', '.', $value);
                }
                return "'" . $db->escape (strval ($value)) . "'";

		}
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