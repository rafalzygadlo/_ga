<?php

$GLOBALS['kolumny'] = 'srodkowa';

// plik
$WywolanyPlik = 'zamowienie_potwierdzenie';

include('start.php');

$Blad = '';

if ( $GLOBALS['koszykKlienta']->KoszykIloscProduktow() == 0 || (!isset($_SESSION['customer_id']) || (int)$_SESSION['customer_id'] == 0) ) {

    Funkcje::PrzekierowanieURL('koszyk.html'); 

}

// przekierowanie do koszyka jezeli nie ma zadnej ustawionej metody wyslki
if ( !isset($_SESSION['rodzajDostawy']['wysylka_id']) ) {

    Funkcje::PrzekierowanieURL('koszyk.html'); 

}

// jezeli kraj dostawy nie jest rowny zapisanemu w sesji - powraca do koszyka
if ( $_SESSION['krajDostawy']['id'] != $_SESSION['adresDostawy']['panstwo'] ) {
 
    Funkcje::PrzekierowanieSSL('zamowienie-zmien-dane.html'); 

}

// sprawdza czy jest dostepna wczesniej wybrana w koszyku forma wysylki
$wysylki = new Wysylki($_SESSION['krajDostawy']['kod']);

if ( isset($_SESSION['rodzajDostawy']) && !array_key_exists($_SESSION['rodzajDostawy']['wysylka_id'], $wysylki->wysylki) ) {

  unset($_SESSION['rodzajDostawy']);
  Funkcje::PrzekierowanieURL('koszyk.html'); 
  
}

// czy wartosc zamowienia nie jest mniejsza niz koszyk
$MinimalneZamowienieGrupy = Klient::MinimalneZamowienie();
if ( $MinimalneZamowienieGrupy > 0 ) {

    $MinZamowienie = $GLOBALS['waluty']->PokazCeneBezSymbolu($MinimalneZamowienieGrupy,'',true);
    $WartoscKoszyka = $GLOBALS['koszykKlienta']->ZawartoscKoszyka();

    if ( $WartoscKoszyka['brutto'] < $MinZamowienie ) {
         //
         Funkcje::PrzekierowanieURL('koszyk.html'); 
         //
    }
    unset($MinZamowienie, $WartoscKoszyka);
    
}  
unset($MinimalneZamowienieGrupy);

$GLOBALS['tlumacz'] = array_merge( $i18n->tlumacz( array('KOSZYK','ZAMOWIENIE_REALIZACJA', 'WYSYLKI', 'PLATNOSCI', 'PRZYCISKI', 'PODSUMOWANIE_ZAMOWIENIA', 'REJESTRACJA', 'PRODUKT') ), $GLOBALS['tlumacz'] );

// produkty koszyka
$ProduktyKoszyka = array();

//
// generuje tablice globalne z nazwami cech
Funkcje::TabliceCech();         
//
$MaksymalnyCzasWysylki = 0;
$MaksymalnyCzasWysylkiProdukt = true;

// sprawdzi czy w zamowieniu sa produkty w formie uslugi
$ProduktUsluga = false;
// sprawdzi czy w zamowieniu sa produkty elektroniczne
$ProduktOnline = false;
// sprawdzi czy w zamowieniu sa produkty niestandardowe, indywidualne
$ProduktNiestandardowy = false;

foreach ($_SESSION['koszyk'] AS $TablicaZawartosci) {
    //
    $Produkt = new Produkt( Funkcje::SamoIdProduktuBezCech( $TablicaZawartosci['id'] ), 40, 40 );
    // elementy kupowania
    $Produkt->ProduktKupowanie(); 
    // czas wysylki
    $Produkt->ProduktCzasWysylki();
    // stan produktu
    if ( KARTA_PRODUKTU_STAN_PRODUKTU == 'tak' ) {
         $Produkt->ProduktStanProduktu();
    }  
    // gwarancja produktu
    if ( KARTA_PRODUKTU_GWARANCJA == 'tak' ) {
         $Produkt->ProduktGwarancja();
    }      
    //
    // jezeli jest kupowanie na wartosci ulamkowe to sformatuje liczbe
    if ( $Produkt->info['jednostka_miary_typ'] == '0' ) {
         $TablicaZawartosci['ilosc'] = number_format( $TablicaZawartosci['ilosc'] , 2, '.', '' );
    }
    //
    // czy produkt ma cechy
    $CechaPrd = Funkcje::CechyProduktuPoId( $TablicaZawartosci['id'] );
    $JakieCechy = '';
    if ( count($CechaPrd) > 0 ) {
        //
        for ($a = 0, $c = count($CechaPrd); $a < $c; $a++) {
            $JakieCechy .= '<span class="Cecha">' . $CechaPrd[$a]['nazwa_cechy'] . ': <b>' . $CechaPrd[$a]['wartosc_cechy'] . '</b></span>';
        }
        //
    }
    //
    // czy produkt ma komentarz
    $KomentarzProduktu = '';
    if ( $TablicaZawartosci['komentarz'] != '' ) {
        //
        $KomentarzProduktu = '<span class="Komentarz">' . $GLOBALS['tlumacz']['KOMENTARZ_PRODUKTU'] . ' <b>' . $TablicaZawartosci['komentarz'] . '</b></span>';
        //
    }
    // czy sa pola tekstowe
    $PolaTekstowe = '';
    if ( $TablicaZawartosci['pola_txt'] != '' ) {
        //
        $TblPolTxt = Funkcje::serialCiag($TablicaZawartosci['pola_txt']);
        foreach ( $TblPolTxt as $WartoscTxt ) {
            //
            // jezeli pole to plik
            if ( $WartoscTxt['typ'] == 'plik' ) {
                $PolaTekstowe .= '<span class="Cecha">' . $WartoscTxt['nazwa'] . ': <a href="inne/wgranie.php?src=' . base64_encode(str_replace('.',';',$WartoscTxt['tekst'])) . '"><b>' . $GLOBALS['tlumacz']['WGRYWANIE_PLIKU_PLIK'] . '</b></a></span>';
              } else {
                $PolaTekstowe .= '<span class="Cecha">' . $WartoscTxt['nazwa'] . ': <b>' . $WartoscTxt['tekst'] . '</b></span>';
            }
        }
        unset($TblPolTxt);
        //
    }    
    // jezeli produkt jest tylko za PUNKTY - ilosc pkt w koszyku jest > 0
    if ( $Produkt->info['tylko_za_punkty'] == 'tak' ) {
         //
         $CenaProduktu = $GLOBALS['waluty']->PokazCenePunkty( $TablicaZawartosci['cena_punkty'], $TablicaZawartosci['cena_brutto'] );
         $WartoscProduktu = $GLOBALS['waluty']->PokazCenePunkty( $TablicaZawartosci['cena_punkty'] * $TablicaZawartosci['ilosc'], $TablicaZawartosci['cena_brutto'] * $TablicaZawartosci['ilosc'] );
         //          
      } else {
         //
         $CenaProduktu = $GLOBALS['waluty']->PokazCene($TablicaZawartosci['cena_brutto'], $TablicaZawartosci['cena_netto'], 0, $_SESSION['domyslnaWaluta']['id']);
         $WartoscProduktu = $GLOBALS['waluty']->PokazCene($TablicaZawartosci['cena_brutto'] * $TablicaZawartosci['ilosc'], $TablicaZawartosci['cena_netto'] * $TablicaZawartosci['ilosc'], 0, $_SESSION['domyslnaWaluta']['id']);
         //
    }    
    //
    $ProduktyKoszyka[$TablicaZawartosci['id']] = array('id'            => $TablicaZawartosci['id'],
                                                       'zdjecie'       => $Produkt->fotoGlowne['zdjecie_link'],
                                                       'nazwa'         => $Produkt->info['link'] . $JakieCechy,
                                                       'link_opisu'    => '<a class="Informacja" href="' . $Produkt->info['adres_seo'] . '">' . $GLOBALS['tlumacz']['SZCZEGOLOWY_OPIS_PRODUKTU'] . '</a>',
                                                       'producent'     => (( !empty($Produkt->info['nazwa_producenta']) ) ? '<span class="Cecha">' . $GLOBALS['tlumacz']['PRODUCENT'] . ': <b>' . $Produkt->info['nazwa_producenta'] . '</b></span>' : ''),
                                                       'czas_wysylki'  => (( !empty($Produkt->czas_wysylki) ) ? '<span class="Cecha">' . $GLOBALS['tlumacz']['CZAS_WYSYLKI'] . ': <b>' . $Produkt->czas_wysylki . '</b></span>' : ''),
                                                       'stan_produktu' => (( !empty($Produkt->stan_produktu) ) ? '<span class="Cecha">' . $GLOBALS['tlumacz']['STAN_PRODUKTU'] . ': <b>' . $Produkt->stan_produktu . '</b></span>' : ''),
                                                       'gwarancja'     => (( !empty($Produkt->gwarancja) ) ? '<span class="Cecha">' . $GLOBALS['tlumacz']['GWARANCJA'] . ': <b>' . str_replace('<a ', '<a style="font-weight:bold" ', $Produkt->gwarancja) . '</b></span>' : ''),
                                                       'komentarz'     => $KomentarzProduktu,
                                                       'pola_txt'      => $PolaTekstowe,
                                                       'ilosc'         => $TablicaZawartosci['ilosc'],
                                                       'cena'          => $CenaProduktu,
                                                       'wartosc'       => $WartoscProduktu);
    // maksymalny czas wysylki
    if ( (int)$Produkt->czas_wysylki_dni > $MaksymalnyCzasWysylki ) {
         $MaksymalnyCzasWysylki = (int)$Produkt->czas_wysylki_dni;
    }
    // sprawdza czy kazdy produkt ma czas wysylki
    if ( (int)$Produkt->czas_wysylki_dni == 0 ) {
         $MaksymalnyCzasWysylkiProdukt = false;
    }
    
    // sprawdzi czy w zamowieniu sa produkty w formie uslugi
    if ( $Produkt->info['typ_produktu'] == 'usluga' ) {
         $ProduktUsluga = true;
    }
    
    // sprawdzi czy w zamowieniu sa produkty elektroniczne
    if ( $Produkt->info['typ_produktu'] == 'online' ) {
         $ProduktOnline = true;
    }
    
    // sprawdzi czy w zamowieniu sa produkty niestandardowe, indywidualne
    if ( $Produkt->info['typ_produktu'] == 'indywidualny' ) {
         $ProduktNiestandardowy = true;
    }    
    
    //
    unset($Produkt, $CenaProduktu, $WartoscProduktu, $KomentarzProduktu, $PolaTekstowe);
    //
}
//
// jezeli wszystkie produkty mialy czas wysylki
if ( $MaksymalnyCzasWysylkiProdukt == true ) {
     $MaksymalnyCzasWysylki = str_replace('{0}', $MaksymalnyCzasWysylki, $GLOBALS['tlumacz']['SZACOWANY_CZAS_WYSYLKI']);
}
//

// parametry do ustalenia podsumowania zamowienia
$podsumowanie = new Podsumowanie();
$PodsumowanieZamowienia = $podsumowanie->GenerujWPotwierdzeniu();

$CssDokumentSprzedazy = '';

if ( !isset($_SESSION['adresFaktury']['dokument']) ) {
  
    $_SESSION['adresFaktury']['dokument'] = '';
    
}

// jezeli jest wybrany do zaznaczenia paragon lub faktura
if ( $_SESSION['adresFaktury']['dokument'] == '' ) {
     //
     if ( KLIENT_DOMYSLNY_DOKUMENT == 'paragon' || KLIENT_DOMYSLNY_DOKUMENT == 'faktura' ) {

        if ( KLIENT_DOMYSLNY_DOKUMENT == 'faktura' ) {
            $_SESSION['adresFaktury']['dokument'] = '1';
        } elseif ( KLIENT_DOMYSLNY_DOKUMENT == 'paragon' ) {
            $_SESSION['adresFaktury']['dokument'] = '0';
        }
        
        // jezeli klient jest jako firma i ma byc faktura to ustawic domyslne fakture
        if ( KLIENT_DOMYSLNY_DOKUMENT_FIRMA == 'tak' && $_SESSION['adresDostawy']['firma'] != '' ) {
            $_SESSION['adresFaktury']['dokument'] = '1';
        }    
        
     }

     // jezeli jest obsluga tylko firm to ustawi fakture jako dokument sprzedazy
     if ( KLIENT_TYLKO_FIRMA == 'tylko firma' ) {
        $_SESSION['adresFaktury']['dokument'] = '1';
        //
        // jezeli jest tylko firma to nie potrzebny jest wybor dokumentu sprzedazy i pozostaje tylko faktura
        $CssDokumentSprzedazy = 'style="display:none"';
     }
     //
     
     // jezeli jest ukryty wybor dokumentu sprzedazy przyjmuje domyslnie paragon dla zamowienia
     if ( KOSZYK_WYBOR_DOKUMENTU_SPRZEDAZY == 'nie' ) {
         $_SESSION['adresFaktury']['dokument'] = '0';
     }
     
}

$DaneDoWysylki = '';

$DaneDoWysylki .= $_SESSION['adresDostawy']['imie'] . ' ' . $_SESSION['adresDostawy']['nazwisko'] . '<br />';

if ( $_SESSION['adresDostawy']['firma'] != '' ) {
    $DaneDoWysylki .= $_SESSION['adresDostawy']['firma'] . '<br />';
}

$DaneDoWysylki .= $_SESSION['adresDostawy']['ulica'] . '<br />';

$DaneDoWysylki .= $_SESSION['adresDostawy']['kod_pocztowy'] . ' ' . $_SESSION['adresDostawy']['miasto'] . '<br />';

if ( KLIENT_POKAZ_WOJEWODZTWO == 'tak' ) {
    $DaneDoWysylki .= Klient::pokazNazweWojewodztwa($_SESSION['adresDostawy']['wojewodztwo']) . '<br />';
}

$DaneDoWysylki .= Klient::pokazNazwePanstwa($_SESSION['adresDostawy']['panstwo']) . '<br />';

if ( KLIENT_POKAZ_TELEFON == 'tak' ) {
    $DaneDoWysylki .= $GLOBALS['tlumacz']['TELEFON_SKROCONY'] . ' ' . $_SESSION['adresDostawy']['telefon'] . '<br />';
}

$DaneDoFaktury = '';

if ( $_SESSION['adresFaktury']['imie'] != '' && $_SESSION['adresFaktury']['nazwisko'] != '' ) {
    $DaneDoFaktury .= $_SESSION['adresFaktury']['imie'] . ' ' . $_SESSION['adresFaktury']['nazwisko'] . '<br />';
}

if ( $_SESSION['adresFaktury']['firma'] != '' ) {
    $DaneDoFaktury .= $_SESSION['adresFaktury']['firma'] . '<br />';
    $DaneDoFaktury .= $_SESSION['adresFaktury']['nip'] . '<br />';
}

$DaneDoFaktury .= $_SESSION['adresFaktury']['ulica'] . '<br />';

$DaneDoFaktury .= $_SESSION['adresFaktury']['kod_pocztowy'] . ' ' . $_SESSION['adresFaktury']['miasto'] . '<br />';

if ( KLIENT_POKAZ_WOJEWODZTWO == 'tak' ) {
    $DaneDoFaktury .= Klient::pokazNazweWojewodztwa($_SESSION['adresFaktury']['wojewodztwo']) . '<br />';
}

$DaneDoFaktury .= Klient::pokazNazwePanstwa($_SESSION['adresFaktury']['panstwo']);

// parametry do ustalenia dostepnych punktow odbioru
$WysylkaPotwierdzenieZamowienia = $wysylki->Potwierdzenie( $_SESSION['rodzajDostawy']['wysylka_id'], $_SESSION['rodzajDostawy']['wysylka_klasa'] );
$WysylkaPotwierdzenieZamowieniaInfo = '';
if ( isset($GLOBALS['tlumacz']['WYSYLKA_'.$_SESSION['rodzajDostawy']['wysylka_id'].'_INFORMACJA']) ) {
    $WysylkaPotwierdzenieZamowieniaInfo = $GLOBALS['tlumacz']['WYSYLKA_'.$_SESSION['rodzajDostawy']['wysylka_id'].'_INFORMACJA'];
    $_SESSION['rodzajDostawy']['informacja'] = $WysylkaPotwierdzenieZamowieniaInfo;
}

// parametry do ustalenia danych do wplaty
$platnosci = new Platnosci($_SESSION['rodzajDostawy']['wysylka_id']);
$PlatnoscPotwierdzenieZamowienia = $platnosci->Potwierdzenie( $_SESSION['rodzajPlatnosci']['platnosc_id'], $_SESSION['rodzajPlatnosci']['platnosc_klasa'] );

// meta tagi
$Meta = MetaTagi::ZwrocMetaTagi( basename(__FILE__) );
$tpl->dodaj('__META_TYTUL', $Meta['tytul']);
$tpl->dodaj('__META_SLOWA_KLUCZOWE', $Meta['slowa']);
$tpl->dodaj('__META_OPIS', $Meta['opis']);
unset($Meta);

// css do kalendarza
$tpl->dodaj('__CSS_PLIK', ',zebra_datepicker');
// dla wersji mobilnej
$tpl->dodaj('__CSS_KALENDARZ', ',zebra_datepicker');

// breadcrumb
$nawigacja->dodaj($GLOBALS['tlumacz']['NAGLOWEK_ZAMOWIENIE_POTWIERDZENIE']);
$tpl->dodaj('__BREADCRUMB', $nawigacja->sciezka(' ' . $GLOBALS['tlumacz']['NAWIGACJA_SEPARATOR'] . ' '));

// wyglad srodkowy
$srodek = new Szablony($Wyglad->TrescLokalna($WywolanyPlik), $ProduktyKoszyka);
//

$srodek->parametr('ProduktyUsluga', $ProduktUsluga);
$srodek->parametr('ProduktyOnline', $ProduktOnline);
$srodek->parametr('ProduktyNiestandardowe', $ProduktNiestandardowy);

unset($ProduktyKoszyka, $ProduktUsluga, $ProduktOnline, $ProduktNiestandardowy);

// maksymalny czas wysylki
$srodek->dodaj('__MAKSYMALNY_CZAS_WYSYLKI', '');
if ( $MaksymalnyCzasWysylkiProdukt == true ) {
     $srodek->dodaj('__MAKSYMALNY_CZAS_WYSYLKI', '<div class="Informacja">' . $MaksymalnyCzasWysylki . '</div>');
}
unset($MaksymalnyCzasWysylki, $MaksymalnyCzasWysylkiProdukt);

// wartosc koszyka
$ZawartoscKoszyka = $GLOBALS['koszykKlienta']->ZawartoscKoszyka();
$srodek->dodaj('__WARTOSC_KOSZYKA', $GLOBALS['waluty']->PokazCene($ZawartoscKoszyka['brutto'], $ZawartoscKoszyka['netto'], 0, $_SESSION['domyslnaWaluta']['id']));
unset($ZawartoscKoszyka);

$TekstZgody = str_replace('{INFO_NAZWA_SKLEPU}',DANE_NAZWA_FIRMY_PELNA,$GLOBALS['tlumacz']['ZGODA_NA_PRZEKAZANIE_DANYCH']);

// dodatkowe elementy do podsumowania zamowienia
$srodek->dodaj('__PODSUMOWANIE_ZAMOWIENIA', $PodsumowanieZamowienia);
$srodek->dodaj('__DANE_DO_WYSYLKI', $DaneDoWysylki);
$srodek->dodaj('__DANE_DO_FAKTURY', $DaneDoFaktury);
$srodek->dodaj('__WYSYLKA_W_POTWIERDZENIU', $WysylkaPotwierdzenieZamowienia);
$srodek->dodaj('__WYSYLKA_W_POTWIERDZENIU_INFORMACJA', $WysylkaPotwierdzenieZamowieniaInfo);
$srodek->dodaj('__PLATNOSC_W_POTWIERDZENIU', $PlatnoscPotwierdzenieZamowienia);
$srodek->dodaj('__TEKST_ZGODY', $TekstZgody);

// jezeli jest wylaczony wybor dokumentu sprzedazy
if ( KOSZYK_WYBOR_DOKUMENTU_SPRZEDAZY == 'nie' ) {
     $CssDokumentSprzedazy = 'style="display:none"';
}

$srodek->dodaj('__CSS_DOKUMENT_SPRZEDAZY', $CssDokumentSprzedazy);

$DodatkowePolaZamowienia = Zamowienie::pokazDodatkowePolaZamowienia($_SESSION['domyslnyJezyk']['id']);
if ( $DodatkowePolaZamowienia != '' ) {
     $DodatkowePolaZamowienia = '<div class="PolaZamowienie">' . $DodatkowePolaZamowienia . '</div>';
}

$srodek->dodaj('__DODATKOWE_POLA_ZAMOWIENIA', $DodatkowePolaZamowienia);

unset($DodatkowePolaZamowienia);

// dodatkowe adresy dostawy
$srodek->dodaj('__DODATKOWE_ADRESY_DOSTAWY', '');

$TablicaAdresow = array();
//
$zapytanie = "SELECT c.customers_id, 
                     a.address_book_id, 
                     a.entry_company, 
                     a.entry_nip, 
                     a.entry_pesel, 
                     a.entry_firstname, 
                     a.entry_lastname, 
                     a.entry_street_address, 
                     a.entry_postcode, 
                     a.entry_city, 
                     a.entry_country_id, 
                     a.entry_zone_id
                FROM customers c 
           LEFT JOIN address_book a ON a.customers_id = c.customers_id
               WHERE c.customers_id = '".$_SESSION['customer_id']."' AND c.customers_guest_account = '0' AND c.customers_status = '1'";

$sql = $GLOBALS['db']->open_query($zapytanie); 
if ((int)$GLOBALS['db']->ile_rekordow($sql) > 1) {
  
  $TablicaAdresow[] = array( 'id' => 0,
                             'text' => $GLOBALS['tlumacz']['WYBIERZ_INNY_ADRES_DOSTAWY'] );  

  while ( $info = $sql->fetch_assoc() ) {
  
        $TablicaAdresow[] = array( 'id' => $info['address_book_id'],
                                   'text' => ((!empty($info['entry_company'])) ? $info['entry_company'] . ', ' : '') . 
                                               $info['entry_firstname'] . ' ' . $info['entry_lastname'] . ', ' . 
                                               $info['entry_street_address'] . ', ' . 
                                               $info['entry_postcode'] . ' ' . 
                                               $info['entry_city'] .
                                               (($info['entry_country_id'] != $_SESSION['krajDostawy']['id']) ? ', ' . Klient::pokazNazwePanstwa($info['entry_country_id']) : ''));

  }
  
  $srodek->dodaj('__DODATKOWE_ADRESY_DOSTAWY', '<br />' . Funkcje::RozwijaneMenu('dodatkowe_adresy', $TablicaAdresow, '', ' id="wybor_adresu" style="width:80%"'));

  unset($TablicaAdresow);
  
}

$GLOBALS['db']->close_query($sql);
unset($zapytanie, $info);

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
     
     $Klikochron .= '_sdbag.push([\'products\', [' . "\n";
     
     $KlikochronProdukty = '';

     foreach ($_SESSION['koszyk'] AS $TablicaZawartosci) {
       
          $Produkt = new Produkt( Funkcje::SamoIdProduktuBezCech( $TablicaZawartosci['id'] ) );
          
          $IdKategoriiProduktu = array();
          $SciezkaKategoriiProduktu = Kategorie::SciezkaKategoriiId( $Produkt->info['id_kategorii'] );
          
          foreach ( explode('_', $SciezkaKategoriiProduktu) as $PodkategoriaProduktu ) {
              //
              $IdKategoriiProduktu[] = '{' . $PodkategoriaProduktu . ':"' . addslashes(Kategorie::NazwaKategoriiId( $PodkategoriaProduktu )) . '"}';
              //
          }
       
          $KlikochronProdukty .= "\n" . '{' . "\n";
          $KlikochronProdukty .= '  id:' . $Produkt->info['id'] . ',' . "\n";
          $KlikochronProdukty .= '  categories: [' . implode(',', $IdKategoriiProduktu) . '],' . "\n";
          $KlikochronProdukty .= '  name: "' . addslashes($Produkt->info['nazwa']) . '",' . "\n";
          $KlikochronProdukty .= '  price: "' . number_format($TablicaZawartosci['cena_brutto'], 2, '.', '') . '",' . "\n";
          $KlikochronProdukty .= '  currency: "' . $_SESSION['domyslnaWaluta']['kod'] . '",' . "\n";
          $KlikochronProdukty .= '  sku: "' . $TablicaZawartosci['nr_katalogowy'] . '",' . "\n";
          $KlikochronProdukty .= '  qty: ' . $TablicaZawartosci['ilosc'] . "\n";
          $KlikochronProdukty .= '},';   

          unset($Produkt, $IdKategoriiProduktu, $SciezkaKategoriiProduktu);
       
     }
     
     $KlikochronProdukty = substr($KlikochronProdukty, 0, -1);

     $Klikochron .= "\n" . $KlikochronProdukty . "\n" . ']]);' . "\n"; 
     $Klikochron .= '_sdbag.push([\'init\', \'checkout\']);' . "\n\n";     

     $Klikochron .= '(function () {' . "\n"; 
     $Klikochron .= 'var ss = document.createElement(\'script\'); ss.type = \'text/javascript\'; ss.async = true;' . "\n"; 
     $Klikochron .= 'ss.src = (\'https:\' == document.location.protocol ? \'https://\' : \'http://\') + \'www.schutzklick.de/jsapi/sisu-checkout-2.x.min.js\';' . "\n"; 
     $Klikochron .= 'var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ss, s);' . "\n"; 
     $Klikochron .= '})();' . "\n"; 
     $Klikochron .= '</script>' . "\n\n";  

     $Klikochron .= '<div id="interfacePlaceHolderSelector" style="margin:20px 0px 10px 0px"></div>';
}
$srodek->dodaj('__INTEGRACJA_KLIKOCHRON', $Klikochron);

$tpl->dodaj('__SRODKOWA_KOLUMNA', $srodek->uruchom());

unset($srodek, $WywolanyPlik, $PodsumowanieZamowienia, $DaneDoWysylki, $DaneDoFaktury, $WysylkaPotwierdzenieZamowienia, $WysylkaPotwierdzenieZamowieniaInfo, $PlatnoscPotwierdzenieZamowienia, $TekstZgody, $CssDokumentSprzedazy);

//maxkod analitycs
$ga = new gaOrder();
$gaCode = $ga->CheckoutStep3($_SESSION['koszyk']);
//

include('koniec.php');

?>