<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/08/14
 * Time: 17:21
 */

namespace Neuron\Net\Outputs;


use Neuron\Net\Response;

class Table extends HTML {

	public function __construct ()
	{
		
	}

	private function printTable ($data, $var_dump = true)
	{
		if (!headers_sent ()) {
			header ('Content-type: text/html; charset=utf-8');
		}

		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf8" />';
		echo '</head>';
		echo '<body>';

		echo '<style type="text/css">';
		echo '.api-data-table * { margin: 0; padding: 0; }';
		echo '.api-data-table td, .api-data-table th { padding: 5px; }';
		echo '.api-data-table { border-collapse: collapse; }';
		echo '.api-data-table td, .api-data-table th { border-bottom: 1px solid gray; border-top: 1px solid gray;  }';
		echo '.api-data-table th { background: #ddd; border-right: 1px solid orange; vertical-align: top; text-align: right; padding-left: 15px; }';
		echo '</style>';

		$this->printTableInner ($data, $var_dump);

		echo '</body>';
		echo '</html>';
	}

	private function printTableInner ($data, $var_dump = true)
	{
		if (is_array ($data))
		{
			echo '<table class="api-data-table">';
			foreach ($data as $k => $v)
			{
				echo '<tr>';

				echo '<th>' . self::escape ($k) . '</th>';
				echo '<td>';

				if ($k === 'debug')
				{
					$this->printTableInner ($v, false);
				}
				else
				{
					$this->printTableInner ($v, $var_dump);
				}

				echo '</td>';

				echo '</tr>';
			}
			echo '</table>';
		}
		else
		{
			// The table is a view on data: every key and value is text and
			// is escaped, whatever it contains.
			if ($var_dump)
			{
				ob_start ();
				var_dump ($data);
				echo self::escape (ob_get_clean ());
			}
			else
			{
				echo self::escape (is_scalar ($data) || $data === null ? (string) $data : print_r ($data, true));
			}
		}
	}

	/**
	 * @param mixed $text
	 * @return string
	 */
	private static function escape ($text)
	{
		return htmlspecialchars ((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	public function outputContent (Response $response)
	{
		if (!is_string ($response->getData ()))
		{
			$this->printTable ($response->getData ());
		}

		else
		{
			echo self::escape ($response->getData ());
		}
	}
	
	
} 