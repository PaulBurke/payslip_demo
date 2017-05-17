<?php
require_once("autoloader.php");
require_once("functions.php");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Payslip Demo</title>
		<link rel="stylesheet" href="/css/bootstrap.min.css"/>
		<link rel="stylesheet" href="/css/font-awesome.min.css"/>
		<script src="/js/jquery-3.2.1.min.js"></script>

		<style>
			.attendance-day-off>td
			{
				background-color:#9ccc65;
			}

			.attendance-on-leave>td
			{
				background-color:#3F729B;
			}

			.attendance-absent>td
			{
				background-color:#d2322d;
			}

			#cog
			{
				text-align:center;
				position:fixed;
				left:37%;
				top:30%;
				width:26%;
				font-size:25vmin;
				z-index:5000;
			}

			#cog>i
			{
			   position:absolute;
			   width: 100%;
			   left:0;
			   color: #F0F0F6;
			}

			#cog>p
			{
			   width:100%;
			   position:absolute;
			   height:25vh;
			   text-align:center;
			   vertical-align:middle;
			   line-height:25vh;
			   font-size:3vmin;
			}

			#cog>canvas
			{
				position:absolute;
			   width:100%;
			   height:25vh;
			   left:0;
			   z-index:20;
			}

			#overlay {
				z-index:99;
				position:fixed;
				display:none; 
				background-color: rgba(0, 0, 0, 0.3);
				top: 0;
				left: 0;
				bottom: 0;
				right: 0;
			}
		</style>
	</head>
	<body>
<div id='overlay'>
	<div id="cog">
		<i class="fa fa-cog fa-spin"></i>
		<canvas id='canvas_loading'></canvas>
		<p></p>
	</div>
</div>

<?php

$form = new form;
$form->class = "form-inline";
$form->addProperty("onsubmit", "return false;");

if(!$select = $form->addElement(new dbSelect("employee", "id", "name")))
{
	print $select->error->json();
	exit;
}


$select->addSpacer();
$select->class = "form-control";
$select->label = "Employee:";
$select->id = "empid";

$start = $form->addElement(new input);
$start->addProperty("disabled");
$start->class = "form-control";

$end = $form->addElement(clone $start);

$start->label = "Start Date:";
$start->addProperty("value", "2017-03-01");
$start->id = "start_date";

$end->label = "End Date:";
$end->addProperty("value", "2017-03-31");
$end->id = "end_date";

$button = $form->addElement(new button);

$button->addFunction("onclick", "getPayslip");
$button->class = "btn btn-primary";
$button->addContent("Submit");

print $form->render();

// print "<br><br>";

// print generatePayslip(1002,"2017-03-01", "2017-03-31");

?>

<div id="report"></div>

<script>
	function getPayslip(el)
	{
		// var event = window.event;
		// event.stopPropagation();

		var empid = document.getElementById("empid");

		if(empid.value < 0)
		{
			alert("Please select an employee from the list before proceeding");
			return false;
		}

		var start = document.getElementById("start_date");
		var end = document.getElementById("end_date");

		working();

		$.ajax({
				url: 	"/funcs/generate_payslip.php",
				type: 	"POST",
				data: 	{'empid': empid.value, 'start': start.value, 'end': end.value},
				success: function(data_json)
				{
					try
					{
						var data = JSON.parse(data_json);
					}catch(e){
						alert("Data retrieval failure. Please try again.");
						stopWorking();
						return false;
					}

					if(data.error)
					{
						alert(data.message);
						stopWorking();
						return false;
					}

					document.getElementById("report").innerHTML = data.result;
					stopWorking();
					return false;
				},
				error: function()
				{
					alert("Something went wrong, please contact a system administrator.");
					stopWorking();
					return false;
				}

		});
	}

	function working()
	{
		document.getElementById("overlay").style.display = "block";
	}

	function stopWorking()
	{
		document.getElementById("overlay").style.display = "none";
	}

</script>

</body>
</html>