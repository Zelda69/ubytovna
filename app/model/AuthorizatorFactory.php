<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 22.06.2017
 */

namespace App\Model;

use Nette\Security\Permission;


class AuthorizatorFactory {

    public static function create() {
        $acl = new Permission();


        return $acl;
    }
}