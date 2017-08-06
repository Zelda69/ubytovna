<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 19.06.2017
 */

namespace App\Model;


class RoomManager extends BaseManager {
    /** Konstanty pro manipulaci s modelem. */
    const
        TABLE_NAME = 'room';

    public function getRooms($id = NULL) {
        if ($id) {
            return $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->fetch();
        } else return $this->database->table(self::TABLE_NAME)->order('name')->fetchAll();
    }

    public function getPeopleInRoom($id, $withExtra = TRUE) {
        if ($withExtra) {
            return $this->database->table(self::TABLE_NAME)
                ->where('room.id = ?', $id)
                ->select('SUM(room.extra_beds + type.single_bed + 2 * type.double_bed) AS pocet')->fetch();
        } else {
            return $this->database->table(self::TABLE_NAME)
                ->where('room.id = ?', $id)
                ->select('SUM(type.single_bed + 2 * type.double_bed) AS pocet')->fetch();
        }
    }

    public function getRoomServices($room_id, $onlyID = FALSE) {
        $query = $this->database->table('room_services')->where('room_id = ?', $room_id);
        if ($onlyID) {
            $result = array();
            foreach ($query as $row) {
                $result[] = $row->service_id;
            }
            return $result;
        } else return $query;

    }

    public function addRoom($room) {
        $this->database->table(self::TABLE_NAME)->insert($room);

    }

    public function getRoomsServices($id = NULL) {
        if ($id) {
            $query = $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->fetch();
        } else $query = $this->database->table(self::TABLE_NAME)->fetchAll();

        $services = array();
        foreach ($query as $result) {
            $services[$result->id] = $this->getRoomServices($result->id);
        }

        return $services;
    }

    public function getAvaibleRooms($person = 1, $services = array()) {
        $have_service = $this->database->table('room_services')->where('service_id', $services)->group('room_id');
        $array = array();
        foreach ($have_service as $one) {
            $array[] = $one->room_id;
        }

        if (count($array) === 0)
            $array = 0;
        return $this->database->table(self::TABLE_NAME)
            ->where('room.id IN (?)', $array)
            ->where('(type.single_bed + 2 * type.double_bed + extra_beds) >= ?', $person);
    }

    public function getRoomTypes() {
        return $this->database->table('room_type')->where('used = 1');
    }

    public function editRoom($id, $data) {
        $this->database->table(self::TABLE_NAME)->where('id', $id)->update($data);
    }

    public function newRoomType($data) {
        return $this->database->table('room_type')->insert($data)->id;
    }

    public function editRoomType($id, $data) {
        return $this->database->table('room_type')->where('id = ?', $id)->update($data);
    }

    public function deleteRoomType($id) {
        return $this->database->table('room_type')->where('id = ?', $id)->delete();
    }

    public function getUsedRoomTypes($onlyID = FALSE) {
        $query = $this->database->table('room')->group('type_id')->select('type_id');
        if ($onlyID) {
            $arrayUsed = array();
            foreach ($query as $type) {
                $arrayUsed[] = $type->type_id;
            }

            return $arrayUsed;
        } else return $query;
    }

    public function editRoomServices($id, $services) {
        $this->deleteRoomServices($id);
        $this->addRoomServices($id, $services);
    }

    public function addRoomServices($id, $services) {
        foreach ($services as $service) {
            $this->database->table('room_services')->insert(['room_id' => $id, 'service_id' => $service]);
        }
    }

    public function deleteRoomServices($id) {
        return $this->database->table('room_services')->where('room_id = ?', $id)->delete();
    }
}