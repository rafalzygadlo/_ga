<?php


// przy karcie produktu moga byc tylko 2 kolumny - lewa i srodek lub prawa i srodek
$GLOBALS['kolumny'] = 'wszystkie_lewa';

// plik
$WywolanyPlik = 'produkt';

include('start.php');


$Produkt = new Produkt( Funkcje::SamoIdProduktuBezCech($_GET['idprod']), SZEROKOSC_OBRAZEK_SREDNI, WYSOKOSC_OBRAZEK_SREDNI, 'DoKoszykaKartaProduktu' );                

if ($Produkt->CzyJestProdukt == true) {

    // sprawdzenie linku SEO z linkiem w przegladarce
    Seo::link_Spr($Produkt->info['adres_seo']);

    // elementy kupowania
    $Produkt->ProduktKupowanie();      
    $Produkt->ProduktDostepnosc();
    $Produkt->ProduktCzasWysylki();
    $Produkt->ProduktProducent();
    $Produkt->ProduktDodatkowePola();
    $Produkt->ProduktDodatkowePolaTekstowe();
    $Produkt->ProduktRecenzje();
    $Produkt->ProduktLinki();
    $Produkt->ProduktDodatkoweZakladki();
    $Produkt->ProduktPliki();
    $Produkt->ProduktYoutube();
    $Produkt->ProduktFilmyFLV();
    $Produkt->ProduktMp3();
    
    if ( KARTA_PRODUKTU_ZAKLADKA_ZAKUPY == 'tak' ) {
         $Produkt->ProduktZakupy();
    }
    
    if ( KARTA_PRODUKTU_STAN_PRODUKTU == 'tak' ) {
         $Produkt->ProduktStanProduktu();
    }
    if ( KARTA_PRODUKTU_GWARANCJA == 'tak' ) {
         $Produkt->ProduktGwarancja();
    }    
    //
    
    // okresla czy ilosc jest ulamkowa zeby pozniej odpowiednio sformatowac wynik
    $Przecinek = 2;
    // jezeli sa wartosci calkowite to dla pewnosci zrobi int
    if ( $Produkt->info['jednostka_miary_typ'] == '1' ) {
        $Przecinek = 0;
    }    

    // aktualizacja informacji o wyswietlaniach produktu
    $sql = $GLOBALS['db']->open_query("SELECT products_viewed FROM products_description WHERE products_id = '" . $Produkt->info['id'] . "' AND language_id = '" . $_SESSION['domyslnyJezyk']['id'] . "'");  
    $ile = $sql->fetch_assoc();
    //
    $pola = array(array('products_viewed', $ile['products_viewed'] + 1));		
    $GLOBALS['db']->update_query('products_description' , $pola, " products_id = '".Funkcje::SamoIdProduktuBezCech($_GET['idprod'])."' AND language_id = '".$_SESSION['domyslnyJezyk']['id']."'");	
    unset($pola); 
    //
    $GLOBALS['db']->close_query($sql);  
    //
    
    // aktualizacja w sesji informacji o produktach poprzednio ogladanych
    $_SESSION['produktyPoprzednioOgladane'][Funkcje::SamoIdProduktuBezCech($_GET['idprod'])] = Funkcje::SamoIdProduktuBezCech($_GET['idprod']);

    $Meta = MetaTagi::ZwrocMetaTagi( basename(__FILE__) );
    //
    // meta tagi
    if ( $Meta['nazwa_pliku'] != null ) { 
         //     
         $tpl->dodaj('__META_TYTUL', MetaTagi::MetaTagiProduktPodmien('tytul', $Produkt, $Meta));
         $tpl->dodaj('__META_SLOWA_KLUCZOWE', MetaTagi::MetaTagiProduktPodmien('slowa', $Produkt, $Meta));
         $tpl->dodaj('__META_OPIS', MetaTagi::MetaTagiProduktPodmien('opis', $Produkt, $Meta));
         //
      } else {
         //
         $tpl->dodaj('__META_TYTUL', ((empty($Produkt->metaTagi['tytul'])) ? $Meta['tytul'] : $Produkt->metaTagi['tytul']));
         $tpl->dodaj('__META_SLOWA_KLUCZOWE', ((empty($Produkt->metaTagi['slowa'])) ? $Meta['slowa'] : $Produkt->metaTagi['slowa']));
         $tpl->dodaj('__META_OPIS', ((empty($Produkt->metaTagi['opis'])) ? $Meta['opis'] : $Produkt->metaTagi['opis']));
         //         
    }

    $tpl->dodaj('__META_OG_ADRES_STRONY', ADRES_URL_SKLEPU . '/' . $Produkt->info['adres_seo']);
    $tpl->dodaj('__META_OG_FOTO', ADRES_URL_SKLEPU . '/' . KATALOG_ZDJEC . '/' . $Produkt->fotoGlowne['plik_zdjecia']);

    unset($Meta);
    
    // Breadcrumb dla kategorii produktow
    if ( $_SESSION['sciezka'] != '' ) {
        //
        $RodzajSciezka = explode('#', $_SESSION['sciezka']);
        //
        if ($RodzajSciezka[0] == 'kategoria') {
            //
            $tablica_kategorii = explode('_',$RodzajSciezka[1]); 
            //
            $sciezkaPath = '';
            for ( $i = 0, $n = count($tablica_kategorii); $i < $n; $i++ ) {
                if ( isset($GLOBALS['tablicaKategorii'][$tablica_kategorii[$i]]['IdKat']) ) {
                    //
                    $sciezkaPath .= $GLOBALS['tablicaKategorii'][$tablica_kategorii[$i]]['IdKat'] . '_';
                    //
                    if ( $GLOBALS['tablicaKategorii'][$tablica_kategorii[$i]]['Widocznosc'] == '1' ) {
                         $nawigacja->dodaj($GLOBALS['tablicaKategorii'][$tablica_kategorii[$i]]['Nazwa'], Seo::link_SEO($GLOBALS['tablicaKategorii'][$tablica_kategorii[$i]]['Nazwa'], substr($sciezkaPath, 0, -1) , 'kategoria'));
                    }
                    //
                }
            }
            unset($tablica_kategorii, $sciezkaPath);
            //
        }
        if ($RodzajSciezka[0] == 'producent') {
            //
            $nawigacja->dodaj($Produkt->producent['nazwa'], Seo::link_SEO($Produkt->producent['nazwa'], $Produkt->producent['id'], 'producent'));
            //$_SESSION['sciezka'] = '';
            //
        }
        //
        unset($RodzajSciezka);
        //
    }    
    $nawigacja->dodaj($Produkt->info['nazwa']);
    $tpl->dodaj('__BREADCRUMB', $nawigacja->sciezka(' ' . $GLOBALS['tlumacz']['NAWIGACJA_SEPARATOR'] . ' '));
    
    // style css
    $tpl->dodaj('__CSS_PLIK_GLOWNY', '');
    $tpl->dodaj('__CSS_PLIK', ',produkt');
    
    $GLOBALS['tlumacz'] = array_merge( $i18n->tlumacz( array('SYSTEM_PUNKTOW','WYSYLKI') ), $GLOBALS['tlumacz'] );
    
    // wyswietlanie informacji o wysylkach produktu
    $NajtanszaWysylka = '';
    if ( KARTA_PRODUKTU_KOSZTY_WYSYLKI == 'tak' ) {
    
        // parametry do ustalenia dostepnych wysylek
        $tablicaWysylek = array();
        //
        $wysylki = new Wysylki($_SESSION['krajDostawy']['kod'], $Produkt->info['id'], $Produkt->info['waga'], $Produkt->info['cena_brutto_bez_formatowania'], $Produkt->info['dostepne_wysylki'], $Produkt->info['gabaryt'], $Produkt->info['koszt_wysylki']);
        $tablicaWysylek = $wysylki->wysylki;
        //
        $NajtanszaWysylka = array( 'koszt' => 10000, 'nazwa' => '' );
        $DostepneWysylki = '';
        //
        $przelicznik = 1 / $_SESSION['domyslnaWaluta']['przelicznik'];
        $marza = 1 + ( $_SESSION['domyslnaWaluta']['marza']/100 );

        foreach ( $tablicaWysylek as $Wysylka ) {
            //
            // jezeli produkt ma darmowa wysylke
            if ( $Produkt->info['darmowa_wysylka'] == 'tak' && $Produkt->info['wykluczona_darmowa_wysylka'] == 'nie' ) {
                 $Wysylka['wartosc'] = 0;
            }

            $Wysylka['wartosc'] = number_format( round( ($Wysylka['wartosc'] / $przelicznik) * $marza, CENY_MIEJSCA_PO_PRZECINKU ), CENY_MIEJSCA_PO_PRZECINKU, '.', '');
            //
            // sprawdza nizszy koszt oraz pomija odbior osobisty
            if ( $Wysylka['wartosc'] < $NajtanszaWysylka['koszt'] && $Wysylka['id'] != '9' ) {
                //
                $NajtanszaWysylka = array( 'koszt' => $Wysylka['wartosc'], 'nazwa' => $Wysylka['text'] );            
                // 
            }
            //
            $CenaWysylki = $GLOBALS['waluty']->FormatujCene($Wysylka['wartosc'], 0, 0, $_SESSION['domyslnaWaluta']['id'], true);

            $DostepneWysylki .= $Wysylka['text'] . ' - ' . $CenaWysylki['brutto'] . '<br />';
            unset($CenaWysylki);
            //
        }
        //
        if ( $NajtanszaWysylka['nazwa'] == '' ) {
             $NajtanszaWysylka = '';
        }
        unset($tablicaWysylek, $przelicznik, $marza);

    }
    
    $WyswietlaniePrzyciskuSzybkiZakup = 'nie';
    
    if ( KARTA_PRODUKTU_ZAMOWIENIE_KONTAKT == 'tak' && $Produkt->info['tylko_za_punkty'] == 'nie' && $Produkt->info['status_akcesoria'] == 'nie' ) {
      
        $WyswietlaniePrzyciskuSzybkiZakup = 'tak';
      
        if ( isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0' && KARTA_PRODUKTU_ZAMOWIENIE_KONTAKT_RODZAJ == 'tak' ) {
             $WyswietlaniePrzyciskuSzybkiZakup = 'nie';
        }
    
    }
    
    // wyglad srodkowy
    $srodek = new Szablony($Wyglad->TrescLokalna($WywolanyPlik), $Produkt, $NajtanszaWysylka, $WyswietlaniePrzyciskuSzybkiZakup);   
    
    unset($WyswietlaniePrzyciskuSzybkiZakup);

    $srodek->dodaj('__DOMYSLNY_SZABLON', DOMYSLNY_SZABLON);    
    
    // najtansza wysylka i tablica wysylek
    $srodek->dodaj('__NAJTANSZY_KOSZT_WYSYLKI', '');  
    $srodek->dodaj('__NAJTANSZY_SPOSOB_WYSYLKI', '');   
    $srodek->dodaj('__SPOSOB_WYSYLKI_TIP', '');
    //
    if ( KARTA_PRODUKTU_KOSZTY_WYSYLKI == 'tak' ) {
        //
        if ( is_array($NajtanszaWysylka) ) {
            //
            $CenaWysylki = $GLOBALS['waluty']->FormatujCene($NajtanszaWysylka['koszt'], 0, 0, $_SESSION['domyslnaWaluta']['id']);
            $srodek->dodaj('__NAJTANSZY_KOSZT_WYSYLKI', $CenaWysylki['brutto']);  
            unset($CenaWysylki);
            //
            $srodek->dodaj('__NAJTANSZY_SPOSOB_WYSYLKI', $NajtanszaWysylka['nazwa']);
            $srodek->dodaj('__SPOSOB_WYSYLKI_TIP', '<b>' . $GLOBALS['tlumacz']['KOSZT_WYSYLKI_INFO'] . '</b>' . $DostepneWysylki);
            //
            unset($DostepneWysylki);
        }
        //
    }
    unset($NajtanszaWysylka);  
    
    // elementy karty produktu
    $srodek->dodaj('__ID_PRODUKTU_UNIKALNE', $Produkt->idUnikat . $Produkt->info['id']);
    
    $Ikonki = '';
    // ikonki
    if ( $Produkt->ikonki['nowosc'] == '1' ) {
        $Ikonki .= '<span><b>'. $GLOBALS['tlumacz']['IKONKA_NOWOSC'] . '</b></span>';
    }
    if ( $Produkt->ikonki['promocja'] == '1' ) {
        $Ikonki .= '<span><b>' . $GLOBALS['tlumacz']['IKONKA_PROMOCJA'] . '</b></span>';
    } 
    if ( $Produkt->ikonki['polecany'] == '1' ) {
        $Ikonki .= '<span><b>' . $GLOBALS['tlumacz']['IKONKA_POLECANY'] . '</b></span>';
    }
    if ( $Produkt->ikonki['hit'] == '1' ) {
        $Ikonki .= '<span><b>' . $GLOBALS['tlumacz']['IKONKA_HIT'] . '</b></span>';
    }     
    $srodek->dodaj('__IKONKI', $Ikonki);
    unset($Ikonki);

    // dodatkowe zdjecia produktu
    $DodatkoweZdjecia = $Produkt->ProduktDodatkoweZdjecia();
    //
    $ZdjeciaDuze = '';
    $ZdjeciaMiniaturki = '';
    //
    $FotoDod = 1;
    //
    if ( TEKST_COPYRIGHT_POKAZ == 'tak' || OBRAZ_COPYRIGHT_POKAZ == 'tak' ) {
        $zdjecie_glowne = Funkcje::pokazObrazekWatermark($Produkt->fotoGlowne['plik_zdjecia']);
    } else {
        $zdjecie_glowne = KATALOG_ZDJEC . '/' . $Produkt->fotoGlowne['plik_zdjecia'];
    }
    
    // wersja mobilna
    if ( $_SESSION['mobile'] == 'tak' ) { 
    
        $ZdjeciaDuze = '<a class="ZdjecieProduktu" href="'. $zdjecie_glowne . '" title="' . $Produkt->fotoGlowne['opis_zdjecia'] . '">' . Funkcje::pokazObrazek($Produkt->fotoGlowne['plik_zdjecia'], $Produkt->fotoGlowne['opis_zdjecia'], SZEROKOSC_OBRAZEK_SREDNI, WYSOKOSC_OBRAZEK_SREDNI, array(), 'class="Zdjecie" itemprop="image"', 'sredni') . '</a>';
        
      } else { 
    
        // glowne zdjecie produktu
        $ZdjeciaDuze .= '<a class="ZdjecieProduktu Wyswietlane" id="DuzeFoto' . $FotoDod . '" href="'. $zdjecie_glowne . '" title="' . $Produkt->fotoGlowne['opis_zdjecia'] . '">' . Funkcje::pokazObrazek($Produkt->fotoGlowne['plik_zdjecia'], $Produkt->fotoGlowne['opis_zdjecia'], SZEROKOSC_OBRAZEK_SREDNI, WYSOKOSC_OBRAZEK_SREDNI, array(), 'class="Zdjecie" itemprop="image"', 'sredni') . '</a>';
        $ZdjeciaMiniaturki .= Funkcje::pokazObrazek($Produkt->fotoGlowne['plik_zdjecia'], $Produkt->fotoGlowne['opis_zdjecia'], SZEROKOSC_MINIATUREK_KARTA_PRODUKTU, WYSOKOSC_MINIATUREK_KARTA_PRODUKTU, array(), 'class="Zdjecie" id="Foto' . $FotoDod . '"', 'maly', true, false, false);
        //
        
    }
    
    if ( count($DodatkoweZdjecia) > 0 ) {
        //
        foreach ($DodatkoweZdjecia As $DodFoto) {
            //
            $FotoDod++;
            //
            // generowanie alt zdjec
            $AltFoto = ((empty($DodFoto['alt'])) ? $Produkt->info['nazwa'] : $DodFoto['alt']);
            //
            if ( TEKST_COPYRIGHT_POKAZ == 'tak' || OBRAZ_COPYRIGHT_POKAZ == 'tak' ) {
                $zdjecie_dodatkowe = Funkcje::pokazObrazekWatermark($DodFoto['zdjecie']);
            } else {
                $zdjecie_dodatkowe = KATALOG_ZDJEC . '/' . $DodFoto['zdjecie'];
            }
            
           // wersja mobilna
            if ( $_SESSION['mobile'] == 'tak' ) { 

                $ZdjeciaMiniaturki .= '<a class="ZdjecieProduktu" href="'. $zdjecie_dodatkowe . '" title="' . $AltFoto . '">' .  Funkcje::pokazObrazek($DodFoto['zdjecie'], $AltFoto, SZEROKOSC_MINIATUREK_KARTA_PRODUKTU, WYSOKOSC_MINIATUREK_KARTA_PRODUKTU, array(), 'class="Zdjecie"', 'maly', true, false, false) . '</a>';                 
                
              } else {
              
                $ZdjeciaDuze .= '<a class="ZdjecieProduktu" id="DuzeFoto' . $FotoDod . '" href="' . $zdjecie_dodatkowe . '" title="' . ((empty($DodFoto['alt'])) ? $Produkt->fotoGlowne['opis_zdjecia'] : $DodFoto['alt']) . '">' . Funkcje::pokazObrazek($DodFoto['zdjecie'], $AltFoto, SZEROKOSC_OBRAZEK_SREDNI, WYSOKOSC_OBRAZEK_SREDNI, array(), 'class="Zdjecie"', 'sredni') . '</a>';
                $ZdjeciaMiniaturki .= Funkcje::pokazObrazek($DodFoto['zdjecie'], $AltFoto, SZEROKOSC_MINIATUREK_KARTA_PRODUKTU, WYSOKOSC_MINIATUREK_KARTA_PRODUKTU, array(), 'class="Zdjecie" id="Foto' . $FotoDod . '"', 'maly', true, false, false);                   
              
            }
            //
            unset($AltFoto);
            //
        }
        //
        unset($TablicaMetaTagow, $FotoDod);
        //
    }

    // zdjecia duze
    $srodek->dodaj('__ZDJECIA_DUZE', $ZdjeciaDuze);
    // zdjecia miniaturki
    if ( count($DodatkoweZdjecia) > 0 ) {
        $srodek->dodaj('__ZDJECIA_MINIATURKI', $ZdjeciaMiniaturki);
      } else {
        $srodek->dodaj('__ZDJECIA_MINIATURKI', '');
    }
    //
    unset($ZdjeciaDuze, $ZdjeciaMiniaturki);
    
    // nazwa produktu
    $srodek->dodaj('__NAZWA_PRODUKTU', $Produkt->info['nazwa']);
    
    // data dostepnosci
    $srodek->dodaj('__DATA_DOSTEPNOSCI', $Produkt->info['data_dostepnosci']);
    
    // srednia ocena produktu
    $srodek->dodaj('__SREDNIA_OCENA_GWIAZDKI', $Produkt->recenzjeSrednia['srednia_ocena_obrazek']);
    //
    $srodek->dodaj('__SREDNIA_OCENA_ILOSC_TEKST', $Produkt->recenzjeSrednia['srednia_ocena']);
    $srodek->dodaj('__SREDNIA_OCENA_ILOSC_GLOSOW', '<span class="WszystkieRecenzje">' . $Produkt->recenzjeSrednia['ilosc_glosow'] . '</span>');    
    
    // producent logo albo nazwa
    $srodek->dodaj('__PRODUCENT', '');
    if ( KARTA_PRODUKTU_PRODUCENT == 'tak' ) {
        //
        if ( trim($Produkt->producent['foto']) != '' ) {
            $srodek->dodaj('__PRODUCENT', $Produkt->producent['foto_link']);
          } else {
            $srodek->dodaj('__PRODUCENT', $Produkt->producent['link']);
        }    
        //
    }
    
    // ceny
    
    if ( CENY_DLA_WSZYSTKICH == 'tak' || ( CENY_DLA_WSZYSTKICH == 'nie' && ( isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0' ) ) ) {
        
        // cena poprzednia
        $srodek->dodaj('__CENA_POPRZEDNIA', '');
        if ( $Produkt->info['cena_poprzednia_bez_formatowania'] > 0 ) {
            //
            $srodek->dodaj('__CENA_POPRZEDNIA', $GLOBALS['waluty']->WyswietlFormatCeny( $Produkt->info['cena_poprzednia_bez_formatowania'], $_SESSION['domyslnaWaluta']['id'], true, false ));
            //
        }       
        
        // cena katalogowa
        $srodek->dodaj('__CENA_KATALOGOWA', '');
        $srodek->dodaj('__CENA_OSZCZEDZASZ', '');
        if ( $Produkt->info['cena_katalogowa_bez_formatowania'] > 0 ) {
            //
            $srodek->dodaj('__CENA_KATALOGOWA', $GLOBALS['waluty']->WyswietlFormatCeny( $Produkt->info['cena_katalogowa_bez_formatowania'], $_SESSION['domyslnaWaluta']['id'], true, false ));
            //
            // oszczedzasz
            if ( KARTA_PRODUKTU_CENA_KATALOGOWA_TYP == 'procent' ) {
                //
                $oszczedzasz = ( 1 - ( $Produkt->info['cena_brutto_bez_formatowania'] / $Produkt->info['cena_katalogowa_bez_formatowania'] ) ) * 100;
                if ( KARTA_PRODUKTU_CENA_KATALOGOWA_TYP_ZAOKRAGLENIE == 'ułamek' ) {
                     $srodek->dodaj('__CENA_OSZCZEDZASZ', number_format($oszczedzasz,2, '.', '') . '%');
                   } else {
                     $srodek->dodaj('__CENA_OSZCZEDZASZ', number_format($oszczedzasz,0, '.', '') . '%');
                }
                unset($oszczedzasz);
                //
              } else {
                //
                $srodek->dodaj('__CENA_OSZCZEDZASZ', $GLOBALS['waluty']->WyswietlFormatCeny( $Produkt->info['cena_katalogowa_bez_formatowania'] - $Produkt->info['cena_brutto_bez_formatowania'], $_SESSION['domyslnaWaluta']['id'], true, false ));
                //
            }
            //        
        }
        
        // cena netto i brutto
        if ( $Produkt->info['tylko_za_punkty'] == 'nie' ) {
             //
             $srodek->dodaj('__CENA_BRUTTO', $GLOBALS['waluty']->WyswietlFormatCeny( $Produkt->info['cena_brutto_bez_formatowania'], $_SESSION['domyslnaWaluta']['id'], true, false ));
             $srodek->dodaj('__CENA_NETTO', $GLOBALS['waluty']->WyswietlFormatCeny( $Produkt->info['cena_netto_bez_formatowania'], $_SESSION['domyslnaWaluta']['id'], true, false ));
             //
          } else {
             // jezeli kupowanie tylko za punkty
             $srodek->dodaj('__CENA_BRUTTO', $GLOBALS['waluty']->PokazCenePunkty( $Produkt->info['cena_w_punktach'], $Produkt->info['cena_brutto_bez_formatowania'], false ));
             $srodek->dodaj('__CENA_NETTO', $GLOBALS['waluty']->PokazCenePunkty( $Produkt->info['cena_w_punktach'], $Produkt->info['cena_netto_bez_formatowania'], false ));
             //
        }
        
        // ceny do inputow - ukryte
        $srodek->dodaj('__CENA_BRUTTO_BEZ_FORMATOWANIA', $Produkt->info['cena_brutto_bez_formatowania']);
        $srodek->dodaj('__CENA_NETTO_BEZ_FORMATOWANIA', $Produkt->info['cena_netto_bez_formatowania']);
        $srodek->dodaj('__CENA_POPRZEDNIA_BEZ_FORMATOWANIA', $Produkt->info['cena_poprzednia_bez_formatowania']);
        $srodek->dodaj('__CENA_KATALOGOWA_BEZ_FORMATOWANIA', $Produkt->info['cena_katalogowa_bez_formatowania']);
        
        // cena w puktach jezeli produkt jest tylko za PUNKTY
        $srodek->dodaj('__CENA_PRODUKTU_PKT', $Produkt->info['cena_w_punktach']);
    
    } else {
    
        // cena poprzednia
        $srodek->dodaj('__CENA_POPRZEDNIA', '');
        
        // cena netto i brutto
        $srodek->dodaj('__CENA_BRUTTO', '');
        $srodek->dodaj('__CENA_NETTO', '');
        
        // ceny do inputow - ukryte
        $srodek->dodaj('__CENA_BRUTTO_BEZ_FORMATOWANIA', '0');
        $srodek->dodaj('__CENA_NETTO_BEZ_FORMATOWANIA', '0');
        $srodek->dodaj('__CENA_POPRZEDNIA_BEZ_FORMATOWANIA', '0');    
    
    }
    
    // jezeli produkt nie ma ceny
    $srodek->dodaj('__INFO_BRAK_CENY_PRODUKTU','');
    if ( $Produkt->info['jest_cena'] == 'nie' ) {
        //
        if ( CENY_DLA_WSZYSTKICH == 'tak' || ( CENY_DLA_WSZYSTKICH == 'nie' && ( isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0' ) ) ) {
             $srodek->dodaj('__INFO_BRAK_CENY_PRODUKTU', $GLOBALS['tlumacz']['CENA_ZAPYTAJ_O_CENE']);
           } else {
             $srodek->dodaj('__INFO_BRAK_CENY_PRODUKTU', $GLOBALS['tlumacz']['CENA_TYLKO_DLA_ZALOGOWANYCH']);
        }
        //
    }
    
    // informacja o rabatach
    $srodek->dodaj('__INFO_O_RABATACH_PRODUKTU', '');
    if ( $Produkt->info['rabat_produktu'] > 0 ) {
        $srodek->dodaj('__INFO_O_RABATACH_PRODUKTU', $GLOBALS['tlumacz']['INFO_RABAT_CENY'] .' <strong>' . $Produkt->info['rabat_produktu'] . '%</strong>');
    }
    
    // dostepnosc
    $srodek->dodaj('__DOSTEPNOSC', $Produkt->dostepnosc['dostepnosc']);
    
    // czas wysylki
    $srodek->dodaj('__CZAS_WYSYLKI', $Produkt->czas_wysylki);
    
    // stan produktu
    $srodek->dodaj('__STAN_PRODUKTU', $Produkt->stan_produktu);    
    
    // gwarancja produktu
    $srodek->dodaj('__GWARANCJA', $Produkt->gwarancja);
    
    // nr katalogowy
    $srodek->dodaj('__NR_KATALOGOWY', $Produkt->info['nr_katalogowy']);
    
    // kod producenta
    $srodek->dodaj('__KOD_PRODUCENTA', $Produkt->info['kod_producenta']);
    
    // kod ean
    $srodek->dodaj('__KOD_EAN', $Produkt->info['ean']);
    
    // kod pkwiu
    $srodek->dodaj('__KOD_PKWIU', $Produkt->info['pkwiu']);    
    
    // dostepna ilosc - stan magazynowy
    // wersja graficzna lub tekstowa
    if ( KARTA_PRODUKTU_MAGAZYN_FORMA == 'liczba' ) {
         $srodek->dodaj('__STAN_MAGAZYNOWY', number_format( $Produkt->info['ilosc'], $Przecinek, '.', '' ) . ' ' . $Produkt->info['jednostka_miary']);
       } else {
         $srodek->dodaj('__STAN_MAGAZYNOWY', Produkty::PokazPasekMagazynu($Produkt->info['ilosc']));   
    }

    // cechy produktu
    $srodek->dodaj('__OPCJE_PRODUKTU', $Produkt->ProduktCechyGeneruj());

    // kupowanie
    // jezeli jest sklep jako katalog produktow
    if ( PRODUKT_KUPOWANIE_STATUS == 'nie' ) {
        //
        $srodek->dodaj('__INPUT_ILOSC', '');
        $srodek->dodaj('__PRZYCISK_KUP', '');
        $srodek->dodaj('__INFO_NIEDOSTEPNY', '');
        $srodek->dodaj('__RATY_SANTANDER', '');
        $srodek->dodaj('__SANTANDER_PARAMETRY', '');
        $srodek->dodaj('__RATY_LUKAS', '');
        $srodek->dodaj('__SANTANDER_PARAMETRY', '');
        $srodek->dodaj('__RATY_MBANK', '');
        $srodek->dodaj('__MBANK_PARAMETRY', '');
        $srodek->dodaj('__RATY_PAYURATY', '');
        $srodek->dodaj('__PAYURATY_PARAMETRY', '');
        //
    } else {
        //
        $srodek->dodaj('__INPUT_ILOSC', $GLOBALS['tlumacz']['ZAMAWIANA_ILOSC'] . ' <input type="text" id="ilosc_' . $Produkt->idUnikat . $Produkt->id_produktu . '" value="' . $Produkt->zakupy['domyslna_ilosc'] . '" size="3" onchange="SprIlosc(this,' . $Produkt->zakupy['minimalna_ilosc'] . ',' . $Produkt->info['jednostka_miary_typ'] . ')" name="ilosc" />' . $Produkt->info['jednostka_miary']);
        $srodek->dodaj('__PRZYCISK_KUP', $Produkt->zakupy['przycisk_kup_karta']);
        //
        if ( $Produkt->info['status_kupowania'] == 'tak' ) {
             //
             $srodek->dodaj('__INFO_NIEDOSTEPNY', '<span class="Info">' . $GLOBALS['tlumacz']['PRODUKT_NIEDOSTEPNY'] . '</span>');

             // Tworzenie tablicy systemow ratalnych
             $SystemyRatalne = Funkcje::AktywneSystemyRatalne();

             // Jezeli jest wlaczony modul ratalny Santander
             if ( isset($SystemyRatalne['platnosc_santander']) && count($SystemyRatalne['platnosc_santander']) > 0 ) {
                $srodek->dodaj('__SANTANDER_PARAMETRY', $SystemyRatalne['platnosc_santander']['PLATNOSC_SANTANDER_NUMER_SKLEPU'].';'.$SystemyRatalne['platnosc_santander']['PLATNOSC_SANTANDER_WARIANT_SKLEPU'].';'.$SystemyRatalne['platnosc_santander']['PLATNOSC_WARTOSC_ZAMOWIENIA_MIN'].';'.$SystemyRatalne['platnosc_santander']['PLATNOSC_WARTOSC_ZAMOWIENIA_MAX'] );
                $srodek->dodaj('__RATY_SANTANDER', '<div id="RatySantander" style="margin-bottom:10px;"><a onclick="PoliczRateSantander();" style="cursor: pointer;"><img src="'.KATALOG_ZDJEC . '/platnosci/oblicz_rate_santander_white_produkt.png" alt="" /></a></div>');
             } else {
                $srodek->dodaj('__SANTANDER_PARAMETRY', '');
                $srodek->dodaj('__RATY_SANTANDER', '');
             }

             // Jezeli jest wlaczony modul ratalny MBANK
             if ( isset($SystemyRatalne['platnosc_mbank']) && count($SystemyRatalne['platnosc_mbank']) > 0 ) {
                $srodek->dodaj('__MBANK_PARAMETRY', $SystemyRatalne['platnosc_mbank']['PLATNOSC_MBANK_NUMER_SKLEPU'].';'.$SystemyRatalne['platnosc_mbank']['PLATNOSC_WARTOSC_ZAMOWIENIA_MIN'].';'.$SystemyRatalne['platnosc_mbank']['PLATNOSC_WARTOSC_ZAMOWIENIA_MAX'] );
                $srodek->dodaj('__RATY_MBANK', '<div id="RatyMbank" style="margin-bottom:10px;"><a onclick="PoliczRateMbank();" style="cursor: pointer;"><img src="'.KATALOG_ZDJEC . '/platnosci/oblicz_rate_mbank_produkt.png" alt="" /></a></div>');
             } else {
                $srodek->dodaj('__MBANK_PARAMETRY', '');
                $srodek->dodaj('__RATY_MBANK', '');
             }

             // Jezeli jest wlaczony modul ratalny PayU
             if ( (isset($SystemyRatalne['platnosc_payu']) && count($SystemyRatalne['platnosc_payu']) > 0) && ($Produkt->info['cena_brutto_bez_formatowania'] >= 300 && $Produkt->info['cena_brutto_bez_formatowania'] <= 20000)) {
                $srodek->dodaj('__PAYURATY_PARAMETRY', '300;20000');
                $srodek->dodaj('__RATY_PAYURATY', '<div id="RatyPayU" style="margin-bottom:10px;"><a onclick="PoliczRatePauYRaty();" style="cursor: pointer;"><img src="'.KATALOG_ZDJEC . '/platnosci/raty_payu_small_grey.png" alt="" /></a></div>');
             } else {
                $srodek->dodaj('__PAYURATY_PARAMETRY', '');
                $srodek->dodaj('__RATY_PAYURATY', '');
             }


             // Jezeli jest wlaczony modul ratalny Lukas
             if ( isset($SystemyRatalne['platnosc_lukas']) && count($SystemyRatalne['platnosc_lukas']) > 0 ) {
                $lukas_ok = true;
                $wykluczoneKategorie = explode(',',$SystemyRatalne['platnosc_lukas']['PLATNOSC_LUKAS_KATEGORIE']);
                for($i=0, $x=sizeof($wykluczoneKategorie); $i<$x; $i++){
                    if ( $wykluczoneKategorie[$i] == $Produkt->info['id_kategorii'] ) {
                        $lukas_ok = false;
                    }
                }
                if ( $lukas_ok ) {
                    $srodek->dodaj('__LUKAS_PARAMETRY', $SystemyRatalne['platnosc_lukas']['PLATNOSC_LUKAS_NUMER_SKLEPU'].';'.$SystemyRatalne['platnosc_lukas']['PLATNOSC_WARTOSC_ZAMOWIENIA_MIN'].';'.$SystemyRatalne['platnosc_lukas']['PLATNOSC_WARTOSC_ZAMOWIENIA_MAX'] );
                    $srodek->dodaj('__RATY_LUKAS', '<div id="RatyLukas" style="margin-bottom:10px;"><a onclick="PoliczRateLukas();" style="cursor: pointer;"><img src="'.KATALOG_ZDJEC . '/platnosci/oblicz_rate_lukas_produkt.png" alt="" /></a></div>');
                } else {
                    $srodek->dodaj('__LUKAS_PARAMETRY', '');
                    $srodek->dodaj('__RATY_LUKAS', '');
                }
             } else {
                $srodek->dodaj('__LUKAS_PARAMETRY', '');
                $srodek->dodaj('__RATY_LUKAS', '');
             }

             //
        } else {
             //
             $srodek->dodaj('__INPUT_ILOSC', '');
             $srodek->dodaj('__PRZYCISK_KUP', '<span class="Info">' . $GLOBALS['tlumacz']['PRODUKT_NIE_MOZNA_KUPIC'] . '</span>');
             $srodek->dodaj('__INFO_NIEDOSTEPNY', '<span class="Info">' . $GLOBALS['tlumacz']['PRODUKT_NIE_MOZNA_KUPIC'] . '</span>');
             $srodek->dodaj('__RATY_SANTANDER', '');
             $srodek->dodaj('__SANTANDER_PARAMETRY', '');
             $srodek->dodaj('__RATY_LUKAS', '');
             $srodek->dodaj('__LUKAS_PARAMETRY', '');
             $srodek->dodaj('__RATY_MBANK', '');
             $srodek->dodaj('__MBANK_PARAMETRY', '');
             $srodek->dodaj('__RATY_PAYURATY', '');
             $srodek->dodaj('__PAYURATY_PARAMETRY', '');
             //
        }
    }
    
    // jezeli kupowanie tylko za PUNKTY to nie ma rat
    if ( $Produkt->info['tylko_za_punkty'] == 'tak' ) {
         //
         $srodek->dodaj('__RATY_SANTANDER', '');
         $srodek->dodaj('__SANTANDER_PARAMETRY', '');
         $srodek->dodaj('__RATY_LUKAS', '');
         $srodek->dodaj('__LUKAS_PARAMETRY', '');
         $srodek->dodaj('__RATY_MBANK', '');
         $srodek->dodaj('__MBANK_PARAMETRY', '');
         $srodek->dodaj('__RATY_PAYURATY', '');
         $srodek->dodaj('__PAYURATY_PARAMETRY', '');      
         //
    }
    
    //
    // css do kupowania - pokazuje albo przycisk kupowania albo info o tym ze nie mozna kupic
    if ( $Produkt->zakupy['mozliwe_kupowanie'] == 'tak' ) {
        $srodek->dodaj('__CSS_KOSZYK','');
        $srodek->dodaj('__CSS_INFO_KOSZYK','style="display:none"');
      } else {
        $srodek->dodaj('__CSS_KOSZYK','style="display:none"');
        $srodek->dodaj('__CSS_INFO_KOSZYK','');
    }
    
    // jezeli produkt jest za punkty a klient nie jest zalogowany - nie moze kupic produktu i info ma sie nie wyswietlac
    if ( $Produkt->info['tylko_za_punkty'] == 'tak' && ((!isset($_SESSION['customer_id']) || (int)$_SESSION['customer_id'] == 0) || $_SESSION['gosc'] == '1') ) {
         //
         $srodek->dodaj('__INFO_NIEDOSTEPNY', '<span class="Info">' . $GLOBALS['tlumacz']['PRODUKT_TYLKO_ZALOGOWANI'] . '</span>');
         //
    }
        
    // przycisk do schowka
    $srodek->dodaj('__PRZYCISK_SCHOWEK', '');
    if (PRODUKT_SCHOWEK_STATUS == 'tak') {
        $srodek->dodaj('__PRZYCISK_SCHOWEK', '<span onclick="DoSchowka(' . $Produkt->info['id'] . ')">' . $GLOBALS['tlumacz']['LISTING_DODAJ_DO_SCHOWKA'] . '</span>');
    }
    
    // zapytanie o produkt
    $srodek->dodaj('__LINK_ZAPYTANIA_O_PRODUKT', '');
    if ( isset($Wyglad->Formularze[2]) ) {
         $srodek->dodaj('__LINK_ZAPYTANIA_O_PRODUKT', ( WLACZENIE_SSL == 'tak' ? ADRES_URL_SKLEPU_SSL."/" : '') . Seo::link_SEO( $Wyglad->Formularze[2], 2, 'formularz' ) . '/produkt=' . Funkcje::SamoIdProduktuBezCech($_GET['idprod']));    
    }
    
    // polec produkt znajomemu
    $srodek->dodaj('__LINK_POLEC_PRODUKT', '');
    if ( isset($Wyglad->Formularze[3]) ) {
         $srodek->dodaj('__LINK_POLEC_PRODUKT', ( WLACZENIE_SSL == 'tak' ? ADRES_URL_SKLEPU_SSL."/" : '') . Seo::link_SEO( $Wyglad->Formularze[3], 3, 'formularz' ) . '/produkt=' . Funkcje::SamoIdProduktuBezCech($_GET['idprod']));     
    }

    // link negocjacji ceny
    $srodek->dodaj('__LINK_NEGOCJUJ_CENE', '');
    if ( isset($Wyglad->Formularze[4]) ) {
         $srodek->dodaj('__LINK_NEGOCJUJ_CENE', ( WLACZENIE_SSL == 'tak' ? ADRES_URL_SKLEPU_SSL."/" : '') . Seo::link_SEO( $Wyglad->Formularze[4], 4, 'formularz' ) . '/produkt=' . Funkcje::SamoIdProduktuBezCech($_GET['idprod'])); 
    }
    
    // link karty produktu pdf
    $srodek->dodaj('__LINK_PRODUKT_PDF', Seo::link_SEO( $Produkt->info['nazwa_seo'], $Produkt->info['id'], 'produkt_pdf') );     

    // portale
    $srodek->dodaj('__ADRES_STRONY_PRODUKTU', urlencode(ADRES_URL_SKLEPU . '/' . $Produkt->info['adres_seo'])); 
    $srodek->dodaj('__NAZWA_PRODUKTU', $Produkt->info['nazwa']); 
    $srodek->dodaj('__ZDJECIE_PRODUKTU', urlencode(ADRES_URL_SKLEPU . '/' . KATALOG_ZDJEC . '/' . $Produkt->fotoGlowne['plik_zdjecia'])); 
    
    // facebook    
    $srodek->dodaj('__FACEBOOK_FORMAT', INTEGRACJA_FB_LUBIETO_STYL); 
    $srodek->dodaj('__FACEBOOK_KOLOR', INTEGRACJA_FB_LUBIETO_KOLOR);
    // szerokosci i wysokosci
    if ( INTEGRACJA_FB_LUBIETO_STYL == 'standard' ) {
         $srodek->dodaj('__FACEBOOK_SZEROKOSC', '250'); 
         $srodek->dodaj('__FACEBOOK_WYSOKOSC', '80'); 
    }
    if ( INTEGRACJA_FB_LUBIETO_STYL == 'button_count' ) {
         $srodek->dodaj('__FACEBOOK_SZEROKOSC', '130'); 
         $srodek->dodaj('__FACEBOOK_WYSOKOSC', '20'); 
    }  
    if ( INTEGRACJA_FB_LUBIETO_STYL == 'box_count' ) {
         $srodek->dodaj('__FACEBOOK_SZEROKOSC', '80'); 
         $srodek->dodaj('__FACEBOOK_WYSOKOSC', '70'); 
    }       
    
    // nasza klasa 
    $srodek->dodaj('__NK_FORMAT', INTEGRACJA_NK_FAJNE_STYL); 
    $srodek->dodaj('__NK_KOLOR', ((INTEGRACJA_NK_FAJNE_KOLOR == 'jasny') ? '0' : '1'));   

    // google plus
    $srodek->dodaj('__GOGOLE_ROZMIAR', INTEGRACJA_PLUSONE_ROZMIAR); 
    $srodek->dodaj('__GOOGLE_INFO', INTEGRACJA_PLUSONE_ADNOTACJA);      
    $srodek->dodaj('__GOOGLE_ROZMIAR', INTEGRACJA_PLUSONE_SZEROKOSC);  

    // zakladki 
    // opis produktu
    $srodek->dodaj('__OPIS_PRODUKTU', $Produkt->info['opis']);    
    
    // filmy youtube - ciag do javascript
    $CiagJs = '';
    foreach ( $Produkt->Youtube as $Film ) {
        $CiagJs .= $Film['id_film'] . "," . $Film['film'] . "," . $Film['szerokosc'] . "," . $Film['wysokosc'] . ";";
    }
    $srodek->dodaj('__KOD_YOUTUBE', substr($CiagJs, 0, -1));   
    unset($CiagJs);
    
    // filmy flv - ciag do javascript
    $CiagJs = '';
    foreach ( $Produkt->FilmyFlv as $Film ) {
        $CiagJs .= $Film['id_film'] . "," . strrev($Film['film']) . "," . $Film['szerokosc'] . "," . $Film['wysokosc'] . "," . $Film['ekran'] .";";
    }
    $srodek->dodaj('__KOD_FLV', substr($CiagJs, 0, -1));  
    unset($CiagJs);   

    // pliki mp3 - ciag do javascript
    $CiagJs = '';
    foreach ( $Produkt->Mp3 as $Mp3 ) {
        $CiagJs .= $Mp3['id_mp3'] . "," . strrev($Mp3['plik']) . ";";
    }
    $srodek->dodaj('__KOD_MP3', substr($CiagJs, 0, -1));  
    unset($CiagJs);      
    
    // jezeli nikt nie napisal recenzji wyswietli informacje
    if ( $Produkt->recenzjeSrednia['ilosc_glosow'] == 0 ) {
         $srodek->dodaj('__INFO_O_BRAKU_RECENZJI',$GLOBALS['tlumacz']['RECENZJA_BADZ_PIERWSZY']);
    }
    
    // system punktow i recenzje    
    if ( SYSTEM_PUNKTOW_STATUS == 'tak' && (int)SYSTEM_PUNKTOW_PUNKTY_RECENZJE > 0 && Punkty::PunktyAktywneDlaKlienta() ) {
        $srodek->dodaj('__INFO_O_PUNKTACH_RECENZJI', str_replace('{ILOSC_PUNKTOW}', (int)SYSTEM_PUNKTOW_PUNKTY_RECENZJE, $GLOBALS['tlumacz']['PUNKTY_RECENZJE']));
    }    
    $srodek->dodaj('__LINK_DO_NAPISANIA_RECENZJI', 'napisz-recenzje-rw-' . $Produkt->info['id'] . '.html'); 
     
    // informacja o systemie punktow
    $srodek->dodaj('__INFO_O_PUNKTACH_PRODUKTU','');
    if ( SYSTEM_PUNKTOW_STATUS == 'tak' && Punkty::PunktyAktywneDlaKlienta() && $Produkt->info['tylko_za_punkty'] == 'nie' ) {
        $iloscPunktow = ceil(($Produkt->info['cena_brutto_bez_formatowania']/$_SESSION['domyslnaWaluta']['przelicznik']) * SYSTEM_PUNKTOW_WARTOSC);
        $srodek->dodaj('__INFO_O_PUNKTACH_PRODUKTU', str_replace('{ILOSC_PUNKTOW}', '<span>' . $iloscPunktow . '</span>', $GLOBALS['tlumacz']['PUNKTY_PRODUKT']));
        unset($iloscPunktow);
    }      
    
    // opinie facebook
    $srodek->dodaj('__KOMENTARZE_FACEBOOK', '');
    if ( INTEGRACJA_FB_OPINIE_WLACZONY == 'tak' ) {
        //
        $KomentarzeFb = '<div id="fb-root"></div><script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "https://connect.facebook.net/pl_PL/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, \'script\', \'facebook-jssdk\'));</script>';
        $KomentarzeFb .= '<br /><div class="fb-comments" data-href="' . ADRES_URL_SKLEPU . '/' . $Produkt->info['adres_seo'] . '" ' . (((int)INTEGRACJA_FB_OPINIE_SZEROKOSC > 200) ? 'data-width="' . INTEGRACJA_FB_OPINIE_SZEROKOSC . '"' : '') . ' data-numposts="' . INTEGRACJA_FB_OPINIE_ILOSC_POSTOW . '" data-colorscheme="' . INTEGRACJA_FB_OPINIE_KOLOR . '"></div>';
        //
        $srodek->dodaj('__KOMENTARZE_FACEBOOK', $KomentarzeFb);
        unset($KomentarzeFb);
        //    
    }
    
    // akcesoria dodatkowe
    $zapytanie = Produkty::SqlProduktyAkcesoriaDodatkowe( $Produkt->info['id'] );
    $sql = $GLOBALS['db']->open_query($zapytanie);    
    //
    $IloscProduktow = (int)$GLOBALS['db']->ile_rekordow($sql);
    $srodek->parametr('AkcesoriaDodatkoweIlosc', $IloscProduktow); 
    //
    ob_start();
    
    // listing wersji mobilnej
    if ( $_SESSION['mobile'] == 'tak' ) {    
    
        if (in_array( 'listing_akcesoria_dodatkowe.mobilne.php', $Wyglad->PlikiListingiLokalne )) {
            require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_akcesoria_dodatkowe.mobilne.php');
        }
        
      } else {
      
        if (in_array( 'listing_akcesoria_dodatkowe.php', $Wyglad->PlikiListingiLokalne )) {
            require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_akcesoria_dodatkowe.php');
          } else {
            require('listingi/listing_akcesoria_dodatkowe.php');
        }
    
    }
    
    $ListaDodatkowychAkcesorii = ob_get_contents();
    ob_end_clean(); 
    
    $srodek->dodaj('__LISTING_AKCESORIA', $ListaDodatkowychAkcesorii); 
    unset($ListaDodatkowychAkcesorii);
    
    
    // dodatkowe pola opisowe
    $PolaTekstowe = '';
    foreach ( $Produkt->dodatkowePolaTekstowe as $TxtPole ) {
        //
        $PolaTekstowe .= '<span id="txt_' . $TxtPole['id_pola'] . '" ' . ((trim($TxtPole['opis']) != '') ? 'class="TxtOpis"' : '') . '>' . $TxtPole['nazwa'] . '</span>';
        //
        switch( $TxtPole['typ'] ) {
            case 'input': $PolaTekstowe .= '<input type="text" id="pole_txt_' . $TxtPole['id_pola'] . '" name="pole_txt_' . $TxtPole['id_pola'] . '" class="UsunTekst" value="' . $TxtPole['domyslny'] . '" data-text="" size="30" autocomplete="off" />'; break;
            case 'textarea': $PolaTekstowe .= '<textarea id="pole_txt_' . $TxtPole['id_pola'] . '" name="pole_txt_' . $TxtPole['id_pola'] . '" rows="4" class="UsunTekst" cols="25" data-text="">' . $TxtPole['domyslny'] . '</textarea>'; break;
            case 'plik': $PolaTekstowe .= '<input type="hidden" id="pole_txt_' . $TxtPole['id_pola'] . '" name="pole_txt_' . $TxtPole['id_pola'] . '" value="" />
                                           <input type="file" class="wgraniePliku" id="plik_' . $TxtPole['id_pola'] . '" name="plik_' . $TxtPole['id_pola'] . '" />
                                           <div id="wynik_plik_' . $TxtPole['id_pola'] . '"></div>'; break;       
        }  
        //
    }
    $srodek->dodaj('__POLA_TEKSTOWE', $PolaTekstowe);
    //
    if ( $PolaTekstowe != '' ) {
         $srodek->dodaj('__PLIK_FORMULARZA', 'inne/wgranieForm.php?tok=' . Sesje::Token());
         $srodek->dodaj('__TRYB_FORMULARZA', 'enctype="multipart/form-data"');
       } else {
         $srodek->dodaj('__PLIK_FORMULARZA', '/');
         $srodek->dodaj('__TRYB_FORMULARZA', '');       
    }
    unset($PolaTekstowe);
    
    
    $SzerokoscKolumnProduktow = 100;
    $IleBedzieKolumn = 0;
    
    // produkty podobne
    if ( KARTA_PRODUKTU_PODOBNE_PRODUKTY == 'tak') {
        //
        $zapytanie = Produkty::SqlProduktyPodobne( $Produkt->info['id'] );
        $sql = $GLOBALS['db']->open_query($zapytanie);    
        //
        $IloscProduktow = (int)$GLOBALS['db']->ile_rekordow($sql);
        $srodek->parametr('ProduktyPodobneIlosc', $IloscProduktow);
        //
        ob_start();
        if (in_array( 'listing_wiersze_karta_produktu.php', $Wyglad->PlikiListingiLokalne )) {
            require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_wiersze_karta_produktu.php');
          } else {
            require('listingi/listing_wiersze_karta_produktu.php');
        }
        $ListaProduktowPodobnych = ob_get_contents();
        ob_end_clean();    

        $srodek->dodaj('__LISTING_PRODUKTY_PODOBNE', $ListaProduktowPodobnych);   
        unset($ListaProduktowPodobnych);
        //
        unset($IloscProduktow);
        //
    } else {
        //
        $srodek->parametr('ProduktyPodobneIlosc', 0);
        //
    }
    
    // klienci kupili takze
    if ( KARTA_PRODUKTU_KLIENCI_KUPILI_TAKZE == 'tak' ) {
        //
        // wyszukiwanie nr zamowien w ktorych byl kupowany produkt
        $nrZamowien = array();
        $zapytanie = "select op.orders_id from orders_products op, orders o where products_id = '" . $Produkt->info['id'] . "' and op.orders_id = o.orders_id and DATE_SUB(CURDATE(), INTERVAL 720 DAY) <= o.date_purchased order by o.date_purchased desc limit 100";
        $sql = $GLOBALS['db']->open_query($zapytanie);    
        while ($info = $sql->fetch_assoc()) {
            $nrZamowien[] = $info['orders_id'];
        }
        $GLOBALS['db']->close_query($sql); 
        unset($info, $zapytanie);    
        
        // szukanie id produktow
        $zapytanie = Produkty::SqlProduktyKlienciKupiliTakze( $Produkt->info['id'], $nrZamowien );
        $sql = $GLOBALS['db']->open_query($zapytanie);    
        unset($nrZamowien);
        //
        $IloscProduktow = (int)$GLOBALS['db']->ile_rekordow($sql);
        $srodek->parametr('KlienciKupiliTakzeIlosc', $IloscProduktow);
        //
        ob_start();
        if (in_array( 'listing_wiersze_karta_produktu.php', $Wyglad->PlikiListingiLokalne )) {
            require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_wiersze_karta_produktu.php');
          } else {
            require('listingi/listing_wiersze_karta_produktu.php');
        }
        $ListaKlienciZakupiliTakze = ob_get_contents();
        ob_end_clean();    

        $srodek->dodaj('__LISTING_KLIENCI_ZAKUPILI_TAKZE', $ListaKlienciZakupiliTakze);
        unset($ListaKlienciZakupiliTakze);
        //
        unset($IloscProduktow);
        //
    } else {
        //
        $srodek->parametr('KlienciKupiliTakzeIlosc', 0);
        //
    }
    
    // produkt nastepny / poprzedni oraz pozostale z kategorii
    //
    $RodzajSciezka = explode('#', $_SESSION['sciezka']);
    $IdKategoriiProducenta = 0;
    $Typ = '';
    $TekstNaglowka = '';

    if ($RodzajSciezka[0] == 'kategoria') {
        //
        $tablica_kategorii = explode('_',$RodzajSciezka[1]);
        if ( (int)$tablica_kategorii[ count($tablica_kategorii)-1 ] > 0 ) {
            $IdKategoriiProducenta = (int)$tablica_kategorii[ count($tablica_kategorii)-1 ];
            $Typ = 'kategoria';
            $TekstNaglowka = $GLOBALS['tlumacz']['NAGLOWEK_POZOSTALE_PRODUKTY_Z_KATEGORII'];
        }
        //
    }
    if ($RodzajSciezka[0] == 'producent') {
        //
        if ( $Produkt->producent['id'] > 0 ) {
            $IdKategoriiProducenta = (int)$Produkt->producent['id'];
            $Typ = 'producent';
            $TekstNaglowka = $GLOBALS['tlumacz']['NAGLOWEK_POZOSTALE_PRODUKTY_PRODUCENTA'];
        }
        //
    }    
    //
    
    // nastepny/poprzedni
    //###############################################################################
    /*
    if ( isset($_SESSION['sortowanie']) ) {
        $TablicaSortowania = array( '1' => 'p.sort_order desc, pd.products_name',
                                    '2' => 'p.sort_order asc, pd.products_name',
                                    '3' => 'p.products_price desc',
                                    '4' => 'p.products_price asc',
                                    '5' => 'pd.products_name desc',
                                    '6' => 'pd.products_name asc' );
        $Sortowanie = $TablicaSortowania[$_SESSION['sortowanie']];
    } else {
        $Sortowanie = 'p.sort_order asc, pd.products_name';
    }

    $Tbl = Produkty::ProduktyPoprzedniNastepny( $IdKategoriiProducenta, $Sortowanie, $Produkt->info['id'] );

    Przyklad do wykorzystania poprzedni/nastepny na karcie produktu
    if ( isset($Tbl['prev']) ){
        echo '<a href="' . Seo::link_SEO( $Tbl['prev']['nazwa'], $Tbl['prev']['id'], 'produkt' ) . '">Poprzedni</a>';
    }
    if ( isset($Tbl['next']) ){
        echo '<a href="' . Seo::link_SEO( $Tbl['next']['nazwa'], $Tbl['next']['id'], 'produkt' ) . '">Następny</a>';
    }
    */
    //###############################################################################

    //

    // pozostale produkty z kategorii
    if ( KARTA_PRODUKTU_POZOSTALE_PRODUKTY == 'tak' ) {

        $zapytanie = Produkty::SqlProduktyPozostaleKategorii( $IdKategoriiProducenta, $Typ, $Produkt->info['id'] );
        $sql = $GLOBALS['db']->open_query($zapytanie);    
        //
        $IloscProduktow = (int)$GLOBALS['db']->ile_rekordow($sql);
        $srodek->parametr('PozostaleProduktyIlosc', $IloscProduktow);     
        //
        ob_start();
        if (in_array( 'listing_wiersze_karta_produktu.php', $Wyglad->PlikiListingiLokalne )) {
            require('szablony/'.DOMYSLNY_SZABLON.'/listingi_lokalne/listing_wiersze_karta_produktu.php');
          } else {
            require('listingi/listing_wiersze_karta_produktu.php');
        }
        $ListaProduktowPozostalych = ob_get_contents();
        ob_end_clean();    

        $srodek->dodaj('NAGLOWEK_POZOSTALE_PRODUKTY_Z_KATEGORII_PRODUCENTA', $TekstNaglowka);   
        $srodek->dodaj('__LISTING_PRODUKTY_POZOSTALE_Z_KATEGORII_PRODUCENTA', $ListaProduktowPozostalych);     
        //
        //
    } else {
        //
        $srodek->parametr('PozostaleProduktyIlosc', 0);    
        //
    }

    unset($IdKategoriiProducenta, $Typ, $RodzajSciezka);
    unset($IloscProduktow, $TekstNaglowka, $ListaProduktowPozostalych);

    // szerokosc kolumn z produktami
    if ( $IleBedzieKolumn > 0 ) {
         $srodek->dodaj('CSS_SZEROKOSC_PRODUKTOW', ' style="width:' . (int)($SzerokoscKolumnProduktow / $IleBedzieKolumn) . '%"');
      } else {
         $srodek->dodaj('CSS_SZEROKOSC_PRODUKTOW', '');
    }
    
    // ustawienie http - czy ssl
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '') {
        $srodek->dodaj('__HTTP_LINK', 'https');
      } else {
        $srodek->dodaj('__HTTP_LINK', 'http');
    }
    
    $tpl->dodaj('__SRODKOWA_KOLUMNA', $srodek->uruchom());

    unset($srodek, $WywolanyPlik);

    // jezeli byl producent czysci sciezke sesji
    if ( strpos($_SESSION['sciezka'], 'producent') > -1 ) {
        //
        $_SESSION['sciezka'] = '';
        //
    }
    //
  } else {
    //
    Funkcje::PrzekierowanieURL('brak-produktu.html'); 
    //    
}
    //maxkod
    $id = $_GET['idprod'];
    $ga = new gaProduct();
    // $gaCode globalna zmienna w pliku szablonu strona_glowna
    $gaCode  = $ga->Detail($id);
    $gaCode .= $ga->Click($id);

//include('interium_ga_enhanced_ecommerce.php');

include('koniec.php');

?>