<?php

/*
    1. plik załadowany do db_ustawienia.php
    2. klasy/Koszyk DodajDoKoszyka
    3. klasy/Koszyk UsunZKoszyka
    4. zamowienie-potwierdzenie.php
*/

/*
    Jakie akcje
    1. Przeglądanie produktu
    2. Dodanie do koszyka
    3. Usunięcie z koszyka
    4. Zamówienie potwierdzenie
    5. Zamówienie realizacja

*/

include_once "Config/db.config.php";
include_once "Config/ga.config.php";

class ga
{
	
   
	public function Header()
	{
		if(SYSTEM_IS_ON)
		{
		
			$js  = '<script>';
			$js .= "ga('create','".GA_ID."','auto');";
			$js .= "ga('require', 'ec');";
		
		}else{
			
			$js ='<script>e-commerce is off <!-- ';
	
		}
		
		return $js;
	}
	
	public function Footer()
	{
		if(SYSTEM_IS_ON)
		{
			$js = '</script>';
		}else{
			
			$js = '--></script>';
		}
		return $js;
	}
	
	public function SetActionAdd()
	{
		return "ga('ec:setAction', 'add');";
	}
	
	public function SetActionRemove()
	{
		return "ga('ec:setAction', 'remove');";
	}
	
	public function SetActionClick()
	{
		return "ga('ec:setAction', 'click');";
	}
	
	public function SetActionDetail()
	{
		return "ga('ec:setAction', 'detail');";
	}
	
	public function SetActionCheckout($options)
	{
		return "ga('ec:setAction','checkout', {".$options."});";
	}
	
	public function SetActionPurchase($id, $revenue, $tax, $shipping)
	{
		return "ga('ec:setAction', 'purchase', {'id': '".$id."','affiliation': 'Artinus','revenue': '".$revenue."','tax': '".$tax."','shipping': '".$shipping."'});";
	}
	
	public function Send()
	{
		return "ga('send', 'pageview');"; 
	}
	
    public function WriteToFile($filename,$txt)
    {
        $file = fopen($filename,"a");
        fprintf($file,$txt);
        fclose($file);
    }

    public function DebugToFile()
    {
        $file = fopen("debug","a");
        fprintf($file,$this->Debug());
        fclose($file);
    }

    public function DebugPrint()
    {
	print "<pre>";
	print $this->Debug();
	print "</pre>";
    }

    public function Debug()
    {    
        //$txt = '<pre><div class="alert alert-danger">';
        //$txt .= "Debug<br>";
        //print "Page: [" . $this->Page . "]<br>";
        //print "Controller: [" . $this->Ctrl . "]<br>";
        //print "Method: [" . $this->Method . "]<br>";
        //print "Params: [" . var_export($this->Params,true) . "]<br>";
		//$txt.=sprintf("SERVER: %s<br>", var_export($_SERVER, true));
        $txt=sprintf("GET: %s<br>", var_export($_GET, true));
        $txt.=sprintf("POST: %s<br>", var_export($_POST, true));
        //$txt.=sprintf("FILES: %s<br>", var_export($_FILES, true));
        //$txt.=sprintf("REQUEST: %s<br>", var_export($_REQUEST, true));
        $txt.=sprintf("SESSION: %s<br>", var_export($_SESSION, true));
        $txt.=sprintf("COOKIE: %s<br>", var_export($_COOKIE, true));
        //printf("SERVER: %s<br>", var_export($_SERVER, true));
        //$txt.= '</div></pre>';
        
        return $txt;
        //print '<div class="alert alert-warning">Render time: ' . $this->RenderTime . '</div>';
    }
}


?>