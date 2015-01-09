<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/08/14
 * Time: 17:21
 */

namespace Neuron\Net\Outputs;


use Neuron\Net\Response;
use XMLWriter;

class XML extends HTML {

	private $version;
	private $root;
	private $parameters;
	private $itemName;

	public function __construct ($root = 'root', $version = '1.0', array $parameters = array (), $itemName = 'item')
	{
		$this->root = $root;
		$this->version = $version;
		$this->itemName = $itemName;
		$this->parameters = $parameters;
	}

	public static function writexml (XMLWriter $xml, $data, $item_name = 'item')
	{
		foreach($data as $key => $value)
		{
			if (is_int ($key))
			{
				$key = $item_name;
			}

			if (is_array($value))
			{
				if ($key != 'items')
				{
					$xml->startElement($key);
				}

				if (isset ($value['attributes']) && is_array ($value['attributes']))
				{
					foreach ($value['attributes'] as $k => $v)
					{
						$xml->writeAttribute ($k, $v);
					}

					unset ($value['attributes']);
				}

				self::writexml ($xml, $value, substr ($key, 0, -1));

				if ($key != 'items')
				{
					$xml->endElement();
				}
			}

			elseif ($key == 'element-content')
			{
				$xml->text ($value);
			}

			else
			{
				$xml->writeElement($key, $value);
			}
		}
	}

	public static function output_xml ($data, $version = '0.1', $root = 'root', $parameters = array (), $sItemName = 'item')
	{
		$xml = new XmlWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement($root);
		$xml->setIndent (true);

		if (!empty ($version))
		{
			$xml->writeAttribute ('version', $version);
		}

		foreach ($parameters as $paramk => $paramv)
		{
			$xml->writeAttribute ($paramk, $paramv);
		}

		self::writexml ($xml, $data, $sItemName);

		$xml->endElement();
		return $xml->outputMemory(true);
	}

	private static function xml_escape ($input)
	{
		//$input = str_replace ('"', '&quot;', $input);
		//$input = str_replace ("'", '&apos;', $input);


		$input = str_replace ('<', '&lt;', $input);
		$input = str_replace ('>', '&gt;', $input);
		$input = str_replace ('&', '&amp;', $input);


		return $input;
	}

	public static function output_partly_xml ($data, $key =  null)
	{
		$output = '<'.$key;

		if (isset ($data['attributes']) && is_array ($data['attributes']))
		{
			foreach ($data['attributes'] as $k => $v)
			{
				$output .= ' '.$k.'="'.$v.'"';
			}

			unset ($data['attributes']);
		}

		$output .= '>';
		if (!is_array ($data))
		{
			$output .= self::xml_escape ($data);
		}

		elseif (count ($data) == 1 && isset ($data['element-content']))
		{
			$output .= self::xml_escape ($data['element-content']);
		}

		else
		{
			foreach ($data as $k => $v)
			{
				if (is_numeric ($k))
				{
					$k = substr ($key, 0, -1);
				}

				$output .= self::output_partly_xml ($v, $k);
			}
		}
		$output .= '</'.$key.'>'."\n";

		return $output;
	}

	public function outputContent (Response $response)
	{
		header ('Content-type: application/xml');

		if (!is_string ($response->getData ()))
		{
			echo self::output_xml ($response->getData (), $this->version, $this->root, $this->parameters, $this->itemName);
		}

		else
		{
			echo $response->getData ();
		}
	}
	
	
} 