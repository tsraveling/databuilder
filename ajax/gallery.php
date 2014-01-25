<?php

if (isset($_POST["array"])) {
	
	$artx = $_POST["array"];
	
	if (strlen($artx)>0) {
    	$imgs = split("\|", $artx);
    	foreach ($imgs as $img) {
	    	echo "<img src='$img' class='input-image'/>";
    	}
    } else {
	    echo "<em>No images in gallery</em>";
    }
	
}

?>