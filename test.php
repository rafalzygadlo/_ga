#!/usr/local/bin/php
<?php

    ini_set("display_errors","on");
    error_reporting(E_ALL);

    // autoloader
    /*
    spl_autoload_extensions(".php");
    spl_autoload_register
    (
        function ($class)
        {
            $file = str_replace("\\", "/", $class) . ".php";
            require_once($file);
        }
    );
    */

    include "gaBasket.php";

    $gaBasket = new gaBasket();
    print $gaBasket->Add(255,2);
    print $gaBasket->Remove(255,2);


?>