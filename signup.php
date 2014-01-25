<?php
	require("bootstrap.php");
	
	if (inputsPosted()) {
		$user = new User();
		$user->firstName=$_POST["firstname"];
		$user->lastName=$_POST["lastname"];
		$user->userEmail=$_POST["email"];
		$user->registerAsNewUser($_POST["password"]);
		header("location:index.php");
	}
	
	setupInputs("Sign Up");
	registerInput("Email","email");
	registerInput("First Name","firstname");
	registerInput("Last Name","lastname");
	registerInput("Password","password",Input::NEWPASSWORD);
	
	start_header("Sign Up");
	end_header();

	drawInputs();

	do_footer();
?>