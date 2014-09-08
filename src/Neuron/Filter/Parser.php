<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 28/04/14
 * Time: 12:17
 */

namespace Neuron\Filter;

use Exception;
use Neuron\Exceptions\InvalidParameter;

class SyntaxError extends Exception {}
class ParseError extends Exception {}
class RuntimeError extends Exception {}

class Parser {

	const OP_FIELD = 'FIELD'; // Any value

	const OP_EQUALS = 'EQUALS';
	const OP_CONTAINS = 'CONTAINS';

	const OP_AND = 'AND';
	const OP_OR = 'OR';

	const OP_NOT = 'NOT';

	const OP_POPEN = '(';
	const OP_PCLOSE = ')';
	const OP_COMMA = ',';

	const OP_FUNCTION = 'FUNCTION';

	const OP_OPERATOR = 'OPERATOR';

	const ST_1 = 1, // waiting for operand or unary sign
		ST_2 = 2; // waiting for operator

	protected $scanner, $state = self::ST_1;
	protected $queue, $stack;

	private $context;

	public function __construct (Scanner $scanner, Context $context = null)
	{
		$this->scanner = $scanner;
		//$this->reset ();

		if (isset ($context))
		{
			$this->setContext ($context);
		}
		else
		{
			$this->setContext (new Context ());
		}
	}

	private function reset ()
	{
		// alloc
		$this->queue = array();
		$this->stack = array();

		foreach ($this->scanner as $v)
		{
			$this->handle ($v);
		}

		// When there are no more tokens to read:
		// While there are still operator tokens in the stack:
		while ($t = array_pop($this->stack)) {
			if ($t->type === self::OP_POPEN || $t->type === self::OP_PCLOSE)
				throw new ParseError('parser error: incorrect nesting of `(` and `)`');

			$this->queue[] = $t;
		}
	}

	public function setContext (Context $context)
	{
		$this->context = $context;
	}

	private function reduce(Context $ctx, $model = null)
	{
		$this->reset ();

		$this->stack = array();
		$len = 0;

		// While there are input tokens left
		// Read the next token from input.
		while ($t = array_shift($this->queue)) {

			//var_dump ($t->type);

			switch ($t->type) {

				case self::OP_FIELD:

					// If the token is a value or identifier
					// Push it onto the stack.
					$this->stack[] = $t;
					++$len;
					break;

				case self::OP_EQUALS:
				case self::OP_CONTAINS:
				case self::OP_AND:
				case self::OP_OR:
				case self::OP_NOT:

					// It is known a priori that the operator takes n arguments.
					$na = $this->argc($t);

					// If there are fewer than n values on the stack
					if ($len < $na)
						throw new RuntimeError('run-time error: too few paramter for operator "' . $t->value . '" (' . $na . ' -> ' . $len . ')');

					$rhs = array_pop($this->stack);
					$lhs = null;

					if ($na > 1)
						$lhs = array_pop($this->stack);

					// Values!
					if ($ctx->hasField ($rhs->value))
					{
						$rhs = new Token (self::OP_FIELD, $ctx->getField ($rhs->value));
					}

					if ($na > 1 && $ctx->hasField ($lhs->value))
					{
						$lhs = new Token (self::OP_FIELD, $ctx->getField ($lhs->value));
					}

					// if ($lhs) print "{$lhs->value} {$t->value} {$rhs->value}\n";
					// else print "{$t->value} {$rhs->value}\n";

					$len -= $na - 1;

					// Push the returned results, if any, back onto the stack.
					$this->stack[] = new Token(self::OP_FIELD, $this->op($t->type, $lhs, $rhs, $model));
					break;

				/*
				case self::OP_FUNCTION:
					// function
					$argc = $t->argc;
					$argv = array();

					$len -= $argc - 1;

					for (; $argc > 0; --$argc)
						array_unshift($argv, array_pop($this->stack)->value);

					// Push the returned results, if any, back onto the stack.
					$this->stack[] = new Token(self::OP_FIELD, $ctx->fn($t->value, $argv));
					break;
				*/

				default:
					throw new RuntimeError('run-time error: unexpected token`' . $t->value . '`');
			}
		}

		// If there is only one value in the stack
		// That value is the result of the calculation.
		if (count($this->stack) === 1)
			return array_pop($this->stack)->value;

		// If there are more values in the stack
		// (Error) The user input has too many values.
		throw new RuntimeError('run-time error: too many values in the stack');
	}

	protected function op($op, $lhs, $rhs, $model)
	{
		if ($lhs !== null) {
			$lhs = $lhs->value;
			$rhs = $rhs->value;

			switch ($op) {
				case self::OP_AND:
					return $lhs && $rhs;
				break;

				case self::OP_OR:
					return $lhs || $rhs;
				break;
			}

			// Not processed yet? This is a comparison!
			// Fields?
			$field = null;
			$argument = null;

			// Both parameters are fields: we will use right one as value
			if ($lhs instanceof Field && $rhs instanceof Field)
			{
				$field = $lhs;
				$argument = $rhs->getValue ($model);
			}

			// The left part is a field
			else if ($lhs instanceof Field)
			{
				$field = $lhs;
				$argument = $rhs;
			}

			// The right part is a field
			else if ($rhs instanceof Field)
			{
				$field = $rhs;
				$argument = $lhs;
			}

			// There is no field, let's create a temporary field (left hand)
			else
			{
				$field = new TemporaryField ();
				$field->setValue ($lhs);

				$argument = $rhs;
			}

			return $field->operate ($op, $model, $argument);
		}

		switch ($op) {
			case self::OP_NOT:
				return !$rhs->value;
			break;
		}

		return 0;
	}

	protected function argc(Token $t)
	{
		switch ($t->type) {
			case self::OP_EQUALS:
			case self::OP_CONTAINS:
			case self::OP_AND:
			case self::OP_OR:
				return 2;
			break;

			case self::OP_NOT:
				return 1;
			break;
		}

		return 1;
	}

	public function dump($str = false)
	{
		if ($str === false) {
			print_r($this->queue);
			return;
		}

		$res = array();

		foreach ($this->queue as $t) {
			$val = $t->value;
			$res[] = $val;
		}

		print implode(' ', $res);
	}

	protected function fargs($fn)
	{
		$this->handle($this->scanner->next()); // '('

		$argc = 0;
		$next = $this->scanner->peek();

		if ($next && $next->type !== self::OP_PCLOSE) {
			$argc = 1;

			while ($t = $this->scanner->next()) {
				$this->handle($t);

				if ($t->type === self::OP_PCLOSE)
					break;

				if ($t->type === self::OP_COMMA)
					++$argc;
			}
		}

		$fn->argc = $argc;
	}

	protected function handle(Token $t)
	{
		switch ($t->type) {
			case self::OP_FIELD:

				// If the token is a number (identifier), then add it to the output queue.
				$this->queue[] = $t;
				$this->state = self::ST_2;
				break;

			case self::OP_FUNCTION:
				// If the token is a function token, then push it onto the stack.
				$this->stack[] = $t;
				$this->fargs($t);
				break;


			case self::OP_COMMA:
				// If the token is a function argument separator (e.g., a comma):

				$pe = false;

				while ($t = end($this->stack)) {
					if ($t->type === self::OP_POPEN) {
						$pe = true;
						break;
					}

					// Until the token at the top of the stack is a left parenthesis,
					// pop operators off the stack onto the output queue.
					$this->queue[] = array_pop($this->stack);
				}

				// If no left parentheses are encountered, either the separator was misplaced
				// or parentheses were mismatched.
				if ($pe !== true)
					throw new ParseError('parser error: missing token `(` or misplaced token `,`');

				break;

			// If the token is an operator, op1, then:
			case self::OP_CONTAINS:
			case self::OP_EQUALS:
			case self::OP_AND:
			case self::OP_OR:
			case self::OP_NOT:

				while (!empty($this->stack)) {
					$s = end($this->stack);

					// While there is an operator token, o2, at the top of the stack
					// op1 is left-associative and its precedence is less than or equal to that of op2,
					// or op1 has precedence less than that of op2,
					// Let + and ^ be right associative.
					// Correct transformation from 1^2+3 is 12^3+
					// The differing operator priority decides pop / push
					// If 2 operators have equal priority then associativity decides.
					switch ($s->type) {
						default: break 2;

						case self::OP_CONTAINS:
						case self::OP_EQUALS:
						case self::OP_AND:
						case self::OP_OR:
						case self::OP_NOT:
							$p1 = $this->preced($t);
							$p2 = $this->preced($s);

							if (!(($this->assoc($t) === 1 && ($p1 <= $p2)) || ($p1 < $p2)))
								break 2;

							// Pop o2 off the stack, onto the output queue;
							$this->queue[] = array_pop($this->stack);
					}
				}

				// push op1 onto the stack.
				$this->stack[] = $t;
				$this->state = self::ST_1;
				break;

			case self::OP_POPEN:
				// If the token is a left parenthesis, then push it onto the stack.
				$this->stack[] = $t;
				$this->state = self::ST_1;
				break;

			// If the token is a right parenthesis:
			case self::OP_PCLOSE:
				$pe = false;

				// Until the token at the top of the stack is a left parenthesis,
				// pop operators off the stack onto the output queue
				while ($t = array_pop($this->stack)) {
					if ($t->type === self::OP_POPEN) {
						// Pop the left parenthesis from the stack, but not onto the output queue.
						$pe = true;
						break;
					}

					$this->queue[] = $t;
				}

				// If the stack runs out without finding a left parenthesis, then there are mismatched parentheses.
				if ($pe !== true)
					throw new ParseError('parser error: unexpected token `)`');

				// If the token at the top of the stack is a function token, pop it onto the output queue.
				if (($t = end($this->stack)) && $t->type === T_FUNCTION)
					$this->queue[] = array_pop($this->stack);

				$this->state = self::ST_2;
				break;

			default:
				throw new ParseError('parser error: unknown token "' . $t->value . '"');
		}
	}

	protected function assoc(Token $t)
	{
		switch ($t->type)
		{
			case self::OP_EQUALS:
			case self::OP_CONTAINS:
			case self::OP_AND:
			case self::OP_OR:
				return 1; //ltr
			break;

			case self::OP_NOT:
				return 2; //rtl
			break;
		}

		// ggf. erweitern :-)
		return 0; //nassoc
	}

	/**
	 * Get priority of the action
	 * @param Token $t
	 * @return int
	 */
	protected function preced(Token $t)
	{
		switch ($t->type) {

			case self::OP_OR:
				return 1;
				break;

			case self::OP_AND:
				return 3;
				break;

			case self::OP_NOT;
				return 5;
				break;

			case self::OP_EQUALS:
			case self::OP_CONTAINS:
				return 7;
			break;
		}

		return 0;
	}

	/**
	 * @param array $objects
	 * @return array
	 */
	public function filter (array $objects)
	{
		$out = array ();
		foreach ($objects as $object)
		{
			if ($this->validate ($object))
			{
				$out[] = $object;
			}
			//return $out;
		}
		return $out;
	}

	/**
	 * Validate a single object
	 * @param $object
	 * @throws \Neuron\Exceptions\InvalidParameter
	 * @return bool
	 */
	public function validate ($object = null)
	{
		if (!isset ($this->context))
		{
			throw new InvalidParameter ("You must set a context before validating or filtering.");
		}

		$result = $this->reduce ($this->context, $object);

		if ($result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @return Token[]
	 */
	public function getPostfix ()
	{
		$this->reset ();
		return $this->queue;
	}
}

