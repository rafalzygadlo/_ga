<?php

include_once "ga.php";
include_once "Libs/Model.php";
include_once "Models/productModel.php";

class gaProductList extends ga
{
	private function SetProduct($product)
	{
	    
		$js  = "ga('ec:addImpression',{";
		$js .= "'id':'".$product['products_id']."',";
		$js .= "'name':'".$product['products_name']."',";
		//$js .= "'category':'".$product->products_name."',";
		//$js .= "'brand':'".$product->products_name."',";
		//$js .= "'variant':'".$product->products_name."',";
		$js .= "'price':'".$product['products_price_tax']."";
		//$js .= "'quantity':'".$qty."'";
		$js .= "});";
		
		//$js .= "console.log('test')";
		return $js;

	}
	
	private function Items($items)
	{
		$productModel = new productModel();
		$js = NULL;
	    
		foreach($items as $item)
		{
			$js .= $this->SetProduct($item);
		
			$sub_items = $productModel->GetItems($item);
			
			foreach($sub_items as $item)
			{
				$js .= $this->SetProduct($item);
			}
		}
		
		return $js;
	}
	
	/*
	 * @ param $items expected array
	 **/
	public function AddItems($items)
	{
		$js  = $this->Header();
		$js .= $this->Items($items);
		$js .= $this->Send();
		$js .= $this->Footer();
		
		return $js;
	}

}

?>