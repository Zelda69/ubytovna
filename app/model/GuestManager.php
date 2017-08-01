<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 31.07.2017
 */

namespace App\Model;

use Nette\Database\Context;

class GuestManager extends BaseManager {
    const TABLE_NAME = 'guests';

    public function __construct(Context $database) {
        parent::__construct($database);
    }

    public function get($id) {
        return $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->fetch();
    }

    public function add($data) {
        $res = $this->database->table(self::TABLE_NAME)->insert($data);
        return $res->id;
    }

    public function delete($id) {
        $this->database->table(self::TABLE_NAME)->where('id = ?', intval($id))->delete();
    }

    public function update($id, $data) {
        $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->update($data);
    }

    public function get_all() {
        return $this->database->table(self::TABLE_NAME);
    }
}