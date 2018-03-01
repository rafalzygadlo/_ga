<?php

class Koszyk {

    public function Koszyk() {
        //
        if (!isset($_SESSION['koszyk'])) {
            $_SESSION['koszyk'] = array();
        }    
        //
    }
    
    public function PrzywrocKoszykZalogowanego() {
        //    
        $wynikPrzeliczania = false;
        //
        if (isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0') {
            //
            // przeniesie produktow z bazy do sesji
            $zapytanie = "SELECT DISTINCT * FROM customers_basket WHERE customers_id = '" . (int)$_SESSION['customer_id'] . "' and price_type = 'baza'";
            $sql = $GLOBALS['db']->open_query($zapytanie);        
            //
            while ($info = $sql->fetch_assoc()) {
                //
                $Produkt = new Produkt( Funkcje::SamoIdProduktuBezCech( $info['products_id'] ) );
                //
                if ($Produkt->CzyJestProdukt == true) {
                
                    //
                    if ($this->CzyJestWKoszyku($info['products_id']) == false) {
                        //
                        $_SESSION['koszyk'][$info['products_id']] = array('id'          => $info['products_id'],
                                                                          'ilosc'       => $info['customers_basket_quantity'],
                                                                          'komentarz'   => $info['products_comments'],
                                                                          'pola_txt'    => $info['products_text_fields'],
                                                                          'rodzaj_ceny' => 'baza');
                        //
                     } else {
                        //
                        $_SESSION['koszyk'][$info['products_id']]['ilosc'] += $info['customers_basket_quantity'];
                        //
                    }
                    //   

                    $this->SprawdzIloscProduktuMagazyn( $info['products_id'] );
                    
                    $wynikPrzeliczania = true;

                } else {
                
                    // jezeli nie jest aktywny usunie produkt z bazy                
                    $GLOBALS['db']->delete_query('customers_basket' , "products_id = '" . $info['products_id'] . "'");
                    //
                    
                }
                //
                unset($Produkt);
                //
            }
            //
            $GLOBALS['db']->close_query($sql);
            unset($zapytanie, $info);                  
            //
            $this->PrzeliczKoszyk();            
            //            
        }
        //
        return $wynikPrzeliczania;
        //
    }    
    
    // sprawdzanie ilosci produktow przy przywracaniu koszyka klienta oraz przy potwierdzeniu zamowienia - czy ktos nie kupil produktu
    public function SprawdzIloscProduktuMagazyn($id, $koszyk = false) {
      
        if ( !isset($_SESSION['koszyk'][$id]) ) {
              return true;
        }
        
        $KoncowaIlosc = $_SESSION['koszyk'][$id]['ilosc'];

        // jezeli jest wlaczona opcja kazdego produktu osobno w koszyku to sprawdzi czy nie ma wiecej pozycji
        if ( KOSZYK_SPOSOB_DODAWANIA == 'tak' ) {
             //
             $KoncowaIlosc = 0;
             //
             foreach ( $_SESSION['koszyk'] As $TablicaWartosci ) {
                
                if ( substr($id, 0, strpos($id, 'U')) == substr($TablicaWartosci['id'], 0, strpos($TablicaWartosci['id'], 'U')) ) {
                     $KoncowaIlosc += $TablicaWartosci['ilosc'];
                }
                
             }
             //                     
        }          

        $Akcja = '';
        
        $ProduktKontrola = new Produkt( (int)Funkcje::SamoIdProduktuBezCech($id) );
        
        // jezeli produkt jest wylaczony to usuwa go z koszyka
        if ( $ProduktKontrola->CzyJestProdukt == false) {
             //
             $this->UsunZKoszyka( $id );
             return true;
             //
        }
        
        // okresla czy ilosc jest ulamkowa zeby pozniej odpowiednio sformatowac wynik
        $Przecinek = 2;
        // jezeli sa wartosci calkowite to dla pewnosci zrobi int
        if ( $ProduktKontrola->info['jednostka_miary_typ'] == '1' ) {
            $Przecinek = 0;
        }
        //         
    
        // czy produkt ma cechy
        $cechy = '';
        
        if ( strpos($id, "x") > -1 ) {
            // wyciaga same cechy z produktu
            $cechy = substr( $id, strpos($id, "x"), strlen($id) );
        }   

        if ( $cechy != '' && MAGAZYN_SPRAWDZ_STANY == 'tak' && MAGAZYN_SPRZEDAJ_MIMO_BRAKU == 'nie' && CECHY_MAGAZYN == 'tak' && $ProduktKontrola->info['kontrola_magazynu'] == 1 ) {
            $ProduktKontrola->ProduktKupowanie( $cechy ); 
          } else {
            $ProduktKontrola->ProduktKupowanie();
        }   

        // jezeli ilosc w magazynie jest mniej niz w koszyku
        if ( $ProduktKontrola->zakupy['ilosc_magazyn'] < $KoncowaIlosc && MAGAZYN_SPRAWDZ_STANY == 'tak' && MAGAZYN_SPRZEDAJ_MIMO_BRAKU == 'nie' && $ProduktKontrola->info['kontrola_magazynu'] == 1 ) {
             //
             $KoncowaIlosc = $ProduktKontrola->zakupy['ilosc_magazyn'];
             $Akcja = 'przelicz';
             //
        }
        
        // jezeli ilosc jest mniejsza o minimalnej
        if ( MAGAZYN_SPRAWDZ_STANY == 'tak' && MAGAZYN_SPRZEDAJ_MIMO_BRAKU == 'nie' && $KoncowaIlosc < $ProduktKontrola->zakupy['minimalna_ilosc'] && $ProduktKontrola->info['kontrola_magazynu'] == 1 ) {
        
            // jezeli jest mniej niz wymagana ilosc - usunie produkt z koszyka
            $Akcja = 'usun';
        
        }
        
        // jezeli ilosc jest wieksza niz maksymalna
        if ( $KoncowaIlosc > $ProduktKontrola->zakupy['maksymalna_ilosc'] && $ProduktKontrola->zakupy['maksymalna_ilosc'] > 0 ) {
             //
             $KoncowaIlosc = $ProduktKontrola->zakupy['maksymalna_ilosc'];
             $Akcja = 'przelicz';
             //
        }
        
        // jezeli jest przyrost ilosci
        if ( $ProduktKontrola->zakupy['przyrost_ilosci'] > 0 ) {
            //
            $Przyrost = $ProduktKontrola->zakupy['przyrost_ilosci'];
            //
            if ( (int)(round(($KoncowaIlosc / $Przyrost) * 100, 2) / 100) != (round(($KoncowaIlosc / $Przyrost) * 100, 2) / 100) ) {
                // 
                $KoncowaIlosc = (int)($KoncowaIlosc / $Przyrost) * $Przyrost;
                $Akcja = 'przelicz';
                //
            }
            //
        }  
        
        if ( $KoncowaIlosc <= 0 ) {
             //
             $this->UsunZKoszyka( $id );
             return true;
             //
        }
        
        // jezeli jest dodawanie osobno do koszyka to usunie pozycje ktore nie spelniaja magazynu
        if ( $Akcja != '' && KOSZYK_SPOSOB_DODAWANIA == 'tak' ) {
             //
             foreach ( $_SESSION['koszyk'] As $TablicaWartosci ) {
                
                if ( substr($id, 0, strpos($id, 'U')) == substr($TablicaWartosci['id'], 0, strpos($TablicaWartosci['id'], 'U')) ) {
                     $this->UsunZKoszyka( $TablicaWartosci['id'] );
                }
                
             }
             // 
        } else {
             //
             if ( $Akcja == 'przelicz' ) {
                //
                $_SESSION['koszyk'][$id]['ilosc'] = $KoncowaIlosc;
                //
              } else if ( $Akcja == 'usun' ) {
                //
                $this->UsunZKoszyka( $id );
                //
             }
        }
        
        if ( isset($_SESSION['koszyk'][$id]) ) {
             //
             $_SESSION['koszyk'][$id]['ilosc'] = number_format( $_SESSION['koszyk'][$id]['ilosc'], $Przecinek, '.', '' );
             //
        }
        
        if ( $Akcja != '' ) {
             return true;
        }
    
    }
    
    // sprawdzanie ilosci produktow przy podgladzie zapisanego koszyka
    public function SprawdzIloscProduktuMagazynZapisanyKoszyk($id, $ilosc, $id_koszyka) {
      
        $KoncowaIlosc = $ilosc;

        // jezeli jest wlaczona opcja kazdego produktu osobno w koszyku to sprawdzi czy nie ma wiecej pozycji w zapisanym koszyku
        if ( KOSZYK_SPOSOB_DODAWANIA == 'tak' ) {
             //
             $KoncowaIlosc = 0;
             //
             $zapytanie = "SELECT DISTINCT * FROM basket_save_products WHERE basket_id = '" . $id_koszyka . "'";
             $sql = $GLOBALS['db']->open_query($zapytanie); 
             //
             while ($info = $sql->fetch_assoc()) {
               
                if ( substr($id, 0, strpos($id, 'U')) == substr($info['products_id'], 0, strpos($info['products_id'], 'U')) ) {
                     $KoncowaIlosc += $info['basket_quantity'];
                }               
               
             }
             //
             $GLOBALS['db']->close_query($sql);
              unset($zapytanie, $info);
             //                     
        }          

        $Akcja = false;
        
        $ProduktKontrola = new Produkt( (int)Funkcje::SamoIdProduktuBezCech($id) );

        // czy produkt ma cechy
        $cechy = '';
        
        if ( strpos($id, "x") > -1 ) {
            // wyciaga same cechy z produktu
            $cechy = substr( $id, strpos($id, "x"), strlen($id) );
        }   

        if ( $cechy != '' && MAGAZYN_SPRAWDZ_STANY == 'tak' && MAGAZYN_SPRZEDAJ_MIMO_BRAKU == 'nie' && CECHY_MAGAZYN == 'tak' && $ProduktKontrola->info['kontrola_magazynu'] == 1 ) {
            $ProduktKontrola->ProduktKupowanie( $cechy ); 
          } else {
            $ProduktKontrola->ProduktKupowanie();
        }   

        // jezeli ilosc w magazynie jest mniej niz w koszyku
        if ( $ProduktKontrola->zakupy['ilosc_magazyn'] < $KoncowaIlosc && MAGAZYN_SPRAWDZ_STANY == 'tak' && MAGAZYN_SPRZEDAJ_MIMO_BRAKU == 'nie' && $ProduktKontrola->info['kontrola_magazynu'] == 1 ) {
             //
             $Akcja = true;
             //
        }
        
        // jezeli ilosc jest mniejsza o minimalnej
        if ( MAGAZYN_SPRAWDZ_STANY == 'tak' && MAGAZYN_SPRZEDAJ_MIMO_BRAKU == 'nie' && $KoncowaIlosc < $ProduktKontrola->zakupy['minimalna_ilosc'] && $ProduktKontrola->info['kontrola_magazynu'] == 1 ) {
        
            $Akcja = true;
        
        }
        
        // jezeli ilosc jest wieksza niz maksymalna
        if ( $KoncowaIlosc > $ProduktKontrola->zakupy['maksymalna_ilosc'] && $ProduktKontrola->zakupy['maksymalna_ilosc'] > 0 ) {
             //
             $Akcja = true;
             //
        }
        
        // jezeli jest przyrost ilosci
        if ( $ProduktKontrola->zakupy['przyrost_ilosci'] > 0 ) {
            //
            $Przyrost = $ProduktKontrola->zakupy['przyrost_ilosci'];
            //
            if ( (int)(round(($KoncowaIlosc / $Przyrost) * 100, 2) / 100) != (round(($KoncowaIlosc / $Przyrost) * 100, 2) / 100) ) {
                // 
                $Akcja = true;
                //
            }
            //
        }  
        
        return $Akcja;
    
    }    

    // czysci sesje koszyka przy wylogowaniu - tylko dla zalogowanych klientow
    public function WyczyscSesjeKoszykZalogowanego() {
        //  
        if (isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0') {
            //
            $_SESSION['koszyk'] = array(); 
            //           
        }
        //
    }      
    
    // sprawdza czy produkt jest w koszyku sesji
    public function CzyJestWKoszyku( $id ) {
        //
        // czy juz nie ma produktu w koszyku
        $ProduktJest = false;
        foreach ( $_SESSION['koszyk'] As $TablicaWartosci ) {
            //
            if ( $id == $TablicaWartosci['id'] ) {
                $ProduktJest = true;
            }
            //
        }
        //
        return $ProduktJest;
    }

    public function DodajDoKoszyka( $id, $ilosc, $komentarz, $pola_txt, $rodzaj_ceny = 'baza', $cena = 0 ) {
        //
        if ( $rodzaj_ceny == 'baza' ) {
            //
            if ($this->CzyJestWKoszyku($id) == false || KOSZYK_SPOSOB_DODAWANIA == 'tak') {
                //
                $LosowaWartosc = '';
                //
                if ( KOSZYK_SPOSOB_DODAWANIA == 'tak' ) {
                     //
                     $LosowaWartosc = 'U' . rand(1,99999);
                     //
                }
                //
                $_SESSION['koszyk'][$id . $LosowaWartosc] = array('id'          => $id . $LosowaWartosc,
                                                                  'ilosc'       => $ilosc,
                                                                  'komentarz'   => $komentarz,  
                                                                  'pola_txt'    => $pola_txt,
                                                                  'rodzaj_ceny' => $rodzaj_ceny);
                //
             } else {
                //
                $_SESSION['koszyk'][$id]['ilosc'] += $ilosc;
                $_SESSION['koszyk'][$id]['komentarz'] .= $komentarz;
                $_SESSION['koszyk'][$id]['pola_txt'] = $pola_txt;
                //
            }
            //
        }
        if ( $rodzaj_ceny == 'gratis' ) {
            //
            $_SESSION['koszyk'][$id . '-gratis'] = array('id'          => $id . '-gratis',
                                                         'ilosc'       => $ilosc,
                                                         'komentarz'   => '',   
                                                         'pola_txt'    => '',
                                                         'rodzaj_ceny' => 'gratis',
                                                         'cena_brutto' => $cena);
            //
        }
        $this->PrzeliczKoszyk();
        
        //maxkod analitycs
        $ga = new gaBasket();
        // $gaCode globalna zmienna w pliku szablonu strona_glowna
        // zmienna ta jest nie używana ponieważ jest to akcja z ajax i musi być print
        print $ga->Add($id, $ilosc);
        //
        //
        unset($ProduktJest);
        //  
    } 
    
    public function AktualizujKomentarz( $id, $komentarz ) {
        //
        $_SESSION['koszyk'][$id]['komentarz'] = $komentarz;
        //
    }
    
    public function ZmienIloscKoszyka( $id, $ilosc, $przeliczaj = true ) {
        //
        $_SESSION['koszyk'][$id]['ilosc'] = $ilosc;
        //
        if ( $przeliczaj == true ) {
             $this->PrzeliczKoszyk();
        }
        //
    }
    
    public function UsunZKoszyka( $id, $przeliczaj = true ) {
        //
        unset($_SESSION['koszyk'][$id]);
        //
        // usuwa z bazy jezeli jest zalogowany klient
        if ( isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0' ) { 
            //
            $GLOBALS['db']->delete_query('customers_basket' , "products_id = '" . $id . "' and  customers_id = '".(int)$_SESSION['customer_id']."'");	   
            //
        }
        //
        if ( $przeliczaj == true ) {
             $this->PrzeliczKoszyk();
        }
        //
        
        //maxkod analitycs
        $ga = new gaBasket();
        // $gaCode globalna zmienna w pliku szablonu strona_glowna
        // zmienna ta jest nie używana ponieważ jest to akcja z ajax i musi być print
        print $ga->Remove($id);
        //
    } 

    public function PrzeliczKoszyk() {
        //
        foreach ($_SESSION['koszyk'] AS $TablicaZawartosci) {
        
            //
            $Produkt = new Produkt( Funkcje::SamoIdProduktuBezCech($TablicaZawartosci['id']) );
            
            if ( $Produkt->CzyJestProdukt ) {
            
                // definicja czy jest tylko akcesoria dodatkowe
                $TylkoAkcesoria = false;
            
                // sprawdzi czy produkt nie jest jako tylko akcesoria dodatkowe i czy jest w koszyku produkt z ktorym mozna go kupic
                if ( $Produkt->info['status_akcesoria'] == 'tak' ) {
                     //
                     $TylkoAkcesoria = true;
                     //
                     // tablica do id produktow ktore maja akcesoria dodatkowe o danym id produktu
                     $TablicaId = array();
                     //
                     $zapytanie = "select distinct pacc_products_id_master from products_accesories where pacc_products_id_slave = '" . $Produkt->info['id'] . "'";
                     $sql = $GLOBALS['db']->open_query($zapytanie);    
                     //
                     while ($info = $sql->fetch_assoc()) {
                         $TablicaId[] = $info['pacc_products_id_master'];
                     }
                     //
                     $GLOBALS['db']->close_query($sql);
                     unset($info, $zapytanie);  
                     //
                     // sprawdzi czy w koszyku jest produkt z ktorym mozna kupic ten produkt
                     foreach ($_SESSION['koszyk'] AS $TablicaZawartosciAkcesoria) {
                        //
                        $IdProduktuKoszyka = Funkcje::SamoIdProduktuBezCech( $TablicaZawartosciAkcesoria['id'] );
                        //
                        if ( in_array($IdProduktuKoszyka, $TablicaId) ) {
                             $TylkoAkcesoria = false;
                             break;
                        }
                        //
                        unset($IdProduktuKoszyka);
                        //
                     }
                     //
                     unset($TablicaId);
                     //
                }           

                // jezeli produkt moze byc tylko jako acesoria dodatkowe a nie ma produktu dla ktorego jest przypisany
                if ( $TylkoAkcesoria == true ) {
                
                     $this->UsunZKoszyka( $TablicaZawartosci['id'] );
                     
                  } else {
            
                    // elementy kupowania
                    $Produkt->ProduktKupowanie();
                    //
                                
                    //
                    // jezeli do koszyka jest dodawany normalny produkt
                    if ( $TablicaZawartosci['rodzaj_ceny'] == 'baza' ) {

                        $WartoscCechBrutto = 0;
                        $WartoscCechNetto = 0;
                        $WagaCechy = 0;
                        $Znizka = 1;
                        
                        // przeliczy cechy tylko jezeli produkt nie jest za PUNKTY
                        if ( $Produkt->info['tylko_za_punkty'] == 'nie' ) {
                        
                            // jezeli produkt ma cechy oraz cechy wplywaja na wartosc produktu to musi ustalic ceny cech
                            if ( strpos($TablicaZawartosci['id'], "x") > -1 && $Produkt->info['typ_cech'] == 'cechy' ) {
                                //
                                $DodatkoweParametryCechy = $Produkt->ProduktWartoscCechy( $TablicaZawartosci['id'] );
                                //
                                $WartoscCechBrutto = $DodatkoweParametryCechy['brutto'];
                                $WartoscCechNetto = $DodatkoweParametryCechy['netto'];
                                $WagaCechy = $DodatkoweParametryCechy['waga'];
                                //
                                unset($DodatkoweParametryCechy);
                                //
                                // lub jezeli sa stale ceny dla kombinacji cech
                            } else if ( $Produkt->info['typ_cech'] == 'ceny' ) {
                                //
                                $DodatkoweCenyCech = $Produkt->ProduktWartoscCechyCeny( $TablicaZawartosci['id'] );
                                //
                                $Produkt->info['cena_netto_bez_formatowania'] = $DodatkoweCenyCech['netto'];
                                $Produkt->info['cena_brutto_bez_formatowania'] = $DodatkoweCenyCech['brutto'];
                                $Produkt->info['vat_bez_formatowania'] = $DodatkoweCenyCech['brutto'] - $DodatkoweCenyCech['netto'];
                                $WagaCechy = $DodatkoweCenyCech['waga'];
                                //
                                unset($DodatkoweCenyCech);
                                //
                            }

                        }
                        
                        //
                        $IloscSzt = $TablicaZawartosci['ilosc'];      
                        //
                        // znizki zalezne od ilosci
                        // warunki czy stosowac znizki od ilosci
                        $StosujZnizki = true;
                        
                        // jezeli nie ma sumowania rabatow
                        if ( ZNIZKI_OD_ILOSCI_SUMOWANIE_RABATOW == 'nie' && $Produkt->info['rabat_produktu'] != 0 ) {
                            $StosujZnizki = false;
                        }

                        // jezeli znizki zalezne od ilosci produktow w koszyku sa wlaczone dla promocji lub produkt nie jest w promocji
                        if ( ZNIZKI_OD_ILOSCI_PROMOCJE == 'nie' && $Produkt->ikonki['promocja'] == '1' && $Produkt->znizkiZalezneOdIlosciTyp == 'procent' ) {
                            $StosujZnizki = false;                
                        }
                        
                        // jezeli produkt jest tylko za PUNKTY to nie ma znizek
                        if ( $Produkt->info['tylko_za_punkty'] == 'tak' ) {
                            $StosujZnizki = false;                
                        }                            
                        
                        if ( $StosujZnizki == true ) {
                                        
                            $IloscSztDoZnizek = 0;
                            
                            // jezeli produkty ze cechami maja byc traktowane jako osobne produkty
                            if ( ZNIZKI_OD_ILOSCI_PRODUKT_CECHY == 'nie' ) {
                            
                                // ---------------------------------------------------------------------------
                                // musi poszukac ile jest produktow z roznymi cechami i zsumowac produkty
                                foreach ($_SESSION['koszyk'] AS $TablicaDoZnizek) {
                                    //
                                    if (Funkcje::SamoIdProduktuBezCech($TablicaDoZnizek['id']) == Funkcje::SamoIdProduktuBezCech($TablicaZawartosci['id'])) {
                                        $IloscSztDoZnizek += $TablicaDoZnizek['ilosc'];
                                    }
                                    //
                                }
                                // ---------------------------------------------------------------------------
                                //
                                
                              } else {
                              
                                $IloscSztDoZnizek = $IloscSzt;
                                
                            }

                            if ($Produkt->ProduktZnizkiZalezneOdIlosci( $IloscSztDoZnizek ) > 0) {
                                // jezeli jest procent to obliczy wskaznik dzielenia - jezeli cena to pobierze cene
                                if ( $Produkt->znizkiZalezneOdIlosciTyp == 'procent' ) {
                                     $Znizka = 1 - ($Produkt->ProduktZnizkiZalezneOdIlosci( $IloscSztDoZnizek ) / 100);
                                  } else {
                                     $Znizka = $Produkt->ProduktZnizkiZalezneOdIlosci( $IloscSztDoZnizek );
                                     if ( $Znizka <= 0 ) {
                                          $Znizka = 1;
                                     }
                                }
                            }
                            //
                            unset($IloscSztDoZnizek);
                            //
                            
                        }

                        // jezeli nie ma znizki
                        if ($Znizka == 1) {
                            //
                            $CenaNetto = $Produkt->info['cena_netto_bez_formatowania'] + $WartoscCechNetto;
                            $CenaBrutto = $Produkt->info['cena_brutto_bez_formatowania'] + $WartoscCechBrutto;
                            $Vat = $Produkt->info['vat_bez_formatowania'];
                            //
                        } else {
                            //
                            if ( $Produkt->znizkiZalezneOdIlosciTyp == 'procent' ) {
                                 //
                                 $CenaBrutto = round( ($Produkt->info['cena_brutto_bez_formatowania'] + $WartoscCechBrutto) * $Znizka, CENY_MIEJSCA_PO_PRZECINKU );               
                                 //
                            }
                            if ( $Produkt->znizkiZalezneOdIlosciTyp == 'cena' ) {
                                 //
                                 // jezeli znizki od ilosci sa w formie cen cechy trzeba policzyc od ceny po znizkach
                                 $DodatkoweParametryCechy = $Produkt->ProduktWartoscCechy( $TablicaZawartosci['id'], $Znizka, round( $Znizka / (1 + ($Produkt->info['stawka_vat'] / 100)), CENY_MIEJSCA_PO_PRZECINKU ) );
                                 //
                                 $WartoscCechBrutto = $DodatkoweParametryCechy['brutto'];
                                 $WartoscCechNetto = $DodatkoweParametryCechy['netto'];
                                 $WagaCechy = $DodatkoweParametryCechy['waga'];
                                 //
                                 unset($DodatkoweParametryCechy);
                                 //
                                 $CenaBrutto = round( $Znizka + $WartoscCechBrutto, CENY_MIEJSCA_PO_PRZECINKU );             
                                 //
                            }                            
                            //
                            $CenaNetto = round( $CenaBrutto / (1 + ($Produkt->info['stawka_vat'] / 100)), CENY_MIEJSCA_PO_PRZECINKU );
                            $Vat = $CenaBrutto - $CenaNetto;                             
                            //
                        }
                        //
                    }
                    
                    // jezeli do koszyka jest dodawany gratis
                    if ( $TablicaZawartosci['rodzaj_ceny'] == 'gratis' ) {
                        //
                        $WagaCechy = 0;
                        $IloscSzt = $TablicaZawartosci['ilosc'];
                        //
                        if ( $TablicaZawartosci['cena_brutto'] > 0 ) {
                              //
                              $CenaBrutto = $TablicaZawartosci['cena_brutto'];
                              $CenaNetto = round( $CenaBrutto / (1 + ($Produkt->info['stawka_vat'] / 100)), CENY_MIEJSCA_PO_PRZECINKU );
                              $Vat = $CenaBrutto - $CenaNetto;
                              //
                          } else { 
                              //
                              $CenaBrutto = 0;
                              $CenaNetto = 0;
                              $Vat = 0;
                              //
                        }
                        //
                    }
                    
                    // usuwa wpis z koszyka sesji
                    unset($WartoscCechBrutto, $WartoscCechNetto);
                    
                    $NrKatalogowy = $Produkt->ProduktCechyNrKatalogowy( substr( $TablicaZawartosci['id'], strpos($TablicaZawartosci['id'], "x") + 1, strlen($TablicaZawartosci['id']) ) );
                    
                    //
                    // dodaje na nowo do koszyka sesji przeliczone wartosci
                    $_SESSION['koszyk'][$TablicaZawartosci['id']] = array('id'                         => $TablicaZawartosci['id'],
                                                                          'ilosc'                      => $IloscSzt,
                                                                          'cena_netto'                 => $CenaNetto,
                                                                          'cena_brutto'                => $CenaBrutto,
                                                                          'cena_punkty'                => (($Produkt->info['tylko_za_punkty'] == 'tak') ? (int)$Produkt->info['cena_w_punktach'] : 0),
                                                                          'vat'                        => $Vat,
                                                                          'waga'                       => $Produkt->info['waga'] + $WagaCechy,
                                                                          'promocja'                   => (($Produkt->ikonki['promocja'] == 1 && $Produkt->info['tylko_za_punkty'] == 'nie') ? 'tak' : 'nie'),
                                                                          'darmowa_wysylka'            => $Produkt->info['darmowa_wysylka'], 
                                                                          'wykluczona_darmowa_wysylka' => $Produkt->info['wykluczona_darmowa_wysylka'],
                                                                          'gabaryt'                    => $Produkt->info['gabaryt'],
                                                                          'wysylki'                    => $Produkt->info['dostepne_wysylki'],
                                                                          'koszt_wysylki'              => $Produkt->info['koszt_wysylki'],
                                                                          'nr_katalogowy'              => $NrKatalogowy,
                                                                          'komentarz'                  => $TablicaZawartosci['komentarz'],
                                                                          'pola_txt'                   => $TablicaZawartosci['pola_txt'],
                                                                          'rodzaj_ceny'                => $TablicaZawartosci['rodzaj_ceny'],
                                                                          'id_kategorii'               => $Produkt->info['id_kategorii'],
                    );

                    // jezeli klient jest zalogowany to aktualizuje baze
                    if (isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0 && $_SESSION['gosc'] == '0') {
                        //
                        // nie zapisuje koszyka jezeli produkt jest za punkty
                        if ($TablicaZawartosci['rodzaj_ceny'] == 'baza') {
                            //
                            // musi sprawdzic czy produkt jest juz w bazie
                            $zapytanie = "SELECT DISTINCT * FROM customers_basket WHERE products_id = '" . $TablicaZawartosci['id'] . "' and customers_id = '" . (int)$_SESSION['customer_id'] . "'";
                            $sql = $GLOBALS['db']->open_query($zapytanie);   
                            //
                            if ( (int)$GLOBALS['db']->ile_rekordow($sql) > 0 ) {
                              
                                // aktualizuje produkt
                                $pola = array(
                                        array('products_id',$TablicaZawartosci['id']),
                                        array('customers_id',(int)$_SESSION['customer_id']),
                                        array('customers_basket_quantity',$IloscSzt),
                                        array('products_price',$CenaNetto),
                                        array('products_price_tax',$CenaBrutto),
                                        array('products_tax',$Vat),
                                        array('products_weight',$Produkt->info['waga']),
                                        array('products_comments',$TablicaZawartosci['komentarz']),
                                        array('products_text_fields',$TablicaZawartosci['pola_txt']),
                                        array('products_model',$NrKatalogowy),
                                        array('price_type',$TablicaZawartosci['rodzaj_ceny']));
                                        
                                $GLOBALS['db']->update_query('customers_basket' , $pola, "products_id = '" . $TablicaZawartosci['id'] ."' and customers_id = '" . (int)$_SESSION['customer_id'] . "'");	
                                unset($pola);                       
                                //         
                                
                            } else {
                              
                                // jezeli go nie ma musi go dodac
                                $pola = array(
                                        array('products_id',$TablicaZawartosci['id']),
                                        array('customers_id',(int)$_SESSION['customer_id']),
                                        array('customers_basket_quantity',$IloscSzt),
                                        array('products_price',$CenaNetto),
                                        array('products_price_tax',$CenaBrutto),
                                        array('products_tax',$Vat),
                                        array('products_weight',$Produkt->info['waga']),
                                        array('products_comments',$TablicaZawartosci['komentarz']),
                                        array('products_text_fields',$TablicaZawartosci['pola_txt']),
                                        array('products_model',$NrKatalogowy),
                                        array('customers_basket_date_added','now()'),
                                        array('price_type',$TablicaZawartosci['rodzaj_ceny']));

                                $GLOBALS['db']->insert_query('customers_basket' , $pola);	
                                unset($pola);
                                //
                                
                            }
                            //            
                        }
                        //
                    }
                    
                }
                
                unset($TylkoAkcesoria, $NrKatalogowy, $CenaNetto, $CenaBrutto, $Vat, $WagaCechy);
                
            } else {
            
                $this->UsunZKoszyka( $TablicaZawartosci['id'] );
            
            }
            //
            unset($Produkt);
            //            
        }
        //
        // sprawdzi czy nie trzeba skasowac jakis gratisow jezeli zmienila sie wartosc koszyka
        $JakieSaGratisy = Gratisy::TablicaGratisow( 'nie' );
        $GratisyKilka = array();
        $GratisyJeden = array();
        //   
        foreach ($_SESSION['koszyk'] AS $TablicaZawartosci) {
            //
            if ( $TablicaZawartosci['rodzaj_ceny'] == 'gratis' ) {
                //
                if (!isset($JakieSaGratisy[ Funkcje::SamoIdProduktuBezCech($TablicaZawartosci['id']) ])) {
                    $this->UsunZKoszyka($TablicaZawartosci['id']);
                }
                //
                if ( isset($JakieSaGratisy[ Funkcje::SamoIdProduktuBezCech($TablicaZawartosci['id']) ]) ) {
                    //
                    if ( $JakieSaGratisy[ Funkcje::SamoIdProduktuBezCech($TablicaZawartosci['id']) ]['tylko_jeden'] == 1 ) {
                         $GratisyJeden[ $TablicaZawartosci['id'] ] = $JakieSaGratisy[ Funkcje::SamoIdProduktuBezCech($TablicaZawartosci['id']) ];
                      } else {
                         $GratisyKilka[ $TablicaZawartosci['id'] ] = $JakieSaGratisy[ Funkcje::SamoIdProduktuBezCech($TablicaZawartosci['id']) ];
                    }
                    //
                }
                //
            }
            //
        }
        
        if ( count($GratisyJeden) > 0 ) {
             //
             foreach ( $GratisyKilka as $Klucz => $Tab ) {
                 $this->UsunZKoszyka($Klucz);
             }
             //
        }
        //
        unset($GratisyKilka, $GratisyJeden);
        //

        if ( isset($_SESSION['rodzajDostawy']) && isset($_SESSION['rodzajPlatnosci']) ) {
            $i18n = new Translator($_SESSION['domyslnyJezyk']['id']);

            $GLOBALS['tlumacz'] = array_merge( $i18n->tlumacz( array('KOSZYK', 'WYSYLKI', 'PODSUMOWANIE_ZAMOWIENIA', 'PLATNOSCI') ), $GLOBALS['tlumacz'] );

            $wysylki = new Wysylki( $_SESSION['krajDostawy']['kod'] );
            $tablicaWysylek = $wysylki->wysylki;
            $WysylkaID = $_SESSION['rodzajDostawy']['wysylka_id'];
            
            if ( isset($tablicaWysylek[$WysylkaID]) && count($tablicaWysylek[$WysylkaID]) > 0 ) {
            
                unset($_SESSION['rodzajDostawy']);
                $_SESSION['rodzajDostawy'] = array('wysylka_id' => $tablicaWysylek[$WysylkaID]['id'],
                                                   'wysylka_klasa' => $tablicaWysylek[$WysylkaID]['klasa'],
                                                   'wysylka_koszt' => $tablicaWysylek[$WysylkaID]['wartosc'],
                                                   'wysylka_nazwa' => $tablicaWysylek[$WysylkaID]['text'],
                                                   'wysylka_vat_id' => $tablicaWysylek[$WysylkaID]['vat_id'],
                                                   'wysylka_vat_stawka' => $tablicaWysylek[$WysylkaID]['vat_stawka'],                                                    
                                                   'dostepne_platnosci' => $tablicaWysylek[$WysylkaID]['dostepne_platnosci'] );

                $platnosci = new Platnosci( $_SESSION['rodzajDostawy']['wysylka_id'] );
                $tablicaPlatnosci = $platnosci->platnosci;
                $PlatnoscID = $_SESSION['rodzajPlatnosci']['platnosc_id'];
                unset($_SESSION['rodzajPlatnosci']);
                
                if ( isset($tablicaPlatnosci[$PlatnoscID]['id']) ) {
                    $_SESSION['rodzajPlatnosci'] = array('platnosc_id' => $tablicaPlatnosci[$PlatnoscID]['id'],
                                                         'platnosc_klasa' => $tablicaPlatnosci[$PlatnoscID]['klasa'],
                                                         'platnosc_koszt' => $tablicaPlatnosci[$PlatnoscID]['wartosc'],
                                                         'platnosc_nazwa' => $tablicaPlatnosci[$PlatnoscID]['text'] );
                }
                
            }
        }


    }

    public function ZawartoscKoszyka() {
        //
        $WartoscKoszykaNetto = 0;
        $WartoscKoszykaBrutto = 0;
        $WartoscKoszykaVat = 0;
        $IloscProduktowKoszyka = 0;
        $WagaProduktowKoszyka = 0;
        //
        $WartoscKoszykaNettoInne = 0;
        $WartoscKoszykaBruttoInne = 0;
        $WartoscKoszykaVatInne = 0;
        $IloscProduktowKoszykaInne = 0;
        //
        $WartoscProduktowZaPunkty = 0;
        //
        foreach ($_SESSION['koszyk'] AS $TablicaZawartosci) {
            //
            $SumaBrutto = $TablicaZawartosci['cena_brutto'] * $TablicaZawartosci['ilosc'];
            $SumaNetto = $TablicaZawartosci['cena_netto'] * $TablicaZawartosci['ilosc'];
            $SumaVat = $SumaBrutto - $SumaNetto;
            //
            $WagaProduktowKoszyka += $TablicaZawartosci['waga'] * $TablicaZawartosci['ilosc'];
            //
            $WartoscKoszykaNetto += $SumaNetto;
            $WartoscKoszykaBrutto += $SumaBrutto;
            $WartoscKoszykaVat += $SumaVat;
            $IloscProduktowKoszyka += $TablicaZawartosci['ilosc'];
            //
            $WartoscProduktowZaPunkty += $TablicaZawartosci['cena_punkty'] * $TablicaZawartosci['ilosc'];
            //
            unset($SumaBrutto, $SumaNetto, $SumaVat);
            
            // suma innych produktow (np gratisow)
            if ( $TablicaZawartosci['rodzaj_ceny'] != 'baza' ) {
                //
                $SumaBrutto = $TablicaZawartosci['cena_brutto'] * $TablicaZawartosci['ilosc'];
                $SumaNetto = $TablicaZawartosci['cena_netto'] * $TablicaZawartosci['ilosc'];
                $SumaVat = $SumaBrutto - $SumaNetto;
                //
                $WartoscKoszykaNettoInne += $SumaNetto;
                $WartoscKoszykaBruttoInne += $SumaBrutto;
                $WartoscKoszykaVatInne += $SumaVat;
                //
                $IloscProduktowKoszykaInne += $TablicaZawartosci['ilosc'];
                //
                unset($SumaBrutto, $SumaNetto, $SumaVat);
            }            
            //
        }
        //
        // wynik z _baza sa to produkty wg cen z bazy - odliczone np ceny gratisow - potrzebne do obliczania np gratisow
        $Wynik = array('netto'       => $WartoscKoszykaNetto,
                       'brutto'      => $WartoscKoszykaBrutto,
                       'wartosc_pkt' => $WartoscProduktowZaPunkty,
                       'vat'         => $WartoscKoszykaVat,
                       'ilosc'       => $IloscProduktowKoszyka,
                       'waga'        => $WagaProduktowKoszyka,
                       'ilosc_baza'  => $IloscProduktowKoszyka - $IloscProduktowKoszykaInne,
                       'netto_baza'  => $WartoscKoszykaNetto - $WartoscKoszykaNettoInne,
                       'brutto_baza' => $WartoscKoszykaBrutto - $WartoscKoszykaBruttoInne,
                       'vat_baza'    => $WartoscKoszykaVat - $WartoscKoszykaVatInne);
        //
        unset($WartoscKoszykaNetto, $WartoscKoszykaBrutto, $WartoscProduktowZaPunkty, $WartoscKoszykaVat, $IloscProduktowKoszyka, $WagaProduktowKoszyka, $WartoscKoszykaNettoInne, $WartoscKoszykaBruttoInne, $WartoscKoszykaVatInne, $IloscProduktowKoszykaInne);
        //
        return $Wynik;
        //
    }
    
    public function KoszykIloscProduktow() {
        //
        $ZawartoscKoszyka = $this->ZawartoscKoszyka();
        return $ZawartoscKoszyka['ilosc'];
        //
    }
    
    public function KoszykWartoscProduktow() {
        //
        $ZawartoscKoszyka = $this->ZawartoscKoszyka();
        return $ZawartoscKoszyka['brutto'];
        //
    } 

    public function KoszykWartoscProduktowZaPunkty() {
        //
        $ZawartoscKoszyka = $this->ZawartoscKoszyka();
        return $ZawartoscKoszyka['wartosc_pkt'];
        //
    }     
    
    // zapisanie koszyka
    public function KoszykZapisz($nazwa = '', $opis = '') {

        // dane glowne zapisu
        $pola = array(
                array('customers_id',(int)$_SESSION['customer_id']),
                array('basket_code',time()),
                array('basket_name',$nazwa),
                array('basket_description',$opis),
                array('basket_date_added','now()'));

        $GLOBALS['db']->insert_query('basket_save' , $pola);	
        unset($pola); 
        
        $id_dodanej_pozycji = $GLOBALS['db']->last_id_query();

        foreach ($_SESSION['koszyk'] AS $TablicaZawartosci) {
          
            // dodaje tylko same produkty - bez gratisow
            if ( $TablicaZawartosci['rodzaj_ceny'] == 'baza' ) {
              
                // dane produktow
                $pola = array(
                        array('basket_id',$id_dodanej_pozycji),
                        array('products_id',$TablicaZawartosci['id']),
                        array('basket_quantity',$TablicaZawartosci['ilosc']),
                        array('products_comments',$TablicaZawartosci['komentarz']),
                        array('products_text_fields',$TablicaZawartosci['pola_txt']));

                $GLOBALS['db']->insert_query('basket_save_products' , $pola);	
                unset($pola);
                //
                
            }

        }      
     
    }
    
    // wczytanie koszyka
    public function WczytajKoszyk( $id ) {
      
        // usuwa zawartosc koszyka przed wczytaniem
        foreach ( $_SESSION['koszyk'] As $TablicaWartosci ) {
            //
            $GLOBALS['koszykKlienta']->UsunZKoszyka( $TablicaWartosci['id'] ); 
            //
        }      
      
        // przeniesie produktow z bazy do sesji
        $zapytanie = "SELECT DISTINCT * FROM basket_save_products WHERE basket_id = '" . $id . "'";
        $sql = $GLOBALS['db']->open_query($zapytanie); 
        //
        $DodanoWszystkie = true;
        //
        while ($info = $sql->fetch_assoc()) {
            //
            $Produkt = new Produkt( Funkcje::SamoIdProduktuBezCech( $info['products_id'] ) );
            //
            if ($Produkt->CzyJestProdukt == true) {
            
                //
                if ($this->CzyJestWKoszyku($info['products_id']) == false) {
                    //
                    $_SESSION['koszyk'][$info['products_id']] = array('id'          => $info['products_id'],
                                                                      'ilosc'       => $info['basket_quantity'],
                                                                      'komentarz'   => $info['products_comments'],
                                                                      'pola_txt'    => $info['products_text_fields'],
                                                                      'rodzaj_ceny' => 'baza');
                    //
                 } else {
                    //
                    $_SESSION['koszyk'][$info['products_id']]['ilosc'] += $info['basket_quantity'];
                    //
                }
                //   
                
                $SprMagazyn = $this->SprawdzIloscProduktuMagazyn( $info['products_id'] );
                
                if ( $SprMagazyn == true ) { 
                     $DodanoWszystkie = false;
                }
                
                unset($SprMagazyn);

            } else {
              
                $DodanoWszystkie = false;
             
            }
            //
            unset($Produkt);
            //
        }
        //
        $GLOBALS['db']->close_query($sql);
        unset($zapytanie, $info);                  
        //
        $this->PrzeliczKoszyk();            
        //            
        
        return $DodanoWszystkie;

    }

}

?>