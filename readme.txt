    
    1.plik szablonu strona_glowna.tp ma kod ze zmienną globalną $gaCode do którego przypisujemy wygenerowany kod klas e-commerce
    ale w przypadku akcji ajax poprostu używamy funkcji print
    <code>
    <!-- maxkod and interium e-commerce code begins here -->
    <?php
    global $gaCode;
    if (isset($gaCode))
    {
        print $gaCode;
    }
    ?>
    <!-- maxkod and interium e-commerce code endss here bye -->
    </code>
    
    ----------------------------------------
    
    2. plik załadowany do ustawienia_db.php
    <code>
    // maxkod
    include("_ga/gaBasket.php");
    include("_ga/gaOrder.php");
    include("_ga/gaProduct.php");
    include("_ga/gaProductList.php");
    </code>
    
    ---------------------------------------
    
    3.Koszyk jest ajax więc akcje te są odrazu printowane w klasach bez przypisywania do zmiennej $gaCode;
    1. klasy\Koszyk.php 
        - DodajDoKoszyka    gaBasket->Add();
        <code>
        //maxkod analitycs
        $ga = new gaBasket();
        // $gaCode globalna zmienna w pliku szablonu strona_glowna
        // zmienna ta jest nie używana ponieważ jest to akcja z ajax i musi być print
        print $ga->Add($id, $ilosc);
        //
        </code>
        
        - UsunZKoszyka      gaBasket->Remove();
        <code>
        //maxkod analitycs
        $ga = new gaBasket();
        // $gaCode globalna zmienna w pliku szablonu strona_glowna
        // zmienna ta jest nie używana ponieważ jest to akcja z ajax i musi być print
        print $ga->Remove($id);
        //
        </code>
    
    ----------------------------------------
    
    4. Zamówienie
    1. koszyk.php
        - linia 610 gaOrder->CheckoutStep1()
        <code>
        // kiedy jest coś w koszyku musi to tu być ponieważ zamowienie-podsumowanie.html wywołuje znowu ten plik
        //i w podsumowaniu mamy CheckoutStep1 a powinno być Purchase
        //maxkod analitycs
        if($GLOBALS['koszykKlienta']->KoszykIloscProduktow() > 0)
        {
            $ga = new gaOrder();
            $gaCode = $ga->CheckoutStep1($_SESSION['koszyk']);
        }
        //
        </code>
    
    2. zamowienie_potwierdzenie.php
        - linia 478 gaOrder->CheckoutStep3();
        <code>
        //maxkod analitycs
        $ga = new gaOrder();
        $gaCode = $ga->CheckoutStep3($_SESSION['koszyk']);
        //
        </code>
        
    3. zamowienie_podsumowanie.php
        - linia 142 gaOrder->Purchase();
        <code>
        //maxkod
        $ga = new gaOrder();
        //print "<pre>";
        //print_r($_SESSION);
        //print "</pre>";
        $gaCode = $ga->Purchase($_SESSION);
        //
        </code>
    
    -----------------------------------------
    
    5. Produkt plik produkt.php
    1. klik
        - linia 963 gaProduct->Detail();
        - linia 964 gaProduct->Click();
        <code>
        //maxkod
        $id = $_GET['idprod'];
        $ga = new gaProduct();
        // $gaCode globalna zmienna w pliku szablonu strona_glowna
        $gaCode  = $ga->Detail($id);
        $gaCode .= $ga->Click($id);
        </code>
    
    ---------------------------------------------    
    
    6. Lista produktów plik listing_dol.php przed renderem listy wyświetlanie zgapione z
    szablony/standardowy.rwd/listingi_lokalne/listing_wiersze_podobne_produkty.php
        - linia 49

    <code>
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
	

    </code>
    
    --------------------------------------------
    
    7. w pliku start.php zmienna INTEGRACJA_GOOGLE_ID w tabeli settings
    szablony\standardowy.rwd\strona_glowna.tp jest kod analitycs wklejony
    ma być włączony bo wtedy koszyk nie działa
    ---------------------------------------------
    
    Jakie akcje
    OK 1. Lista produktów
    OK 2. Przeglądanie produktu
    OK 3. Dodanie do koszyka
    OK 4. Usunięcie z koszyka
    OK 5. Zamówienie potwierdzenie
    OK 6. Zamówienie realizacja

