<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 28/04/14
 * Time: 15:06
 */

namespace Neuron\Filter;

use Neuron\Collections\Collection;

class Scanner
	extends Collection
{
	//                  operations          numbers               words                   blank
	//const PATTERN = '/^([=,!,\+\-\*\/\^%\(\)]|\d*\.\d+|\d+\.\d*|\d+|[a-z_A-Zπ]+[a-z_A-Z0-9]*|[ \t]+)/';

	const ERR_EMPTY = 'empty found! (infinite loop) near: `%s`',
		ERR_MATCH = 'syntax error in the vicinity of `%s`';

	protected $lookup = array(
		'=' => Parser::OP_EQUALS,
		'CONTAINS' => Parser::OP_CONTAINS,
		'AND' => Parser::OP_AND,
		'OR' => Parser::OP_OR,
		'NOT' => Parser::OP_NOT,
		'!' => Parser::OP_NOT,
		'(' => Parser::OP_POPEN,
		')' => Parser::OP_PCLOSE
	);

	public function __construct($input)
	{
		$matches = array ();

		/** Many thanks to reko_t from irc.QuakeNet.org #php */
		preg_match_all('/(?|(["\'])((?>\\.|.)*?)\1|()([()=!])|()([a-zA-Z]+)|()(\d+))/', $input, $matches);

		$tokens = array ();
		foreach ($matches[2] as $match)
		{
			$lookupValue = strtoupper ($match);
			$type = isset($this->lookup[$lookupValue]) ? $this->lookup[$lookupValue] : Parser::OP_FIELD;
			$tokens[] = new Token($type, $match);
		}

		$this->setCollectionValues ($tokens);
	}
}