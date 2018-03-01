<?php

/**
 * Model
 * 
 * @category   Libs
 * @package    Mass-Symfonia
 * @author     Rafał Żygadło <rafal@maxkod.pl>

 * @copyright  2018 maxkod.pl
 * @version    1.0
 */



include "Database.php";

class Model
{

    public $DB;
   
    public function __construct()
    {
        $this->DB = Database::getInstance();
    }

    public function GetTitle()
    {
        new myException('NOT IMPLEMENTED', __FUNCTION__);
    }

    public function Insert()
    {
        new myException('NOT IMPLEMENTED', __FUNCTION__);
    }

    public function Update()
    {
        new myException('NOT IMPLEMENTED', __FUNCTION__);
    }

    public function CountAll()
    {
        return 0;
    }
    
    public function All()
    {
        return array();
    }

    public function LastInsertId()
    {
        return $this->DB->lastInsertId();
    }

    public function RowCount()
    {
        return $this->DB->RowCount();
    }

}

