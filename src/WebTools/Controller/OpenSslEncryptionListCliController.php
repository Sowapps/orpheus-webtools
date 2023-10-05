<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\WebTools\Controller;

use Orpheus\InputController\CliController\CliRequest;
use Orpheus\InputController\CliController\CliResponse;

class OpenSslEncryptionListCliController extends AbstractOpenSslCliController {
	
	/**
	 * @param CliRequest $request The input CLI request
	 */
	public function run($request): CliResponse {
		$this->checkSupport();
		
		return new CliResponse(0, implode("\n", openssl_get_cipher_methods()));
	}
	
	
}
