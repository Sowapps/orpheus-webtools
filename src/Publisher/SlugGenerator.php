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
	 * @var int
	 */
	protected int $caseProcessing = self::CASE_CAMEL_UPPER;
	
	/**
	 * Format the $string
	 */
	public function format(string $string): string {
		// Order is very important
		
		$string = ucwords(str_replace('&', 'and', strtolower($string)));
		
		if( $this->isRemovingSpaces() ) {
			$string = str_replace(' ', '', $string);
		}
		
		$string = strtr($string, ' .\'"', '----');
		
		// This function convert also spaces into underscore, behavior we don't want here, so spaces should be removed before
		$string = convertSpecialChars($string);
		
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
		
		// As last change, remove duplicate hyphens
		/** @noinspection RegExpRedundantEscape */
		$string = trim(preg_replace('#\-+#', '-', $string), '-');
		
		// After all changes, we truncate the string if over max length
		if( $this->getMaxLength() !== null ) {
			$string = substr($string, 0, $this->getMaxLength());
		}
		
		return $string;
	}
	
	/**
	 * Is this generator camel case processing ?
	 */
	public function isCamelCaseProcessing(): bool {
		return matchBits($this->caseProcessing, self::CASE_CAMEL);
	}
	
	public function getMaxLength(): ?int {
		return $this->maxLength;
	}
	
	public function setMaxLength(?int $maxLength): static {
		$this->maxLength = $maxLength;
		
		return $this;
	}
	
	/**
	 * Is this generator removing spaces ?
	 */
	public function isRemovingSpaces(): bool {
		return $this->removeSpaces;
	}
	
	/**
	 * Set removing spaces
	 */
	public function setRemoveSpaces(bool $removeSpaces = true): static {
		$this->removeSpaces = $removeSpaces;
		
		return $this;
	}
	
	/**
	 * Get camel case processing
	 */
	public function getCaseProcessing(): int {
		return $this->caseProcessing;
	}
	
	/**
	 * Set camel case processing
	 */
	public function setCaseProcessing(int $caseProcessing): static {
		$this->caseProcessing = $caseProcessing;
		
		return $this;
	}
	
}
