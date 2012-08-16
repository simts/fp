<?php
    include_once "header.php";
    include_once "../common/fpdb.php";

    try {
        $db = new FPDB_User();
    } catch (FPDB_Exception $e) {
        die($e->getMessage());
    }

    /* Print radio buttons, one for each beer on inventory. */
    try {
        $qres = $db->inventory_get_all();
    } catch (FPDB_Exception $e) {
        die($e->getMessage());
    }

    printf("<form action=\"%s\" method=\"post\">", $_SERVER["PHP_SELF"]);
    foreach ($qres as $inventory_item) {
        $beer_name = $inventory_item["namn"];
        $beer_id = $inventory_item["beer_id"];

        printf("<input type=\"radio\" name=\"beer_id\" value=%d> %s </br>", 
            $beer_id, $beer_name);
    }
    printf("<input type=\"submit\" name=\"submit\" value=\"Register\"/>");
    printf("</form>");


    /* Record beer purchase in the database. */
    if (isset($_POST["submit"])) {
        $user_id = $_SESSION["user_id"];
        $beer_id = $_POST["beer_id"];

        try {
            $db->purchase_append($user_id, $beer_id);
        } catch (FPDB_Exception $e) {
            die($e->getMessage());
        }

        printf("One large beer sold to %s %s<br/>",
            $_SESSION["first_name"], $_SESSION["last_name"]);
    }
    include_once "footer.php"; 
?>