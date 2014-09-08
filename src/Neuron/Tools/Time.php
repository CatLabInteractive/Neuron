<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 16/04/14
 * Time: 16:03
 */

namespace Neuron\Tools;


class Time {

	public static function intervalToText ($totalseconds, $precision = 1)
	{
		$intervals = array ();

		$intervals['year'] = 60 * 60 * 24 * 365;
		$intervals['month'] = 60 * 60 * 24 * 28;
		$intervals['week'] = 60 * 60 * 24 * 7;
		$intervals['day'] = 60 * 60 * 24;
		$intervals['hour'] = 60 * 60;
		$intervals['minute'] = 60;
		$intervals['second'] = 1;

		$tmp = $totalseconds;

		$out = array ();
		foreach ($intervals as $interval => $seconds)
		{
			if (($tmp / $seconds) >= 1)
			{
				$out[$interval] = floor ($tmp / $seconds) . ' ' . $interval;
				$tmp -= $out[$interval] * $seconds;
			}
		}

		/*
		echo '<pre>';
		echo $totalseconds . "\n";
		print_r ($out);
		echo '</pre>';
		*/

		$tmp = array ();
		for ($i = 0; $i < $precision; $i ++)
		{
			$data = array_shift ($out);
			if (!$data)
			{
				break;
			}

			$tmp[] = $data;
		}

		return implode (', ', $tmp);
	}

} 