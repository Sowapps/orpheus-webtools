<?php
/**
 * PasswordGenerator
 */

namespace Orpheus\Publisher;

/**
 * The PasswordGenerator class
 * 
 * Class to generate random secured password
 * 
 * @author Florent Hazard <contact@sowapps.com>
 *
 */
class PasswordGenerator {
	
	/**
	 * The set of character we could require in password
	 * 
	 * @var array
	 */
	protected array $primarySets = [];
	
	const CHAR_ALPHA_LOWER	= 1;
	const CHAR_ALPHA_UPPER	= 2;
	const CHAR_DIGIT		= 4;
	const CHAR_SYMBOL		= 8;
	const CHAR_ALPHA		= 3;//self::CHAR_ALPHA_LOWER|self::CHAR_ALPHA_UPPER;
	const CHAR_ALPHA_DIGIT = 7;//self::CHAR_ALPHA|self::CHAR_DIGIT;
	const CHAR_ALL = 15;//self::CHAR_ALPHA_DIGIT|self::CHAR_SYMBOL;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setPrimarySet(self::CHAR_ALPHA_LOWER, 'abcdefghijklmnopqrstuvwxyz');
		$this->setPrimarySet(self::CHAR_ALPHA_UPPER, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		$this->setPrimarySet(self::CHAR_DIGIT, '0123456789');
		$this->setPrimarySet(self::CHAR_SYMBOL, '!@#$%&*?');
	}
	
	/**
	 * Set a known set of character by flag
	 */
	public function setPrimarySet(int $flag, string $characters): static {
		$this->primarySets[$flag] = $characters;
		
		return $this;
	}

	/**
	 * Generate a random complex password
	 *
	 * @param int[] $tokens
	 */
	public function generate(int $length = 10, int $restriction = self::CHAR_ALPHA_DIGIT, array $tokens = [self::CHAR_ALPHA, self::CHAR_DIGIT]): string {
		$tokens = array_pad($tokens, $length, $restriction);
		shuffle($tokens);
		$password	= '';
		foreach( $tokens as $token ) {
			$tokenChars = '';
			foreach( $this->primarySets as $flag => $chars ) {
				if( matchBits($token, $flag) ) {
					$tokenChars .= $chars;
				}
			}
			$password .= $tokenChars[mt_rand(0, strlen($tokenChars)-1)];
		}
		return $password;
	}
}
