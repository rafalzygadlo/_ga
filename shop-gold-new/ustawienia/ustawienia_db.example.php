<?php

define('DB_SERVER', 'localhost');
define('DB_PORT', '3306');
define('DB_SERVER_USERNAME', '');
define('DB_SERVER_PASSWORD', 'artinus'); 
define('DB_DATABASE', 'artinus');


require_once(str_replace(DIRECTORY_SEPARATOR.'zarzadzanie','',dirname(__FILE__)).DIRECTORY_SEPARATOR.'ustawienia_ssl.php');

if ( defined('TRYB_SSL_SKLEPU') && TRYB_SSL_SKLEPU == 'tak' && WLACZENIE_SSL == 'tak' ) {
    define('ADRES_URL_SKLEPU', 'http://artinus.zygadlo.org');
} else {
    define('ADRES_URL_SKLEPU', 'http://artinus.zygadlo.org');
}

//define('ADRES_URL_SKLEPU_SSL', 'https://artinus.eu');

define('KATALOG_SKLEPU', '/home/qotsa2/domains/zygadlo.org/sub/artinus/');

// maxkod
include(KATALOG_SKLEPU."_ga/gaBasket.php");
include(KATALOG_SKLEPU."_ga/gaOrder.php");
include(KATALOG_SKLEPU."_ga/gaProduct.php");
include(KATALOG_SKLEPU."_ga/gaProductList.php");

?>