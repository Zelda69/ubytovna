<?php
/**
 * Created by Zbyněk Mlčák
 * Date: 12.06.2017
 */

namespace App\Model;

use Nette\Database\Context;
use Nette\Object;

/**
 * Class BaseManager - základní třída modelu
 * Předává přístup pro práci s databází
 * @package app\model
 */
class BaseManager extends Object{

    /** @var Context Instance PDO databáze */
    protected $database;
    /** @var bool|mixed|\Nette\Database\Table\IRow Základní servisní informace */
    protected $service_information;

    /**
     * BaseManager constructor.
     * @param Context $database
     */
    public function __construct(Context $database) {
        $this->database = $database;
    }
}