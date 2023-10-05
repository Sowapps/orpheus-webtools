<?php
/**
 * @author Florent Hazard <contact@sowapps.com>
 */

namespace Orpheus\File;

use Orpheus\Exception\UserException;
use SplFileInfo;

/**
 * Class representing a http uploaded file
 */
class UploadedFile {
	
	/**
	 * Allowed extension to upload
	 *
	 * @var array|null
	 */
	public ?array $allowedExtensions = null;
	
	/**
	 * Allowed mime types to upload
	 *
	 * @var array|null
	 */
	public ?array $allowedMimeTypes = null;
	
	/**
	 * Allowed type to upload
	 *
	 * @var string|null
	 */
	public ?string $expectedType = null;
	
	/**
	 * The file name
	 *
	 * @var string
	 */
	protected string $fileName;
	
	/**
	 * The file size
	 *
	 * @var int
	 */
	protected int $fileSize;
	
	/**
	 * The file temp path
	 *
	 * @var string
	 */
	protected string $tempPath;
	
	/**
	 * The file uploading error
	 *
	 * @var int
	 */
	protected int $error;
	
	/**
	 * Constructor
	 */
	public function __construct(string $tempPath, string $fileName, int $fileSize, int $error) {
		$this->tempPath = $tempPath;
		$this->fileName = $fileName;
		$this->fileSize = $fileSize;
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
	 */
	public function getFileName(): string {
		return $this->fileName;
	}
	
	/**
	 * Get the file basename
	 */
	public function getBaseName(): string {
		return basename($this->fileName);
	}
	
	/**
	 * Get the file size
	 *
	 * @return number
	 */
	public function getFileSize(): int {
		return $this->fileSize;
	}
	
	/**
	 * Get the upload error
	 */
	public function getError(): int {
		return $this->error;
	}
	
	/**
	 * Move the file to $path
	 */
	public function moveTo(string $path): bool {
		return move_uploaded_file($this->getTempPath(), $path);
	}
	
	/**
	 * Get temporarily path to file
	 */
	public function getTempPath(): string {
		return $this->tempPath;
	}
	
	/**
	 * Validate the input file is respecting upload restrictions
	 * This function throws exception in case of error
	 *
	 * @throws UserException
	 */
	public function validate(): void {
		if( $this->error ) {
			switch( $this->error ) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
				{
					throw new UserException('fileTooBig');
				}
				case UPLOAD_ERR_PARTIAL:
				case UPLOAD_ERR_NO_FILE:
				{
					throw new UserException('transfertIssue');
				}
				default:
				{
					// UPLOAD_ERR_NO_TMP_DIR UPLOAD_ERR_CANT_WRITE UPLOAD_ERR_EXTENSION
					// http://php.net/manual/fr/features.file-upload.errors.php
					log_error("Server upload error (error={$this->error}, name={$this->fileName})", 'Uploading file');
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
			if( !in_array($ext, $this->allowedExtensions) ) {
				throw new UserException('invalidExtension');
			}
		}
		if( $this->allowedMimeTypes !== null ) {
			$mt = $this->getMimeType();
			if( !in_array($mt, $this->allowedMimeTypes) ) {
				throw new UserException('invalidMimeType');
			}
		}
	}
	
	/**
	 * Get the type of the file from its mimetype
	 *
	 * return string
	 */
	public function getType(): string {
		[$type,] = explodeList('/', $this->getMimeType(), 2);
		return $type;
	}
	
	/**
	 * Get the file mime type
	 */
	public function getMimeType(): string {
		return getMimeType($this->tempPath);
	}
	
	/**
	 * Get the file extension
	 */
	public function getExtension(): string {
		return strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
	}
	
	/**
	 * Get SplFileInfo object for this file
	 */
	public function getSplFileInfo(): SplFileInfo {
		return new SplFileInfo($this->getTempPath());
	}
	
	/**
	 * Load file from input $name
	 *
	 * @return UploadedFile|UploadedFile[]|null
	 */
	public static function loadFiles(string $name): UploadedFile|array|null {
		if( empty($_FILES[$name]['name']) ) {
			return null;
		}
		if( is_array($_FILES[$name]['name']) ) {
			return static::loadInputFiles($_FILES[$name]);
		}
		return new static($_FILES[$name]['tmp_name'], $_FILES[$name]['name'], $_FILES[$name]['size'], $_FILES[$name]['error']);
	}
	
	/**
	 * Get uploaded file from path
	 *
	 * @return UploadedFile[]
	 */
	protected static function loadInputFiles(array $input, array &$files = [], ?string $path = null): array {
		$fileName = $path ? array_path_get($input['name'], $path) : $input['name'];
		if( empty($fileName) ) {
			return $files;
		}
		if( is_array($fileName) ) {
			if( $path ) {
				$path .= '/';
			}
			foreach( $fileName as $index => $fn ) {
				static::loadInputFiles($input, $files, $path . $index);
			}
			return $files;
		}
		array_path_set($files, $path, new static(array_path_get($input, 'tmp_name/' . $path), $fileName, array_path_get($input, 'size/' . $path),
			array_path_get($input, 'error/' . $path)));
		return $files;
	}
	
}
