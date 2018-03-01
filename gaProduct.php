<?php

include_once "ga.php";
include_once "Libs/Model.php";
include_once "Models/productModel.php";

class gaProduct extends ga
{
	private function SetProduct($id)
	{
	    $productModel = new productModel();
	    $product = $productModel->Get($id);

		$js  = "ga('ec:addProduct',{";
		$js .= "'id':'".$product->products_id."',";
		$js .= "'name':'".$product->products_name."'";
		//$js .= "'category':'".$product->products_name."',";
		//$js .= "'brand':'".$product->products_name."',";
		//$js .= "'variant':'".$product->products_name."',";
		$js .= "'price':'".$product->products_price."'";
		//$js .= "'quantity':'".$qty."'";
		$js .= "});";
		
		//$js .= "console.log('test')";
		return $js;

	}
	
    public function Click($id)
    {
		$js  = $this->Header();
		$js .= $this->SetProduct($id);
		$js .= $this->SetActionClick();
		$js .= $this->Send();
		$js .= $this->Footer();
		
		return $js;		
    }
	
	public function Detail($id)
    {
		$js  = $this->Header();
		$js .= $this->SetProduct($id);
		$js .= $this->SetActionDetail();
		$js .= $this->Send();
		$js .= $this->Footer();

		return $js;    
    }

}

?>