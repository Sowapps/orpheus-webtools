<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\WebTools\Controller;

use Orpheus\Exception\UserException;
use Orpheus\InputController\CliController\CliController;

abstract class AbstractOpenSslCliController extends CliController {
	
	public function checkSupport(): void {
		if( !$this->isSupported() ) {
			throw new UserException('Server is not supporting OpenSSL features');
		}
	}
	
	public function isSupported(): bool {
		return function_exists('openssl_get_cipher_methods');
	}
	
	
}
