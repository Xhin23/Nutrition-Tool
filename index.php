<?php 
require "php/functions.php";
require "classes/ZXC.php";

ZXC::INIT('<host>','<username>','<password>','<database name>');

require "classes/db.php";
require "classes/weight.php";
require "classes/ajax.php";

if ($_POST['ajax'])
{
    $ajax = new Ajax();
    $ajax->db = new DB();
    echo json_encode($ajax->$_POST['ajax']($_POST['data']));
    exit;
}

$db = new DB();

require "php/tool.php";

?>
