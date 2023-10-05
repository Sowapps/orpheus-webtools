<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\WebTools\Controller;

use Orpheus\InputController\CliController\CliRequest;
use Orpheus\InputController\CliController\CliResponse;
use Throwable;

class OpenSslEncryptionComparisonCliController extends AbstractOpenSslCliController {
	
	/**
	 * @param CliRequest $request The input CLI request
	 */
	public function run($request): CliResponse {
		$this->checkSupport();
		
		$value = $request->getArgument(0) ?? 'Default test value [9é$£¤/,?\+€`]';
		$passphrase = $request->getArgument(1) ?? 'DefaultPassphrase';
		
		$this->printLine(sprintf('Compare value "%s" through all supported open ssl encryption methods using passphrase "%s"', $value, $passphrase));
		
		foreach( openssl_get_cipher_methods() as $method ) {
			try {
				$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
				$encrypted = openssl_encrypt($value, $method, $passphrase, 0, $iv, $tag);
				$this->printLine(sprintf('%s => [%d] %s [tag=%s]', $method, mb_strlen($encrypted), $encrypted, $tag));
			} catch( Throwable $exception ) {
				$this->printError(sprintf('%s =[ERROR]> %s', $method, $exception->getMessage()));
			}
		}
		
		return new CliResponse(0, 'ok');
	}
	
}
