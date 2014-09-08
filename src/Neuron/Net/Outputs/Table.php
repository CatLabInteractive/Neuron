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
		header ('Content-type: text/html, charset=utf-8');

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

				echo '<th>' . $k . '</th>';
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
			if ($var_dump)
			{
				var_dump ($data);
			}
			else
			{
				echo $data;
			}
		}
	}

	public function outputContent (Response $response)
	{
		if (!is_string ($response->getData ()))
		{
			$this->printTable ($response->getData ());
		}

		else
		{
			echo $response->getData ();
		}
	}
	
	
} 