<?php

	require("../bootstrap.php");
	
	if (isset($_POST["secret"])) {
		if ($_POST["secret"] == getSecret()) {
			
			if (isset($_POST["table"]) && isset($_POST["id"])) {
				$stmt = $DBH->prepare("DELETE FROM ".$_POST["table"]." WHERE id=:id");
				$stmt->bindParam(":id",$_POST["id"],PDO::PARAM_INT);
				$stmt->execute();
				exit();
			}
			
		}
	}
	
	echo "Unable to locate record.";

?>