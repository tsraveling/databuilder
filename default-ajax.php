<?php

require("bootstrap.php");

if (isset($_POST["objectid"])) {

    $newID = 0;
    $stmt = $DBH->prepare("SELECT * FROM defaults WHERE parent_object=:objectid");
    $stmt->bindParam(":objectid",$_POST["objectid"],PDO::PARAM_INT);
    $stmt->execute();
    while ($res = $stmt->fetch()) {
        if ($res->uid>=$newID)
            $newID=$res->uid+1;
    }

    // Handle special requests
    if (isset($_POST["request"])) {

        // Grab the form for a given row
        if ($_POST["request"] == "rowform") {
            $varpar="";
            $defpar="";
            if (isset($_POST["varpar"])) {
                $varpar = $_POST["varpar"];
                $defpar = $_POST["defpar"];
            }
            $defaultid = $_POST["defaultid"];
            echo buildForm($_POST["objectid"],$defaultid,$varpar,$defpar);
        }

        // Fill a row with its default
        if ($_POST["request"] == "rowdefault") {
            $defaultid = $_POST["defaultid"];
            echo buildRow($_POST["objectid"],$defaultid);
        }

        // Delete a default
        if ($_POST["request"] == "delete") {
            $delid = $_POST["del"];

            // Delete the subvars
            $stmt = $DBH->prepare("DELETE FROM defaultvar WHERE parent_default=:did");
            $stmt->bindParam(":did",$delid,PDO::PARAM_INT);
            $stmt->execute();

            // Delete the default itself
            $stmt = $DBH->prepare("DELETE FROM defaults WHERE id=:did");
            $stmt->bindParam(":did",$delid,PDO::PARAM_INT);
            $stmt->execute();
        }

        // Save the data from a row
        if ($_POST["request"] == "saverow") {

            $objectid = $_POST["objectid"];
            $defaultid = $_POST["defaultid"];
            $formvalues = $_POST["formvalues"];
            $defpar = $_POST["defpar"];
            $varpar = $_POST["varpar"];
            $vals = json_decode($formvalues,true);

            $title = "";
            $uid = "";
            $varquery = "";

            // If this is a new default, insert it into the db and get the id
            if ($defaultid == "new") {

                if ($defpar == -1) {
                    $stmt = $DBH->prepare("INSERT INTO defaults (parent_object) VALUES (:parob)");
                    $stmt->bindParam(":parob",$objectid,PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    $stmt = $DBH->prepare("INSERT INTO defaults (parent_default,parent_variable) VALUES (:defpar,:varpar)");
                    $stmt->bindParam(":defpar",$defpar,PDO::PARAM_INT);
                    $stmt->bindParam(":varpar",$varpar,PDO::PARAM_INT);
                    $stmt->execute();
                }

                $defaultid = $DBH->lastInsertId();
            }

            // Delete any old matches
            $stmt = $DBH->prepare("DELETE FROM defaultvar WHERE parent_default=:pid");
            $stmt->bindParam(":pid",$defaultid,PDO::PARAM_INT);
            $stmt->execute();

            // Loop through the values and insert them into the db
            foreach ($vals as $val) {

                $varid = intval($val["varid"]);

                $stmt = $DBH->prepare("INSERT INTO defaultvar (parent_default,parent_var,val) VALUES (:pardef,:varpar,:val)");
                $stmt->bindParam(":pardef",$defaultid,PDO::PARAM_INT);
                $stmt->bindParam(":varpar",$varid,PDO::PARAM_INT);
                $stmt->bindParam(":val",$val["value"],PDO::PARAM_STR);
                $stmt->execute();

                if ($val["varname"]=="sTitle")$title = $val["value"];
                if ($val["varname"]=="sUID")$uid = intval($val["value"]);
            }

            // Finally, update the record itself
            $stmt = $DBH->prepare("UPDATE defaults SET title=:title,uid=:uid WHERE id=:did");
            $stmt->bindParam(":title",$title,PDO::PARAM_STR);
            $stmt->bindParam(":uid",$uid,PDO::PARAM_INT);
            $stmt->bindParam(":did",$defaultid,PDO::PARAM_INT);
            $stmt->execute();

            // Return the row
            echo buildRow($_POST["objectid"],$defaultid);
        }

        exit();
    }

    // A flat request just builds the whole thing, so carry on!

    ?>

    <script type="text/javascript">

        function ajaxFill(element,data,done)
        {
            $.ajax({
                url:"default-ajax.php",
                type:"post",
                data:data
            }).done(function(dat){
                element.html(dat);
                done();
            });
        }

        function deselectForms()
        {
            $("div.var-row.open").each(function(){
                var th = $(this);
                ajaxFill($(this),{
                    objectid:$(this).attr("objectid"),
                    defaultid:$(this).attr("defaultid"),
                    request:"rowdefault"
                },function(){
                    th.removeClass("open");
                });
            });

            $("#new-subob").remove();
        }

        function reloadSelf()
        {
            $.ajax({
                url:"default-ajax.php",
                type:"post",
                data:{
                    objectid:<?php echo $_POST["objectid"]; ?>
                }
            }).done(function(dt){
                    $("#default-box").html(dt);
                });
        }

        function addSubobject(pardef,varpar)
        {
            // Stop the mouse propagation
            var event = arguments[2] || window.event;
            event.stopPropagation();

            // Get rid of other views
            deselectForms();

            // Insert the form
            var html = "<div class='var-row' id='new-subob' objectid='<?php echo $_POST["objectid"]; ?>' defpar='"+pardef+"' varpar='"+varpar+"' defaultid='new'>x</div>";
            $("#def_"+pardef).after(html);
            var newrow = $("#new-subob");
            ajaxFill(newrow,{
                request:"rowform",
                objectid:newrow.attr("objectid"),
                defaultid:newrow.attr("defaultid"),
                varpar:varpar,
                defpar:pardef
            },function(){
                // findme
            });
        }

        function deleteDefault(defid)
        {
            // Stop the mouse propagation
            var event = arguments[2] || window.event;
            event.stopPropagation();

            if (confirm("Are you sure you want to delete this item?")) {
                $.ajax({
                    url:"default-ajax.php",
                    type:"post",
                    data:{
                        objectid:<?php echo $_POST["objectid"]; ?>,
                        request:"delete",
                        del:defid
                    }
                }).done(function(data){
                        reloadSelf();
                    });
            }
        }

        function saveSelf(buttonid)
        {
            // Grab the button
            var button = $("#formbutton"+buttonid);

            // Grab the parent row
            var parentrow = button.parent().parent();

            // Loop through the inputs and stick em in
            var formvalues = [];
            $(parentrow).find(".form-field").each(function(){
                var varname = $(this).attr("id");
                var varid = $(this).attr("varid");
                var val = $(this).val();
                var vartype = $(this).attr("vartype");
                formvalues.push({
                    varname:varname,
                    varid:varid,
                    value:val,
                    vartype:vartype
                });
            });

            // Grab the default id
            var defaultid = parentrow.attr("defaultid");
            var defpar = parentrow.attr("defpar");
            var varpar = parentrow.attr("varpar");

            // Compile the data
            var data = {
                request:"saverow",
                objectid:parentrow.attr("objectid"),
                defpar:defpar,
                varpar:varpar,
                defaultid:defaultid,
                formvalues:JSON.stringify(formvalues)
            };

            console.log(data);

            // Set up the target
            var targetrow = parentrow;
            ajaxFill(targetrow,data,function(){

                targetrow.removeClass("open");
                if (defaultid == "new") {
                    reloadSelf();
                }
            });
        }

        $(document).ready(function(){

            // Clicking on a row turns it into a form.

            $("div.var-row").unbind("click");
            $("div.var-row").click(function(){

                // Then continue

                var th = $(this);
                var objectid = $(this).attr("objectid");
                var defaultid = $(this).attr("defaultid");
                var defpar = $(this).attr("defpar");
                var varpar = $(this).attr("varpar");
                if (!$(this).hasClass("open")) {

                    // First close other forms

                    deselectForms();

                    // Then continue

                    ajaxFill(th,{
                        objectid:objectid,
                        request:"rowform",
                        defaultid:defaultid,
                        defpar:defpar,
                        varpar:varpar
                    },function(){
                        th.addClass("open");
                        th.find("input").first().focus();
                    });

                }
            });

            // Hotkeys
            $(document).unbind("keyup");
            $(document).keyup(function(e) {

                // Hitting escape closes forms
                if (e.keyCode == 27) {
                    deselectForms();
                }

                if (e.keyCode == 13) {

                    $("#default-box").find("button").trigger("click");

                }

                if (e.keyCode == 187) {
                    $("#new-default").trigger("click");
                }
            });

        });
    </script>

    <?php

    $objectid = $_POST["objectid"];
    $object = resForID("objects",$objectid);

    $stmt = $DBH->prepare("SELECT * FROM variables WHERE parent=:oid");
    $stmt->bindParam(":oid",$object->id,PDO::PARAM_INT);
    $stmt->execute();

    echo "<div class='var-header-row'>";
    while ($res = $stmt->fetch())
    {
        if ($res->in_populate==1) {
            echo "<span class='input-span'";
            if ($res->kind==7 && $res->title=="UID")echo "style='width:40px;'";
            echo ">".$res->title."</span>";
        }
    }
    echo "</div>";

    $newID = 0;
    $stmt = $DBH->prepare("SELECT * FROM defaults WHERE parent_object=:objectid ORDER BY uid");
    $stmt->bindParam(":objectid",$objectid,PDO::PARAM_INT);
    $stmt->execute();
    while ($res = $stmt->fetch()) {
        recursiveDefault($res);
        $newID ++;

    }

    openRow("new-default","new");
    buildRow($objectid,"new");
    closeRow();

} else {
    echo "<em>No object supplied.</em>";
}

// Recursive build defaults
function recursiveDefault($res)
{
    global $objectid,$DBH;
    openRow("def_".$res->id,$res->id,$res->parent_default,$res->parent_variable);
    buildRow($objectid,$res->id);
    closeRow();

    $stmt = $DBH->prepare("SELECT * FROM defaults WHERE parent_default=:pardef ORDER BY parent_variable");
    $stmt->bindParam(":pardef",$res->id,PDO::PARAM_INT);
    $stmt->execute();

    while ($subres = $stmt->fetch()) {
        recursiveDefault($subres);
    }

}

// Builds a form for a row

$formsBuilt = 0;
function buildForm($objectid,$defaultid,$varpar="",$defpar="")
{
    global $DBH,$formsBuilt,$newID;
    $formsBuilt++;

    $object = resForID("objects",$objectid);
    $default = null;
    if ($defaultid!="new")
        $default = resForID("defaults",$defaultid);

    $variable = null;
    if ($default)$varpar = $default->parent_variable;
    if ($varpar!="" && $varpar!="0" && $varpar!="-1") {
        $variable = resForID("variables",$varpar);
        echo "<span class='input-span'> -> ".makeVarName($variable->title,$variable->kind)."</span>";
        $stmt = $DBH->prepare("SELECT * FROM objects WHERE project=:project");
        $stmt->bindParam(":project",$object->project,PDO::PARAM_STR);
        $stmt->execute();
        while ($res = $stmt->fetch()) {
            if ($variable->class == makeClassName($res->title)) {
                $object = $res;
                break;
            }
        }
    }

    $stmt = $DBH->prepare("SELECT * FROM variables WHERE parent = :oid");
    $stmt->bindParam(":oid",$object->id,PDO::PARAM_INT);
    $stmt->execute();
    while ($res = $stmt->fetch())
    {
        if ($res->in_populate == 1) {

            // First find the default value
            $defval = "";
            if ($default) {
                $defstmt = $DBH->prepare("SELECT * FROM defaultvar WHERE parent_default=:parid AND parent_var=:varid");
                $defstmt->bindParam("parid",$default->id,PDO::PARAM_INT);
                $defstmt->bindParam("varid",$res->id,PDO::PARAM_INT);
                $defstmt->execute();

                $dval = $defstmt->fetch();
                $defval = $dval->val;
            }

            // Figure out the variable stuff
            $varname = makeVarName($res->title,$res->kind);
            $varid = $res->id;

            echo "<span class='input-span";
            if ($variable)echo " subob";
            echo "'";
            if ($variable)echo "style='width:150px;'";
            else {
                if ($res->kind==7&&$res->title=="UID")echo "style='width:40px;'";
            }
            echo ">";
            if ($variable)echo $res->title.": ";

            switch($res->kind) {
                case 4:
                    echo "<input class='form-field' type='hidden' vartype='4' id='$varname' varid='$varid' value='Array'/>";
                    break;
                case 7:

                    if ($res->title=="UID" || $res->class=="") {
                        echo "<input class='form-field' vartype='7' type='text' id='$varname' varid='$varid'";
                        if (!$default)echo " value='$newID'";
                        else echo " value='$defval'";
                        echo "/>";
                    } else {

                        echo "<select class='form-field' id='$varname' varid='$varid'>";
                        echo "<option value='-1'";
                        if ($defval=="-1")echo " SELECTED";
                        echo ">Null</option>";

                        $obstmt = $DBH->prepare("SELECT id FROM objects WHERE title=:title");
                        $obstmt->bindParam(":title",$res->class,PDO::PARAM_STR);
                        $obstmt->execute();
                        $varob = $obstmt->fetch();

                        $sdstmt = $DBH->prepare("SELECT * FROM defaults WHERE parent_object=:oid");
                        $sdstmt->bindParam(":oid",$varob->id,PDO::PARAM_INT);
                        $sdstmt->execute();
                        while ($subdef = $sdstmt->fetch()) {
                            echo "<option value='".$subdef->uid."'";
                            if (intval($defval) == $subdef->uid)echo " SELECTED";
                            echo ">".$subdef->title."</option>";
                        }

                        echo "</select>";
                    }

                    break;
                default: // Standard inputs
                    echo "<input type='text' class='form-field' varid='$varid' vartype='".$res->kind."' id='$varname' value='$defval'/>";
                break;
            }
            echo "</span>";
        }
    }
    echo "<span style='display:none;'><button id='formbutton$formsBuilt' onclick='return saveSelf($formsBuilt);'>Save</button></span>";
}

// Returns the default contents of a row

function buildRow($objectid,$defaultid)
{
    global $DBH;
    if ($defaultid=="new") {
        echo "<div style='width:100%;text-align:center;'>+</div>";
    } else {
        $default = resForID("defaults",$defaultid);
        $object = resForID("objects",$objectid);

        if ($default->parent_default>0) {
            $varpar = $default->parent_variable;
            $variable = resForID("variables",$varpar);
            echo "<span class='input-span'> -> ".makeVarName($variable->title,$variable->kind)."</span>";
            $stmt = $DBH->prepare("SELECT * FROM objects WHERE project=:project");
            $stmt->bindParam(":project",$object->project,PDO::PARAM_STR);
            $stmt->execute();
            while ($res = $stmt->fetch()) {
                if ($variable->class == makeClassName($res->title)) {
                    $object = $res;
                    break;
                }
            }
        }

        $stmt = $DBH->prepare("SELECT * FROM defaultvar WHERE parent_default=:did");
        $stmt->bindParam(":did",$defaultid,PDO::PARAM_INT);
        $stmt->execute();

        while ($res = $stmt->fetch()) {
            $var = resForID("variables",$res->parent_var);
            echo "<span class='input-span'";
            if ($var->kind==7&&$var->title=="UID")echo "style='width:40px;'";
            echo ">";
            if ($var->kind==7 && $var->title != "UID") {
                $obstmt = $DBH->prepare("SELECT id FROM objects WHERE title=:title");
                $obstmt->bindParam(":title",$var->class,PDO::PARAM_STR);
                $obstmt->execute();
                $varob = $obstmt->fetch();

                $sdstmt = $DBH->prepare("SELECT * FROM defaults WHERE parent_object=:oid AND uid=:uid");
                $sdstmt->bindParam(":oid",$varob->id,PDO::PARAM_INT);
                $sdstmt->bindParam(":uid",$res->val,PDO::PARAM_INT);
                $sdstmt->execute();
                $subdef = $sdstmt->fetch();
                echo $subdef->title;

            } else {

                if ($var->kind==4) {
                    echo "<span onclick='addSubobject($defaultid,".$var->id.")'><strong>(+)</strong></span>";
                } else
                    echo $res->val;
            }
            echo "</span>";
        }

        if ($defaultid!="new")
            echo "<span onclick='deleteDefault($defaultid)'>(X)</button>";
    }
}

// Row wrappers

function openRow($id,$defaultid,$objectid="",$defpar="-1",$varpar="-1",$style="")
{
    if ($defpar==0)$defpar="-1";
    if ($varpar==0)$varpar="-1";
    if ($objectid == "")$objectid = $_POST["objectid"];
    $objectid = $_POST["objectid"];

    echo "<div class='var-row' id='$id' objectid='$objectid' defpar='$defpar' varpar='$varpar' defaultid='$defaultid' style='$style'>";
}

function closeRow()
{
    echo "</div>";
}

?>