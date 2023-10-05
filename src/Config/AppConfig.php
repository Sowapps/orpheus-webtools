<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Config;

use Exception;
use RuntimeException;

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
	const DEFAULT_TYPE = self::TYPE_STRING;
	const ALL_TYPES = [self::TYPE_STRING, self::TYPE_BOOLEAN, self::TYPE_LONG_TEXT];
	
	/** @var static */
	protected static AppConfig $instance;
	
	protected ?string $path;
	
	protected array $data = [];
	
	protected array $meta = [];
	
	protected bool $changed = false;
	
	/**
	 * AppConfig constructor
	 *
	 * @throws Exception
	 */
	protected function __construct() {
		$this->path = defined('STORE_PATH') ? STORE_PATH . '/config.json' : null;
		$this->loadSmartly();
	}
	
	/**
	 * Load config if it exists
	 * @throws Exception
	 */
	public function loadSmartly(): void {
		if( $this->path && is_readable($this->path) ) {
			$this->load();
		}
	}
	
	/**
	 * Load config from filesystem
	 *
	 * @throws Exception
	 */
	public function load(): void {
		if( !$this->path ) {
			throw new RuntimeException('Unable to load AppConfig from undefined path');
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
	 */
	public function asArray(): array {
		return $this->data;
	}
	
	/**
	 * Set $key if not yet set
	 */
	public function preset(string $key, mixed $default, ?string $type = null): bool {
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
	 */
	public function has(string $key): bool {
		return isset($this->data[$key]);
	}
	
	/**
	 * Set the $value of $key
	 */
	public function set(string $key, mixed $value): bool {
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
	public function get(string $key, mixed $default = null): mixed {
		return $this->has($key) ? $this->data[$key] : $default;
	}
	
	/**
	 * Test if config has $key
	 */
	public function remove(string $key): void {
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
	 */
	public function save(): int {
		return file_put_contents($this->path, json_encode([
			'meta'    => $this->meta,
			'data'    => $this->data,
			'version' => static::VERSION,
		]));
	}
	
	/**
	 * Get the type of $key
	 */
	public function getType(string $key): string {
		return $this->meta[$key] ?? self::DEFAULT_TYPE;
	}
	
	/**
	 * Get the path
	 */
	public function getPath(): ?string {
		return $this->path;
	}
	
	/**
	 * Set the path
	 */
	public function setPath(string $path): static {
		$this->path = $path;
		
		return $this;
	}
	
	/**
	 * Get the data
	 */
	public function getData(): array {
		return $this->data;
	}
	
	/**
	 * Set the data
	 */
	public function setData(array $data): static {
		$this->data = $data;
		
		return $this;
	}
	
	/**
	 * Alias for getInstance()
	 *
	 * @see getInstance()
	 */
	public static function instance(): AppConfig {
		return static::getInstance();
	}
	
	/**
	 * Get main instance
	 */
	public static function getInstance(): AppConfig {
		if( !isset(static::$instance) ) {
			static::$instance = new static();
		}
		
		return static::$instance;
	}
	
}
