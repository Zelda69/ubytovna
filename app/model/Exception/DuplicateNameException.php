<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 22.06.2017
 */

namespace App\Model\Exception;

use Nette\Security\AuthenticationException;

/**
 * Class DuplicateNameException
 * @package App\Model\Exception
 */
class DuplicateNameException extends AuthenticationException {

    public function __construct() {
        parent::__construct();
        $this->message = 'Uživatel s tímto jménem je již zaregistrovaný.';
    }
}