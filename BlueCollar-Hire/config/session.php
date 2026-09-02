<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/database.php";


function e($string)
{
    return htmlspecialchars($string ?? "", ENT_QUOTES, "UTF-8");
}

