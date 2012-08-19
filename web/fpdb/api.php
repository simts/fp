<?php
    include_once "../fpdb/fpdb.php";
    include_once "../include/credentials.php";

    define("ERROR_USERNAME",  1);
    define("ERROR_PASSWORD",  2);
    define("ERROR_CRED",      3);
    define("ERROR_DATABASE",  4);
    define("ERROR_ACTION",    5);
    define("ERROR_ARGUMENTS", 6);

    $error_strings = array(
            ERROR_USERNAME  => "Username not found",
            ERROR_PASSWORD  => "Wrong password (your fucked)",
            ERROR_CRED      => "Not enough credentails",
            ERROR_DATABASE  => "Database error",
            ERROR_ACTION    => "Unknown action requested",
            ERROR_ARGUMENTS => "Wrong number or type of arguments",
    );

    class API_Reply
    {
        public $type;
        public $payload;
        
        function __construct($type, $payload = array())
        {
            $this->type = $type;
            $this->payload = $payload;
        }
    }

    /*
     * Helper functions
     */ 
    function return_error($code)
    {
        global $error_strings;

        $payload = array(array("code" => $code, "msg" => $error_strings[$code]));

        $reply = new API_Reply("error", $payload);
        echo my_json_encode($reply);
        exit(-1);
    }

    function check_credentials($have, $need)
    {
        if ($have > $need) {
            return_error(ERROR_CRED);
        }
    }

    /* Not all servers at uni have json_encode, hence this wrapper */
    function my_json_encode($reply)
    {
        if (function_exists(json_encode)) {
            return json_encode($reply);
        } else {
            $json = "{\"type\" : \"$reply->type\", \"payload\" : [";
            foreach ($reply->payload as $record) {
                $json .= "{";
                foreach ($record as $key => $val) {
                    $json .= "\"$key\" : \"$val\",";
                }
                $json .= "},";
            }
            $json .= "]}";
            return $json;
        }
    }

    /*
     * Functions to handle action requests
     */
    function action_inventory_get($db, $user_id)
    {
        $qres = $db->inventory_get_all()->get_array();
        return new API_Reply("inventory_get", $qres);
    }

    function action_purchase_get($db, $user_id)
    {
        $qres = $db->purchase_get($user_id)->get_array();
        return new API_Reply("inventory_get", $qres);
    }

    function action_purchase_get_all($db, $user_id)
    {
        $qres = $db->purchase_get_all()->get_array();
        return new API_Reply("inventory_get", $qres);
    }

    function action_purchase_append($db, $user_id)
    {
        $beer_id = $_GET["beer_id"];
        if (!$beer_id) {
            return_error(ERROR_ARGUMENTS);
        }
        $db->purchase_append($user_id, $beer_id);
        return new API_Reply("empty");
    }

    function action_iou_get($db, $user_id)
    {
        $qres = $db->iou_get($user_id)->get_array();
        return new API_Reply("iou_get", $qres);
    }

    function action_iou_get_all($db, $user_id)
    {
        $qres = $db->iou_get_all()->get_array();
        return new API_Reply("iou_get_all", $qres);
    }

    $username = $_GET["username"];
    $password = $_GET["password"];

    if (!$username || !$password) {
        return_error(ERROR_ARGUMENTS);
    }
    
    /* Check username and password */
    try {
        $db = new FPDB_User();
        $qres = $db->user_get($username)->next();
    } catch (FPDB_Exception $e) {
        return_error(ERROR_DATABASE);
    }

    if (!$qres) {
        return_error(ERROR_USERNAME);
    }

    if ($qres["password"] != md5($password)) {
        return_error(ERROR_PASSWORD);
    }

    $user = $qres["user_id"];
    $cred = $qres["credentials"];

    /* Reconnect to database with correct credentials */
    try {
        if ($credentials == CRED_ADMIN) {
            unset($db); /* Close connection */
            $db = new FPDB_Admin();
        }
    } catch (FPDB_Exception $e) {
        return_error(ERROR_DATABASE);
    }

    $action = $_GET["action"];
    if (!$action) {
        return_error(ERROR_ARGUMENTS);
    }

    /* Perform requested action */
    try {
        switch ($action) {
            case "inventory_get":
                check_credentials($cred, CRED_USER);
                $reply = action_inventory_get($db, $user);
                break;

            case "purchase_get":
                check_credentials($cred, CRED_USER);
                $reply = action_purchase_get($db, $user);
                break;

            case "purchase_get_all":
                check_credentials($cred, CRED_ADMIN);
                $reply = action_purchase_get_all($db, $user);
                break;

            case "purchase_append":
                check_credentials($cred, CRED_USER);
                $reply = action_purchase_append($db, $user);
                break;

            case "iou_get":
                check_credentials($cred, CRED_USER);
                $reply = action_iou_get($db, $user);
                break;

            case "iou_get_all":
                check_credentials($cred, CRED_ADMIN);
                $reply = action_iou_get_all($db, $user);
                break;

            default:
                return_error(ERROR_ACTION);
                break;
        }
    } catch (FPDB_Exception $e) {
        return_error(ERROR_DATABASE);
    }

    echo my_json_encode($reply);
?>