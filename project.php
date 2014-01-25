<?php
require("bootstrap.php");

setupInputs("Save Project Details",$_GET["id"],"projects");
registerInput("Title","title");
registerInput("Copyright","copyright");
postInputs();

start_header("Edit Project");
end_header();

$nm = "New Project";
if ($editingRes)$nm=$editingRes["title"];
startBlock($nm);
doLink("Home","index.php");
endBlock();

drawInputs();

// OBJECTS

if ($editingRes) {
    startBlock("Object Hierarchy");
    $stmt=$DBH->prepare("SELECT * FROM objects WHERE parent=0 AND project=:uid ORDER BY title");
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
    //doLink("Add New Object","object.php?id=new&parent=0&project=".$editingRes["id"]);
    endBlock();

    startBlock("Object List");
    startMenu("objects","Object","Parent");

    $stmt=$DBH->prepare("SELECT * FROM objects WHERE project=:uid ORDER BY title");
    $stmt->bindParam(":uid",$_GET["id"],PDO::PARAM_INT);
    $stmt->execute();

    while ($res = $stmt->fetch()) {
        $parent = "Project Root";
        if ($res->parent>0) {
            $parent = objectNameForId($res->parent);
        }

        drawMenuRow($res->title,"object.php?id=".$res->id,$res->id,$parent);
    }

    endMenu();
    doLink("Add New Object at Project Root","object.php?id=new&parent=0&project=".$editingRes["id"]);
    endBlock();

} else {
    startBlock("Objects");
    echo "<em>You have to save this project before you can add child objects to it.</em>";
    endBlock();
}

// COMPILE
startBlock("Compile");
doLink("Review Project","review.php?id=".$_GET["id"]);
doLink("Compile to Objective-C","compile.php?id=".$_GET["id"]);
endBlock();

do_footer();

?>