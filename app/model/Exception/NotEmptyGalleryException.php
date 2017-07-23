<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 23.06.2017
 */

namespace App\Model\Exception;


use Nette\Database\ForeignKeyConstraintViolationException;

class NotEmptyGalleryException extends ForeignKeyConstraintViolationException {

    public function __construct() {
        parent::__construct();
        $this->message = 'Nelze smazat galerii, která obsahuje obrázky!';
    }
}