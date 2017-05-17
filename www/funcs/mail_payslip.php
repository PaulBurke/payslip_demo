<?php
require_once("functions.php");

if(!emailCheck::test($_POST['email']))
{
	print json_encode([
						'error' => 1,
						'message' => $_POST['email']." is not a valid e-mail address.\nPlease enter a valid e-mail address before continuing"
						]);
	exit;
}

if($_POST['empid'] == "all")
{
	$employee = new employee;

	if(!$results = $employee->readAll())
	{
		print $employee->error->json();
		exit;
	}

	$arr = [];

	while($results->fetch())
	{
		$arr[] = $employee->id;
	}
}else{
	$arr = [$_POST['empid']];
}

$mail = new email;
$mail->to = $_POST['email'];

$subject = "Payslip for the ";

$start_date = new DateTime($_POST['start'], system_constants::getTimezone());
$end_date = new DateTime($_POST['end'], system_constants::getTimezone());

if($start_date->format("Y") != $end_date->format("Y"))
{
	$subject .= $start_date->format("jS \of F Y");
}else if($start_date->format("n") != $end_date->format("n")){
	$subject .= $start_date->format("jS \of F");
}else{
	$subject .= $start_date->format("jS");
}

$subject .= " to the ".$end_date->format("jS \of F Y");

$mail->subject = $subject;

$message = "Mail successfully sent for the following employee IDs:";

foreach($arr as $empid)
{
	$payslip = generatePayslip($empid, $_POST['start'], $_POST['end'], true);

	if($payslip['error'])
	{
		print json_encode($payslip);
		exit;
	}

	$mail->body = $payslip['result'];

	if(!$mail->send())
	{
		print $mail->error->json();
		exit;
	}

	$message .= "\n$empid";
}

print json_encode([
					'error' => 0,
					'message' => $message
					]);
?>