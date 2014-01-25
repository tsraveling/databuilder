<?php
	require("bootstrap.php");
	
	if (inputsPosted()) {
		$user = new User();
		$user->authenticate($_POST["email"],$_POST["password"]);
		if (isLoggedIn()) {
			header("location:index.php");
		}
	}
	
	setupInputs("Login");
	registerInput("Email","email");
	registerInput("Password","password",Input::PASSWORD);
	
	start_header("Login");
	end_header();
	
	drawInputs();
	if (defined("LOGIN_ERROR"))
		echo "<p>".LOGIN_ERROR."</p>";

	doLink("Sign Up","signup.php");

	do_footer();
?>