<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 28.06.2017
 */

namespace App\FrontModule\Model;

use App\Model\BaseManager;
use Nette\Database\IRow;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class ReservationManager extends BaseManager {
    const TABLE_NAME = 'orders';

    /**
     * @param $room
     * @param $from
     * @param $to
     * @param $user
     * @param $note
     * @return string
     */
    public function add($room, $from, $to, $user, $note) {
        return $this->database->table(self::TABLE_NAME)
            ->insert(['user_id' => $user, 'room_id' => $room, 'note' => $note, 'date_from' => $from, 'date_to' => $to])->id;
    }

    /***
     * Vrací rezervace podle zadaných požadavký, bez požadavků vrací všechny
     *
     * @param null $room
     * @param null $user
     * @param null $from
     * @param null $to
     * @return Selection
     */
    public function get($room = NULL, $user = NULL, $from = NULL, $to = NULL) {
        $cond = array();
        if(!is_null($room)) $cond['room_id'] = $room;
        if(!is_null($user)) $cond['user_id'] = $user;
        if(!is_null($from)) $cond['date_from'] = $from;
        if(!is_null($to)) $cond['date_to'] = $to;
        return $this->database->table(self::TABLE_NAME)->where($cond)->order('date_from DESC, date_to DESC');
    }
}