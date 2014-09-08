<?php


namespace Neuron\Tests;

use PHPUnit_Framework_TestCase;


class SyntaxTest 
	extends PHPUnit_Framework_TestCase
{
	private function isPHP ($filename)
	{
		return substr ($filename, -3) == 'php';
	}

	private function isValidPHP ($filename)
	{
		$output = shell_exec ('php -l ' . $filename);

		$value = 'No syntax errors detected in';
		return substr ($output, 0, strlen ($value)) == $value;
	}

	protected function scanDirectory ($directory)
	{
		if (substr ($directory, -5) == "Tests")
		{
			return;
		}

		foreach (scandir ($directory) as $v)
		{
			if ($v != '.' && $v != '..' && is_dir ($directory . '/' . $v))
			{
				$this->scanDirectory ($directory . '/' . $v);
			}

			elseif (is_file ($directory . '/' . $v))
			{
				// Check the file
				$result = $this->isValidPHP ($directory .'/' . $v);

				$this->assertTrue ($result);
				if (!$result)
				{
					echo "Syntax error found in " . $directory . '/' . $v . "\n\n\n";
				}
			}
		}
	}

	public function testCodeSyntax ()
	{
		$this->scanDirectory (dirname (dirname (__FILE__)));
	}
}