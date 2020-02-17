<?php
/**
 * Email
 */

namespace Orpheus\Email;

use Exception;

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
	private $headers = [
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
	 * @var string
	 */
	private $htmlBody;
	
	/**
	 * The text body
	 *
	 * @var string
	 */
	private $textBody;
	
	/**
	 * The alternative body
	 *
	 * @var string
	 */
	private $altBody;
	
	/**
	 * Attached files to mail
	 *
	 * As list of filename
	 *
	 * @var array
	 */
	private $attachedFiles = [];
	
	/**
	 * The mail subject
	 *
	 * @var string
	 */
	private $subject;
	
	/**
	 * The mime boundary
	 *
	 * @var array
	 */
	private $mimeBoundary = [];
	
	//Methods
	
	/**
	 * Constructor
	 *
	 * @param string $subject The subject of the mail. Default value is an empty string.
	 * @param string $text The body of the message, used as text and html. Default value is an empty string.
	 */
	public function __construct($subject = '', $text = '') { //Class' Constructor
		$this->init();
		$this->setSubject($subject);
		$this->setText($text);
	}
	
	/**
	 * Initialize the object
	 */
	private function init() {
		$this->headers['Date'] = date('r');
		$allowReply = true;
		if( defined('REPLYEMAIL') ) {
			$sendEmail = REPLYEMAIL;
			$allowReply = false;
		} elseif( defined('ADMINEMAIL') ) {
			$sendEmail = ADMINEMAIL;
		} else {
			return;
		}
		if( defined('SITENAME') ) {
			$this->setSender($sendEmail, SITENAME, $allowReply);
		} else {
			$this->setSender($sendEmail, null, $allowReply);
		}
	}
	
	/**
	 * Set the Sender value of the mail
	 *
	 * @param string $senderEmail The email address to send this mail
	 * @param string $senderName The email address to send this mail. Default value is null.
	 * @param boolean $allowReply True to use this address as reply address. Default value is true.
	 *
	 * Set the Sender value of the mail.
	 * This function also sets the ReplyTo value if undefined.
	 * If a sender name is provided, it sets the "From" header to NOM \<EMAIL\>
	 */
	public function setSender($senderEmail, $senderName = null, $allowReply = true) {
		//=?utf-8?b?".base64_encode($from_name)."?= <".$from_a.">\r\n
		$this->setHeader('From', $senderName === null ? $senderEmail : static::escapeB64($senderName) . ' <' . $senderEmail . '>');
		$this->setHeader('Sender', $senderEmail);
		if( $allowReply && empty($this->headers['Return-Path']) ) {
			$this->setReplyTo($senderEmail);
		}
	}
	
	/**
	 * Set the value of a header
	 *
	 * @param string $key The key of the header to set.
	 * @param string $value The new value of the header.
	 */
	public function setHeader($key, $value) {
		$this->headers[$key] = $value;
	}
	
	/**
	 * Escape the string using base64 encoding
	 *
	 * @param string $string The string to escape
	 * @return    string The escaped string in base64
	 */
	public static function escapeB64($string) {
		return '=?UTF-8?B?' . base64_encode("$string") . '?=';
	}
	
	/**
	 * Set the ReplyTo value of the mail
	 *
	 * @param string $email The email address to send this mail
	 */
	public function setReplyTo($email) {
		$this->setHeader('Return-Path', $email);
		$this->setHeader('Reply-To', $email);
	}
	
	/**
	 * Set the subject of the mail
	 *
	 * @param string $subject The new subject
	 */
	public function setSubject($subject) {
		// If subject is too long, QP returns a bad string, it's working with b64.
		$this->subject = static::escapeB64($subject);// Supports UTF-8
	}
	
	/**
	 * Set the mail content
	 *
	 * @param string $text The new text for the mail contents
	 *
	 * Fills Text and HTML bodies from the given text
	 */
	public function setText($text) {
		if( !is_string($text) ) {
			throw new Exception('RequireStringParameter');
		}
		$this->setTEXTBody(strip_tags($text));
		$this->setHTMLBody(nl2br($text));
	}
	
	/**
	 * Set the text body of the mail
	 *
	 * @param string $body The new body
	 */
	public function setTEXTBody($body) {
		if( !is_string($body) ) {
			throw new Exception('RequireStringParameter');
		}
		$this->textBody = static::escape($body);
	}
	
	/**
	 * Escape the string for mails
	 *
	 * @param string $string The string to escape
	 * @return string The escaped string for mails
	 */
	public static function escape($string) {
		//It seems that utf8_encode() is not sufficient, it does not work, but UTF-8 do.
		return quoted_printable_encode((mb_detect_encoding($string, 'UTF-8') === 'UTF-8') ? $string : utf8_encode($string));
	}
	
	/**
	 * Set the html body of the mail
	 *
	 * @param string $body The new body
	 */
	public function setHTMLBody($body) {
		if( !is_string($body) ) {
			throw new Exception('RequireStringParameter');
		}
		$this->htmlBody = static::convHTMLBody($body);
	}
	
	/**
	 * Convert body to email-compliant HTML
	 *
	 * @param string $body
	 * @return string
	 */
	protected static function convHTMLBody($body) {
		// Supports UTF-8 and Quote printable encoding
		return static::escape(str_replace(["\r", "\n"], '', '<div dir="ltr">' . $body . '</div>'));
	}
	
	/**
	 * Add a file to the files list
	 *
	 * @param string $filename The file name
	 *
	 * Add $filename to the attached files list.
	 */
	public function addFile($filename) {
		if( $this->containsFile($filename) ) {
			throw new Exception('FileAlreadyContained');
		}
		$this->attachedFiles[] = $filename;
	}
	
	/**
	 * Check if this file is in the files list
	 *
	 * @param string $filename The file name
	 * @return boolean True if this file is in the attached files list
	 */
	public function containsFile($filename) {
		return in_array($filename, $this->attachedFiles);
	}
	
	/**
	 * Remove a file from the files list
	 *
	 * @param string $filename The file name
	 *
	 * Remove $filename from the attached files list.
	 */
	public function removeFile($filename) {
		if( ($key = array_search($filename, $this->attachedFiles)) === false ) {
			throw new Exception('FileNotContained');
		}
		unset($this->attachedFiles[$key]);
	}
	
	/**
	 * Set the alternative body of the mail
	 *
	 * @param string $body The new body.
	 */
	public function setAltBody($body) {
		if( !is_string($body) ) {
			throw new Exception('RequireStringParameter');
		}
		$this->altBody = $body;
	}
	
	/**
	 * Send the mail to the given address
	 * You can pass an array of address to send it to multiple recipients.
	 *
	 * @param string $toAddress The email address to send this mail
	 * @throws Exception
	 */
	public function send($toAddress) {
		if( empty($toAddress) ) {
			throw new Exception('InvalidEmailAddress');
		}
		if( $this->isMultiContent() ) {
			$boundary = $this->getBoundary();
			$this->setHeader('MIME-Version', '1.0');
			$this->setHeader('Content-Type', "multipart/alternative; boundary=\"{$boundary}\"");
			$body = '';
			$ContentsArr = [];
			if( $this->isAlternative() ) {
				$ContentsArr[] = [
					'headers' => [
						'Content-Type' => 'multipart/alternative',
					],
					'body'    => (mb_detect_encoding($this->altBody, 'UTF-8') === 'UTF-8') ? utf8_decode($this->altBody) : $this->altBody,
				];
			}
			
			if( $this->isTEXT() ) {
				$ContentsArr[] = [
					'headers' => [
						'Content-Type'              => 'text/plain; charset="UTF-8"',
						'Content-Transfer-Encoding' => 'quoted-printable',
					],
					'body'    => $this->textBody,
				];
			}
			
			if( $this->isHTML() ) {
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
						throw new Exception('ContentRequireHeaders');
					}
					if( empty($Content['body']) ) {
						throw new Exception('ContentRequireBody');
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
			if( $this->isHTML() ) {
				$this->setHeader('MIME-Version', '1.0');
				$this->setHeader('Content-Type', 'text/html; charset="UTF-8"');
				$this->setHeader('Content-Transfer-Encoding', 'quoted-printable');
				$body = $this->htmlBody;
				
			} elseif( $this->isTEXT() ) {
				$this->setHeader('MIME-Version', '');
				$this->setHeader('Content-Type', 'text/plain; charset="UTF-8"');
				$this->setHeader('Content-Transfer-Encoding', 'quoted-printable');
				$body = $this->textBody;
			}
		}
		if( empty($body) ) {
			throw new Exception('emptyMailBody');
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
				throw new Exception("issueSendingEmail");
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
					throw new Exception('issueSendingEmail');
				}
			}
		}
		return true;
	}
	
	/**
	 * Check if this mail contains mutiple contents
	 *
	 * @return boolean True if this object contains multiple contents
	 */
	public function isMultiContent() {
		return ($this->isHTML() + $this->isTEXT() + $this->containsFiles()) > 1;
	}
	
	/**
	 * Check if this mail is a HTML mail
	 *
	 * @return boolean True if this object has a HTML message
	 */
	public function isHTML() {
		return !empty($this->htmlBody);
	}
	
	/**
	 * Check if this mail is a TEXT mail
	 *
	 * @return boolean True if this object has a TEXT message
	 */
	public function isTEXT() {
		return !empty($this->textBody);
	}
	
	/**
	 * Check if the file list contains any file
	 *
	 * @return boolean True if the file list is not empty
	 *
	 * Check if the file list is not empty.
	 */
	public function containsFiles() {
		return !empty($this->attachedFiles);
	}
	
	/**
	 * Get a boundary
	 *
	 * @param integer $boundaryInd The index of the boundary to get. Default value is 0.
	 * @return string The value of the boundary.
	 */
	public function getBoundary($boundaryInd = 0) {
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
	public function isAlternative() {
		return !empty($this->altBody);
	}
	
	/**
	 * Get the mime type of a file
	 *
	 * @param string $filename The file name
	 * @return string The mime type of the file
	 */
	public static function getMimeType($filename) {
		if( function_exists('finfo_open') ) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			return finfo_file($finfo, $filename);
		}
		return mime_content_type($filename);
	}
	
	/**
	 * Check if the given mail address is valid
	 *
	 * @param string $email The email address
	 * @return boolean True if this email is valid
	 */
	public static function is_email($email) {
		return is_email($email);
	}
}
