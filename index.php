<?php

require("bootstrap.php");
start_header("Dashboard");
end_header();

startVerticalCenter();


startMenu("x");

$stmt=$DBH->prepare("SELECT * FROM projects ORDER BY title");
$stmt->execute();

while ($res = $stmt->fetch()) {
    drawMenuRow($res->title,"project.php?id=".$res->id,$res->id);
}

drawMenuItem("New Project","project.php?id=new");


endMenu();

endVerticalCenter();

do_footer();

?>