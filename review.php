<?php
/**
 * Created by PhpStorm.
 * User: tsraveling
 * Date: 1/21/14
 * Time: 6:20 PM
 */

require("bootstrap.php");

function lout($tx)
{
    echo "<div class='logtext'>$tx</div>";
}

function stout($label,$val)
{
    echo "<div class='stat-label'>$label</div>$val<br/>";
}

$projectName="Invalid";
$projectCopyright="None";
$today = date("F j, Y");

function makeFileHeader($fname)
{
    global $projectName,$today,$projectCopyright;
    return "//§".
    "//  $fname §".
    "//  $projectName §".
    "//  §".
    "//  Created by Timothy Raveling on $today.§".
    "//  Copyright (c) $projectCopyright. All rights reserved.§".
    "//  §";
}

start_header("Review");
end_header();

$parentObject = 0;
if (isset($_GET["object"]))
    $parentObject = $_GET["object"];

if (isset($_GET["id"])) {

    $stmt = $DBH->prepare("SELECT * FROM projects WHERE id=:uid");
    $stmt->bindParam(":uid",$_GET["id"],PDO::PARAM_INT);
    $stmt->execute();

    if ($project = $stmt->fetch()) {

        echo "<div class='contents'>";
        $stmt=$DBH->prepare("SELECT * FROM objects WHERE parent=0 AND project=:uid ORDER BY title");
        $stmt->bindParam(":uid",$_GET["id"],PDO::PARAM_INT);
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

        $projectName = $project->title;
        $projectCopyright = $project->copyright;

        $title = "Reviewing $projectName";
        if ($parentObject>0) {
            $title .= ": ".objectNameForId($parentObject);
        }
        startBlock($title, 1);

        stout("Project Title: ",$projectName);
        stout("Copyright: ",$projectCopyright);

        $stmt = $DBH->prepare("SELECT id FROM objects WHERE project=:uid");
        $stmt->bindParam(":uid",$_GET["id"],PDO::PARAM_INT);
        $stmt->execute();

        stout("Objects: ",$stmt->rowCount());

        endBlock();

        if ($parentObject!=0) {
            $ostmt = $DBH->prepare("SELECT * FROM objects WHERE project=:pid AND id=:parid");
            $ostmt->bindParam(":pid",$project->id,PDO::PARAM_INT);
            $ostmt->bindParam(":parid",$parentObject,PDO::PARAM_INT);
            $ostmt->execute();
        } else {
            $ostmt = $DBH->prepare("SELECT * FROM objects WHERE project=:pid AND parent=0");
            $ostmt->bindParam(":pid",$project->id,PDO::PARAM_INT);
            $ostmt->execute();
        }

        while ($object = $ostmt->fetch()) {
            recursiveReview($object,2);
        }

        // Fill the DataManager, if we're compiling the whole project
        if ($parentObject==0) {
            startBlock("DataManager insert");

            lout("Will add saveSelf, init from file, and coding.");

            endBlock();
        }

        startBlock("Complete!");
        if (isset($_GET["object"]))
            doLink("Back to object","object.php?id=".$_GET["object"]);
        else
            doLink("Back to project","project.php?id=".$_GET["id"]);
        endBlock();

    } else {
        echo "Invalid.";
    }


} else {
    echo "Invalid.";
}

function recursiveReview($res,$level)
{
    global $DBH;

    // Compile the object
    $title = $res->title;

    $classname = makeClassName($res->title);
    echo "<a name='$classname'></a>";

    $uselev = $level;
    if ($uselev>5)$uselev = 5;


    if ($level>2)
        startBlock($title,$uselev,"right");
    else
        startBlock($title,$uselev);

    reviewObject($res,$level);

    $stmt=$DBH->prepare("SELECT * FROM objects WHERE parent=:uid ORDER BY title");
    $stmt->bindParam(":uid",$res->id,PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount()>0) {
        while ($sres = $stmt->fetch()) {
            recursiveReview($sres,$level+1);
        }
    }
    endBlock();
}

function reviewObject($object,$level) {
    global $DBH;


    $classname = makeClassName($object->title);

    stout("Class Name: ",$classname);
    stout("Link: ","<a href='object.php?id=".$object->id."' target='_blank'>".$object->title."</a>");
    stout("Comments: ",$object->comments);

    echo "<div class='header'>Variables:</div>";
    echo "<table>";
    $var_stmt = $DBH->prepare("SELECT * FROM variables WHERE parent=".$object->id." ORDER BY kind,title");
    $var_stmt->execute();
    while ($variable = $var_stmt->fetch()) {
        echo "<tr><td class='stat-label'><a href='variable.php?id=".$variable->id."' target='_blank'>".$variable->title."</a></td><td class='stat-cell'>";

        $varc = variableClassType($variable->kind);
        if ($varc=="x")$varc=$variable->class;
        else $varc = variableType($variable->kind);

        if ($variable->class!="")
            $classlink = "<a href='#".$variable->class."'>".$variable->class."</a>";

        if ($variable->kind==4) {
            if ($variable->class!="")
                echo "Array[$classlink]";
            else
                echo "<span style='color:red;'>Array[".$variable->class."]</span>";
        } else {
            if ($variable->kind==5) {
                if ($variable->class=="")
                    echo "<span style='color:red;'>Empty Object Pointer</span>";
                else
                    echo $classlink;
            } else
                echo $varc;
        }

        if ($variable->defaultval!="") echo " = ".$variable->defaultval;

        if ($variable->defines!="")echo "<br/><em class='note'>#defines: ".$variable->defines."</em>";
        if ($variable->comments!="")echo "<br/><em class='note'>// ".$variable->comments."</em>";
    }
    echo "</table>";
}

function recursiveContent($res)
{
    global $DBH;
    echo "<li><a href='#".makeClassName($res->title)."'>".$res->title."</a>";

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

do_footer();

?>