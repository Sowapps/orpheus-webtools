<?php
/**
 * Email
 */

namespace Orpheus\Email;

use RuntimeException;

/**
 * The email class
 *
 * This class is a tool to send mails
 */
class Email {
	
	/**
	 * The email headers
	 *
	 * @var array
	 */
	private array $headers = [
		'MIME-Version'              => '',
		'Content-Type'              => 'text/plain, charset=UTF-8',
		'Content-Transfer-Encoding' => '',
		//'Content-Transfer-Encoding' => '7bit',
		'Date'                      => '',//See init()
		'From'                      => 'no-reply@nodomain.com',//Override PHP's default
		'Sender'                    => '',
		'X-Sender'                  => '',
		'Reply-To'                  => '',
		'Return-Path'               => '',
		'Organization'              => '',
		'Bcc'                       => '',
	];
	
	/**
	 * The HTML body
	 *
	 * @var string|null
	 */
	private ?string $htmlBody = null;
	
	/**
	 * The text body
	 *
	 * @var string|null
	 */
	private ?string $textBody = null;
	
	/**
	 * The alternative body
	 *
	 * @var string|null
	 */
	private ?string $altBody = null;
	
	/**
	 * Attached files to mail as list of filename
	 *
	 * @var array
	 */
	private array $attachedFiles = [];
	
	/**
	 * The mail subject
	 *
	 * @var string
	 */
	private string $subject;
	
	/**
	 * The mime boundary
	 *
	 * @var array
	 */
	private array $mimeBoundary = [];
	
	/**
	 * Constructor
	 *
	 * @param string $subject The subject of the mail. Default value is an empty string.
	 * @param string $text The body of the message, used as text and html. Default value is an empty string.
	 */
	public function __construct(string $subject = '', string $text = '') {
		$this->initialize();
		$this->setSubject($subject);
		$this->setText($text);
	}
	
	/**
	 * Initialize the object
	 */
	private function initialize(): void {
		$this->headers['Date'] = date('r');
		$allowReply = true;
		$senderName = null;
		if( defined('REPLY_EMAIL') ) {
			$sendEmail = REPLY_EMAIL;
			$allowReply = false;
		} else if( defined('ADMIN_EMAIL') ) {
			$sendEmail = ADMIN_EMAIL;
		} else {
			return;
		}
		if( defined('EMAIL_SENDER_NAME') ) {
			$senderName = EMAIL_SENDER_NAME;
		}
		$this->setSender($sendEmail, $senderName, $allowReply);
	}
	
	/**
	 * Set the Sender value of the mail.
	 * This function also sets the ReplyTo value if undefined.
	 * If a sender name is provided, it sets the "From" header to NOM \<EMAIL\>
	 *
	 * @param string $senderEmail The email address to send this mail
	 * @param string|null $senderName The email address to send this mail. Default value is null.
	 * @param boolean $allowReply True to use this address as reply address. Default value is true.
	 */
	public function setSender(string $senderEmail, ?string $senderName = null, bool $allowReply = true): static {
		//=?utf-8?b?".base64_encode($from_name)."?= <".$from_a.">\r\n
		$this->setHeader('From', $senderName === null ? $senderEmail : static::escapeB64($senderName) . ' <' . $senderEmail . '>');
		$this->setHeader('Sender', $senderEmail);
		if( $allowReply && empty($this->headers['Return-Path']) ) {
			$this->setReplyTo($senderEmail);
		}
		
		return $this;
	}
	
	/**
	 * Set the value of a header
	 *
	 * @param string $key The key of the header to set.
	 * @param string $value The new value of the header.
	 */
	public function setHeader(string $key, string $value): static {
		$this->headers[$key] = $value;
		
		return $this;
	}
	
	/**
	 * Escape the string using base64 encoding
	 *
	 * @param mixed $string The string to escape (converted to string)
	 * @return string The escaped string in base64
	 */
	public static function escapeB64(string $string): string {
		return '=?UTF-8?B?' . base64_encode($string) . '?=';
	}
	
	/**
	 * Set the ReplyTo value of the mail
	 *
	 * @param string $email The email address to send this mail
	 */
	public function setReplyTo(string $email): static {
		$this->setHeader('Return-Path', $email);
		$this->setHeader('Reply-To', $email);
		
		return $this;
	}
	
	/**
	 * Set the subject of the mail
	 *
	 * @param string $subject The new subject
	 */
	public function setSubject(string $subject): static {
		// If subject is too long, QP returns a bad string, it's working with b64.
		$this->subject = static::escapeB64($subject);// Supports UTF-8
		
		return $this;
	}
	
	/**
	 * Set the mail content to the html text
	 *
	 * @param string $text The new text for the mail contents
	 */
	public function setText(string $text): static {
		$this->setTextBody(strip_tags($text));
		$this->setHtmlBody(nl2br($text));
		
		return $this;
	}
	
	/**
	 * Set the text body of the mail
	 *
	 * @param string $body The new body
	 */
	public function setTextBody(string $body): static {
		$this->textBody = static::escape($body);
		
		return $this;
	}
	
	/**
	 * Escape the string for mails
	 *
	 * @param string $string The string to escape (converted to string)
	 * @return string The escaped string for mails
	 */
	public static function escape(string $string): string {
		return quoted_printable_encode((mb_detect_encoding($string, 'UTF-8') === 'UTF-8') ? $string : mb_convert_encoding($string, 'UTF-8'));
	}
	
	/**
	 * Set the html body of the mail
	 *
	 * @param string $body The new body
	 */
	public function setHtmlBody(string $body): static {
		$this->htmlBody = static::formatHtmlBody($body);
		
		return $this;
	}
	
	/**
	 * Convert body to email-compliant HTML
	 */
	protected static function formatHtmlBody(string $body): string {
		// Supports UTF-8 and Quote printable encoding
		return static::escape(str_replace(["\r", "\n"], '', '<div dir="ltr">' . $body . '</div>'));
	}
	
	/**
	 * Add a file to the files list
	 *
	 * @param string $filename The file name
	 */
	public function addFile(string $filename): static {
		if( $this->containsFile($filename) ) {
			throw new RuntimeException('FileAlreadyContained');
		}
		$this->attachedFiles[] = $filename;
		
		return $this;
	}
	
	/**
	 * Check if this file is in the files list
	 *
	 * @param string $filename The file name
	 * @return boolean True if this file is in the attached files list
	 */
	public function containsFile(string $filename): bool {
		return in_array($filename, $this->attachedFiles);
	}
	
	/**
	 * Remove a file from the files list
	 *
	 * @param string $filename The file name
	 */
	public function removeFile(string $filename): static {
		if( ($key = array_search($filename, $this->attachedFiles)) === false ) {
			throw new RuntimeException('FileNotContained');
		}
		unset($this->attachedFiles[$key]);
		
		return $this;
	}
	
	/**
	 * Set the alternative body of the mail
	 *
	 * @param string $body The new body.
	 */
	public function setAltBody(string $body): static {
		$this->altBody = $body;
		
		return $this;
	}
	
	/**
	 * Send the mail to the given address
	 * You can pass an array of address to send it to multiple recipients.
	 *
	 * @param string|array $toAddress The email address to send this mail
	 */
	public function send(string|array $toAddress): void {
		if( !$toAddress ) {
			throw new RuntimeException('InvalidEmailAddress');
		}
		if( $this->hasMultiplesContents() ) {
			$boundary = $this->getBoundary();
			$this->setHeader('MIME-Version', '1.0');
			$this->setHeader('Content-Type', "multipart/alternative; boundary=\"{$boundary}\"");
			$body = null;
			$ContentsArr = [];
			if( $this->isAlternative() ) {
				$ContentsArr[] = [
					'headers' => [
						'Content-Type' => 'multipart/alternative',
					],
					'body' => (mb_detect_encoding($this->altBody, 'UTF-8') === 'UTF-8') ? mb_convert_encoding($this->altBody, 'ISO-8859-1') : $this->altBody,
				];
			}
			
			if( $this->isText() ) {
				$ContentsArr[] = [
					'headers' => [
						'Content-Type'              => 'text/plain; charset="UTF-8"',
						'Content-Transfer-Encoding' => 'quoted-printable',
					],
					'body'    => $this->textBody,
				];
			}
			
			if( $this->isHtml() ) {
				$ContentsArr[] = [
					'headers' => [
						'Content-Type'              => 'text/html; charset="UTF-8"',
						'Content-Transfer-Encoding' => 'quoted-printable',
					],
					'body'    => $this->htmlBody,
				];
			}
			
			if( $this->containsFiles() ) {
				$this->setHeader('Content-Type', "multipart/mixed; boundary=\"{$boundary}\"");
				
				//With files, mail content is overloaded, also we make a blocklist under a bloc with own boundary.
				$subContentsArr = $ContentsArr;
				if( !empty($subContentsArr) ) {
					$ContentsArr = [];
					$subBoundary = $this->getBoundary(1);
					$subBody = '';
					
					foreach( $subContentsArr as $Content ) {
						$subHeaders = '';
						$Content['headers']['Content-Type'] .= '; format=flowed';
						foreach( $Content['headers'] as $headerName => $headerValue ) {
							$subHeaders .= "{$headerName}: {$headerValue}\r\n";
						}
						$subBody .= <<<BODY
--{$subBoundary}\r\n{$subHeaders}\r\n{$Content['body']}\r\n\r\n
BODY;
					}
					$subBody .= <<<BODY
--{$subBoundary}--
BODY;
					$ContentsArr[] = [
						'headers' => [
							'Content-Type' => "multipart/alternative; boundary=\"{$subBoundary}\"",
						],
						'body'    => $subBody,
					];
					
				}
				
				foreach( $this->attachedFiles as $fileName ) {
					if( !is_readable($fileName) ) {
						continue;
					}
					$ContentsArr[] = [
						'headers' => [
							'Content-Type'              => self::getMimeType($fileName) . '; name="' . pathinfo($fileName, PATHINFO_BASENAME) . '"',
							'Content-Transfer-Encoding' => 'base64',
							'Content-Disposition'       => 'attachment; filename="' . pathinfo($fileName, PATHINFO_BASENAME) . '"',
						],
						'body'    => chunk_split(base64_encode(file_get_contents($fileName))),
					];
				}
			}
			if( !empty($ContentsArr) ) {
				$body = '';
				
				foreach( $ContentsArr as $Content ) {
					$ContentHeaders = '';
					
					if( empty($Content['headers']) ) {
						throw new RuntimeException('ContentRequireHeaders');
					}
					if( empty($Content['body']) ) {
						throw new RuntimeException('ContentRequireBody');
					}
					foreach( $Content['headers'] as $headerName => $headerValue ) {
						$ContentHeaders .= "{$headerName}: {$headerValue}\r\n";
					}
					$body .= <<<BODY
--{$boundary}\r\n{$ContentHeaders}\r\n{$Content['body']}\r\n\r\n
BODY;
				}
				$body .= <<<BODY
--{$boundary}--
BODY;
			}
			
		} else {
			if( $this->isHtml() ) {
				$this->setHeader('MIME-Version', '1.0');
				$this->setHeader('Content-Type', 'text/html; charset="UTF-8"');
				$this->setHeader('Content-Transfer-Encoding', 'quoted-printable');
				$body = $this->htmlBody;
				
			} elseif( $this->isText() ) {
				$this->setHeader('MIME-Version', '');
				$this->setHeader('Content-Type', 'text/plain; charset="UTF-8"');
				$this->setHeader('Content-Transfer-Encoding', 'quoted-printable');
				$body = $this->textBody;
			}
		}
		if( empty($body) ) {
			throw new RuntimeException('emptyMailBody');
		}
		
		$headers = '';
		foreach( $this->headers as $headerName => $headerValue ) {
			if( !empty($headerValue) ) {
				$headers .= "{$headerName}: {$headerValue}\r\n";
			}
		}
		$headers .= "\r\n";
		if( !is_array($toAddress) ) {
			if( !mail($toAddress, $this->subject, $body, $headers) ) {
				throw new RuntimeException("issueSendingEmail");
			}
		} else {
			foreach( array_unique($toAddress) as $MailToData ) {
				$MailToEmail = '';
				if( self::is_email($MailToData) ) {
					$MailToEmail = $MailToData;
					
					//More compatibilities with array of data.
				} elseif( is_array($MailToData) ) {
					if( !empty($MailToData['mail']) && self::is_email($MailToData['mail']) ) {
						$MailToEmail = $MailToData['mail'];
					} elseif( !empty($MailToData['email']) && self::is_email($MailToData['email']) ) {
						$MailToEmail = $MailToData['email'];
					}
				}
				if( empty($MailToEmail) ) {
					continue;
				}
				
				if( !mail($MailToEmail, $this->subject, $body, $headers) ) {
					throw new RuntimeException('issueSendingEmail');
				}
			}
		}
	}
	
	/**
	 * Check if this mail contains multiple contents
	 *
	 * @return boolean True if this object contains multiple contents
	 */
	public function hasMultiplesContents(): bool {
		return ($this->isHtml() + $this->isText() + $this->containsFiles()) > 1;
	}
	
	/**
	 * Check if this mail is a HTML mail
	 *
	 * @return boolean True if this object has an HTML message
	 */
	public function isHtml(): bool {
		return !empty($this->htmlBody);
	}
	
	/**
	 * Check if this mail is a TEXT mail
	 *
	 * @return boolean True if this object has a TEXT message
	 */
	public function isText(): bool {
		return !empty($this->textBody);
	}
	
	/**
	 * Check if the file list contains any file
	 *
	 * @return boolean True if the file list is not empty
	 */
	public function containsFiles(): bool {
		return !!$this->attachedFiles;
	}
	
	/**
	 * Get a boundary
	 *
	 * @param int $boundaryInd The index of the boundary to get. Default value is 0.
	 * @return string The value of the boundary.
	 */
	public function getBoundary(int $boundaryInd = 0): string {
		if( empty($this->mimeBoundary[$boundaryInd]) ) {
			$this->mimeBoundary[$boundaryInd] = 'ORPHEUS_' . md5(microtime(1) + $boundaryInd);
		}
		
		return $this->mimeBoundary[$boundaryInd];
	}
	
	/**
	 * Check if this mail is an alternative mail
	 *
	 * @return boolean True if this object has an alternative message
	 */
	public function isAlternative(): bool {
		return !empty($this->altBody);
	}
	
	/**
	 * Get the mime type of file
	 *
	 * @param string $filename The file name
	 * @return string The mime type of the file
	 */
	public static function getMimeType(string $filename): string {
		if( function_exists('finfo_open') ) {
			$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
			
			return finfo_file($fileInfo, $filename);
		}
		return mime_content_type($filename);
	}
	
	/**
	 * Check if the given mail address is valid
	 *
	 * @param string $email The email address
	 * @return boolean True if this email is valid
	 */
	public static function is_email(string $email): bool {
		return is_email($email);
	}
}
