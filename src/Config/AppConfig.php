<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Config;

use Exception;

/**
 * The AppConfig class
 *
 * Store application configuration in a file
 *
 * @author Florent Hazard <contact@sowapps.com>
 *
 */
class AppConfig {
	
	const VERSION = '2';
	CONST TYPE_STRING = 'simple';
	CONST TYPE_BOOLEAN = 'boolean';
	CONST TYPE_LONG_TEXT = 'text';
	CONST DEFAULT_TYPE = self::TYPE_STRING;
	
	/** @var static */
	protected static $instance;
	
	/** @var string */
	protected $path;
	
	/** @var array */
	protected $data;
	
	/** @var array */
	protected $meta;
	
	/** @var bool */
	protected $changed;
	
	/**
	 * Constructor
	 */
	protected function __construct() {
		$this->path = defined('STORE_PATH') ? STORE_PATH . '/config.json' : null;
		$this->meta = [];
		$this->data = [];
		$this->loadSmartly();
	}
	
	/**
	 * Load config if it exists
	 */
	public function loadSmartly() {
		if( $this->path && is_readable($this->path) ) {
			$this->load();
		}
	}
	
	/**
	 * Load config from filesystem
	 *
	 * @throws Exception
	 */
	public function load() {
		if( !$this->path ) {
			throw new Exception('Unable to load AppConfig from undefined path');
		}
		$jsonConfig = (object) json_decode(file_get_contents($this->path), true);
		if( isset($jsonConfig->data) ) {
			// New format
			$this->meta = $jsonConfig->meta;
			$this->data = $jsonConfig->data;
		} else {
			// BC with old format
			$this->data = (array) $jsonConfig;
		}
	}
	
	/**
	 * Get it as array
	 *
	 * @return array
	 */
	public function asArray() {
		return $this->data;
	}
	
	/**
	 * Set $key if not yet set
	 *
	 * @param string $key
	 * @param mixed $default
	 * @param string $type
	 * @return bool
	 */
	public function preset($key, $default, $type = null) {
		$changed = false;
		if( $type || !isset($this->meta[$key]) ) {
			$type = $type ?: self::DEFAULT_TYPE;
			if( !isset($this->meta[$key]) || $this->meta[$key] !== $type ) {
				$this->meta[$key] = $type ?: self::DEFAULT_TYPE;
				$this->changed = true;
				$changed = true;
			}
		}
		if( !$this->has($key) ) {
			$this->set($key, $default);
			$changed = true;
		}
		return $changed;
	}
	
	/**
	 * Test if config has $key
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		return isset($this->data[$key]);
	}
	
	/**
	 * Set the $value of $key
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	public function set($key, $value) {
		if( array_key_exists($key, $this->data) && $this->data[$key] === $value ) {
			return false;
		}
		$this->data[$key] = $value;
		$this->changed = true;
		return true;
	}
	
	/**
	 * Get value by key
	 *
	 * @param string $key The key to look for
	 * @param mixed|null $default The default value if key is not set
	 * @return mixed|null
	 */
	public function get($key, $default = null) {
		return $this->has($key) ? $this->data[$key] : $default;
	}
	
	/**
	 * Test if config has $key
	 *
	 * @param string $key
	 */
	public function remove($key) {
		unset($this->data[$key]);
	}
	
	/**
	 * Destructor auto save configuration
	 */
	public function __destruct() {
		if( $this->changed ) {
			$this->save();
		}
	}
	
	/**
	 * Save config into the filesystem
	 *
	 * @return int
	 */
	public function save() {
		return file_put_contents($this->path, json_encode([
			'meta'    => $this->meta,
			'data'    => $this->data,
			'version' => static::VERSION,
		]));
	}
	
	/**
	 * Get the type of $key
	 *
	 * @param $key
	 * @return string
	 */
	public function getType($key) {
		return isset($this->meta[$key]) ? $this->meta[$key] : self::DEFAULT_TYPE;
	}
	
	/**
	 * Get the path
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
	
	/**
	 * Set the path
	 *
	 * @param string $path
	 * @return $this
	 */
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}
	
	/**
	 * Get the data
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Set the data
	 *
	 * @param array $data
	 * @return $this
	 */
	public function setData($data) {
		$this->data = $data;
		return $this;
	}
	
	/**
	 * Alias for getInstance()
	 *
	 * @return AppConfig
	 * @see getInstance()
	 */
	public static function instance() {
		return static::getInstance();
	}
	
	/**
	 * Get main instance
	 *
	 * @return AppConfig
	 */
	public static function getInstance() {
		if( !static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}
	
}
