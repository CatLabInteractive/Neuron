<?php

namespace Neuron\SessionHandlers;

use Neuron\Models\Logger;

/**
 * Class SessionHandler
 * @package Neuron\SessionHandlers
 */
class SessionHandler
	extends \SessionHandler
	implements \SessionUpdateTimestampHandlerInterface
{
	private $started = false;

	const SESSION_QUERY_PARAMETER = 'PSID';

	/** Where the id of a starting session comes from. */
	const SOURCE_PROVIDED = 'provided';
	const SOURCE_COOKIE = 'cookie';
	const SOURCE_QUERY = 'query';
	const SOURCE_NEW = 'new';

	/**
	 * Session ids as PHP generates them: 22 to 256 characters out of
	 * a-z, A-Z, 0-9, "," and "-" (session.sid_length and
	 * session.sid_bits_per_character).
	 */
	const SESSION_ID_PATTERN = '/\A[A-Za-z0-9,-]{22,256}\z/';

	/** @var bool */
	private static $queryParameterEnabled = false;

	/** @var string|null The save handler PHP used before this one was registered. */
	private $nativeSaveHandler;

	/** @var string|null */
	private $savePath;

	/**
	 * Accept a session id from the query parameter
	 * (SESSION_QUERY_PARAMETER) when the request has no session cookie.
	 * Off by default; turn it on before the session starts.
	 * @param bool $enabled
	 */
	public static function setQueryParameterEnabled ($enabled = true)
	{
		self::$queryParameterEnabled = (bool) $enabled;
	}

	/**
	 * @return bool
	 */
	public static function isQueryParameterEnabled ()
	{
		return self::$queryParameterEnabled;
	}

	/**
	 * Does this look like a session id PHP could have generated?
	 * @param mixed $sessionId
	 * @return bool
	 */
	public static function isValidSessionId ($sessionId)
	{
		return is_string ($sessionId) && preg_match (self::SESSION_ID_PATTERN, $sessionId) === 1;
	}

	/**
	 * Decide which session id a request starts with. An id that is not
	 * in the format PHP generates is ignored, wherever it comes from.
	 * @param string|null $providedId Id given by the application.
	 * @param array $cookies
	 * @param array $query
	 * @return array [ 'id' => string|null, 'source' => one of the SOURCE_ constants ]
	 */
	public static function resolveSessionId ($providedId, array $cookies, array $query)
	{
		if (self::isValidSessionId ($providedId)) {
			return array ('id' => $providedId, 'source' => self::SOURCE_PROVIDED);
		}

		if (isset ($cookies['PHPSESSID']) && self::isValidSessionId ($cookies['PHPSESSID'])) {
			return array ('id' => $cookies['PHPSESSID'], 'source' => self::SOURCE_COOKIE);
		}

		if (
			self::$queryParameterEnabled
			&& isset ($query[self::SESSION_QUERY_PARAMETER])
			&& self::isValidSessionId ($query[self::SESSION_QUERY_PARAMETER])
		) {
			return array ('id' => $query[self::SESSION_QUERY_PARAMETER], 'source' => self::SOURCE_QUERY);
		}

		return array ('id' => null, 'source' => self::SOURCE_NEW);
	}

	/**
	 * The log line for a starting session. It names where the id came
	 * from and never the id itself.
	 * @param string $source One of the SOURCE_ constants.
	 * @return string
	 */
	public static function getStartLogMessage ($source)
	{
		switch ($source) {
			case self::SOURCE_PROVIDED:
				return 'Starting session with an id provided by the application';

			case self::SOURCE_COOKIE:
				return 'Starting session with the id from the session cookie';

			case self::SOURCE_QUERY:
				return 'Starting session with the id from the query parameter';

			default:
				return 'Starting a new session';
		}
	}

	public final function start ($sessionId = null)
	{
		if (!$this->started)
		{
			$this->register ();

			// An id that has no stored session is not adopted (see validateId).
			ini_set ("session.use_strict_mode", 1);

			$resolved = self::resolveSessionId ($sessionId, $_COOKIE, $_GET);

			if ($resolved['source'] === self::SOURCE_PROVIDED) {
				session_id ($resolved['id']);

				ini_set ("session.use_cookies", 0);
				ini_set ("session.use_only_cookies", 0);
				ini_set ("session.use_trans_sid", 0); # Forgot this one!
			} elseif ($resolved['id'] !== null) {
				session_id ($resolved['id']);
			} elseif (session_status() == PHP_SESSION_ACTIVE) {
				session_regenerate_id ();
			}

			Logger::getInstance ()->log (self::getStartLogMessage ($resolved['source']), false, 'cyan');

			session_start ();

			$this->started = true;
		}
	}

	public final function stop ()
	{
		Logger::getInstance ()->log ("Closing session", false, 'cyan');

		session_write_close ();
		$this->started = false;
	}

	/**
	 * @return string
	 */
	public function getSessionQueryString()
	{
		return self::SESSION_QUERY_PARAMETER . '=' . session_id();
	}

	/* Methods */
	#[\ReturnTypeWillChange]
	public function close ()
	{
		return parent::close ();
	}

	#[\ReturnTypeWillChange]
	public function destroy ($session_id)
	{
		return parent::destroy ($session_id);
	}

	#[\ReturnTypeWillChange]
	public function gc ( $maxlifetime )
	{
		return parent::gc ($maxlifetime);
	}

	#[\ReturnTypeWillChange]
	public function open ( $save_path , $name )
	{
		$this->savePath = $save_path;
		return parent::open ( $save_path, $name );
	}

	#[\ReturnTypeWillChange]
	public function read ( $session_id )
	{
		return parent::read ($session_id);
	}


	#[\ReturnTypeWillChange]
	public function write ( $session_id , $session_data )
	{
		return parent::write ($session_id, $session_data);
	}

	/**
	 * Called by PHP in strict mode (session.use_strict_mode) for an id
	 * the request brought along: only an id in the generated format
	 * that has a stored session is kept, any other is replaced by a
	 * newly generated one.
	 * @param string $session_id
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function validateId ($session_id)
	{
		return self::isValidSessionId ($session_id) && $this->sessionExists ($session_id);
	}

	/**
	 * Called by PHP instead of write() when the session data did not
	 * change. Writing the session again refreshes its timestamp, and
	 * stores a session that has no data yet.
	 * @param string $session_id
	 * @param string $session_data
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function updateTimestamp ($session_id, $session_data)
	{
		return $this->write ($session_id, $session_data);
	}

	/**
	 * Is there a stored session for this (well formed) id?
	 * This class wraps PHP's own save handler and can only tell for the
	 * "files" handler; with any other it answers yes. Subclasses with
	 * their own storage override this.
	 * @param string $session_id
	 * @return bool
	 */
	protected function sessionExists ($session_id)
	{
		if ($this->nativeSaveHandler !== 'files' || $this->savePath === null) {
			return true;
		}

		return is_file (self::getSessionFilePath ($this->savePath, $session_id));
	}

	/**
	 * Where PHP's "files" save handler keeps a session, for a
	 * session.save_path of the form "[depth;[mode;]]path".
	 * @param string $savePath
	 * @param string $sessionId
	 * @return string
	 */
	public static function getSessionFilePath ($savePath, $sessionId)
	{
		$parts = explode (';', (string) $savePath);
		$directory = rtrim (array_pop ($parts), '/\\');
		$depth = count ($parts) > 0 ? (int) $parts[0] : 0;

		if ($directory === '') {
			$directory = rtrim (sys_get_temp_dir (), '/\\');
		}

		for ($i = 0; $i < $depth; $i ++) {
			$directory .= '/' . substr ($sessionId, $i, 1);
		}

		return $directory . '/sess_' . $sessionId;
	}

	public function register ()
	{
		$handler = ini_get ('session.save_handler');
		if ($handler !== 'user') {
			$this->nativeSaveHandler = $handler;
		}

		session_set_save_handler($this, true);
	}
} 
