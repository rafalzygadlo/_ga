<?php

/**
 * orderModel
 *
 * @category   Model
 * @package    ga
 * @author     Rafał Żygadło <rafal@maxkod.pl>

 * @copyright  2018 maxkod.pl
 * @version    1.0
 */


class productModel extends Model
{
    
    function __construct()
    {  
        parent::__construct();
       
    }
    
	public function GetItems($item)
    {

        $params = array
        (
          ':products_id' => $item['products_id']
        );
	
	    //print_r($params);
        		
		$sql = 'SELECT DISTINCT p.products_id, p.products_price_tax, pd.products_name, cu.value,
        cu.currencies_marza, (p.products_price_tax/cu.value)+(cu.value*cu.currencies_marza/100) AS cena,
        p.products_weight FROM products p
        LEFT JOIN currencies cu ON cu.currencies_id = p.products_currencies_id
        LEFT JOIN products_options_products pop ON pop.pop_products_id_slave = p.products_id
        LEFT JOIN products_description pd ON pd.products_id = p.products_id AND pd.language_id = 1
        WHERE pop.pop_products_id_master=:products_id
        AND p.products_status = 1 AND (p.customers_group_id = 0 or p.customers_group_id = "")
        ORDER BY p.products_weight';
    	
		return $this->DB->Query($sql,$params);
	}
	
    public function Get($id)
    {
        $params = array
        (
          ':products_id' => $id,
        );
			
		return $this->DB->Row("SELECT products.products_id, products_name, products_price, products_type FROM products LEFT JOIN products_description ON products.products_id = products_description.products_id AND products_description.language_id = 1 WHERE products.products_id =:products_id", $params);
    }

}
