<?php

date_default_timezone_set('AMERICA/ARGENTINA/BUENOS_AIRES');
ini_set("display_errors", true);
error_reporting(E_ALL | E_STRICT);
mb_internal_encoding('UTF-8');

// requirements
require("constants.php");
require("functions.php");

session_name('ID');
// enable sessions
session_start();

require_once '../src/Token.php';
require_once '../src/Db.php';
require_once '../src/Flash.php';

// require authentication for most pages
// 22-30
if (!preg_match("{(?:usuario_login|usuario_logout|usuario_register|usuario_forgot_password|usuario_reset_password)\.php$}", $_SERVER["PHP_SELF"]))
{
    if (empty($_SESSION["uid"]))
    {
        redirect("usuario_login.php");
    }
}