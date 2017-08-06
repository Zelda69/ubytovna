<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 02.08.2017
 */

namespace App\BackModule\Presenters;


use App\Model\ReservationManager;
use App\Model\RoomManager;
use App\Presenters\BasePresenter;
use Nette\Utils\DateTime;

class DataPresenter extends BasePresenter {
    /** @var ReservationManager */
    private $reservationManager;
    /** @var RoomManager */
    private $roomManager;

    /**
     * DataPresenter constructor.
     * @param ReservationManager $reservationManager
     * @param RoomManager $roomManager
     */
    public function __construct(ReservationManager $reservationManager, RoomManager $roomManager) {
        parent::__construct();
        $this->reservationManager = $reservationManager;
        $this->roomManager = $roomManager;
    }

    public function renderDefault($id) {
        $data = 'No data to parse';
        if ($this->getUser()->isLoggedIn() && $this->getUser()->isInRole('admin')) {
            switch (intval($id)) {
                case 1:
                    $data = $this->getGraphDataTrafficInMonth();
                    break;
                case 2:
                    $data = $this->getGraphDataOccupancyInMonth();
                    break;
                case 3:
                    $data = $this->getGraphDataBookedDayInYear();
                    break;
                case 4:
                    $data = $this->getGraphDataProfitsInMonth();
                    break;
            }
        }
        $this->template->data = $data;
    }


    private function addColumn($title, $type) {
        return array('id' => '', 'label' => $title, 'type' => $type);
    }

    private function addRow($values) {
        $one_row = array('c' => array());
        foreach ($values as $value) {
            $one_row['c'][] = array('v' => $value, 'f' => NULL);
        }
        return $one_row;
    }

    private function getGraphDataTrafficInMonth() {
        $result = array('cols' => array(), 'rows' => array());
        // Záhlaví
        $result['cols'][] = $this->addColumn('Měsíc', 'string');
        $result['cols'][] = $this->addColumn('Počet', 'number');

        // Vložení dat
        $months = array_reverse($this->getMonthInLastYear());
        foreach ($months as $m) {
            $result['rows'][] = $this->addRow([$m->format('F Y'), $this->reservationManager->getCountOfPeopleInMonth($m)]);
        }

        return $result;
    }

    private function getGraphDataOccupancyInMonth() {
        $result = array('cols' => array(), 'rows' => array());
        // Záhlaví
        $result['cols'][] = $this->addColumn('Pokoj', 'string');
        $result['cols'][] = $this->addColumn('Obsazenost', 'number');

        // Vložení dat
        $month = intval(date("m"));
        $year = intval(date('Y'));
        $date = DateTime::createFromFormat('!m Y', $month.' '.$year);
        foreach ($this->roomManager->getRooms() as $room) {
            $result['rows'][] = $this->addRow([$room->name, $this->reservationManager->getOccupancyOfRoomInMonth($date, $room->id)]);
        }
        return $result;
    }

    private function getGraphDataBookedDayInYear() {
        $result = array('cols' => array(), 'rows' => array());
        // Záhlaví
        $result['cols'][] = $this->addColumn('Měsíc', 'string');
        $result['cols'][] = $this->addColumn('Obsazenost', 'number');

        // Vložení dat
        $months = $this->getMonthInLastYear();
        foreach ($months as $m) {
            $result['rows'][] = $this->addRow([$m->format('F Y'), $this->reservationManager->getSumOfBookedDaysInMonth($m)]);
        }

        return $result;
    }

    private function getGraphDataProfitsInMonth() {
        $result = array('cols' => array(), 'rows' => array());
        // Záhlaví
        $result['cols'][] = $this->addColumn('Měsíc', 'string');
        $result['cols'][] = $this->addColumn('Zisk', 'number');

        // Vložení dat
        $months = array_reverse($this->getMonthInLastYear());
        foreach ($months as $m) {
            $result['rows'][] = $this->addRow([$m->format('F Y'), $this->reservationManager->getProfitsInMonths($m)]);
        }

        return $result;
    }

    /**
     * Pomocná funkce, vrací měsíce s roky za poslední rok
     * @return array
     */
    private function getMonthInLastYear() {
        $months = array();
        $month = intval(date("m"));
        $year = intval(date('Y'));

        for($i = 0; $i <12; $i++) {
            $months[] = DateTime::createFromFormat('!m Y', $month.' '.$year);
            $month--;
            if($month === 0) {
                $month = 12;
                $year--;
            }
        }
        return $months;
    }

}