<?php
	namespace model;

	use Swagger\Annotations as SWG;

	require_once('/media/NAS/www/secured_files/dbConnect.php');
	require_once(dirname(__FILE__) . '/../include/quoteSmart.php');
	require_once(dirname(__FILE__) . '/../include/commonFunctions.php');

	/**
	 * @SWG\Model(id="User")
	 */
	class User {
		/**
		 * @SWG\Property(name="userId",type="string")
		 */
		public $userId;
		/**
		 * @SWG\Property(name="firstName",type="string")
		 */
		public $firstName;
		/**
		 * @SWG\Property(name="lastName",type="string")
		 */
		public $lastName;
		/**
		 * @SWG\Property(name="email",type="string")
		 */
		public $email;
		/**
		 * @SWG\Property(name="phone",type="string")
		 */
		public $phone;
		/**
		 * @SWG\Property(name="userName",type="string")
		 */
		public $userName;
		/**
		 * @SWG\Property(name="password",type="string")
		 */
		public $password;
		/**
		 * @SWG\Property(name="level",type="integer")
		 */
		public $level;
		/**
		 * @SWG\Property(name="isPrimary",type="boolean")
		 */
		public $isPrimary;
		/**
		 * @SWG\Property(name="notification",type="string")
		 */
		public $notification;

		private $logger;
		private $success;
		private $status;
		private $message;


		function __construct(\Slim\Log $logger = null) {
			$logger->info("User->ctor()");

			$this->logger = $logger;
		}

		public function forgotPassword($email) {
			$this->logger->info("User->forgotPassword($email)");

			$this->success = false;
			$this->status = "failure";

			//! TODO: Business rules regarding required fields
			if (is_null($email)) {
				$this->message = "Missing input parameters.";
			}
			else {
				$result = $this->processPasswordRequest($email);
				if (is_string($result)) {
					$this->message = "Error retrieving email record: $result";
				}
				else {
					if ($result) {
						$this->success = $result;
						$this->status = "success";
						//! HACK: This now ties the API call to LeadSmall CMS. The API caller should handle this
						$this->message = "Thank you, your password has been sent.<br />Click <a href='https://leadsmall.rethinkgroup.org/cms/users/login'>here</a> to login";
					}
				}
			}

			return ["data"=>$this, "success"=>$this->success, "status"=>$this->status, "message"=>$this->message];
		}

		private function processPasswordRequest($email) {
			//! HACK: From v2/login/includes/function_forgetPass.php
			$this->logger->info("User->processPasswordRequest($email)");

			$result = false;

			$emailAddress = quote_smart($email);
			$query = "SELECT userID, userFirstName, userLastName, userEmail, userPhone, userLogin, userPass
						FROM users
						WHERE userEmail = '$emailAddress'";
			if($queryResult = mysql_query($query)) {
				$count = mysql_num_rows($queryResult);
				if ($count) {
					if ($count > 1) {
						$this->message = "This email appears to be linked to more than one user account.<br />Please contact Customer Service at (866) 343-4874 to review your account and reset your password.";
						$this->logger->warning("User->processPasswordRequest($email)->{$this->message}");
					}
					elseif ($count == 1) {
						$row = mysql_fetch_assoc($queryResult);
						extract($row);

						//! HACK: Fill in instantiated object
						$this->userId = $userID;
						$this->firstName = $userFirstName;
						$this->lastName = $userLastName;
						$this->email = $userEmail;
						$this->phone = $userPhone;
						$this->userName = $userLogin;
						$this->password = $userPass;

						$this->level = $userDetailValue;
						$this->isPrimary = isPrimary($userDetailValue);

						$failures = $this->generateEmail();
						if (is_null($failures)) {
							$result = true;
						}
						else {
							$this->message = "Failures: ".implode(", ", $failures);
							$this->logger->warning("User->processPasswordRequest($email)->{$this->message}");
						}

						// Mask the password at the last possible second
						$this->password = maskPassword($this->password);
					}
				}
				else {
					$this->message = "Your email ($email) was not found, Please try again.";
					$this->logger->warning("User->processPasswordRequest($email)->{$this->message}");
				}
			}
			else {
				$error = mysql_error();
				$this->logger->error("User->processPasswordRequest($email)->$error");
				//$error .= " || Raw SQL: $query";
				$result = $error;
			}

			return $result;
		}

		private function generateEmail() {
			$this->logger->info("User->generateEmail()");

			$result = null;

			$toAddress = ["{$this->email}" => "{$this->firstName} {$this->lastName}"];
			$fromAddress = "no-reply@rethinkgroup.org";
			$bounceAddress = "bounce@rethinkgroup.org";
			$subject = "reThink Account Username/Password Retrieve";

			// Build swiftmailer decorator array
			$replacements = [];
			$replacements[$this->email] = ["{subject}"=>$subject, "{userName}"=>$this->userName,
				"{password}"=>$this->password, "{url}"=>"https://leadsmall.rethinkgroup.org/cms/users/login",
				"{firstName}"=>$this->firstName, "{lastName}"=>$this->lastName,
				"{emailAddress}"=>$this->email];

			// Read the text template
			$fileName = dirname(__FILE__) . "/../templates/user.txt";
			$textBody = file_get_contents($fileName);
			$text = $textBody;

			// Read the HTML template
			$fileName = dirname(__FILE__) . "/../templates/user.html";
			$htmlBody = file_get_contents($fileName);
			$html = $htmlBody;

			$result = $this->sendEmail($fromAddress, $bounceAddress, $toAddress, $subject, $replacements,
				$text, $html);

			return $result;
		}

		private function sendEmail($fromAddress, $bounceAddress, $toAddress, $subject, $replacements, $text, $html) {
			$this->logger->info("User->sendEmail($fromAddress, $bounceAddress, $toAddress, $subject, $replacements, $text, $html)");

			$result = null;

			$message = \Swift_Message::newInstance();
			$message->setSubject($subject);
			$message->setFrom($fromAddress);
			$message->setReplyTo($fromAddress);
			$message->setReturnPath($bounceAddress);
			$message->setTo($toAddress);
			$message->setBody($text);
			$message->addPart($html, 'text/html');

			$transport = \Swift_MailTransport::newInstance(null);
			$mailer = \Swift_Mailer::newInstance($transport);

			// Setup decorator plugin
			$decorator = new \Swift_Plugins_DecoratorPlugin($replacements);
			$mailer->registerPlugin($decorator);

			if (!$mailer->send($message, $failures)) {
				$result = $failures;
			}

			return $result;
		}
	}
?>