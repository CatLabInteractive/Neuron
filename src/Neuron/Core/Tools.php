<?php


namespace Neuron\Core;

use DateTime;
use Neuron\Core\Tools as NeuronCoreTools;
use Exception;
use Neuron\Core\Text;
use XMLWriter;



class Tools
{
	public static function getInput ($dat, $key, $type, $default = null)
	{
		if (is_string ($dat))
		{
			global $$dat;
			$dat = $$dat;
		}

		if (!isset ($dat[$key])) {
			return $default;
		}

		else {
			// Check if the value has the right type
			if (NeuronCoreTools::checkInput ($dat[$key], $type)) 
			{
				switch ($type)
				{
					// For date's return timestamp.
					case 'date':
						$time = explode ('-', $dat[$key]);
						return mktime (0, 0, 1, $time[1], $time[2], $time[0]);
					break;

					case 'datetime':
						return new DateTime ($dat[$key]);
					break;

					case 'base64':
						return base64_decode ($dat[$key]);
					break;

					default:
						return $dat[$key];
					break;
				}
			}

			else if ($type == 'bool')
			{
				return false;
			}

			else 
			{
				return $default;
			}
		}
	}

	public static function checkInput ($value, $type)
	{
		if ($type == 'text')
		{
			return true;
		}

		else if ($type == 'bool')
		{
			return $value == 1 || $value == 'true';
		}
		
		elseif ($type == 'varchar' || $type == 'string')
		{
			return self::isValidUTF8 ($value);
		}
		
		elseif ($type == 'password')
		{
			// Minimum 8 characters, maximum 256 characters.
			return self::isValidUTF8 ($value)
				&& strlen ($value) > 7
				&& strlen ($value) <= 256;
		}
		
		elseif ($type == 'email')
		{
			//return (bool)preg_match("/^[_a-z0-9-]+(\.[_a-z0-9\+\-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i", $value);
			return self::isValidUTF8 ($value) && filter_var ($value, FILTER_VALIDATE_EMAIL) ? true : false;
		}
		
		elseif ($type == 'username')
		{
			return self::isValidUTF8 ($value) && (bool)preg_match ('/^[a-zA-Z0-9_]{3,20}$/', $value);
		}

		elseif ($type == 'date')
		{
			$time = explode ('-', $value);
			return self::isValidUTF8 ($value) && (count ($time) == 3);
		}

		elseif ($type == 'datetime') {
			$regex  = '/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/';
			return (bool)preg_match ($regex, $value);
		}
		
		elseif ($type == 'md5')
		{
			return self::isValidUTF8 ($value) && strlen ($value) == 32;
		}

		elseif ($type == 'base64') {
			return self::isValidBase64 ($value);
		}

		elseif ($type == 'url')
		{
			$regex = '/((https?:\/\/|[w]{3})?[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/i';
			return self::isValidUTF8 ($value) && (bool)preg_match($regex, $value);

			/*

			$regex = "((https?|ftp)\:\/\/)?"; // Scheme
			$regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
			$regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
			$regex .= "(\:[0-9]{2,5})?"; // Port
			$regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
			$regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
			$regex .= "(#[a-z_.\!-][a-z0-9+\$_.\!-]*)?"; // Anchor

			return (bool)preg_match("/^$regex$/i", $value);
			*/

			//return filter_var ($value, FILTER_VALIDATE_URL) !== false;

			//return (bool)preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $value);
		}

		elseif ($type == 'number')
		{
			return is_numeric ($value);
		}
		
		elseif ($type == 'int')
		{
			return is_numeric ($value) && (int)$value == $value;
		}
		
		else {
		
			return false;
			//echo 'fout: '.$type;
		
		}

	}

    /**
     * Check if a string is valid UTF8
     * @param $str
     * @return bool
     */
    public static function isValidUTF8 ($str)
    {
        return (bool) preg_match('//u', $str);
    }

	public static function isValidBase64 ($str)
	{
		return base64_encode(base64_decode($str, true)) === $str;
	}

	public static function putIntoText ($text, $ar = array(), $delimiter = '@@') 
	{
		foreach ($ar as $k => $v) 
		{
			if (is_string ($v) || is_float ($v) || is_int ($v))
			{
				$text = str_replace ($delimiter.$k, $v, $text);
			}
			else if (is_object ($v))
			{
				$text = str_replace ($delimiter.$k, (string)$v, $text);	
			}
			else
			{
				throw new Exception ("putIntoText excepts an array.");
			}
		}
		
		// Remove all remaining "putIntoTexts"
		$text = preg_replace ('/'.$delimiter.'([^ ]+)/s', '', $text);
		
		return $text;
	}
	
	public function date_long ($stamp)
	{
	
		$text = Text::__getInstance ();
		
		$dag = $text->get ('day'.(date ('w', $stamp) + 1), 'days', 'main');
		$maand = $text->get ('mon'.date ('m', $stamp), 'months', 'main');
	
		return NeuronCoreTools::putIntoText (
			$text->get ('longDateFormat', 'dateFormat', 'main'),
			array
			(
				$dag,
				date ('d', $stamp),
				$maand,
				date ('Y', $stamp)
			)
		);
	
	}

	public static function output_text ($input)
	{
		return nl2br ($input);
	}

	public static function output_datepicker ($date)
	{
		if ($date)
		{
			return date ('Y-m-d', $date);
		}
		return '';
	}
	
	public static function splitLongWords ($input)
	{
	
		$array = explode (' ', $input);
		
		foreach ($array as $k => $v)
		{
		
			$array[$k] = wordwrap ($v, 20, ' ', 1);
		
		}
		
		return implode (' ', $array);
	
	}
	
	public static function output_form ($text)
	{
	
		return htmlspecialchars (($text) , ENT_QUOTES, 'UTF-8');
	
	}
	
	public static function output_varchar ($text)
	{
	
		$input = NeuronCoreTools::splitLongWords ($text);
		return htmlspecialchars (($text), ENT_QUOTES, 'UTF-8');
	
	}
}

?>
