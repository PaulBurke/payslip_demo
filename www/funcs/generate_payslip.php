<?php
require("ajax_test.php");
require_once("functions.php");

print json_encode(generatePayslip($_POST['empid'], $_POST['start'], $_POST['end']));

?>