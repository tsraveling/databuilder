<?php
/**
 * Created by PhpStorm.
 * User: tsraveling
 * Date: 1/19/14
 * Time: 10:28 PM
 */

require("bootstrap.php");

if (isset($_POST["title"])) {
    $stmt = $DBH->prepare("INSERT INTO variables (parent,kind,title,jsonkey) VALUES (:parent,:kind,:title,:jsonkey)");
    $stmt->bindParam(":parent",$_POST["parent"],PDO::PARAM_INT);
    $stmt->bindParam(":kind",$_POST["kind"],PDO::PARAM_INT);
    $stmt->bindParam(":title",$_POST["title"],PDO::PARAM_STR);
    $jsonkey = str_replace(" ","",$_POST["title"]);
    $stmt->bindParam(":jsonkey",$jsonkey,PDO::PARAM_STR);
    $stmt->execute();
}

startMenu("variables","Variables","Type","Comments");

$stmt=$DBH->prepare("SELECT * FROM variables WHERE parent=:parent ORDER BY kind,title");
$stmt->bindParam(":parent",$_POST["parent"],PDO::PARAM_INT);
$stmt->execute();

while ($res = $stmt->fetch()) {
    drawMenuRow($res->title,"variable.php?id=".$res->id,$res->id,variableType($res->kind),"<em>".$res->comments."</em>");
}

?>

<tr>
    <td><input type="text" id="var-title" style="width: 90%;"/></td>
    <td>
        <select id="var-type" style="width: 90%">
            <?php
            $n=0;
            while(1) {
                $tx = variableType($n);
                if ($tx=="Unknown")break;
                echo "<option value='$n'>$tx</option>";
                $n++;
            }
            ?>
        </select>
    </td>
    <td><button onclick="return addVariable();">Add</button></td>

    <script type="text/javascript">
        function addVariable()
        {
            $.ajax({
                url:"object-ajax.php",
                type:"post",
                data:{
                    parent:<?php echo $_POST["parent"]; ?>,
                    title:$("#var-title").val(),
                    kind:$("#var-type").val()
                }
            }).done(function(data){
                    $("#var-holder").html(data);
                });
            return false;
        }
    </script>
</tr>

<?php

endMenu();

?>