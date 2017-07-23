<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 19.06.2017
 */

namespace App\FrontModule\Model;


use App\Model\BaseManager;
use Tracy\Debugger;


class RoomManager extends BaseManager {
    /** Konstanty pro manipulaci s modelem. */
    const
        TABLE_NAME = 'room';

    public function getRooms($id = NULL) {
        if($id) {
            return $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->fetch();
        }
        else return $this->database->table(self::TABLE_NAME)->order('name')->fetchAll();
    }

    public function getRoomServices($room_id) {
        return $this->database->table('room_services')->where('room_id = ?', $room_id);
    }

    public function addRoom($room) {
        $this->database->table(self::TABLE_NAME)->insert($room);

    }

    public function getRoomsServices($id = NULL) {
        if($id) {
            $query = $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->fetch();
        }
        else $query = $this->database->table(self::TABLE_NAME)->fetchAll();

        $services = array();
        foreach ($query as $result) {
            $services[$result->id] = $this->getRoomServices($result->id);
        }

        return $services;
    }

    public function getAvaibleRooms($from, $to, $person = 1, $services = array()) {
        Debugger::barDump($services, 'Services');
        $already_booked = $this->database->table('orders')->where('(? >= date_from AND ? < date_to) OR (? > date_from && ? <= date_to)', $from, $from, $to, $to);
        $array = array();
        foreach ($already_booked as $one) {
            $array = $one->room_id;
        }

        if(count($array) === 0) $array = 0;
        return $this->database->table(self::TABLE_NAME)->where('room.id NOT IN (?)', $array)->where('(type.single_bed + 2 * type.double_bed + extra_beds) >= ?', $person);
    }

    public function isRoomAvaible($id, $from, $to) {
        $already_booked = $this->database->table('orders')->where('(? >= date_from AND ? < date_to) OR (? > date_from && ? <= date_to)', $from, $from, $to, $to);
        $array = array();
        foreach ($already_booked as $one) {
            $array = $one->room_id;
        }
        return !in_array($id, $array);
    }

    public function getRoomTypes() {
        return $this->database->table('room_type');
    }

    public function editRoom($id, $data) {
        $this->database->table(self::TABLE_NAME)->where('id', $id)->update($data);
    }
}