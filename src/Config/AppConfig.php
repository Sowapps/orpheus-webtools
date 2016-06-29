<?php
namespace Orpheus\Config;

class AppConfig {
	
// 	protected $path	= DYNCONFIGPATH;
	protected $path;
	protected $data;
	
	protected function __construct() {
		$this->path = defined('STOREPATH') ? STOREPATH.'config.json' : null;
		$this->data	= array();
		$this->loadSmartly();
// 		if( $this->path && is_readable($this->path) ) {
// 			$this->data	= json_decode(file_get_contents($this->path), true);
// 		}
	}

	public function asArray() {
		return $this->data;
	}

	public function preset($key, $default) {
		if( !$this->has($key) ) {
			$this->set($key, $default);
		}
	}

	public function has($key) {
		return isset($this->data[$key]);
	}
	public function get($key, $default=null) {
		return $this->has($key) ? $this->data[$key] : $default;
	}

	public function set($key, $value) {
		$this->data[$key]	= $value;
	}

	public function remove($key) {
		unset($this->data[$key]);
	}

	public function loadSmartly() {
		if( $this->path && is_readable($this->path) ) {
			$this->load();
		}
	}
	
	public function load() {
		if( !$this->path ) {
			throw new \Exception('Unable to load AppConfig from undefined path');
		}
		$this->data	= json_decode(file_get_contents($this->path), true);
	}

	public function save() {
		return file_put_contents($this->path, json_encode($this->data));
	}

	protected static $instance;
	
	/**
	 * @return GlobalConfig
	 */
	public static function instance() {
		return static::getInstance();
	}
	
	/**
	 * @return GlobalConfig
	 */
	public static function getInstance() {
		if( !static::$instance ) {
			static::$instance	= new static();
		}
		return static::$instance;
	}
	public function getPath() {
		return $this->path;
	}
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}
	public function getData() {
		return $this->data;
	}
	public function setData($data) {
		$this->data = $data;
		return $this;
	}
	
	
	
}
