<?php
/**
 * Created by PhpStorm.
 * User: tsraveling
 * Date: 1/19/14
 * Time: 10:11 PM
 */
require("bootstrap.php");

setupInputs("Save Object Details",$_GET["id"],"objects");
registerInput("Title","title");
registerInput("Comments","comments",Input::TEXTFIELD,"",false);

$defparent = 0;
if (isset($_GET["parent"]))$defparent=$_GET["parent"];

$defproject = 0;
if (isset($_GET["project"]))$defproject=$_GET["project"];

registerInput("Parent","parent",Input::HIDDEN,$defparent);
registerInput("Project","project",INPUT::HIDDEN,$defproject);
postInputs();

start_header("Edit Object");
end_header();

if ($editingRes)$objectname=$editingRes["title"];
else $objectname = "New Object";

startBlock($objectname);

if ($editingRes) {
    if ($editingRes["parent"]==0)
        doLink("Back to Project","project.php?id=".$editingRes["project"]);
    else
        doLink("Back to Parent","object.php?id=".$editingRes["parent"]);
} else {
    doLink("Cancel and back to Parent","object.php?id=".$_GET["parent"]);
}

endBlock();

drawInputs();

// VARIABLES
startBlock("Variables");
if (!$editingRes)echo "<em>You have to save this object before you can add variables to it.</em>";
endBlock();

if ($editingRes) {
?>

<div id="var-holder"></div>

<script type="text/javascript">
    $(document).ready(function(){
        $.ajax({
            url:"object-ajax.php",
            type:"post",
            data:{
                parent:<?php echo $editingRes["id"]; ?>
            }
        }).done(function(data){
               $("#var-holder").html(data);
            });
    });
</script>

<?php
}

// CHILD OBJECTS

startBlock("Child Objects");

if ($editingRes) {
    $stmt=$DBH->prepare("SELECT * FROM objects WHERE parent=:uid ORDER BY title");
    $stmt->bindParam(":uid",$_GET["id"],PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount()>0) {
        echo "<ul>";
        while ($res = $stmt->fetch())
        {
            recursiveObject($res);
        }
        echo "</ul>";
    }
    doLink("Add New Object","object.php?id=new&parent=".$editingRes["id"]."&project=".$editingRes["project"]);
} else {
    echo "<em>You have to save this object before you can add child objects to it.</em>";
}
endBlock();

if ($editingRes) {
    startBlock("Defaults");
    ?>

    <div id="default-box">

    </div>

    <script type="text/javascript">
        $(document).ready(function(){
            $.ajax({
                url:"default-ajax.php",
                type:"post",
                data: {
                    objectid:<?php echo $editingRes["id"]; ?>
                }
            }).done(function(data){
                $("#default-box").html(data);
            });
        });
    </script>

    <?php
    endBlock();
}

if ($editingRes) {
    // COMPILE
    startBlock("Compile");
    doLink("Review Object and Children","review.php?id=".$editingRes["project"]."#".makeClassName(objectNameForId($_GET["id"])));
    doLink("Compile Object and Children to Objective-C","compile.php?id=".$editingRes["project"]."&object=".$_GET["id"]);
    endBlock();
}

do_footer();

?>