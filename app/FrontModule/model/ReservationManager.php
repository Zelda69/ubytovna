<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 28.06.2017
 */

namespace App\FrontModule\Model;

use App\Model\BaseManager;
use Nette\Database\Table\Selection;
use Nette\Database\UniqueConstraintViolationException;

class ReservationManager extends BaseManager {
    const TABLE_NAME = 'reservation';
    const RESERVATION_DELAY_MINUTE = 15;

    public function get_reservation() {
        // Smazání prošlých rezervací
        $this->database->table(self::TABLE_NAME)
            ->where('DATE_ADD(last_change, INTERVAL '.self::RESERVATION_DELAY_MINUTE.' MINUTE) < NOW() && step < 3')
            ->delete();

        // Načtení aktuální rezervace pro daného uživatele
        if (!isset($_SESSION['reservation_id']) || $_SESSION['reservation_id'] == 0) {
            return $this->database->table(self::TABLE_NAME)
                ->where('session_id = ? AND step < 3', session_id())
                ->fetch();
        } else {
            return $this->database->table(self::TABLE_NAME)
                ->where('id = ? AND step < 3', $_SESSION['reservation_id'])
                ->fetch();
        }
    }

    public function get_all_reservations() {
        return $this->database->table('reservation_rooms');
    }

    public function new_reservation($from, $to) {
        $data = array();
        $data['date_from'] = $from;
        $data['date_to'] = $to;
        $data['session_id'] = session_id();

        $res = $this->database->table(self::TABLE_NAME)->insert($data);
        $_SESSION['reservation'] = date("M j, Y H:i:s", time() + self::RESERVATION_DELAY_MINUTE * 60);
        $_SESSION['reservation_id'] = $res->id;
        $_SESSION['filter']['from'] = $from;
        $_SESSION['filter']['to'] = $to;
        $_SESSION['filter']['use'] = TRUE;

    }

    public function delete_reservation($id) {
        $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->delete();
    }

    public function update_reservation($id, $data) {
        $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->update($data);
        $_SESSION['reservation'] = date("M j, Y H:i:s", time() + self::RESERVATION_DELAY_MINUTE * 60);
    }

    public function get_room_in_reservation($id) {
        return $this->database->table('reservation_rooms')->where('reservation_id = ?', $id);
    }

    public function new_room_in_reservation($id, $room_id) {
        try {
            $this->database->table('reservation_rooms')->insert(array('reservation_id' => $id, 'room_id' => $room_id));
        } catch (UniqueConstraintViolationException $e) {
        }
    }

    public function delete_room_in_reservation($id, $room_id) {
        $this->database->table('reservation_rooms')
            ->where('reservation_id = ? AND room_id = ?', $id, $room_id)
            ->delete();
    }

    public function count_rooms_in_reservation($id) {
        return count($this->get_room_in_reservation($id));
    }

    public function isRoomAvaible($id, $from, $to) {
        $already_booked = $this->database->table('reservation_rooms')
            ->where('(? >= reservation.date_from AND ? < reservation.date_to) OR (? > reservation.date_from && ? <= reservation.date_to)', $from, $from, $to, $to);
        $array = array();
        foreach ($already_booked as $one) {
            $array[] = $one->room_id;
        }
        return !in_array($id, $array);
    }

    public function getAvaibleRoomsId($from, $to) {
        $already_booked = $this->database->table('reservation_rooms')
            ->where('(? >= reservation.date_from AND ? < reservation.date_to) OR (? > reservation.date_from && ? <= reservation.date_to)', $from, $from, $to, $to);
        $array = array();
        foreach ($already_booked as $one) {
            $array[] = $one->room_id;
        }

        if (count($array) === 0)
            $array = 0;

        $result = array();
        $query = $this->database->table('room')->where('id NOT IN (?)', $array)->fetchPairs('id');
        foreach ($query as $record) {
            $result[] = $record->id;
        }

        return $result;
    }


    /**
     * @param $data
     * @return string
     */
    public function add($data) {
        return $this->database->table(self::TABLE_NAME)->insert($data)->id;
    }

    public function getReservationFromUser($user) {
        $guest = $this->database->table('user')->where('id = ?', $user)->fetch();
        $reservations = $this->database->table(self::TABLE_NAME)
            ->where('guests_id = ?', $guest->id)
            ->group('id');
        $array = array();
        foreach ($reservations as $r) {
            $array[] = $r->id;
        }

        if(count($array) == 0) $array = 0;
        return $this->database->table('reservation_rooms')->where('reservation_id IN (?)', $array);
    }

    /***
     * Vrací rezervace podle zadaných požadavký, bez požadavků vrací všechny
     *
     * @param null $room
     * @param null $user
     * @param null $from
     * @param null $to
     * @return Selection

    public function get($room = NULL, $user = NULL, $from = NULL, $to = NULL) {
     * $cond = array();
     * if (!is_null($room))
     * $cond['room_id'] = $room;
     * if (!is_null($user))
     * $cond['guests_id'] = $user;
     * if (!is_null($from))
     * $cond['date_from'] = $from;
     * if (!is_null($to))
     * $cond['date_to'] = $to;
     * return $this->database->table(self::TABLE_NAME)->where($cond)->order('date_from DESC, date_to DESC');
     * }
     * */
}