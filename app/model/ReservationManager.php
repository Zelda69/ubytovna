<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 28.06.2017
 */

namespace App\Model;

use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\DateTime;

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

    public function getReservationById($id) {
        return $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->fetch();
    }

    public function get_all_reservations() {
        return $this->database->table('reservation_rooms');
    }

    public function getReservationInRange($from, $to) {
        $from = date('Y-m-j', $from);
        $to = date('Y-m-j', $to);

        return $this->database->table('reservation_rooms')
            ->where('(reservation.date_from BETWEEN ? AND ?) OR (reservation.date_to BETWEEN ? AND ?) OR (reservation.date_from <= ? AND reservation.date_to > ?)', $from, $to, $from, $to, $from, $to);
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

    public function delete_reservation($id, $max_days = 7) {
        return $this->database->table(self::TABLE_NAME)
            ->where('id = ? && DATE_SUB(date_from, INTERVAL '.$max_days.' DAY) > NOW()', $id)
            ->delete();
    }

    public function update_reservation($id, $data) {
        $this->database->table(self::TABLE_NAME)->where('id = ?', $id)->update($data);
        $_SESSION['reservation'] = date("M j, Y H:i:s", time() + self::RESERVATION_DELAY_MINUTE * 60);
    }

    public function get_rooms_in_reservation($id) {
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

    public function update_room_in_reservation($id, $room_id, $data) {
        return $this->database->table('reservation_rooms')
            ->where('reservation_id = ? AND room_id = ?', $id, $room_id)
            ->update($data);
    }

    public function count_rooms_in_reservation($id) {
        return count($this->get_rooms_in_reservation($id));
    }

    public function get_room_in_reservation($id, $room_id) {
        return $this->database->table('reservation_rooms')->where('reservation_id = ? && room_id = ?', $id, $room_id)->fetch();
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
        $reservations = $this->database->table(self::TABLE_NAME)->where('guests_id = ?', $guest->id)->group('id');
        $array = array();
        foreach ($reservations as $r) {
            $array[] = $r->id;
        }

        if (count($array) == 0)
            $array = 0;
        return $this->database->table('reservation_rooms')->where('reservation_id IN (?)', $array);
    }

    public function getAllReservations($user) {
        $guest = $this->database->table('user')->where('id = ?', $user)->fetch();
        $reservations = $this->database->table(self::TABLE_NAME)->where('guests_id = ?', $guest->id)->group('id');

        $result = array();
        foreach ($reservations as $r) {
            $result[$r->id]['info'] = $r;
            $result[$r->id]['rooms'] = $this->database->table('reservation_rooms')->where('reservation_id = ?', $r->id);
        }

        return $result;
    }

    /**
     * Vrací počet klientů v zadaném měsíci
     * @param DateTime $month
     */
    public function getCountOfPeopleInMonth($month) {
        $number_year = intval($month->format('Y'));
        $number_month = intval($month->format('m'));

        return $this->database->table('reservation_rooms')
            ->where('YEAR(reservation.date_from) = ? && MONTH(reservation.date_from) = ?', $number_year, $number_month)
            ->group('room_id')
            ->sum('people');
        /*            ->select('SUM(IF(date_to > LAST_DAY(date_from), DATEDIFF(LAST_DAY(date_from), date_from), DATEDIFF(date_to, date_from))) AS rozdil FROM `reservation` WHERE YEAR(date_from) = 2017 && MONTH(date_from) = 8')
                    ->;*/
    }

    public function getSumOfBookedDaysInMonth($month) {
        $number_year = intval($month->format('Y'));
        $number_month = intval($month->format('m'));

        return $this->database->table(self::TABLE_NAME)
            ->where('YEAR(date_from) = ? && MONTH(date_from) = ?', $number_year, $number_month)
            ->select('SUM(IF(date_to > LAST_DAY(date_from), DATEDIFF(LAST_DAY(date_from), date_from), DATEDIFF(date_to, date_from))) AS pocet')
            ->fetch()->pocet;
/// DATEDIFF(DATE("'.$number_year.'-12-31"), DATE("'.$number_year.'-1-1"))
    }

    public function getOccupancyOfRoomInMonth($month, $room) {
        $number_year = intval($month->format('Y'));
        $number_month = intval($month->format('m'));

        $query = $this->database->table('reservation_rooms')
            ->where('YEAR(reservation.date_from) = ? && MONTH(reservation.date_from) = ? && room_id = ?', $number_year, $number_month, $room)
            ->select('SUM(IF(reservation.date_to > LAST_DAY(reservation.date_from), DATEDIFF(LAST_DAY(reservation.date_from), reservation.date_from), DATEDIFF(reservation.date_to, reservation.date_from))) AS pocet')
            ->fetch();
//       / DATEDIFF(LAST_DAY(reservation.date_from), DATE("'.$number_year.'-'.$number_month.'-1"))
        if ($query)
            return $query->pocet; else return 0;
    }

    public function getProfitsInMonths($month) {
        $number_year = intval($month->format('Y'));
        $number_month = intval($month->format('m'));

        return $this->database->table('reservation_rooms')
            ->where('YEAR(reservation.date_from) = ? && MONTH(reservation.date_from) = ?', $number_year, $number_month)
            ->sum('price * (1 + dph / 100)');
    }

    public function store_room_info($reservation_id) {
        $query = $this->database->table('reservation_rooms')->where('reservation_id = ?', $reservation_id);
        foreach ($query as $q) {
            $data = array('price' => $q->room->price);
            if($q->people == 0) {
                $data['people'] = $q->room->type->single_bed + 2 * $q->room->type->double_bed;
            }
            $this->database->table('reservation_rooms')->where('reservation_id = ? && room_id = ?', $reservation_id, $q->room_id)->update($data);
        }
    }

}