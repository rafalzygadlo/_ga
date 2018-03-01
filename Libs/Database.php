<?php

/**
 * Database
 * 
 * @category   Libs
 * @package    Mass-Symfonia
 * @author     Rafał Żygadło <rafal@maxkod.pl>

 * @copyright  2018 maxkod.pl
 * @version    1.0
 */

class Database extends PDO
{

    private $sth;
    private static $Instance;
    public $Result = true;
    public $Exception = false;    
    
    public function __construct()
    {
		
        try
        {
            parent::__construct(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
            $this->sth = $this->prepare('SET NAMES utf8');
            $this->sth->execute();
        } catch (Exception $ex)
        {
			if($this->Exception)
				new myException('DATABASE CONNECTION ERROR','');
			else
				print $this->sth->errorInfo( )[2];
        }
        
    }

    public static function getInstance()
    {
        if (!self::$Instance)
        {
            self::$Instance = new Database();
            //print '<br><br><div class="alert alert-info">Database instance</div>';
        }
        return self::$Instance;
    }

    public function Row($sql,$params)
    {
        $this->sth = $this->prepare($sql);
        if ($this->sth)
        {
            if ($this->sth->execute($params))
            { 
                //if($class)
                  //  $this->sth->setFetchMode(PDO::FETCH_OBJ);
                //else
                    $this->sth->setFetchMode(PDO::FETCH_OBJ);
                    
                return $this->sth->fetch();
            }else
            {
			    if($this->Exception)
					$this->Exception('DATABASE ERROR',$sql.'<br>'. $this->sth->errorInfo()[2]);
				else
					print $this->sth->errorInfo( )[2];
            }
        }
        else
        {
            return false;
        }

    }

    public function Max($field,$table,$fetchMode = PDO::FETCH_CLASSTYPE)
    {
        $sql = "SELECT MAX($field) as Max FROM $table";

        $this->sth = $this->prepare($sql);
        if ($this->sth)
        {
            if ($this->sth->execute())
            {
                 return $this->sth->fetch($fetchMode);
            }else
            {
				if($this->Exception)
					$this->Exception('DATABASE ERROR',$sql.'<br>'. $this->sth->errorInfo()[2]);
				else
					print $this->sth->errorInfo( )[2];
    
            }
        }
        else
        {
            return false;
        }

    }
    
    public function Query($sql, $params, $fetchMode = PDO::FETCH_ASSOC, $class = null)
    {
        $this->sth = $this->prepare($sql);
        if ($this->sth)
        {
            if ($this->sth->execute($params))
            {
                if ($class == null)
                    return $this->sth->fetchAll($fetchMode);
                else
                    return $this->sth->fetchAll($fetchMode, $class);
            }else
            {
				if($this->Exception)
					$this->Exception('DATABASE ERROR',$sql.'<br>'. $this->sth->errorInfo()[2]);
				else
					print $this->sth->errorInfo( )[2];
            }
        }
        else
        {
            return false;
        }
    }

    public function NonQuery($sql, $params)
    {
 
        $this->sth = $this->prepare($sql);
        if ($this->sth)
        {
            if ($this->sth->execute($params))
            {
                return true;
            }
            else
            {
				if($this->Exception)
					$this->Exception('DATABASE ERROR',$sql.'<br>'. $this->sth->errorInfo()[2]);
				else
					print $this->sth->errorInfo( )[2];
            }
        }
        else
        {
            return false;
        }
    }

    private function Exception($title,$text)
    {
        $this->Result = false;
        new myException($title,$text);
    }
    
    public function RowCount()
    {
        return $this->sth->rowCount();
    }

    public function Count($sql, $params)
    {
        $this->sth = $this->prepare($sql);
        $this->sth->execute($params);
        return $this->sth->fetchColumn();
    }

    public function InsertRandom()
    {
        $counter = 100000;
        while ($counter--)
        {
            $this->query("insert into user set nick='.$counter.'");
        }
    }

}
