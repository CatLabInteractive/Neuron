<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 12/06/14
 * Time: 16:00
 */

namespace Neuron\Net;


use Neuron\Exceptions\NotImplemented;

class Client
{

	public static function getInstance()
	{
		static $in;
		if (!isset ($in)) {
			$in = new self ();
		}
		return $in;
	}

	public static function http_parse_headers($raw_headers)
	{
		$headers = array();
		$key = ''; // [+]

		foreach (explode("\n", $raw_headers) as $i => $h) {
			$h = explode(':', $h, 2);

			if (isset($h[1])) {
				if (!isset($headers[$h[0]]))
					$headers[$h[0]] = trim($h[1]);
				elseif (is_array($headers[$h[0]])) {
					// $tmp = array_merge($headers[$h[0]], array(trim($h[1]))); // [-]
					// $headers[$h[0]] = $tmp; // [-]
					$headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1]))); // [+]
				} else {
					// $tmp = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [-]
					// $headers[$h[0]] = $tmp; // [-]
					$headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [+]
				}

				$key = $h[0]; // [+]
			} else // [+]
			{ // [+]
				if (substr($h[0], 0, 1) == "\t") // [+]
					$headers[$key] .= "\r\n\t" . trim($h[0]); // [+]
				elseif (!$key) // [+]
					$headers[0] = trim($h[0]);
				trim($h[0]); // [+]
			} // [+]
		}

		return $headers;
	}

	private function __construct()
	{

	}

	public function get(Request $request)
	{
		return $this->api($request, 'GET');
	}

	public function post(Request $request)
	{
		return $this->api($request, 'POST');
	}

	public function put(Request $request)
	{
		return $this->api($request, 'PUT');
	}

	public function delete(Request $request)
	{
		return $this->api($request, 'DELETE');
	}

	public function process(Request $request)
	{
		return $this->api($request, $request->getMethod());
	}

	private function api(Request $request, $method)
	{
		$ch = curl_init();

		$post = $request->getBody();

		$parsedUrl = $request->getUrl();

		if ($request->getParameters()) {

			if (strpos($parsedUrl, '?')) {
				$parsedUrl .= '&';
			} else {
				$parsedUrl .= '?';
			}

			$parsedUrl .= http_build_query($request->getParameters());
		};

		curl_setopt($ch, CURLOPT_URL, $parsedUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);

		if ($request->getHeaders()) {
			$headers = [];
			foreach ($request->getHeaders() as $k => $v) {
				switch ($k) {
					case 'User-Agent':
						curl_setopt($ch, CURLOPT_USERAGENT, $v);
						break;

					default:
						$headers[$k] = $v;
						break;
				}
			}

			if (count($headers) > 0) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
		}

		switch ($method) {
			case 'GET':
				break;

			case 'POST':
				curl_setopt($ch, CURLOPT_POST, 1);

				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				break;

			case 'DELETE':
				throw new NotImplemented ("Not implemented.");
				break;

			case 'PUT':
				curl_setopt($ch, CURLOPT_PUT, 1);

				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				break;

		}

		$output = curl_exec($ch);

		// Response


		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($output, 0, $header_size);
		$body = substr($output, $header_size);

		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$response = Response::fromRaw($body, self::http_parse_headers($header));
		curl_close($ch);

		$response->setStatus($status);

		return $response;
	}
}