<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 23.06.2017
 */

namespace App\Model\Exception;


use Nette\Database\ForeignKeyConstraintViolationException;

class ImageIsUsedException extends ForeignKeyConstraintViolationException {

    public function __construct() {
        parent::__construct();
        $this->message = 'Nemůžeš mazat obrázek, který se používá!';
    }
}