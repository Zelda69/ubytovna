<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 27.06.2017
 */

namespace App\Model;

use Nette\Database\Context;

class ServiceManager extends BaseManager {
    const TABLE_NAME = 'service';

    public function __construct(Context $database) {
        parent::__construct($database);
    }

    public function getService($id = NULL) {
        if ($id) {
            return $this->database->table(self::TABLE_NAME)->where('id = ?', $id);
        } else {
            return $this->database->table(self::TABLE_NAME);
        }
    }

    public function getServiceToList($onlyId = FALSE) {
        $result = $this->database->table(self::TABLE_NAME);
        $array = array();
        $nbsp = html_entity_decode('&nbsp;');
        foreach ($result as $item) {
            if($onlyId)  $array[] = $item->id;
            else $array[$item->id] =  \Nette\Utils\Html::el()->setHtml("&nbsp;".$item->image."&nbsp;&nbsp;".$item->name.'&nbsp;&nbsp;');
        }

        return $array;
    }
}