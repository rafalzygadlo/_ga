<?php

include_once "ga.php";


class gaOrder extends ga
{
	
	private function SetProduct($item)
	{
	    $productModel = new productModel();
	    $product = $productModel->Get($item['id']);

		$js  = "ga('ec:addProduct',{";
		$js .= "'id':'".$product->products_id."',";
		$js .= "'name':'".$product->products_name."',";
		//$js .= "'category':'".$product->products_name."',";
		//$js .= "'brand':'".$product->products_name."',";
		//$js .= "'variant':'".$product->products_name."',";
		$js .= "'price':'".$item['cena_brutto']."',";
		$js .= "'quantity':'".$item['ilosc']."'});";
		
		return $js;

	}
	
	private function Items($items)
	{
		$js = NULL;
		
		foreach($items as $item)
		{
			$js .= $this->SetProduct($item);
		}
	
		return $js;
	}
	
	public function CheckoutStep1($items)
	{
		$js  = $this->Header();
		$js .= $this->Items($items);		
		$js .= $this->SetActionCheckout("'step' : 1");
		$js .= $this->Send();
		$js .= $this->Footer();
		
		return $js;
	
	}
    
	public function CheckoutStep3($items)
    {
		$js  = $this->Header();
		$js .= $this->Items($items);		
		$js .= $this->SetActionCheckout("'step' : 3");
		$js .= $this->Send();
		$js .= $this->Footer();
		
		return $js;
		
    }

    public function Purchase($session)
    {
		$items = $session['koszyk'];
		$id = $session['zamowienie_id'];
		$revenue = 0;
		$tax = 0;
		$shipping = $session['rodzajDostawy']['wysylka_koszt'];
		
		foreach($items as $item)
		{
			$revenue += $item['cena_netto'];
			$tax += $item['vat'];
		}
		
		$js  = $this->Header();
		$js .= $this->Items($items);
		$js .= $this->SetActionPurchase($id, $revenue, $tax, $shipping);
		$js .= $this->Send();
		$js .= $this->Footer();
		
		return $js;
    }

}

?>