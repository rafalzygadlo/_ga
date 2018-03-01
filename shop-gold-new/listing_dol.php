<?php

// stronicowanie
$srodek->dodaj('__STRONICOWANIE', '');
//
$IloscProduktow = (int)$GLOBALS['db']->ile_rekordow($sql);

$srodek->dodaj('__ILOSC_PRODUKTOW_OGOLEM', $IloscProduktow);

$LinkPrev = '';
$LinkNext = '';

if ($IloscProduktow > 0) { 
    //
    $Strony = Stronicowanie::PokazStrony($sql, $LinkDoPrzenoszenia);
    //
    $LinkPrev = ((!empty($Strony[2])) ? "\n" . $Strony[2] : '');
    $LinkNext = ((!empty($Strony[3])) ? "\n" . $Strony[3] : '');    
    //
    $LinkiDoStron = $Strony[0];
    $LimitSql = $Strony[1];
    //
    $srodek->dodaj('__STRONICOWANIE', $LinkiDoStron);
    //
    // zabezpieczenie zeby nie mozna bylo wyswietlic wiecej niz ilosc na stronie x 3
    if ( $_SESSION['listing_produktow'] > LISTING_PRODUKTOW_NA_STRONIE * 3 ) {
         $_SESSION['listing_produktow'] = LISTING_PRODUKTOW_NA_STRONIE * 3;
    }
    //
    $zapytanie = $zapytanie . " LIMIT " . $LimitSql . "," . $_SESSION['listing_produktow'];
    $GLOBALS['db']->close_query($sql);
    //            
    $sql = $GLOBALS['db']->open_query($zapytanie);
    //
    unset($Strony, $LinkiDoStron, $LimitSql);
}
//

$IloscProduktow = (int)$GLOBALS['db']->ile_rekordow($sql);

// przycisk usuniecia filtrow
if (isset($WarunkiFiltrowania) && $WarunkiFiltrowania != '') {
    $srodek->dodaj('__LINK_USUNIECIA_FILTROW', '<a href="' . $LinkDoPrzenoszenia . '">' . $GLOBALS['tlumacz']['LISTING_USUN_FILTRY'] . '</a>');
} else {
    $srodek->dodaj('__LINK_USUNIECIA_FILTROW', '');
}
 
	/*
	//maxkod
	*/
    $items = $sql->fetch_all(MYSQLI_ASSOC);
    $gaProductList = new gaProductList();
    $gaCode = $gaProductList->AddItems($items);
	/*
		bardzo ważne wróc ze wskaźnikiem na początek
	*/
    mysqli_data_seek($sql, 0);
	// maxkod koniec kodu
	

   
ob_start();

// listing wersji mobilnej
if ( $_SESSION['mobile'] == 'tak' ) {

    //
    if (in_array( 'listing_okna.mobilne.php', $Wyglad->PlikiListingiLokalne )) {
        require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_okna.mobilne.php');
    }
    //
    
  } else {

    // jezeli sposob wyswietlania okienka
    if ($SposobWyswietlania == 1) {
        //
        if (in_array( 'listing_okienka.php', $Wyglad->PlikiListingiLokalne )) {
            require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_okienka.php');
          } else {
            require('listingi/listing_okienka.php');
        }
        //
    }

    // jezeli sposob wyswietlania wiersze
    if ($SposobWyswietlania == 2) {
        //
        if (in_array( 'listing_wiersze.php', $Wyglad->PlikiListingiLokalne )) {
            require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_wiersze.php');
          } else {
            require('listingi/listing_wiersze.php');
        }
        //
    }

    // jezeli sposob wyswietlania lista
    if ($SposobWyswietlania == 3) {
        //
        if (in_array( 'listing_lista.php', $Wyglad->PlikiListingiLokalne )) {
            require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_lista.php');
          } else {
            require('listingi/listing_lista.php');
        }
        //
    }
    
}    





$ListaProduktow = ob_get_contents();
ob_end_clean();        

$srodek->dodaj('__LISTA_PRODUKTOW', $ListaProduktow);

$tpl->dodaj('__LINK_CANONICAL', '<link rel="canonical" href="' . ADRES_URL_SKLEPU . '/' . $LinkDoPrzenoszenia . '" />' . $LinkPrev . $LinkNext);

unset($LinkDoPrzenoszenia, $IloscProduktow, $ListaProduktow, $Sortowanie, $TablicaSortowania, $SposobWyswietlania);

// wyglad srodkowy
$tpl->dodaj('__SRODKOWA_KOLUMNA',$srodek->uruchom());
unset($srodek);

?>