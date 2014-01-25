<?php

class Input
{
	const TEXTFIELD 	= 1;
	const TEXTBOX 		= 2;
	const PASSWORD		= 3;
	const NEWPASSWORD 	= 4;
	const USERLEVEL		= 5;
	const IMAGE			= 6;
	const INT			= 7;
	const GALLERY		= 8;
    const HIDDEN        = 9;
    const VARTYPE       = 20;

	public $label;
	public $key;
	public $type;
	public $def;
	public $required;
	public $value;
	
	public function __construct($l, $k, $t = TEXTFIELD, $d = "", $req = true)
    {
        $this->label = $l;
        $this->key = $k;
        $this->type = $t;
        $this->def = $d;
        $this->required = $req;
    }
    
    public function preparePost()
    {
    	global $editingRes;
	    switch($this->type)
	    {
	    	case Input::IMAGE:
	    	
	    		// First, copy the image to the server
	    		if ( isset($_FILES[$this->key]) && $_FILES[$this->key]['tmp_name']!="") {
	    		
	    			// Grab the extension in case we need it later
	    			$ext = strtolower(pathinfo($_FILES[$this->key]['name'], PATHINFO_EXTENSION));
	    			
	    			// Grab the image data
	    			$data = file_get_contents($_FILES[$this->key]['tmp_name']);
					
					// Calculate the filename
					$fn = uniqid("img").".png";
					$path = IMAGEDIR . $fn;
					
					// Save Image
					move_uploaded_file($_FILES[$this->key]["tmp_name"],$path);
					
					// Set the value
					$this->value = LOCALIMAGEDIR . $fn;
				} else {
					if ($editingRes)$this->value=$editingRes[$this->key];
					else $this->value=$this->def;
				}
	    	break;
	    	case Input::INT:
	    		$this->value = intval($_POST[$this->key]);
	    	break;
	    	
	    	case Input::GALLERY:
	    		// First, copy the image to the server
	    		if ( isset($_FILES[$this->key."-upload"]) && $_FILES[$this->key."-upload"]['tmp_name']!="") {
	    		
	    			// Grab the extension in case we need it later
	    			$ext = strtolower(pathinfo($_FILES[$this->key."-upload"]['name'], PATHINFO_EXTENSION));
	    			
	    			// Grab the image data
	    			$data = file_get_contents($_FILES[$this->key."-upload"]['tmp_name']);
					
					// Calculate the filename
					$fn = uniqid("img").".png";
					$path = IMAGEDIR . $fn;
					
					// Save Image
					move_uploaded_file($_FILES[$this->key."-upload"]["tmp_name"],$path);
					
					// Set the value
					$nv = LOCALIMAGEDIR . $fn;
					if (strlen($_POST[$this->key])==0)$this->value=$nv;
					else $this->value=$_POST[$this->key]."\|".$nv;
				} else {
					$this->value = $_POST[$this->key];
				}
	    	break;
		    default:
		    	$this->value = $_POST[$this->key];
		    break;
	    }
    }
    
    public function drawSelf()
    {
    	global $editingRes;

        $def = $this->def;
        if ($editingRes)$def = stripslashes($editingRes[$this->key]);

        if ($this->type == Input::HIDDEN) {
            echo "<input type='hidden' name='".$this->key."' value=\"$def\"/>";
            return;
        }

    	echo "<tr><td class='label-cell'>".$this->label."</td><td class='field-cell'>";
    	

	    switch($this->type)
	    {
		    case Input::TEXTFIELD:
		    	echo "<input type='text' name='".$this->key."' value=\"$def\"/>";
		    break;
		    case Input::TEXTBOX:
		    	echo "<textarea name='".$this->key."'>$def</textarea>";
		    break;
		    case Input::PASSWORD:
		    	echo "<input type='password' name='".$this->key."'/>";
		    break;
		    case Input::NEWPASSWORD:
		    	?>
		    	
		    	<script type="text/javascript">
		    		$(document).ready(function(){
			    		$("#submit-button").submit(function(){
				    		if ($("input[name=<?php echo $this->key;?>]"))
			    		});
		    		});
		    	</script>
		    	
		    	<?php
			    echo "<input type='password' name='".$this->key."'/></td</tr>";
		    	echo "<tr><td class='label-cell'>Confirm ".$this->label."</td><td class='field-cell'>";
		    	echo "<input type='password' name='confirm-".$this->key."'/>";
		    break;
		    case Input::USERLEVEL:
		    	echo "<select name='".$this->key."'>";
		    	$n=0;
		    	while (getUserLevel($n)!="Unknown") {
		    		echo "<option value='$n' ";
		    		if ($def==$n)echo " SELECTED";
		    		echo ">".getUserLevel($n)."</option>";
			    	$n++;
		    	}
		    	echo "</select>";
		    break;
            case Input::IMAGE:
		    	$key = $this->key;
		    	$img = "images/ui/add_image.png";
		    	if ($def!="")$img=$def;
		    	echo "<img id='$key-image' src='$img' class='input-image'/>";
		    	echo "<input type='file' id='$key' accept='image/PNG' name='$key'/>";
		    	?>
		    	<script type="text/javascript"> 
		    	
		    	$(document).ready(function() {
		    	
		    		$("#<?php echo $key; ?>").change(function(){
			    	
					    // fadeOut or hide preview
					    $("#<?php echo $key; ?>-image").fadeOut();
					
					    // prepare HTML5 FileReader
					    var oFReader = new FileReader();
					    oFReader.readAsDataURL(document.getElementById("<?php echo $key; ?>").files[0]);
					
					    oFReader.onload = function (oFREvent) {
					         $("#<?php echo $key; ?>-image").attr('src', oFREvent.target.result).fadeIn();
					    };
					});
				});
		    	
		    	</script>
		    	<?php
		    break;
			case Input::INT:
				echo "<input type='text' name='".$this->key."' value='$def'/>";
			break;
			case Input::GALLERY: //findme
		    	$key = $this->key;
		    	
		    	// The Add Image image
		    	echo "<table style='width:100%'><tr><td>";
		    	echo "<img id='$key-image' src='images/ui/add_image.png' class='input-image'/>";
		    	echo "</td>";
		    	
		    	// The gallery
		    	echo "<td id='$key-gallery' class='gallery-cell'></td>";
		    			    	
		    	// The input
		    	echo "</tr><tr><td colspan='2'>";
		    	echo "<input type='hidden' id='$key' name='$key' value='$def'/>";
		    	echo "<input type='file' id='$key-upload' accept='image/PNG' name='$key-upload'/>";
		    	echo "</td></tr></table>";
		    	?>
		    	<script type="text/javascript"> 
		    	
		    	$(document).ready(function() {
		    	
		    		$.ajax({
		    			url:"ajax/gallery.php",
		    			type:"post",
		    			data:{
			    			array:$("#<?php echo $key; ?>").val()
		    			}
		    		}).done(function(data){
			    		$("#<?php echo $key; ?>-gallery").html(data);
		    		});
		    	
			    	$("#<?php echo $key; ?>-upload").change(function(){
			    		// fadeOut or hide preview
					    $("#<?php echo $key; ?>-image").fadeOut();
					
					    // prepare HTML5 FileReader
					    var oFReader = new FileReader();
					    oFReader.readAsDataURL(document.getElementById("<?php echo $key; ?>-upload").files[0]);
					
					    oFReader.onload = function (oFREvent) {
					         $("#<?php echo $key; ?>-image").attr('src', oFREvent.target.result).fadeIn();
					    };
					});
				});
		    	
		    	</script>
		    	<?php
		    break;
            case Input::VARTYPE:
                echo "<select name='".$this->key."'>";
                $n=0;
                while (variableType($n)!="Unknown") {
                    echo "<option value='$n' ";
                    if ($def==$n)echo " SELECTED";
                    echo ">".variableType($n)."</option>";
                    $n++;
                }
                echo "</select>";
                break;

		    default:break;
	    }
	    echo "</td></tr>";
    }
    
    public function echoValidationCode()
    {
    	if ($this->required) {
		    switch($this->type)
		    {
			    case Input::TEXTFIELD:
			    	echo "if ($('input[name=\"".$this->key."\"]').val().length<1)err+=' - ".$this->label." must have a value.\\n';";
			    break;
			    case Input::TEXTBOX:
			    	echo "if ($('textarea[name=\"".$this->key."\"]').val().length<1)err+=' - ".$this->label." must have a value.\\n';";
			    break;
			    case Input::PASSWORD:
			    
			    break;
			    case Input::NEWPASSWORD:
			    	echo "if ($('input[name=\"".$this->key."\"]').val().length<8)err+=' - ".$this->label." must be at least 8 characters long.\\n';";
			    break;
			    case Input::USERLEVEL:
			    break;
			    case Input::IMAGE:
			    	
			    break;
				case Input::INT:
					echo "if ($('input[name=\"".$this->key."\"]').val().length<0)err+=' - ".$this->label." must have a value.\\n';";
				break;
			    default:break;
			}
			echo "\n";
		}
    }
    
    public function postFormat()
    {
	    return $_POST[$this->key];
    }
    
    public function getPDOType()
    {
    	if ($this->type==Input::INT)return PDO::PARAM_INT;
	    return PDO::PARAM_STR;
    }
}

?>