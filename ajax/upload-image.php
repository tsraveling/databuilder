<?php

require("../config.php");

$imagefile_name = $_FILES["image"]["name"];  
$imagefile_tmp = $_FILES["image"]["tmp_name"];  
$imagefile_size = $_FILES["image"]["size"];  
$filename = basename($_FILES["image"]["name"]);  
$file_ext = substr($filename, strrpos($filename, ".") + 1);  
$new_loc = IMAGEDIR.$imageName.".jpg";
				  
//Only process if the file is a JPG and below the allowed limit  
if((!empty($_FILES["image"])) && ($_FILES["image"]["error"] == 0)) {  
    if (($file_ext!="jpg") && ($userfile_size > $max_file)) {  
        $error= "ONLY jpeg images under 1MB are accepted for upload";  
    }  
}else{  
    $error= "Select a jpeg image for upload";  
}  
//Everything is ok, so we can upload the image.  
if (strlen($error)==0){  

    if (isset($_FILES["image"]["name"])){  

        move_uploaded_file($userfile_tmp, $new_loc);  
        chmod ($new_loc, 0777);  

          
    }  
} 

?>