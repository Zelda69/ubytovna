<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 17.07.2017
 */

namespace App\Model;


class ReviewManager extends BaseManager {
    const TABLE_NAME = 'reviews';
    const COLUMN_ID = 'reservation_id';

    public function getOne($id) {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID.' = ?', $id);
    }

    public function getAllByGuest($guest) {
        return $this->database->table(self::TABLE_NAME)->where('reservation.guests_id = ?', $guest);
    }

    public function add($data) {
        return $this->database->table(self::TABLE_NAME)->insert($data)->id;
    }

    public function delete($id) {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID.' = ?', $id)->delete();
    }
}