<?php

$GLOBALS['kolumny'] = 'srodkowa';

// plik
$WywolanyPlik = 'koszyk';

include('start.php');

$GLOBALS['tlumacz'] = array_merge( $i18n->tlumacz( array('WYSYLKI', 'PLATNOSCI', 'PRZYCISKI', 'KOSZYK','KUPONY_RABATOWE','PUNKTY','ZAMOWIENIE_REALIZACJA', 'PODSUMOWANIE_ZAMOWIENIA') ), $GLOBALS['tlumacz'] );

// produkty koszyka
$ProduktyKoszyka = array();

// dodatkowe parametry zamowienia
$DodatkoweInformacje = array();

if ( $GLOBALS['koszykKlienta']->KoszykIloscProduktow() > 0 ) {

    // wartosc produktow w promocji - potrzebne do wysylek
    $WartoscProduktowPromocje = 0;

    // przelicza dodatkowo koszyk
    $GLOBALS['koszykKlienta']->PrzeliczKoszyk(); 

    //
    // generuje tablice globalne z nazwami cech
    Funkcje::TabliceCech();         
    //
    foreach ($_SESSION['koszyk'] AS $TablicaZawartosci) {
        //
        $Produkt = new Produkt( Funkcje::SamoIdProduktuBezCech( $TablicaZawartosci['id'] ), 40, 40 );
        //        
        // sumuje wartosc produktow w promocji
        if ( $TablicaZawartosci['promocja'] == 'tak' ) {
             $WartoscProduktowPromocje += $TablicaZawartosci['cena_brutto'] * $TablicaZawartosci['ilosc'];
        }
        //
        // elementy kupowania
        $Produkt->ProduktKupowanie();     
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
            $KomentarzProduktu = '<span class="Komentarz"><img id="img_' . $Produkt->idUnikat . $TablicaZawartosci['id'] . '" onclick="EdytujKomentarz(\'' . $Produkt->idUnikat . $TablicaZawartosci['id'] . '\')" src="szablony/' . DOMYSLNY_SZABLON . '/obrazki/nawigacja/edytuj.png" alt="" title="' . $GLOBALS['tlumacz']['EDYTUJ_KOMENTARZ'] . '" />' . $GLOBALS['tlumacz']['KOMENTARZ_PRODUKTU'] . ' <b id="komentarz_' . $Produkt->idUnikat . $TablicaZawartosci['id'] . '">' . $TablicaZawartosci['komentarz'] . '</b></span>';
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
             $CenaProduktu = $GLOBALS['waluty']->PokazCene($TablicaZawartosci['cena_brutto'], $TablicaZawartosci['cena_netto'], 0, $_SESSION['domyslnaWaluta']['id'], CENY_BRUTTO_NETTO, false);
             $WartoscProduktu = $GLOBALS['waluty']->PokazCene($TablicaZawartosci['cena_brutto'] * $TablicaZawartosci['ilosc'], $TablicaZawartosci['cena_netto'] * $TablicaZawartosci['ilosc'], 0, $_SESSION['domyslnaWaluta']['id'], CENY_BRUTTO_NETTO, false);
             //
        }
        //
        $ProduktyKoszyka[$TablicaZawartosci['id']] = array('id'            => $TablicaZawartosci['id'],
                                                           'zdjecie'       => $Produkt->fotoGlowne['zdjecie_link'],
                                                           'nazwa'         => $Produkt->info['link'] . $JakieCechy,
                                                           'komentarz'     => $KomentarzProduktu,
                                                           'pola_txt'      => $PolaTekstowe,
                                                           'usun'          => ((KOSZYK_SPOSOB_USUWANIA == 'pojedyncze usuwanie') ? '<span class="UsunKoszyk" onclick="UsunZKoszyka(\'' . $Produkt->idUnikat . $TablicaZawartosci['id'] . '\')"></span>' : '<input type="checkbox" class="InputUsunKoszyk" name="usun[]" value="' . $Produkt->idUnikat . $TablicaZawartosci['id'] . '" />'),
                                                           'ilosc'         => (( $TablicaZawartosci['rodzaj_ceny'] == 'baza' ) ? '<input type="text" class="InputPrzeliczKoszyk" id="ilosc_' . $Produkt->idUnikat . $TablicaZawartosci['id'] . '" value="' . $TablicaZawartosci['ilosc'] . '" size="4" onchange="SprIlosc(this,' . $Produkt->zakupy['minimalna_ilosc'] . ',' . $Produkt->info['jednostka_miary_typ'] . ')" /> <div class="Przelicz"><a onclick="return DoKoszyka(\'' . $Produkt->idUnikat . $TablicaZawartosci['id'] . '\',\'przelicz\',0)" href="/" class="przycisk">' . $GLOBALS['tlumacz']['PRZELICZ'] . '</a></div>' : $TablicaZawartosci['ilosc']),
                                                           'cena'          => $CenaProduktu,
                                                           'wartosc'       => $WartoscProduktu);
        //
        unset($Produkt, $CenaProduktu, $WartoscProduktu, $KomentarzProduktu, $PolaTekstowe);
        //
    }
    //
    // parametry do ustalenia dostepnych wysylek
    $wysylki = new Wysylki($_SESSION['krajDostawy']['kod']);
    $TablicaWysylek = $wysylki->wysylki;

    if ( isset($_SESSION['rodzajDostawy']) && !array_key_exists($_SESSION['rodzajDostawy']['wysylka_id'], $TablicaWysylek) ) {
    
      unset($_SESSION['rodzajDostawy']);
      
    }
    
    // select z panstwami
    $ListaRozwijanaPanstw = Funkcje::RozwijaneMenu('kraj_dostawy',Klient::ListaPanstw('countries_iso_code_2'), $_SESSION['krajDostawy']['kod'], 'id="kraj_dostawy"');    
    
    if ( !isset($_SESSION['rodzajDostawy']) ) {
    
      $PierwszaWysylka = array_slice($TablicaWysylek,0,1);
      
      $_SESSION['rodzajDostawy'] = array(
                                         'wysylka_id' => $PierwszaWysylka['0']['id'],
                                         'wysylka_klasa' => $PierwszaWysylka['0']['klasa'],
                                         'wysylka_koszt' => $PierwszaWysylka['0']['wartosc'],
                                         'wysylka_nazwa' => $PierwszaWysylka['0']['text'],
                                         'wysylka_vat_id' => $PierwszaWysylka['0']['vat_id'],
                                         'wysylka_vat_stawka' => $PierwszaWysylka['0']['vat_stawka'],                                          
                                         'dostepne_platnosci' => $PierwszaWysylka['0']['dostepne_platnosci']);
                                         
      $KosztWysylki = $PierwszaWysylka['0']['wartosc'];
      $ProgBezplatnejWysylki = $PierwszaWysylka['0']['wysylka_free'];
      $DarmowaWysylkaPromocje = $PierwszaWysylka['0']['free_promocje'];
                                         
    } else {
    
      $IdBiezace = $_SESSION['rodzajDostawy']['wysylka_id'];
      unset($_SESSION['rodzajDostawy']);
      $_SESSION['rodzajDostawy'] = array(
                                         'wysylka_id' => $TablicaWysylek[$IdBiezace]['id'],
                                         'wysylka_klasa' => $TablicaWysylek[$IdBiezace]['klasa'],
                                         'wysylka_koszt' => $TablicaWysylek[$IdBiezace]['wartosc'],
                                         'wysylka_nazwa' => $TablicaWysylek[$IdBiezace]['text'],
                                         'wysylka_vat_id' => $TablicaWysylek[$IdBiezace]['vat_id'],
                                         'wysylka_vat_stawka' => $TablicaWysylek[$IdBiezace]['vat_stawka'],                                          
                                         'dostepne_platnosci' => $TablicaWysylek[$IdBiezace]['dostepne_platnosci'] );

      $KosztWysylki = $TablicaWysylek[$_SESSION['rodzajDostawy']['wysylka_id']]['wartosc'];
      $ProgBezplatnejWysylki = $TablicaWysylek[$_SESSION['rodzajDostawy']['wysylka_id']]['wysylka_free'];
      $DarmowaWysylkaPromocje = $TablicaWysylek[$_SESSION['rodzajDostawy']['wysylka_id']]['free_promocje'];
      
    }

    // radio z wysylkami
    $ListaRadioWysylek = '<div id="rodzaj_wysylki">'.Funkcje::ListaRadioKoszyk('rodzaj_wysylki', $TablicaWysylek, $_SESSION['rodzajDostawy']['wysylka_id'], '').'</div>';

    // parametry do ustalenia dostepnych platnosci
    $platnosci = new Platnosci($_SESSION['rodzajDostawy']['wysylka_id']);
    $TablicaPlatnosci = $platnosci->platnosci;

    if ( isset($_SESSION['rodzajPlatnosci']) && !array_key_exists($_SESSION['rodzajPlatnosci']['platnosc_id'], $TablicaPlatnosci) ) {
    
      unset($_SESSION['rodzajPlatnosci']);
      
    }
    
    if ( !isset($_SESSION['rodzajPlatnosci']) ) {
      $PierwszaPlatnosc = array_slice($TablicaPlatnosci,0,1);
      $KosztPlatnosci = $PierwszaPlatnosc['0']['wartosc'];
      $_SESSION['rodzajPlatnosci'] = array(
                                         'platnosc_id' => $PierwszaPlatnosc['0']['id'],
                                         'platnosc_klasa' => $PierwszaPlatnosc['0']['klasa'],
                                         'platnosc_koszt' => $PierwszaPlatnosc['0']['wartosc'],
                                         'platnosc_nazwa' => $PierwszaPlatnosc['0']['text']);
                                         
    } else {
    
      $KosztPlatnosci = $TablicaPlatnosci[$_SESSION['rodzajPlatnosci']['platnosc_id']]['wartosc'];
      
    }

    $CalkowityKoszt = $KosztWysylki + $KosztPlatnosci;
    $CalkowityKosztWysylki = $GLOBALS['waluty']->PokazCene($CalkowityKoszt, 0, 0, $_SESSION['domyslnaWaluta']['id']);

    // radio z platnosciami
    $ListaRadioPlatnosci = '<div id="rodzaj_platnosci">'.Funkcje::ListaRadioKoszyk('rodzaj_platnosci', $TablicaPlatnosci, $_SESSION['rodzajPlatnosci']['platnosc_id'], '').'</div>';

    $UkryjPrzycisk = '';
    if ( $_SESSION['rodzajPlatnosci']['platnosc_id'] == '0' || $_SESSION['rodzajDostawy']['wysylka_id'] == '0' ) {
      $UkryjPrzycisk = 'style="display:none;"';
    }
    
    // sprawdza czy jest wlaczony modul kuponu rabatowego
    $zapytanie = "SELECT skrypt, status FROM modules_total WHERE skrypt = 'kupon_rabatowy.php'"; 
    $sql = $GLOBALS['db']->open_query($zapytanie);
    $info = $sql->fetch_assoc();
    
    $UkryjKupon = '';
    if ( $info['status'] == '0' ) {
         $UkryjKupon = 'style="display:none;"';
    }
    
    $GLOBALS['db']->close_query($sql);
    unset($zapytanie, $info);

    // sprawdzenie czy jest wpisany kupon rabatowy i czy nadal spelnia warunki przyznania
    if ( isset($_SESSION['kuponRabatowy']) ) {
      $kupon = new Kupony($_SESSION['kuponRabatowy']['kupon_kod']);
      $TablicaKuponu = $kupon->kupon;
      if ( $_SESSION['kuponRabatowy'] != $TablicaKuponu ) {
          unset($_SESSION['kuponRabatowy']);
          $_SESSION['kuponRabatowy'] = $TablicaKuponu;
      }
      if ( $TablicaKuponu['kupon_status'] ) {
      } else {
        unset($_SESSION['kuponRabatowy']);
      }
    }

    // parametry do ustalenia podsumowania zamowienia
    $podsumowanie = new Podsumowanie();
    $PodsumowanieZamowienia = $podsumowanie->Generuj();

    // punkty klienta
    if ( SYSTEM_PUNKTOW_STATUS == 'tak' && SYSTEM_PUNKTOW_STATUS_KUPOWANIA == 'tak' && Punkty::PunktyAktywneDlaKlienta() ) {
    
      if ( isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0' ) {
        
        $punkty = new Punkty((int)$_SESSION['customer_id'], true);
        
        // jezeli jest wylaczone realizacja punktow jezeli w koszyku sa produkty za PUNKTY
        if ( $GLOBALS['koszykKlienta']->KoszykWartoscProduktowZaPunkty() == 0 || ( $GLOBALS['koszykKlienta']->KoszykWartoscProduktowZaPunkty() > 0 && SYSTEM_PUNKTOW_KUPOWANIE_PRODUKTOW == 'tak' ) ) {
          
          if ( $punkty->suma >= SYSTEM_PUNKTOW_MIN_ZAMOWIENIA && $GLOBALS['koszykKlienta']->KoszykWartoscProduktow() >= $GLOBALS['waluty']->PokazCeneBezSymbolu(SYSTEM_PUNKTOW_MIN_WARTOSC_ZAMOWIENIA,'',true) ) {
          
            $DodatkoweInformacje['WartoscPunktowKlienta'] = $punkty->suma;
            $DodatkoweInformacje['InfoPunktyKlienta'] = true;
            $DodatkoweInformacje['WartoscPunktowKlientaKwota'] = $punkty->wartosc;
            $DodatkoweInformacje['WartoscMaksymalnaPunktowKwota'] = $punkty->wartosc_maksymalna_kwota;

            $InfoPunkty = str_replace( '{WARTOSC_LACZNA}', '<b>'.$GLOBALS['waluty']->WyswietlFormatCeny($punkty->wartosc, $_SESSION['domyslnaWaluta']['id'], true, false).'</b>', $GLOBALS['tlumacz']['INFO_PUNKTY'] );
            $InfoPunkty = str_replace( '{WARTOSC_MAKSYMALNA}', '<b>'.$GLOBALS['waluty']->WyswietlFormatCeny($punkty->wartosc_maksymalna_kwota, $_SESSION['domyslnaWaluta']['id'], true, false).'</b>', $InfoPunkty );

            $WartoscZamowieniaDoPunktow = 0;
            foreach ( $_SESSION['podsumowanieZamowienia'] as $podsumowanie ) {
              if ( $podsumowanie['prefix'] == '1' ) {
                if ( $podsumowanie['klasa'] == 'ot_shipping' ) {
                  $WartoscZamowieniaDoPunktow;
                } else {
                  $WartoscZamowieniaDoPunktow += $podsumowanie['wartosc'];
                }
              } elseif ( $podsumowanie['prefix'] == '0' ) {
                $WartoscZamowieniaDoPunktow -= $podsumowanie['wartosc'];
              }
            }

            // wartosc punktow klienta
            $WartoscPunktowDoWykorzystania = $punkty->wartosc;

            // jezeli wartosc punktow klienta jest wieksza niz wartosc zamawianych produktow
            if ( $WartoscPunktowDoWykorzystania > $WartoscZamowieniaDoPunktow ) {
              $WartoscPunktowDoWykorzystania = $WartoscZamowieniaDoPunktow;
            }

            // jezeli wartosc punktow klienta jest wieksza niz maks wartosc punktow do wykorzystania w jednym zamowieniu
            if ( $WartoscPunktowDoWykorzystania > $punkty->wartosc_maksymalna_kwota ) {
              $WartoscPunktowDoWykorzystania = $punkty->wartosc_maksymalna_kwota;
            }

            $InfoPunktyDoWykorzystania = str_replace( '{KWOTA_PUNKTOW_W_ZAMOWIENIU}', '<b>'.$GLOBALS['waluty']->WyswietlFormatCeny($WartoscPunktowDoWykorzystania, $_SESSION['domyslnaWaluta']['id'], true, false).'</b>', $GLOBALS['tlumacz']['INFO_PUNKTY_DO_WYKORZYSTANIA'] );

            // ilosc punktow klienta
            $IloscPunktowDoWykorzystania = $punkty->suma;

            // jezeli przeliczona ilosc punktow klienta jest wieksza niz wylicona z wartosci zamowienia
            if ( $IloscPunktowDoWykorzystania > ($WartoscZamowieniaDoPunktow/$_SESSION['domyslnaWaluta']['przelicznik']) * SYSTEM_PUNKTOW_WARTOSC_PRZY_KUPOWANIU) {
              $IloscPunktowDoWykorzystania = ceil(($WartoscZamowieniaDoPunktow/$_SESSION['domyslnaWaluta']['przelicznik']) * SYSTEM_PUNKTOW_WARTOSC_PRZY_KUPOWANIU);
            }

            // jezeli ilosc punktow klienta jest wieksza niz maks ilosc punktow do wykorzystania w jednym zamowieniu
            if ( $IloscPunktowDoWykorzystania > SYSTEM_PUNKTOW_MAX_ZAMOWIENIA ) {
              $IloscPunktowDoWykorzystania = SYSTEM_PUNKTOW_MAX_ZAMOWIENIA;
            }
              
            $InfoPunktyDoWykorzystania = str_replace( '{ILOSC_PUNKTOW_W_ZAMOWIENIU}', '<b>'.$IloscPunktowDoWykorzystania.'</b>', $InfoPunktyDoWykorzystania );

            $DodatkoweInformacje['WartoscPunktowZamowienia'] = $IloscPunktowDoWykorzystania;
            
          }
          
          // wlaczenie informacji o zrezygnowaniu z punktow jezeli ilosc dostepnych punktow jest ponizej 0 a byly wczesniej aktywowane
          if ( $punkty->suma <= 0 && isset($_SESSION['punktyKlienta']) ) {
               $DodatkoweInformacje['InfoPunktyKlienta'] = true;
          }          

        }
        
      }
      
    }

    $BylKalkulator = false;

    // kalkulator ratalny Santander Consumer
    $KalkulatorSantander = '<div id="RataSantander"></div>';
    if ( isset($TablicaPlatnosci) && Funkcje::CzyJestWlaczonaPlatnosc('platnosc_santander', $TablicaPlatnosci) ) {
      $KalkulatorSantander = '<div id="RataSantander"><a onclick="PoliczRateSantander('.$_SESSION['podsumowanieZamowienia']['ot_total']['wartosc'].');" style="cursor: pointer;"><img src="' . KATALOG_ZDJEC . '/platnosci/oblicz_rate_santander_white_koszyk.png" alt="" /></a></div>';
      $BylKalkulator = true;
    }

    // kalkulator ratalny Lukas
    $KalkulatorLukas = '<div id="RataLukas"></div>';
    if ( isset($TablicaPlatnosci) && Funkcje::CzyJestWlaczonaPlatnosc('platnosc_lukas', $TablicaPlatnosci) ) {
      $KalkulatorLukas = '<div id="RataLukas"><a onclick="PoliczRateLukas('.$_SESSION['podsumowanieZamowienia']['ot_total']['wartosc'].');" style="cursor: pointer;"><img src="' . KATALOG_ZDJEC . '/platnosci/oblicz_rate_lukas_white.png" alt="" /></a></div>';
      $BylKalkulator = true;  
    }

    // kalkulator ratalny MBANK
    $KalkulatorMbank = '<div id="RataMbank"></div>';
    if ( isset($TablicaPlatnosci) && Funkcje::CzyJestWlaczonaPlatnosc('platnosc_mbank', $TablicaPlatnosci) ) {
      $KalkulatorMbank = '<div id="RataMbank"><a onclick="PoliczRateMbank('.$_SESSION['podsumowanieZamowienia']['ot_total']['wartosc'].');" style="cursor: pointer;"><img src="' . KATALOG_ZDJEC . '/platnosci/oblicz_rate_mbank_koszyk.png" alt="" /></a></div>';
      $BylKalkulator = true;  
    }

    // kalkulator ratalny PayU Raty
    $KalkulatorPayuRaty = '<div id="RataPayU"></div>';
    if ( isset($_SESSION['podsumowanieZamowienia']) && $_SESSION['podsumowanieZamowienia']['ot_total']['wartosc'] >= 300 && $_SESSION['podsumowanieZamowienia']['ot_total']['wartosc'] < 20000 ) {
        if ( isset($TablicaPlatnosci) && Funkcje::CzyJestWlaczonaPlatnosc('platnosc_payu', $TablicaPlatnosci) ) {
          $zap = "SELECT kod, wartosc FROM modules_payment_params WHERE kod ='PLATNOSC_PAYU_RATY_WLACZONE'";
          $sqlp = $GLOBALS['db']->open_query($zap);
          if ((int)$GLOBALS['db']->ile_rekordow($sqlp) > 0) {
            $infop = $sqlp->fetch_assoc();
            if ( $infop['wartosc'] == 'tak' ) {
              $KalkulatorPayuRaty = '<div id="RataPayU"><a onclick="PoliczRatePauYRaty('.$_SESSION['podsumowanieZamowienia']['ot_total']['wartosc'].');" style="cursor: pointer;"><img src="' . KATALOG_ZDJEC . '/platnosci/oblicz_rate_payu_koszyk.png" alt="" /></a></div>';
              $BylKalkulator = true;
            }
          }
          $GLOBALS['db']->close_query($sqlp); 
          unset($zap, $infop);    
        }
    }

    if ( isset($ProgBezplatnejWysylki) && $ProgBezplatnejWysylki > 0 ) {
        //
        if ( isset($_SESSION['podsumowanieZamowienia']['ot_total']['wartosc']) ) {
             //
             $WartoscZamowienia = $_SESSION['podsumowanieZamowienia']['ot_total']['wartosc'];
             //       
             // jezeli musi pominac promocje
             if ( $DarmowaWysylkaPromocje == 'nie' ) {
                 //
                 $WartoscZamowienia -= $WartoscProduktowPromocje;
                 //
             }
             //
             if ( $WartoscZamowienia > $ProgBezplatnejWysylki ) {
                //
                $BezplatnaDostawa = '';
                //
             } else { 
                //
                $BezplatnaDostawa = str_replace( '{KWOTA}', '<b>'.$GLOBALS['waluty']->WyswietlFormatCeny($ProgBezplatnejWysylki, $_SESSION['domyslnaWaluta']['id'], true, false).'</b>', $GLOBALS['tlumacz']['INFO_BEZPLATNA_DOSTAWA'] );
                //
                if ( $DarmowaWysylkaPromocje == 'nie' ) {
                     $BezplatnaDostawa .= ' ' . $GLOBALS['tlumacz']['INFO_BEZPLATNA_DOSTAWA_BEZ_PROMOCJI'];
                }
                //
                $DodatkoweInformacje['InfoWysylkaDarmo'] = true;
             }
             //
             unset($WartoscZamowienia);
             //
          } else {
             //
             $BezplatnaDostawa = '';
             //       
        }
    } else {
        //
        $BezplatnaDostawa = '';
        //
    }
    
    unset($WartoscProduktowPromocje);

}
    
$Zalogowany = 'nie';
if ( isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0' ) {
     $Zalogowany = 'tak';
}

//
// wyglad srodkowy
$srodek = new Szablony($Wyglad->TrescLokalna($WywolanyPlik), $ProduktyKoszyka, $DodatkoweInformacje, $Zalogowany);
//
unset($ProduktyKoszyka, $Zalogowany);

$Meta = MetaTagi::ZwrocMetaTagi( basename(__FILE__) );
// meta tagi
$tpl->dodaj('__META_TYTUL', $Meta['tytul']);
$tpl->dodaj('__META_SLOWA_KLUCZOWE', $Meta['slowa']);
$tpl->dodaj('__META_OPIS', $Meta['opis']);
unset($Meta);

// breadcrumb
$nawigacja->dodaj($GLOBALS['tlumacz']['NAGLOWEK_KOSZYK']);
$tpl->dodaj('__BREADCRUMB', $nawigacja->sciezka(' ' . $GLOBALS['tlumacz']['NAWIGACJA_SEPARATOR'] . ' '));

$tpl->dodaj('__CSS_PLIK', ',listingi');

if ( $GLOBALS['koszykKlienta']->KoszykIloscProduktow() > 0 ) {
  
    // modul wysylek i platnosci
    
    $srodek->dodaj('__WYBOR_PANSTWA', $ListaRozwijanaPanstw);

    $srodek->dodaj('__WYBOR_WYSYLKI', $ListaRadioWysylek);

    $srodek->dodaj('__WYBOR_PLATNOSCI', $ListaRadioPlatnosci);

    $srodek->dodaj('__KOSZT_WYSYLKI', $CalkowityKosztWysylki);

    $srodek->dodaj('__PODSUMOWANIE_ZAMOWIENIA', $PodsumowanieZamowienia);

    $srodek->dodaj('__PODSUMOWANIE_INFORMACJA', $GLOBALS['tlumacz']['INFO_WARTOSC_ZAMOWIENIA_PO_ZALOGOWANIU']);

    $srodek->dodaj('__DISPLAY_NONE', $UkryjPrzycisk);
    
    $srodek->dodaj('__WYSWIETL_KUPON', $UkryjKupon);

    $srodek->dodaj('__KALKULATOR_SANTANDER', $KalkulatorSantander);
    $srodek->dodaj('__KALKULATOR_LUKAS', $KalkulatorLukas);
    $srodek->dodaj('__KALKULATOR_MBANK', $KalkulatorMbank);
    $srodek->dodaj('__KALKULATOR_PAYURATY', $KalkulatorPayuRaty);

    $srodek->dodaj('__KALKULATOR_CSS','');
    if ( $BylKalkulator == false ) {
         $srodek->dodaj('__KALKULATOR_CSS',' style="display:none"');
    }
    
    $srodek->dodaj('__CSS_PDF_KOSZYK','');
    if ( PDF_KOSZYK_POBRANIE_PDF == 'nie' ) {
         $srodek->dodaj('__CSS_PDF_KOSZYK',' style="display:none"');
    }  

    $srodek->dodaj('__BEZPLATNA_DOSTAWA', $BezplatnaDostawa);
    
    // nastepna strona zamowienia
    $isHTTPS = false;
    if ( WLACZENIE_SSL == 'tak' ) {
        $isHTTPS = true;
    }

    if ( isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 ) {
      $ZamowienieNastepnyKrok = ( $isHTTPS ? ADRES_URL_SKLEPU_SSL : ADRES_URL_SKLEPU ) . '/zamowienie-potwierdzenie.html';
    } else {
      $ZamowienieNastepnyKrok = ( $isHTTPS ? ADRES_URL_SKLEPU_SSL : ADRES_URL_SKLEPU ) . '/zamowienie-logowanie.html';
    }  

    $srodek->dodaj('__ZAMOWIENIE_NASTEPNY_KROK', $ZamowienieNastepnyKrok);
    
    unset($ZamowienieNastepnyKrok, $isHTTPS);
    
    // produkty gratisowe
    $ListaProduktowGratisowych = '';
    //
    $Gratisy = Gratisy::TablicaGratisow( 'tak' );
    //
    if ( count($Gratisy) > 0 ) {
        ob_start();
        
        // listing wersji mobilnej
        if ( $_SESSION['mobile'] == 'tak' ) {    
        
            if (in_array( 'listing_gratisy.mobilne.php', $Wyglad->PlikiListingiLokalne )) {
                require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_gratisy.mobilne.php');
            }
            
          } else { 
          
            if (in_array( 'listing_gratisy.php', $Wyglad->PlikiListingiLokalne )) {
                  require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_gratisy.php');
                } else {
                  require('listingi/listing_gratisy.php');
            }
            
        }
        
        $ListaProduktowGratisowych = ob_get_contents();
        ob_end_clean();    
    }
    //
    $srodek->dodaj('__LISTING_PRODUKTY_GRATISOWE', $ListaProduktowGratisowych);  
    unset($ListaProduktowGratisowych, $Gratisy);
    
    //  
    
    // minimalne zamowienie dla grupy klientow
    $srodek->dodaj('__MINIMALNE_ZAMOWIENIE', '');

    $MinimalneZamowienieGrupy = Klient::MinimalneZamowienie();

    if ( $MinimalneZamowienieGrupy > 0 ) {

        $MinZamowienie = $GLOBALS['waluty']->PokazCeneBezSymbolu($MinimalneZamowienieGrupy,'',true);
        $WartoscKoszyka = $GLOBALS['koszykKlienta']->ZawartoscKoszyka();

        if ( $WartoscKoszyka['brutto'] < $MinZamowienie ) {
             //
             $srodek->dodaj('__MINIMALNE_ZAMOWIENIE', '<strong>' .  $GLOBALS['tlumacz']['MINIMALNE_ZAMOWIENIE'] . ' <span>' . $GLOBALS['waluty']->WyswietlFormatCeny($MinZamowienie, $_SESSION['domyslnaWaluta']['id'], true, false) . '</span></strong>');
             $srodek->dodaj('__DISPLAY_NONE', 'style="display:none"');
             //
        }
        unset($MinZamowienie, $WartoscKoszyka);
        
    }   

    unset($MinimalneZamowienieGrupy); 

    // link uzywany w koszyku do przycisku kontynuuj zakupy
    $srodek->dodaj('__LINK_POPRZEDNIEJ_STRONY', $_SESSION['stat']['przed_koszykiem']);
    
    // informacja o zbyt malej iloscui punktow do zlozenia zamowienia
    $srodek->dodaj('__ZBYT_MALA_ILOSC_PUNKTOW', '');

    if ( SYSTEM_PUNKTOW_STATUS == 'tak' && SYSTEM_PUNKTOW_STATUS_KUPOWANIA == 'tak' && Punkty::PunktyAktywneDlaKlienta() ) {
      
        if ( isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0' ) {
      
            // informacja (okno) z mozliwoscia aktywowania punktow za zamowienie
            
            // jezeli jest wylaczone realizacja punktow jezeli w koszyku sa produkty za PUNKTY
            if ( $GLOBALS['koszykKlienta']->KoszykWartoscProduktowZaPunkty() == 0 || ( $GLOBALS['koszykKlienta']->KoszykWartoscProduktowZaPunkty() > 0 && SYSTEM_PUNKTOW_KUPOWANIE_PRODUKTOW == 'tak' ) ) {

                if ( $punkty->suma >= SYSTEM_PUNKTOW_MIN_ZAMOWIENIA ) {
                  
                  if ( isset($DodatkoweInformacje['InfoPunktyKlienta']) && $DodatkoweInformacje['InfoPunktyKlienta'] ) {
                    
                    $srodek->dodaj('__INFO_PUNKTY', $InfoPunkty);
                    $srodek->dodaj('__INFO_PUNKTY_DO_WYKORZYSTANIA', $InfoPunktyDoWykorzystania);
                    
                  }
                  
                }
                
                if ( isset($_SESSION['punktyKlienta']) ) {
                  
                  $InfoPunktyWykorzystane = str_replace( '{ILOSC_PUNKTOW}', '<b>'.$_SESSION['punktyKlienta']['punkty_ilosc'].'</b>', $GLOBALS['tlumacz']['INFO_PUNKTY_WYKORZYSTANE'] );
                  $srodek->dodaj('__INFO_PUNKTY_WYKORZYSTANE', $InfoPunktyWykorzystane);
                  unset($InfoPunktyWykorzystane);
                  
                }
                
            }      
        
            // sprawdza czy jest wystarczajac ilosc punktow do zlozenia zamowienia
      
            // punkty wykorzystane do zamowienia
            $PktWykorzystane = 0;
            
            if ( isset($_SESSION['punktyKlienta']['punkty_ilosc']) ) {
                 $PktWykorzystane = $_SESSION['punktyKlienta']['punkty_ilosc'];
            }
                 
            if ( $punkty->suma_punktow_klienta < $PktWykorzystane + $GLOBALS['koszykKlienta']->KoszykWartoscProduktowZaPunkty() ) {

                 $srodek->dodaj('__ZBYT_MALA_ILOSC_PUNKTOW', '<strong>' . $GLOBALS['tlumacz']['ZBYT_MALA_ILOSC_PUNKTOW'] . '</strong>');
                 $srodek->dodaj('__DISPLAY_NONE', 'style="display:none"');

            }             
            
            unset($PktWykorzystane);
        
        }
        
    }
    
    // przycisk zapisania do koszyka
    if (isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0') {
        //
        $srodek->dodaj('__CSS_ZAPISZ_KOSZYK', '');
        //
      } else {
        //
        $srodek->dodaj('__CSS_ZAPISZ_KOSZYK', 'style="display:none"');
        //
    }  
    
    $ZawartoscKoszyka = $GLOBALS['koszykKlienta']->ZawartoscKoszyka();

    // wartosc koszyka
    $srodek->dodaj('__WARTOSC_KOSZYKA', $GLOBALS['waluty']->PokazCene($ZawartoscKoszyka['brutto'], $ZawartoscKoszyka['netto'], 0, $_SESSION['domyslnaWaluta']['id'], CENY_BRUTTO_NETTO, false));

    // waga produktow koszyka
    $srodek->dodaj('__WAGA_KOSZYKA', number_format($ZawartoscKoszyka['waga'], 3, ',', ''));

    unset($ZawartoscKoszyka);

}


$tpl->dodaj('__SRODKOWA_KOLUMNA', $srodek->uruchom());
unset($srodek, $WywolanyPlik);

// kiedy jest coś w koszyku musi to tu być ponieważ zamowienie-podsumowanie.html wywołuje znowu ten plik
// i w podsumowaniu mamy CheckoutStep1 a powinno być Purchase
//maxkod analitycs
if($GLOBALS['koszykKlienta']->KoszykIloscProduktow() > 0)
{
    $ga = new gaOrder();
    $gaCode = $ga->CheckoutStep1($_SESSION['koszyk']);
}
//
include('koniec.php');

?>