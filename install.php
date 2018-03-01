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

    //$_GET[]
    
    print "<h1>BACKUP</h1>";
    print "<br>";
    @mkdir("shop-gold-old/ustawienia",0777,true);
    @mkdir("shop-gold-old/szablony/standardowy.rwd",0777,true);
    @mkdir("shop-gold-old/klasy",0777,true);
    chdir("..");
    myCopy("ustawienia/ustawienia_db.php","_ga/shop-gold-old/ustawienia/ustawienia_db.php");
    myCopy("listing_dol.php","_ga/shop-gold-old/listing_dol.php");
    myCopy("szablony/standardowy.rwd/strona_glowna.tp","_ga/shop-gold-old/szablony/standardowy.rwd/strona_glowna.tp");
    myCopy("klasy/Koszyk.php","_ga/shop-gold-old/klasy/Koszyk.php");
    myCopy("zamowienie_potwierdzenie.php","_ga/shop-gold-old/zamowienie_potwierdzenie.php");
    myCopy("zamowienie_podsumowanie.php","_ga/shop-gold-old/zamowienie_podsumowanie.php");
    myCopy("produkt.php","_ga/shop-gold-old/produkt.php");
    myCopy("koszyk.php","_ga/shop-gold-old/koszyk.php");
    print "<h1>INSTALL</h1>";
    print "<br>";
    
function myCopy($from, $to)
{
    if(copy($from,$to))
        print "OK:".$from. "=>".$to;
    else
        print "ERROR".$from. "=>".$to;
    
    print "<br>";    
}
    //print_r($output);
    //$gaBasket = new gaBasket();
    //print $gaBasket->Add(255,2);
    //print $gaBasket->Remove(255,2);


?>