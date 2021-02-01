<?php
/**
 * SlugGenerator
 */

namespace Orpheus\Publisher;

/**
 * The Slug Generator class
 *
 * This class generate slug
 */
class SlugGenerator {
	
	const CASE_LOWER = 0;
	const CASE_CAMEL = 1 << 0;
	const CASE_CAMEL_LOWER = self::CASE_CAMEL;
	const CASE_CAMEL_UPPER = self::CASE_CAMEL | 1 << 1;
	
	/**
	 * Should remove space instead of replacing them
	 *
	 * @var boolean
	 */
	protected bool $removeSpaces = false;
	
	/**
	 * Max length to truncate
	 *
	 * @var int|null
	 */
	protected ?int $maxLength = null;
	
	/**
	 * How to process word case
	 *
	 * @var boolean
	 */
	protected int $caseProcessing = self::CASE_CAMEL_UPPER;
	
	/**
	 * Format the $string
	 *
	 * @param string $string
	 * @return
	 */
	public function format($string) {
		// Order is very important
		
		$string = ucwords(str_replace('&', 'and', strtolower($string)));
		
		if( $this->isRemovingSpaces() ) {
			$string = str_replace(' ', '', $string);
		}
		
		$string = strtr($string, ' .\'"', '----');
		
		// This function convert also spaces into underscore, behavior we don't want here, so spaces should be removed before
		$string = convertSpecialChars($string);
		
		if( $this->caseProcessing !== null ) {
			if( $this->caseProcessing === self::CASE_LOWER ) {
				$string = strtolower($string);
			}
			if( $this->isCamelCaseProcessing() ) {
				if( $this->caseProcessing === self::CASE_CAMEL_LOWER ) {
					$string = lcfirst($string);
					// } else
					// if( $case == UPPERCAMELCASE ) {
					// $string = ucfirst($string);
				}
			}
		}
		
		if( $this->getMaxLength() !== null ) {
			$string = substr($string, 0, $this->getMaxLength());
		}
		
		// At the end, remove duplicate hyphens
		$string = trim(preg_replace('#\-+#', '-', $string), '-');
		
		return $string;
	}
	
	/**
	 * Is this generator removing spaces ?
	 *
	 * @return boolean
	 */
	public function isRemovingSpaces() {
		return $this->removeSpaces;
	}
	
	/**
	 * Is this generator camel case processing ?
	 *
	 * @return boolean
	 */
	public function isCamelCaseProcessing() {
		return bintest($this->caseProcessing, CAMELCASE);
	}
	
	/**
	 * @return int|null
	 */
	public function getMaxLength(): ?int {
		return $this->maxLength;
	}
	
	/**
	 * @param int|null $maxLength
	 */
	public function setMaxLength(?int $maxLength): self {
		$this->maxLength = $maxLength;
		
		return $this;
	}
	
	/**
	 * Get is removing spaces
	 *
	 * @return boolean
	 */
	public function getRemoveSpaces() {
		return $this->removeSpaces;
	}
	
	/**
	 * Set removing spaces
	 *
	 * @param boolean $removeSpaces
	 * @return SlugGenerator
	 */
	public function setRemoveSpaces($removeSpaces = true) {
		$this->removeSpaces = $removeSpaces;
		
		return $this;
	}
	
	/**
	 * Get camel case processing
	 *
	 * @return int
	 */
	public function getCaseProcessing() {
		return $this->caseProcessing;
	}
	
	/**
	 * Set camel case processing
	 *
	 * @param int $caseProcessing
	 * @return SlugGenerator
	 */
	public function setCaseProcessing($caseProcessing) {
		$this->caseProcessing = $caseProcessing;
		
		return $this;
	}
	
}
