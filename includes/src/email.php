<?php
// require_once('PHPMailer/PHPMailerAutoload.php');

class email
{
	public $subject;
	public $body;
	public $to;
	public $success = false;

	public function send()
	{
		$this->body = str_replace("<br>", "<br />", $this->body);

		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->isHTML(true);
		$mail->Host 		= email_constants::$host;
		$mail->Port 		= email_constants::$port;
		$mail->SMTPSecure 	= email_constants::$connection;
		$mail->SMTPAuth 	= email_constants::$smtpauth;
		$mail->Username 	= email_constants::$user;
		$mail->Password 	= email_constants::$password;
		$mail->setFrom(email_constants::$sender, email_constants::$sender_name);
		$mail->addReplyTo(email_constants::$sender, email_constants::$sender_name);
		$mail->CharSet 		= 'UTF-8;';
		$mail->WordWrap 	= 80;
		$mail->Subject 		= $this->subject;
		$mail->Body 		= $this->body;
		$mail->addAddress($this->to);

		try
		{
			if(!$mail->send())
			{
				$this->error = new errorAlert("email1",$mail->ErrorInfo,$_SERVER['PHP_SELF'],__LINE__);
				return false;
			}
		}catch(Exception $e){
			$this->error = new errorAlert("email2",$e->getMessage(),$_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		$this->success = true;
		return $this;
	}
}