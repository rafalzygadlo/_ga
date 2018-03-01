<?php

$GLOBALS['kolumny'] = 'srodkowa';

// plik
$WywolanyPlik = 'zamowienie_podsumowanie';

include('start.php');

if ( !isset($_SESSION['zamowienie_id']) ) {

    Funkcje::PrzekierowanieURL('koszyk.html'); 
    
}

$blad = false;
$zapytanie = "SELECT customers_id FROM orders WHERE orders_id = '" . (int)$_SESSION['zamowienie_id'] . "' LIMIT 1";
$sql = $GLOBALS['db']->open_query($zapytanie);

if ((int)$GLOBALS['db']->ile_rekordow($sql) > 0) {
    $info = $sql->fetch_assoc();
    if ( (int)$info['customers_id'] == (int)$_SESSION['customer_id'] ) {
        $blad = false;
    } else {
        $blad = true;
    }
    unset($info);
} else {
    $blad = true;
}

$GLOBALS['db']->close_query($sql);
unset($zapytanie);

if ( $blad ) {
    Funkcje::PrzekierowanieURL('brak-strony.html'); 
}

unset($blad); 

$GLOBALS['tlumacz'] = array_merge( $i18n->tlumacz( array('ZAMOWIENIE_REALIZACJA','LOGOWANIE','REJESTRACJA','KLIENCI', 'KLIENCI_PANEL', 'PLATNOSCI', 'WYSYLKI', 'PODSUMOWANIE_ZAMOWIENIA') ), $GLOBALS['tlumacz'] );

// meta tagi
$Meta = MetaTagi::ZwrocMetaTagi( basename(__FILE__) );
$tpl->dodaj('__META_TYTUL', $Meta['tytul']);
$tpl->dodaj('__META_SLOWA_KLUCZOWE', $Meta['slowa']);
$tpl->dodaj('__META_OPIS', $Meta['opis']);
unset($Meta);

// breadcrumb
$nawigacja->dodaj($GLOBALS['tlumacz']['NAGLOWEK_ZAMOWIENIE_PODSUMOWANIE']);
$tpl->dodaj('__BREADCRUMB', $nawigacja->sciezka(' ' . $GLOBALS['tlumacz']['NAWIGACJA_SEPARATOR'] . ' '));

// pobranie z bazy informacji o zamowieniu
$zamowienie = new Zamowienie((int)$_SESSION['zamowienie_id']);

// wyglad srodkowy
$srodek = new Szablony($Wyglad->TrescLokalna($WywolanyPlik), $zamowienie);

$srodek->dodaj('__NUMER_ZAMOWIENIA', (int)$_SESSION['zamowienie_id']);
$srodek->dodaj('__METODA_PLATNOSCI', $zamowienie->info['metoda_platnosci']);
$srodek->dodaj('__WYSYLKA_MODUL', $zamowienie->info['wysylka_modul'] . ($zamowienie->info['wysylka_info'] != '' ? ' ('.$zamowienie->info['wysylka_info'].')' : '' ) );
$srodek->dodaj('__DATA_ZAMOWIENIA', date('d-m-Y H:i:s',strtotime($zamowienie->info['data_zamowienia'])));
$srodek->dodaj('__STATUS_ZAMOWIENIA', Funkcje::pokazNazweStatusuZamowienia($zamowienie->info['status_zamowienia'],$_SESSION['domyslnyJezyk']['id']));

$kod_google_header  = "";

// kod do funkcji Google Analytcis ecommerce
if ( INTEGRACJA_GOOGLE_WLACZONY == 'tak' && INTEGRACJA_GOOGLE_ID != '' ) {

    $uzytkownik = '';
    $wartosc_zamowienia = 0;
    $wartosc_wysylki = 0;
    $wartosc_vat = "";

    foreach ( $zamowienie->podsumowanie as $podsumowanie ) {
      if ($podsumowanie['klasa'] == 'ot_total') {
        $wartosc_zamowienia = number_format($podsumowanie['wartosc'], 2, $_SESSION['domyslnaWaluta']['separator'], '');
      } elseif ($podsumowanie['klasa'] == 'ot_shipping') {
        $wartosc_wysylki = number_format($podsumowanie['wartosc'], 2, $_SESSION['domyslnaWaluta']['separator'], '');
      }
    }

    if ( INTEGRACJA_GOOGLE_RODZAJ == 'universal' ) {

        $kod_google_header  .= "<script>\n";
        $kod_google_header  .= "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n";
        $kod_google_header  .= "ga('create', '".INTEGRACJA_GOOGLE_ID."', 'auto');\n";
        $kod_google_header  .= "ga('require', 'displayfeatures');\n";
        $kod_google_header  .= "ga('send', 'pageview');\n";
        $kod_google_header  .= "ga('require', 'ecommerce', 'ecommerce.js');\n";
        $kod_google_header  .= "ga('ecommerce:addTransaction', {\n";
        $kod_google_header  .= "'id': '".$_SESSION['zamowienie_id']."',\n";
        $kod_google_header  .= "'affiliation': '".$uzytkownik."',\n";
        $kod_google_header  .= "'revenue': '".$wartosc_zamowienia."',\n";
        $kod_google_header  .= "'shipping': '".$wartosc_wysylki."',\n";
        $kod_google_header  .= "'tax': '".$wartosc_vat."',\n";
        $kod_google_header  .= "'currency': '".$_SESSION['domyslnaWaluta']['kod']."'\n";
        $kod_google_header  .= "});\n";
        foreach ( $zamowienie->produkty as $produkt ) {
            $kod_google_header  .= "ga('ecommerce:addItem', {\n";
            $kod_google_header  .= "  'id': '".$_SESSION['zamowienie_id']."',\n";
            $kod_google_header  .= "  'name': '".$produkt['nazwa']."',\n";
            $kod_google_header  .= "  'sku': '".$produkt['id_produktu']."',\n";
            $kod_google_header  .= "  'category': '".Produkty::pokazKategorieProduktu($produkt['id_produktu'])."',\n";
            $kod_google_header  .= "  'price': '".number_format($produkt['cena_koncowa_brutto'], 2, $_SESSION['domyslnaWaluta']['separator'], '')."',\n";
            $kod_google_header  .= "  'quantity': '".$produkt['ilosc']."'\n";
            $kod_google_header  .= "});\n";
        }
        $kod_google_header  .= "ga('ecommerce:send');\n";

        $kod_google_header .= "</script>\n";

    } else {

        $kod_google_header .= "<script type=\"text/javascript\">\n";
        $kod_google_header .= "    var _gaq = _gaq || [];\n";
        $kod_google_header .= "    _gaq.push(['_setAccount', '".INTEGRACJA_GOOGLE_ID."']);\n";
        $kod_google_header .= "    _gaq.push(['_setDomainName', '".str_replace('http://', '', ADRES_URL_SKLEPU)."']);\n";
        $kod_google_header .= "    _gaq.push(['_trackPageview']);\n";
        $kod_google_header .= "    _gaq.push(['_set', 'currencyCode', '".$_SESSION['domyslnaWaluta']['kod']."']);\n";
        $kod_google_header .= "    _gaq.push(['_addTrans','".$_SESSION['zamowienie_id']."','".$uzytkownik."','".$wartosc_zamowienia."','".$wartosc_vat."','".$wartosc_wysylki."','".$zamowienie->klient['miasto']."','".$zamowienie->klient['wojewodztwo']."','".$zamowienie->klient['kraj']."']);\n";
        foreach ( $zamowienie->produkty as $produkt ) {
          $kod_google_header .= "    _gaq.push(['_addItem','".$_SESSION['zamowienie_id']."','".$produkt['id_produktu']."','".$produkt['nazwa']."','".Produkty::pokazKategorieProduktu($produkt['id_produktu'])."','".number_format($produkt['cena_koncowa_brutto'], 2, $_SESSION['domyslnaWaluta']['separator'], '')."','".$produkt['ilosc']."']);\n";
        }
        $kod_google_header .= "    _gaq.push(['_trackTrans']);\n\n";
        $kod_google_header .= "   (function() {\n";
        $kod_google_header .= "   var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;\n";
        if ( INTEGRACJA_GOOGLE_ADWORDS == 'tak' ) {
            $kod_google_header .= "    ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';\n";
        } else {
            $kod_google_header .= "    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';\n";
        }
        $kod_google_header .= "   var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);\n";
        $kod_google_header .= "   })();\n";
        $kod_google_header .= "</script>\n";
    }
}

//maxkod
$ga = new gaOrder();
//print "<pre>";
//print_r($_SESSION);
//print "</pre>";

$gaCode = $ga->Purchase($_SESSION);
//
$tpl->dodaj('__GOOGLE_ANALYTICS', $kod_google_header);

unset($kod_google_header);

$skrypty_afiliacji = '';

//integracja z programem WebePartners
if ( INTEGRACJA_WEBEPARTNERS_ZAMOWIENIA_WLACZONY == 'tak' && INTEGRACJA_WEBEPARTNERS_MID != '' ) {

    $id_produktu    = '';
    $ilosc_produktu = '';
    $cena_produktu  = '';
    $rabat_produktu = '';
    $products_array_webe = array();
    foreach ( $zamowienie->produkty as $produkt ) {
        $id_produktu    .= $produkt['id_produktu'] . ':';
        $ilosc_produktu .= $produkt['ilosc'] . ':';
        $cena_produktu  .= $produkt['cena_koncowa_brutto'] . ':';
        $rabat_produktu .= ':';
    }
    $tekst_webe = $_SESSION['zamowienie_id'].'&pid='.substr($id_produktu,0,-1).'&q='.substr($ilosc_produktu,0,-1).'&price='.substr($cena_produktu,0,-1).'&dc='.substr($rabat_produktu,0,-1);

    $skrypty_afiliacji .= "\n";
    $skrypty_afiliacji .= "<script type=\"text/javascript\" src=\"https://webep1.com/order/confirmation.js?mid=".INTEGRACJA_WEBEPARTNERS_MID."&refer=".$tekst_webe."\"></script>"."\n";
    
}

//integracja z programem cash4free Openrate
if ( INTEGRACJA_OPENRATE_WLACZONY == 'tak' ) {

    $wartosc_zamowienia = 0;
    $wartosc_wysylki = 0;
    $wartosc_przekazana = 0;

    foreach ( $zamowienie->podsumowanie as $podsumowanie ) {
      if ($podsumowanie['klasa'] == 'ot_total') {
        $wartosc_zamowienia = $podsumowanie['wartosc'];
      } elseif ($podsumowanie['klasa'] == 'ot_shipping') {
        $wartosc_wysylki = $podsumowanie['wartosc'];
      }
    }
    $wartosc_przekazana = $wartosc_zamowienia - $wartosc_wysylki;

    $skrypty_afiliacji .= "\n";

    $skrypty_afiliacji .= "<script type=\"text/javascript\">\n";
    $skrypty_afiliacji .= "function getCookieByNameOpenrate(cookie) {\n";
    $skrypty_afiliacji .= "    return document.cookie.split(';').reduce(function(prev, c) {\n";
    $skrypty_afiliacji .= "        var arr = c.split('=');\n";
    $skrypty_afiliacji .= "        return (arr[0].trim() === cookie) ? arr[1] : prev;\n";
    $skrypty_afiliacji .= "    }, undefined);\n";
    $skrypty_afiliacji .= "}\n\n";

    $skrypty_afiliacji .= "var oTTUID = getCookieByNameOpenrate(\"MEDIAEFFECT\");\n";
    $skrypty_afiliacji .= "var oTTIDparam = \"&oTTID=\"+oTTUID;\n";
    $skrypty_afiliacji .= "if (oTTUID == undefined) {\n";
    $skrypty_afiliacji .= " oTTIDparam = \"\";\n";
    $skrypty_afiliacji .= "}\n\n";

    $skrypty_afiliacji .= "var domain = \"tracking.mediaeffect.eu\";\n";
    $skrypty_afiliacji .= "var caseType = 3037;\n";
    $skrypty_afiliacji .= "var advertiserID = 4137;\n";
    $skrypty_afiliacji .= "var purchaseNumber = ".$_SESSION['zamowienie_id'].";\n";
    $skrypty_afiliacji .= "var totalValue = ".$wartosc_przekazana." * 100;\n";
    $skrypty_afiliacji .= "var caseNumber = Math.round((new Date().getTime() * Math.random()));\n";
    $skrypty_afiliacji .= "caseNumber = new Number(caseNumber.toString().substring(3, 12));\n\n";

    $skrypty_afiliacji .= "document.write(\"<img src=\\\"http://\"+domain+\"/trackback?CaseType=\"+caseType+\"&CaseNumber=\"+caseNumber+\"&PurchaseNumber=\"+purchaseNumber+\"&TotalValue=\"+totalValue+\"&AdvertiserID=\"+advertiserID+oTTIDparam+\"\\\" height=1 width=1 border=0>\");\n";
    $skrypty_afiliacji .= "</script>\n";

}


//integracja z programem Zaufane Opinie CENEO
if ( INTEGRACJA_CENEO_OPINIE_WLACZONY == 'tak' && INTEGRACJA_CENEO_OPINIE_ID != '' ) {

    if (!empty($zamowienie->klient['adres_email'])) {
        $string_ceneo = '';
        $wartoscZamowienia = 0;

        foreach ( $zamowienie->produkty as $produkt ) {

            $wartosc_produktu = $produkt['cena_koncowa_brutto'] * $produkt['ilosc'];
            $wartoscZamowienia += $wartosc_produktu;

            if ( $produkt['ilosc'] > 1 ) {
                for ($y=0, $z = $produkt['ilosc']; $y < $z; $y++) {
                    $string_ceneo .= '#'.$produkt['id_produktu'];
                }
            } else {
                $string_ceneo .= '#'.$produkt['id_produktu'];
            }

        }

        $skrypty_afiliacji .= "\n";
        if ( INTEGRACJA_CENEO_OPINIE_WARIANT == 'nie' ) { 
            $skrypty_afiliacji .= "<script type=\"text/javascript\"><!--\n";
            $skrypty_afiliacji .= "  ceneo_client_email = '".(($_SESSION['zgodaNaPrzekazanieDanych'] == '1' || INTEGRACJA_CENEO_OPINIE_CHECKBOX == 'nie') ? $zamowienie->klient['adres_email'] : '' )."';\n";
            $skrypty_afiliacji .= "  ceneo_order_id = '".$_SESSION['zamowienie_id']."';\n";
            $skrypty_afiliacji .= "  ceneo_shop_product_ids = '".$string_ceneo."';\n";
            $skrypty_afiliacji .= "  ceneo_work_days_to_send_questionnaire = ".INTEGRACJA_CENEO_OPINIE_CZAS.";\n";
            $skrypty_afiliacji .= "//--></script>\n";
            $skrypty_afiliacji .= "<script type=\"text/javascript\" src=\"https://ssl.ceneo.pl/transactions/track/v2/script.js?accountGuid=".INTEGRACJA_CENEO_OPINIE_ID."\"></script>\n";
        } else {
            $skrypty_afiliacji .= "<script type=\"text/javascript\"><!--\n";
            $skrypty_afiliacji .= "  ceneo_client_email = '".(($_SESSION['zgodaNaPrzekazanieDanych'] == '1' || INTEGRACJA_CENEO_OPINIE_CHECKBOX == 'nie') ? $zamowienie->klient['adres_email'] : 'test@ceneo.pl' )."';\n";
            $skrypty_afiliacji .= "  ceneo_order_id = '".$_SESSION['zamowienie_id']."';\n";
            $skrypty_afiliacji .= "  ceneo_amount = ".round($wartosc_produktu,2).";\n";
            $skrypty_afiliacji .= "  ceneo_shop_product_ids = '".$string_ceneo."';\n";
            $skrypty_afiliacji .= "//--></script>\n";
            $skrypty_afiliacji .= "<script type=\"text/javascript\" src=\"https://ssl.ceneo.pl/transactions/track/v2/script.js?accountGuid=".INTEGRACJA_CENEO_OPINIE_ID."\"></script>\n";
        }

        unset($string_ceneo, $wartoscZamowienia);
    }
    
}

//integracja z programem okazje.info
if ( INTEGRACJA_OKAZJE_WLACZONY == 'tak' && INTEGRACJA_OKAZJE_ID != '' && $_SESSION['zgodaNaPrzekazanieDanych'] == '1' ) {

    $wartosc_zamowienia = 0;
    $products_array_okazje = array();

    foreach ( $zamowienie->produkty as $produkt ) {
        $products_array_okazje[] = array($produkt['id_produktu'],$produkt['ilosc']);
        $wartosc_zamowienia += ($produkt['cena_koncowa_brutto'] * $produkt['ilosc']);
    }
    $dane = array(
           'mail' => $zamowienie->klient['adres_email'],
           'orderId' => $_SESSION['zamowienie_id'],
           'orderAmount' => $wartosc_zamowienia,
           'products' => $products_array_okazje
    );

    include_once 'inne/oiTracker.php';
    $oiTracker = new oiTracker(INTEGRACJA_OKAZJE_ID);
    $r = $oiTracker->eOrder($dane);

    unset($dane, $products_array_okazje, $wartosc_zamowienia, $r);
    
}

//integracja z Zaufane opinie - OPINEO
if ( INTEGRACJA_OPINEO_OPINIE_WLACZONY == 'tak' && INTEGRACJA_OPINEO_OPINIE_LOGIN != '' && INTEGRACJA_OPINEO_OPINIE_PASS != '' && $_SESSION['zgodaNaPrzekazanieDanych'] == '1' ) {

    $products_array_opineo = array();

    foreach ( $zamowienie->produkty as $produkt ) {
        $products_array_opineo[] = array($produkt['producent'],$produkt['nazwa'],$produkt['id_produktu'], '0');
    }

    include_once 'inne/ZaufaneOpineo.php';
    $opinie = new ZaufaneOpineo();
    $r = $opinie->opineo_zapisz_zaproszenie($zamowienie->klient['adres_email'], INTEGRACJA_OPINEO_OPINIE_LOGIN, INTEGRACJA_OPINEO_OPINIE_PASS, INTEGRACJA_OPINEO_OPINIE_CZAS, $_SESSION['zamowienie_id'], $products_array_opineo);

    unset($products_array_opineo, $r);
    
}

//integracja z salesmedia.pl
if ( INTEGRACJA_SALESMEDIA_WLACZONY == 'tak' && INTEGRACJA_SALESMEDIA_ID != '' ) {

    $wartosc_zamowienia = 0;

    foreach ( $zamowienie->produkty as $produkt ) {
        $wartosc_zamowienia += ($produkt['cena_koncowa_brutto'] * $produkt['ilosc']);
    }

    $skrypty_afiliacji .= "\n";
    $skrypty_afiliacji .= "<iframe src=\"http://go.salesmedia.pl/aff_l?offer_id=".INTEGRACJA_SALESMEDIA_ID."&adv_sub=".$_SESSION['zamowienie_id']."&amount=".$wartosc_zamowienia."\" scrolling=\"no\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>\n";

    unset($wartosc_zamowienia);
    
}


$srodek->dodaj('__PDF_ZAMOWIENIE', '<a href="zamowienia-szczegoly-pdf-'.(int)$_SESSION['zamowienie_id'] . '.html"><img alt="' . $GLOBALS['tlumacz']['DRUKUJ_ZAMOWIENIE'] . '" title="' . $GLOBALS['tlumacz']['DRUKUJ_ZAMOWIENIE'] . '" src="szablony/' . DOMYSLNY_SZABLON . '/obrazki/pdf/pdf.png" /></a>');

$srodek->dodaj('__PDF_FAKTURA', '<a href="zamowienia-faktura-pdf-'.(int)$_SESSION['zamowienie_id'] . '.html"><img alt="' . $GLOBALS['tlumacz']['DRUKUJ_FAKTURE_PROFORMA'] . '" title="' . $GLOBALS['tlumacz']['DRUKUJ_FAKTURE_PROFORMA'] . '" src="szablony/' . DOMYSLNY_SZABLON . '/obrazki/pdf/faktura.png" /></a>');

$srodek->dodaj('__SKRYPTY_AFILIACJA', $skrypty_afiliacji);

$platnoscElektroniczna = '';
$platnoscInformacja    = '';

if ( 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_payu' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_dotpay' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_przelewy24' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_pbn' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_payeezy' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_santander' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_lukas' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_mbank' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_paypal' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_cashbill' && 
    $_SESSION['rodzajPlatnosci']['platnosc_klasa'] != 'platnosc_transferuj' ) {

        $platnoscInformacja = $zamowienie->info['platnosc_info'];

        if ( $_SESSION['gosc'] == '1' ) {
            $_SESSION['gosc_id'] = $_SESSION['customer_id'];
            unset($_SESSION['adresDostawy'], $_SESSION['adresFaktury'], $_SESSION['customer_firstname'], $_SESSION['customer_default_address_id'], $_SESSION['customer_id']);
        }

} else {

    $platnosci = new Platnosci($_SESSION['rodzajDostawy']['wysylka_id']);
    $platnoscElektroniczna = $platnosci->Podsumowanie( $_SESSION['rodzajPlatnosci']['platnosc_id'], $_SESSION['rodzajPlatnosci']['platnosc_klasa'] );
    
}

$srodek->dodaj('__PLATNOSC_INFORMACJA', $platnoscInformacja);
$srodek->dodaj('__PLATNOSC_ELEKTRONICZNA', $platnoscElektroniczna);

// integracja z klikchron
$Klikochron = '';
if ( INTEGRACJA_KLIKOCHRON_WLACZONY == 'tak' ) {
     //
     $Klikochron .= '<script>' . "\n";
     $Klikochron .= 'var _sdbag = _sdbag || [];' . "\n";
     $Klikochron .= '_sdbag.push([\'partnerId\', ' . INTEGRACJA_KLIKOCHRON_PARTNERID . ']);' . "\n";
     $Klikochron .= '_sdbag.push([\'shopId\', ' . INTEGRACJA_KLIKOCHRON_SHOPID . ']);' . "\n";
     $Klikochron .= '_sdbag.push([\'country\', \'' . $_SESSION['domyslnyJezyk']['kod'] . '\']);' . "\n";    
     
     if ( INTEGRACJA_KLIKOCHRON_TEST == 'tak' ) {
          $Klikochron .= '_sdbag.push([\'sandbox\', true]);' . "\n";  // wersja testowa
     }
     if ( INTEGRACJA_KLIKOCHRON_DEBUG == 'tak' ) {
          $Klikochron .= '_sdbag.push([\'debug\', 1]);' . "\n";
     }
     
     $Klikochron .= '_sdbag.push([\'orderId\', \'' . $zamowienie->info['id_zamowienia'] . '\']);' . "\n";
     $Klikochron .= '_sdbag.push([\'customer\', {' . "\n";
     $Klikochron .= 'firstname: "' . $zamowienie->klient['nazwa'] . '",' . "\n";
     $Klikochron .= 'lastname: "",' . "\n";
     $Klikochron .= 'email: "' . $zamowienie->klient['adres_email'] . '",' . "\n";
     $Klikochron .= 'phone: "' . $zamowienie->klient['telefon'] . '",' . "\n";
     $Klikochron .= 'street: "' . $zamowienie->klient['ulica'] . '",' . "\n";
     $Klikochron .= 'street_number: "",' . "\n";
     $Klikochron .= 'zip: "' . $zamowienie->klient['kod_pocztowy'] . '",' . "\n";
     $Klikochron .= 'city: "' . $zamowienie->klient['miasto'] . '",' . "\n";
     
     // szuka iso kraju
     $zapytanieKraj = "SELECT c.countries_iso_code_2, cd.countries_name FROM countries c, countries_description cd WHERE c.countries_id = cd.countries_id AND cd.countries_name = '" . $zamowienie->klient['kraj'] . "'";
     $sqlKraj = $GLOBALS['db']->open_query($zapytanieKraj);
     $wynikKraj = $sqlKraj->fetch_assoc();
     //
     $Klikochron .= 'country : "' . strtolower($wynikKraj['countries_iso_code_2']) . '"}' . "\n";
     //
     $GLOBALS['db']->close_query($sqlKraj);
     unset($zapytanieKraj, $wynikKraj); 
       
     $Klikochron .= ']);' . "\n";
     
     $Klikochron .= '_sdbag.push([\'init\', \'success\']);' . "\n\n";      

     $Klikochron .= '(function () {' . "\n"; 
     $Klikochron .= 'var ss = document.createElement(\'script\'); ss.type = \'text/javascript\'; ss.async = true;' . "\n"; 
     $Klikochron .= 'ss.src = (\'https:\' == document.location.protocol ? \'https://\' : \'http://\') + \'www.schutzklick.de/jsapi/sisu-checkout-2.x.min.js\';' . "\n"; 
     $Klikochron .= 'var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ss, s);' . "\n"; 
     $Klikochron .= '})();' . "\n"; 
     $Klikochron .= '</script>' . "\n\n"; 

     $Klikochron .= '<div id="successMessagePlaceHolder" style="margin:10px;line-height:2"></div>';
}
$srodek->dodaj('__INTEGRACJA_KLIKOCHRON', $Klikochron);

// integracja z klikchron
$Trustedshops = '';
if ( INTEGRACJA_TRUSTEDSHOPS_WLACZONY == 'tak' ) {
     //
    $uzytkownik = '';
    $wartosc_zamowienia = 0;

    foreach ( $zamowienie->podsumowanie as $podsumowanie ) {
      if ($podsumowanie['klasa'] == 'ot_total') {
        $wartosc_zamowienia = number_format($podsumowanie['wartosc'], 2, $_SESSION['domyslnaWaluta']['separator'], '');
      }
    }

    $Trustedshops .= "<div id=\"trustedShopsCheckout\" style=\"display: none;\">\n";
    $Trustedshops .= "<span id=\"tsCheckoutOrderNr\">".$zamowienie->info['id_zamowienia']."</span>\n";
    $Trustedshops .= "<span id=\"tsCheckoutBuyerEmail\">".$zamowienie->klient['adres_email']."</span>\n";
    $Trustedshops .= "<span id=\"tsCheckoutOrderAmount\">".$wartosc_zamowienia."</span>\n";
    $Trustedshops .= "<span id=\"tsCheckoutOrderCurrency\">".$zamowienie->info['waluta']."</span>\n";
    $Trustedshops .= "<span id=\"tsCheckoutOrderPaymentType\">".$zamowienie->info['metoda_platnosci']."</span>\n";

    foreach ( $zamowienie->produkty as $produkt ) {
        $Trustedshops .= "<span class=\"tsCheckoutProductItem\">\n";
        $Trustedshops .= "<span class=\"tsCheckoutProductUrl\">".ADRES_URL_SKLEPU . "/". Seo::link_SEO( $produkt['nazwa'], $produkt['id_produktu'], 'produkt' )."</span>\n";
        $Trustedshops .= "<span class=\"tsCheckoutProductName\">".$produkt['nazwa']."</span>\n";
        $Trustedshops .= "<span class=\"tsCheckoutProductSKU\">".$produkt['id_produktu']."</span>\n";
        $Trustedshops .= "<span class=\"tsCheckoutProductImageUrl\">".ADRES_URL_SKLEPU . "/". KATALOG_ZDJEC . "/" . $produkt['zdjecie']."</span>\n";
        $Trustedshops .= "</span>\n";
    }

    $Trustedshops .= "</div>\n";

}
$srodek->dodaj('__INTEGRACJA_TRUSTEDSHOPS', $Trustedshops);

$tpl->dodaj('__SRODKOWA_KOLUMNA', $srodek->uruchom());



unset($srodek, $WywolanyPlik);

if ( isset($_SESSION['zamowienie_id']) ) unset($_SESSION['zamowienie_id']);
if ( isset($_SESSION['rodzajPlatnosci']) ) unset($_SESSION['rodzajPlatnosci']);
if ( isset($_SESSION['rodzajDostawy']) ) unset($_SESSION['rodzajDostawy']);
if ( isset($_SESSION['koszyk']) ) unset($_SESSION['koszyk']);

if ( !isset($_SESSION['koszyk']) ) {
    $_SESSION['koszyk'] = array();   
} 

if ( isset($_SESSION['podsumowanieZamowienia']) ) unset($_SESSION['podsumowanieZamowienia']);
if ( isset($_SESSION['platnoscElektroniczna']) ) unset($_SESSION['platnoscElektroniczna']);
if ( isset($_SESSION['zgodaNaPrzekazanieDanych']) ) unset($_SESSION['zgodaNaPrzekazanieDanych']);



include('koniec.php');

?>