<?php

// Project-specific functions
function requiresLogin()
{
    return false;
}

// Start the session
session_start();

require("config.php");

// Set up the database
try
{
  $DBH = new PDO('mysql:host='.$dbHost.';dbname='.$dbDatabase, $dbUsername, $dbPassword );
  $DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
  $DBH->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ );

  $GLOBALS["DBH"]=$DBH;
}
catch ( PDOException $e )
{
    die( $e->getMessage() );
}

// Get the timestamp for future reference
define( 'TIME', $_SERVER['REQUEST_TIME'] );

// Get various system variables
$currentFile = $_SERVER["SCRIPT_NAME"];
$parts = Explode('/', $currentFile);
$currentFile = $parts[count($parts) - 1];

$GLOBALS["currentfile"]=$currentFile;

if (requiresLogin()) {
    // Users
    $currentUser = new User;
    $currentUser->checkLoggedIn();

    // Use this part once we have a working website
    if (!isLoggedIn())
    {
        if ($currentFile!="login.php"&&$currentFile!="signup.php")
            header("location:login.php");
    }
    else
    {
        if ($currentUser->userLevel==User::ADMIN) {
            if ($currentFile=="login.php")header("location:index.php");
        } else {
            if ($currentFile!="standarduser.php")
                header("location:standarduser.php");
        }
    }
}

// Class autoloader
function __autoload( $class )
{
    require ROOTDIR . 'classes/' . $class . '.php';
}

// Functions to print header content
function start_header($title)
{
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
    <meta http-equiv="Content-type" content="text/html;" />
    <meta http-equiv="Content-Language" content="en-us" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?php echo $title; ?></title>

    <link rel="stylesheet" href="styles.css" type="text/css" media="screen" charset="utf-8" />

    <script type="text/javascript" src="scripts/jquery-1.7.1.js"></script>
    <script type="text/javascript" src="scripts/jquery.form.js"></script>

    <script type="text/javascript">
    	$(document).ready(function(){
	    	$("div.alert-text").fadeIn();
    	});
    </script>

<?php
}

function end_header()
{
	global $currentUser;
    ?>
	</head>
	<body>
	<div id="header" onclick="document.location = 'index.php';">
		<h1>DataBuilder</h1>
	</div>
	<center>
	<div id="content">


	<?php
}

// Function to print footer content
function do_footer()
{
   ?>

	</div></center>

	<div id="footer">
		All content and code copyright 2014 Tim Raveling
	</div>

	</body></html><?php
}

/* SECURITY */

function getSecret()
{
	global $sharedSecret;
	return md5($sharedSecret." ".date("m d y"));
}

/* LINKS */

function doLink($label,$url)
{
	echo "<a href='$url' class='link-button'>$label</a>";
}

/* VALUES */

function getUserLevel($l)
{
	if ($l==0)return "Standard";
	if ($l==1)return "Admin";
	return "Unknown";
}

/* MENUS */

$numColumns=1;

function drawMenuItem($label,$url,$class="")
{
	global $numColumns;
	echo "<tr onclick=\"window.location='$url';\" class='button'><td colspan='$numColumns'><div class='item-fill'>$label</div></td></tr>";
}

function drawMenuRow($label,$url,$id,$row1="",$row2="",$row3="")
{
	echo "<tr class='row' id='row-$id'><td class='label-col' onclick=\"window.location='$url';\">$label</td>";
	if ($row1!="")echo "<td>$row1</td>";
	if ($row2!="")echo "<td>$row2</td>";
	if ($row3!="")echo "<td>$row3</td>";
	echo "<td><div class='del-button' onclick='hitDeleteItem($id)'></div></td>";
	echo "</tr>";
}

function startBlock($title="",$hlevel=2,$align="center")
{
    echo "<div class='block $align'>";
    if ($title!="")echo "<h$hlevel>$title</h$hlevel>";
}

function endBlock()
{
    echo "</div>";
}

function startMenu($table,$title="",$row1="",$row2="",$row3="")
{
	?>

	<script type="text/javascript">

	function hitDeleteItem(iid)
	{
		if (confirm("Are you sure you want to delete this record?")) {
			$.ajax({
				url:"ajax/del-record.php",
				type:"post",
				data:{
					secret:"<?php echo getSecret(); ?>",
					table:"<?php echo $table; ?>",
					id:iid
				}
			}).done(function(data){
				if (data=="") {
					$("#row-"+iid).slideUp();
				} else {
					alert(data);
				}
			});
		}
	}

	</script>

	<?php
	global $numColumns;
	if ($row1!="")$numColumns=3;
	if ($row2!="")$numColumns=4;
	if ($row3!="")$numColumns=5;
	echo "<table class='linklist'>";
	if ($title!="") {
		echo "<tr class='header'><td>$title</td>";
		if ($row1!="")echo "<td>$row1</td>";
		if ($row2!="")echo "<td>$row2</td>";
		if ($row3!="")echo "<td>$row3</td>";
		echo "<td class='del-col'></td>";
		echo "</tr>";
	}
}

function endMenu()
{
	echo "</table>";
}

function startVerticalCenter()
{
	echo "<div class='vertical-center-wrapper'><div class='vertical-center'><div class='vertical-center-content'>";
}

function endVerticalCenter()
{
	echo "</div></div></div>";
}

/* INPUTS */

$inputArray = array();
$editingID	= null;
$editingRes = null;
$editingTable = null;
$submitButton = null;

function setupInputs($sub="Save",$id="x",$table="")
{
	global $DBH,$editingID,$editingRes,$editingTable,$inputArray,$submitButton;

	$inputArray		= null;
	$editingID 		= $id;
	$editingTable 	= $table;
	$submitButton	= $sub;

	if ($id=="x")return;
	if ($id!="new") {
		$stmt = $DBH->prepare("SELECT * FROM `$table` WHERE id=:id");
		$stmt->bindParam(":id",$id,PDO::PARAM_INT);
		$stmt->execute();
		$editingRes = $stmt->fetch(PDO::FETCH_ASSOC);
	}
}

function registerInput($label,$key,$type=Input::TEXTFIELD,$default="",$required=true)
{
	global $inputArray;

	$input = new Input($label,$key,$type,$default,$required);
	$inputArray[] = $input;
}

function drawInputs()
{
	global $inputArray,$editingID,$DBH,$submitButton;

	if (isset($_GET["saved"])) {
		if ($_GET["saved"]==2)
			echo "<div class='alert-text' style='display:none;'>New Record Added</div>";
		else
			echo "<div class='alert-text' style='display:none;'>Record Saved!</div>";
	}

	?>

	<script type="text/javascript">

	function validateForm()
	{
		var err = "";

		<?php
		foreach ($inputArray as $input) {
			$input->echoValidationCode();
		}
		?>

		console.log(err);

		if (err!="") {
			alert("Please correct the following before continuing:\n\n"+err);
			return false;
		}
		return true;
	}

	</script>

	<?php

	echo "<form method='post' enctype='multipart/form-data'>";
	echo "<input type='hidden' name='id' value='".$editingID."'/>";
	echo "<table class='input-table'>";
	foreach ($inputArray as $input)
	{
		$input->drawSelf();
	}
	echo "</table>";
	echo "<input type='submit' id='submit-button' class='link-button' onclick='return validateForm();' value='$submitButton'/>";
	echo "</form>";
}

function inputsPosted()
{
	if (isset($_POST["id"]))return true;
	return false;
}

function postInputs()
{
	global $inputArray,$editingID,$editingTable,$DBH,$currentFile;
	if ($editingID=="")return;
	if (inputsPosted()) {

		foreach ($inputArray as $input) {
			$input->preparePost();
		}

		if ($_POST["id"] == "new") {
			$qi = "";
			$qv = "";
			foreach ($inputArray as $input) {
				if ($qi!="")$qi.=",";
				$qi.="`".$input->key."`";
				if ($qv!="")$qv.=",";
				$qv.=":".$input->key;
			}

			$q = "INSERT INTO `".$editingTable."` ($qi) VALUES ($qv)";
		} else {
			$qi="";
			foreach ($inputArray as $input) {
				if ($qi!="")$qi.=",";
				$qi.="`".$input->key."`=:".$input->key;
			}
			$q = "UPDATE `".$editingTable."` SET $qi WHERE id=$editingID";
		}
		$stmt = $DBH->prepare($q);
		foreach ($inputArray as $input) {
			$stmt->bindParam(":".$input->key,$input->value,$input->getPDOType());
		}
		$stmt->execute();

		if($_POST["id"]=="new") {
			$stmt = $DBH->prepare("SELECT id FROM `".$editingTable."` ORDER BY id DESC LIMIT 0,1");
			$stmt->execute();
			$res = $stmt->fetch();
			header("Location:".$currentFile."?id=".$res->id."&saved=2");
		} else {
			header("Location:".$currentFile."?id=".$_POST["id"]."&saved=1");
		}
	}
}

// LOCAL FUNCTIONS

function objectNameForId($id)
{
    global $DBH;
    $stmt=$DBH->prepare("SELECT * FROM objects WHERE id=:uid");
    $stmt->bindParam(":uid",$id,PDO::PARAM_INT);
    $stmt->execute();

    $res = $stmt->fetch();
    if ($res) {
        return $res->title;
    }
    return "Unknown";
}

function variableType($i)
{
    if ($i==0)return "Int";
    if ($i==1)return "Float";
    if ($i==2)return "String";
    if ($i==3)return "Date";
    if ($i==4)return "Array";
    if ($i==5)return "Object";
    if ($i==6)return "Boolean";
    if ($i==7)return "ID";
    return "Unknown";
}

function variableClassType($i)
{
    if ($i==0)return "NSNumber";
    if ($i==1)return "NSNumber";
    if ($i==2)return "NSString";
    if ($i==3)return "NSDate";
    if ($i==4)return "NSMutableArray";
    if ($i==5)return "x";
    if ($i==6)return "NSNumber";
    if ($i==7)return "NSString";
    return "Unknown";
}

function shorthandClassType($i)
{
    if ($i==0)return "int";
    if ($i==1)return "float";
    if ($i==6)return "BOOL";
    return variableClassType($i)."*";
}

function makeVarName($nm,$type)
{
    $ret = "";
    if ($type==0)$ret="n";
    if ($type==1)$ret="n";
    if ($type==2)$ret="s";
    if ($type==3)$ret="dt";
    if ($type==4)$ret="a";
    if ($type==5)$ret="o";
    if ($type==6)$ret="n";
    if ($type==7)$ret="s";

    $ret .= str_replace(" ","",$nm);

    return $ret;
}

function makeIDHandle($nm)
{
    $ret = str_replace(" ","",$nm);
    return "id".$ret;
}

function makeClassName($nm)
{
    $ret = "Dat";
    $ret .= str_replace(" ","",$nm);

    return $ret;
}

function recursiveObject($res)
{
    global $DBH;
    echo "<li><a href='object.php?id=".$res->id."'>".$res->title;
    $varstmt = $DBH->prepare("SELECT id FROM variables WHERE parent=:uid");
    $varstmt->bindParam(":uid",$res->id,PDO::PARAM_INT);
    $varstmt->execute();
    echo " (".$varstmt->rowCount().")";
    echo "</a>";

    $stmt=$DBH->prepare("SELECT * FROM objects WHERE parent=:uid ORDER BY title");
    $stmt->bindParam(":uid",$res->id,PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount()>0) {
        echo "<ul>";
        while ($sres = $stmt->fetch()) {
            recursiveObject($sres);
        }
        echo "</ul>";
    }

    echo "</li>";
}

function resForID($table,$id)
{
    global $DBH;
    $stmt = $DBH->prepare("SELECT * FROM $table WHERE id=:uid");
    $stmt->bindParam(":uid",$id,PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch();
}

function doTOC($project)
{
    global $DBH;
    echo "<div class='contents'>";
    $stmt=$DBH->prepare("SELECT * FROM objects WHERE parent=0 AND project=:uid ORDER BY title");
    $stmt->bindParam(":uid",$project,PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount()>0) {
        echo "<ul>";
        while ($res = $stmt->fetch())
        {
            recursiveContent($res);
        }
        echo "</ul>";
    }
    echo "</div>";
}

function recursiveContent($res)
{
    global $DBH;
    echo "<li><a href='object.php?id=".$res->id."'>".$res->title."</a>";

    $stmt=$DBH->prepare("SELECT * FROM objects WHERE parent=:uid ORDER BY title");
    $stmt->bindParam(":uid",$res->id,PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount()>0) {
        echo "<ul>";
        while ($sres = $stmt->fetch()) {
            recursiveContent($sres);
        }
        echo "</ul>";
    }

    echo "</li>";
}

function handleFromTitle($title)
{
    $title = str_replace(" ","",$title);
    $title = str_replace("&","",$title);
    $title = str_replace(".","",$title);
    return $title;
}

?>