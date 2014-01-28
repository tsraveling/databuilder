<?php
/**
 * Created by PhpStorm.
 * User: tsraveling
 * Date: 1/19/14
 * Time: 11:25 PM
 */

require("bootstrap.php");

setupInputs("Save Variable",$_GET["id"],"variables");
registerInput("Title","title");
registerInput("Type","kind",Input::VARTYPE);
registerInput("JSON Key","jsonkey");
registerInput("Comments","comments",Input::TEXTFIELD,"",false);
if ($editingRes["kind"]==0)
    registerInput("Defines","defines",Input::TEXTBOX,"",false);
if ($editingRes["kind"]!=4 && $editingRes["kind"]!=5)
    registerInput("Default","defaultval",Input::TEXTFIELD,"",false);
if ($editingRes["kind"]==4 || $editingRes["kind"]==5)
    registerInput("Class","class");
if ($editingRes)$parent=$editingRes["parent"];
else $parent=$_GET["parent"];
registerInput("Parent","parent",Input::HIDDEN,$parent);
$inc_def=1;
if ($editingRes["kind"]==4)$inc_def=0;
registerInput("Include in Instance","in_instance",Input::CHECKBOX,$inc_def);
registerInput("Include in Populater","in_populate",Input::CHECKBOX,$inc_def);

postInputs();

start_header("Edit Project");
?>

<script type="text/javascript">
    $(document).ready(function(){
        $("#class-type").change(function(){
            $("input[name=class]").val($(this).val());
        });
    });
</script>

<?php
end_header();

doLink("Back to Parent","object.php?id=".$editingRes["parent"]);

if ($editingRes["kind"]==4 || $editingRes["kind"]==5) {
    startBlock(variableType($editingRes["kind"])." Type");
    echo "<center>";


    $stmt = $DBH->prepare("SELECT * FROM objects WHERE id=".$parent);
    $stmt->execute();
    $objectparent = $stmt->fetch();
    $projectid = $objectparent->project;

    echo "<select id='class-type' style='width:200px;'>";
    echo "<option value='UIImage'>UIImage</option>";
    echo "<option value='TSRImage'>TSRImage</option>";
    echo "<option value='TSRGallery'>TSRGallery</option>";

    $stmt = $DBH->prepare("SELECT * FROM objects WHERE project=:project ORDER BY title");
    $stmt->bindParam(":project",$projectid,PDO::PARAM_INT);
    $stmt->execute();
    while ($res = $stmt->fetch()) {
        echo "<option value='".makeClassName($res->title)."'>".$res->title."</option>";
    }
    echo "</select>";
    echo "</center>";
    endBlock();
}

startBlock("\"".$editingRes["title"]."\" Traits");
endBlock();

drawInputs();



do_footer();
?>