<?php
/**
 * UploadedFile
 */

namespace Orpheus\File;

use Orpheus\Exception\UserException;
use SplFileInfo;

/**
 * The UploadedFile class
 *
 * @author Florent Hazard <contact@sowapps.com>
 *
 */
class UploadedFile {
	
	/**
	 * Allowed extension to upload
	 *
	 * @var array
	 */
	public $allowedExtensions;
	
	/**
	 * Allowed mime types to upload
	 *
	 * @var array
	 */
	public $allowedMimeTypes;
	
	/**
	 * Allowed type to upload
	 *
	 * @var array
	 */
	public $expectedType;
	
	/**
	 * The file name
	 *
	 * @var string
	 */
	protected $fileName;
	
	/**
	 * The file size
	 *
	 * @var int
	 */
	protected $fileSize;
	
	/**
	 * The file temp path
	 *
	 * @var string
	 */
	protected $tempPath;
	
	/**
	 * The file uploading error
	 *
	 * @var int
	 */
	protected $error;
	
	/**
	 * Constructor
	 *
	 * @param string $fileName
	 * @param int $fileSize
	 * @param string $tempPath
	 * @param int $error
	 */
	public function __construct($fileName, $fileSize, $tempPath, $error) {
		$this->fileName = $fileName;
		$this->fileSize = $fileSize;
		$this->tempPath = $tempPath;
		$this->error = $error;
	}
	
	/**
	 * Get the uploaded file (file name) as string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getFileName();
	}
	
	/**
	 * Get the file name
	 *
	 * @return string
	 */
	public function getFileName() {
		return $this->fileName;
	}
	
	/**
	 * Get the file basename
	 *
	 * @return string
	 */
	public function getBaseName() {
		return basename($this->fileName);
	}
	
	/**
	 * Get the file size
	 *
	 * @return number
	 */
	public function getFileSize() {
		return $this->fileSize;
	}
	
	/**
	 * Get the upload error
	 *
	 * @return int
	 */
	public function getError() {
		return $this->error;
	}
	
	/**
	 * Move the file to $path
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function moveTo($path) {
		return move_uploaded_file($this->getTempPath(), $path);
	}
	
	/**
	 * Get temporarily path to file
	 *
	 * @return string
	 */
	public function getTempPath() {
		return $this->tempPath;
	}
	
	/**
	 * Validate the input file is respecting upload restrictions
	 *
	 * @throws UserException
	 *
	 * This function throws exception in case of error
	 */
	public function validate() {
		if( $this->error ) {
			switch( $this->error ) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
				{
					throw new UserException('fileTooBig');
					break;
				}
				case UPLOAD_ERR_PARTIAL:
				case UPLOAD_ERR_NO_FILE:
				{
					throw new UserException('transfertIssue');
					break;
				}
				default:
				{
					// UPLOAD_ERR_NO_TMP_DIR UPLOAD_ERR_CANT_WRITE UPLOAD_ERR_EXTENSION
					// http://php.net/manual/fr/features.file-upload.errors.php
					log_error("Server upload error (error={$this->error}, name={$this->fileName})", 'Uploading file', false);
					throw new UserException('serverIssue');
				}
			}
		}
		
		if( $this->expectedType !== null ) {
			if( $this->getType() !== $this->expectedType ) {
				throw new UserException('invalidType');
			}
		}
		if( $this->allowedExtensions !== null ) {
			$ext = $this->getExtension();
			if( $ext === $this->allowedExtensions || (is_array($this->allowedExtensions) && !in_array($ext, $this->allowedExtensions)) ) {
				throw new UserException('invalidExtension');
			}
		}
		if( $this->allowedMimeTypes !== null ) {
			$mt = $this->getMIMEType();
			if( $mt === $this->allowedMimeTypes || (is_array($this->allowedMimeTypes) && !in_array($mt, $this->allowedMimeTypes)) ) {
				throw new UserException('invalidMimeType');
			}
		}
	}
	
	/**
	 * Get the type of the file from its mimetype
	 *
	 * return string
	 */
	public function getType() {
		[$type,] = explodeList('/', $this->getMIMEType(), 2);
		return $type;
	}
	
	/**
	 * Get the file mime type
	 *
	 * @return string
	 */
	public function getMIMEType() {
		return getMimeType($this->tempPath);
	}
	
	/**
	 * Get the file extension
	 *
	 * @return string
	 */
	public function getExtension() {
		return strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
	}
	
	/**
	 * Get SplFileInfo object for this file
	 *
	 * @return SplFileInfo
	 */
	public function getSplFileInfo() {
		return new SplFileInfo($this->getTempPath());
	}
	
	/**
	 * Load file from input $name
	 *
	 * @param string $name
	 * @return UploadedFile
	 */
	public static function load($name) {
		if( empty($_FILES[$name]['name']) ) {
			return null;
		}
		if( is_array($_FILES[$name]['name']) ) {
			return static::loadPath($_FILES[$name]);
		}
		return new static($_FILES[$name]['name'], $_FILES[$name]['size'], $_FILES[$name]['tmp_name'], $_FILES[$name]['error']);
	}
	
	/**
	 * Get uploaded file from path
	 *
	 * @param array $from
	 * @param array $files
	 * @param string $path
	 * @return UploadedFile
	 */
	protected static function loadPath($from, &$files = [], $path = '') {
		$fileName = ($path === '') ? $from['name'] : apath_get($from['name'], $path);
		if( empty($fileName) ) {
			return $files;
		}
		if( is_array($fileName) ) {
			if( $path !== '' ) {
				$path .= '/';
			}
			foreach( $fileName as $index => $fn ) {
				static::loadPath($from, $files, $path . $index);
			}
			return $files;
		}
		apath_setp($files, $path, new static($fileName, apath_get($from, 'size/' . $path),
			apath_get($from, 'tmp_name/' . $path), apath_get($from, 'error/' . $path)));
		return $files;
	}
	
}
