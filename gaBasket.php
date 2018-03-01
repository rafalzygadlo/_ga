<?php

include_once "ga.php";
include_once "Libs/Model.php";
include_once "Models/productModel.php";

class gaBasket extends ga
{
	private function SetProduct($id, $qty)
	{
	    $productModel = new productModel();
	    $product = $productModel->Get($id);

		$js  = "ga('ec:addProduct',{";
		$js .= "'id':'".$product->products_id."',";
		$js .= "'name':'".$product->products_name."',";
		//$js .= "'category':'".$product->products_name."',";
		//$js .= "'brand':'".$product->products_name."',";
		//$js .= "'variant':'".$product->products_name."',";
		$js .= "'price':'".$product->products_price."',";
		$js .= "'quantity':'".$qty."'});";
		
		return $js;

	}
	
	/*
	 *	Dodanie do koszyka
	 */
	
    public function Add($id, $qty)
    {
		$js  = $this->Header();
		$js .= $this->SetProduct($id, $qty);
		$js .= $this->SetActionAdd();
		$js .= $this->Send();
		$js .= $this->Footer();
		
		return $js;	
    }

	/*
	 * Usunięcie z koszyka
	 *
	 */
	
    public function Remove($id)
    {
		$js  = $this->Header();
		$js .= $this->SetProduct($id, 0);
		$js .= $this->SetActionRemove();
		$js .= $this->Send();
		$js .= $this->Footer();
		
		
		return $js;
		
        
    }

}

?>