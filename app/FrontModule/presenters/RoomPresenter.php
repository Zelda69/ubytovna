<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 23.06.2017
 */

namespace App\FrontModule\Presenters;


use App\Forms\Rules;
use App\Model\ReservationManager;
use App\Model\RoomManager;
use App\Model\ServiceManager;
use App\Model\ImageManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Utils\Html;
use Tracy\Debugger;

class RoomPresenter extends BasePresenter {

    /** @var RoomManager */
    private $roomManager;
    /** @var ImageManager */
    private $imageManager;
    /** @var  ServiceManager */
    private $serviceManager;
    /** @var ReservationManager */
    private $reservationManager;
    // Filter
    /** @var  int */
    private $filter_from;
    /** @var  int */
    private $filter_to;
    /** @var  int */
    private $filter_persons;
    /** @var  array */
    private $filter_services;
    /** @var int */
    private $selected_room = 0;

    /**
     * RoomPresenter constructor.
     * @param RoomManager $roomManager
     * @param ImageManager $imageManager
     * @param ServiceManager $serviceManager
     * @param ReservationManager $reservationManager
     */
    public function __construct(RoomManager $roomManager, ImageManager $imageManager, ServiceManager $serviceManager, ReservationManager $reservationManager) {
        parent::__construct();
        $this->roomManager = $roomManager;
        $this->imageManager = $imageManager;
        $this->serviceManager = $serviceManager;
        $this->reservationManager = $reservationManager;

        $this->defaultFilterSetup();
        $this->filter_from = $_SESSION['filter']['from'];
        $this->filter_to = $_SESSION['filter']['to'];
        $this->filter_services = $_SESSION['filter']['services'];
        $this->filter_persons = $_SESSION['filter']['person_count'];
    }

    /**
     * Rezervace pokoje o daném ID
     * @param $id
     */
    public function handleRezervuj($id) {
        if ($this->reservationManager->isRoomAvaible($id, $_SESSION['filter']['from'], $_SESSION['filter']['to'])) {
            $reservation = $this->reservationManager->get_reservation();
            if ($reservation) {
                $this->reservationManager->update_reservation($reservation->id, ['last_change' => date('Y-m-d H:i:s')]);
                $this->reservationManager->new_room_in_reservation($reservation->id, $id);
            } else {
                $this->reservationManager->new_reservation($_SESSION['filter']['from'], $_SESSION['filter']['to']);
                $reservation = $this->reservationManager->get_reservation();
                $this->reservationManager->new_room_in_reservation($reservation->id, $id);
            }
            $this->redirect('Reservation:default');
        } else {
            $this->flashMessage('Tento pokoj je v zadaném období již rezervován. Rezervace není možná.', 'error');
            $this->redirect('this');
        }
    }

    /**
     * Zrušení filtru
     */
    public function handleNoFilter() {
        $_SESSION['filter']['use'] = FALSE;
        $this->storeFilter(date('Y-m-d'), date('Y-m-d', time() + 86400), $this->serviceManager->getServiceToList(true), 1);
        $this->redirect('this');
    }


    /**
     * Vrací dostupné pokoje podle filtru
     * @param $from
     * @param $to
     * @param $people
     * @param $services
     * @return array
     */
    private function getAvaibleRooms($from, $to, $people, $services) {
        $results = array();
        $in_date = $this->reservationManager->getAvaibleRoomsId($from, $to);
        $with_other = $this->roomManager->getAvaibleRooms($people, $services);
        Debugger::barDump($in_date, 'date');
        Debugger::barDump($with_other, 'other');
        foreach ($with_other as $one) {
            if (in_array($one->id, $in_date))
                $results[] = $one;
        }

        return $results;
    }

    private function isRoomAvaibleInDates($room, $from, $to) {
        $avaibleInDate = $this->reservationManager->getAvaibleRoomsId($from, $to);
        return in_array($room, $avaibleInDate);
    }

    /**
     * Vykreslení default.latte
     */
    public function renderDefault() {
        if ($_SESSION['filter']['use']) {
            $this->template->rooms = $this->getAvaibleRooms($this->filter_from, $this->filter_to, $this->filter_persons, $this->filter_services);
        } else $this->template->rooms = $this->roomManager->getRooms();
        // Smazání prošlých rezervací
        $this->reservationManager->get_reservation();
        // Informace o filtru
        $this->template->filter = $_SESSION['filter']['use'];
        $this->template->services = $this->roomManager->getRoomsServices();
        $this->template->from = $this->filter_from;
        $this->template->to = $this->filter_to;
        $this->template->dph = 1 + $this->serviceInformationManager->getDPH() / 100;
    }

    /**
     * Formulář pro filtr
     * @return Form
     */
    public function createComponentVacancyFilterForm() {
        $form = new Form();
        $form->getRenderer()->wrappers['group']['label'] = "legend id='room-filter-fieldset'";
        $form->getRenderer()->wrappers['controls']['container'] = "table id='room-filter-table'
        ".(isset($_REQUEST['from']) ? " style='display: block;'" : "")."";
        $form->addText('from', 'Datum příjezdu:')
            ->setDefaultValue($this->filter_from)
            ->setHtmlAttribute('min', date('Y-m-d'))
            ->setType('date')
            ->setRequired('Musíte vyplnit datum příjezdu!');
        $form->addText('to', 'Datum odjezdu:')
            ->setDefaultValue($this->filter_to)
            ->setHtmlAttribute('min', date('Y-m-d'))
            ->setType('date')
            ->addRule(Rules::DATERANGE, 'Neplatné datum odjezdu!', [$form['from'], date('Y-m-d'), 2])
            ->setRequired('Musíte vyplnit datum odjezdu!');
        $form['from']->addRule(Rules::DATERANGE, 'Neplatné datum příjezdu!', [date('Y-m-d'), $form['to']]);
        $form->addText('person', 'Počet osob:')
            ->setType('number')
            ->setHtmlAttribute('min', 1)
            ->setHtmlAttribute('max', 10)
            ->setDefaultValue($this->filter_persons)
            ->addRule(Form::RANGE, 'Počet osob musí být %d - %d', [1, 10])
            ->setRequired('Vyplňte prosím počet osob');
        $form->addCheckboxList('services', 'Služby:', $this->serviceManager->getServiceToList())
            ->getSeparatorPrototype()
            ->setName(NULL);
        $form->setDefaults(['services' => $_SESSION['filter']['services']]);
        $form->addSubmit('submit', 'Filtruj pokoje');
        $form->onSuccess[] = [$this, 'vacancyFilterFormSucceeded'];

        return $form;
    }

    public function vacancyFilterFormSucceeded($form, $values) {
        $this->storeFilter($values->from, $values->to, $values->services, $values->person);
        $_SESSION['filter']['use'] = TRUE;
        $this->redirect('this');
    }

    /**
     * Základní nastavení filtru uložené do sessions pokud neexistuje
     */
    private function defaultFilterSetup() {
        if (!isset($_SESSION['filter']['use']))
            $_SESSION['filter']['use'] = FALSE;
        if (!isset($_SESSION['filter']['from']))
            $_SESSION['filter']['from'] = date('Y-m-d');
        if (!isset($_SESSION['filter']['to']))
            $_SESSION['filter']['to'] = date('Y-m-d', time() + 86400);
        if (!isset($_SESSION['filter']['services']))
            $_SESSION['filter']['services'] = $this->serviceManager->getServiceToList(true);
        if (!isset($_SESSION['filter']['person_count']))
            $_SESSION['filter']['person_count'] = 1;
    }

    /**
     * Uložení hodnot do $_SESSION filtru a zároveň do třídy
     * @param null $from
     * @param null $to
     * @param null $services
     * @param null $person_numbs
     */
    private function storeFilter($from = NULL, $to = NULL, $services = NULL, $person_numbs = NULL) {
        if (!is_null($from))
            $_SESSION['filter']['from'] = $from;
        if (!is_null($to))
            $_SESSION['filter']['to'] = $to;
        if (!is_null($services))
            $_SESSION['filter']['services'] = $services;
        if (!is_null($person_numbs))
            $_SESSION['filter']['person'] = $person_numbs;

        // Zápis do třídy
        $this->filter_from = $_SESSION['filter']['from'];
        $this->filter_to = $_SESSION['filter']['to'];
        $this->filter_services = $_SESSION['filter']['services'];
        $this->filter_persons = $_SESSION['filter']['person_count'];
    }

    /**
     * Vykreslení DETAILU pokoje detail.latte
     * @param $id id pokoje
     */
    public function renderDetail($id) {
        $this->template->room = $this->roomManager->getRooms($id);
        if (!isset($this->template->room['name'])) {
            $this->flashMessage('Zadaný pokoj neexistuje!', 'error');
            $this->redirect('Room:');
        }
        $this->selected_room = $id;

        // Přenesení filtru z vyhledávání
        if (isset($this->template->isAvaible)) Debugger::barDump($this->template->isAvaible, 'Avaible before');
        Debugger::barDump($_SESSION['filter']['use'], 'Filter USE');
        if ($_SESSION['filter']['use'] && !isset($this->template->isAvaible)) {
            $this->template->isAvaible = $this->isRoomAvaibleInDates($id, $this->filter_from, $this->filter_to);
            if($this->template->isAvaible) $this->template->controlVacancy = false;
        }
        // jE POKOJ DOSTUPNÝ
        if(!isset($this->template->isAvaible)) $this->template->isAvaible = NULL;
        Debugger::barDump($this->template->isAvaible, 'Avaible after');

        // Služby
        $this->template->services = $this->roomManager->getRoomServices($id); //služby
        $this->template->dph = 1 + $this->serviceInformationManager->getDPH() / 100; //DPH
        // Přenesení filtru do výchozích hodnot
        $this->template->from = $this->filter_from;
        $this->template->to = $this->filter_to;

        // Pokud nemá galerii, musíš vložit prázdné pole
        if (!is_null($this->template->room->photogallery_id)) {
            $this->template->gallery = $this->imageManager->getGallery($this->template->room->photogallery_id);
        } else $this->template->gallery = array();

        // Zobrazit formulář na ověření
        if(!isset($this->template->controlVacancy)) $this->template->controlVacancy = true;
    }

    /**
     * Formulář, pro zjištění obsazenosti pokoje v datum
     * @param $id
     * @return Form
     */
    public function createComponentVacancyConfirmForm() {
        $form = new Form();
        $form->addHidden('id', $this->selected_room);
        $form->addText('from', 'Datum příjezdu:')
            ->setDefaultValue($this->filter_from)
            ->setHtmlAttribute('min', date('Y-m-d'))
            ->setType('date')
            ->setRequired('Musíte vyplnit datum příjezdu!');
        $form->addText('to', 'Datum odjezdu:')
            ->setDefaultValue($this->filter_to)
            ->setHtmlAttribute('min', date('Y-m-d'))
            ->setType('date')
            ->addRule(Rules::DATERANGE, 'Neplatné datum odjezdu!', [$form['from'], date('Y-m-d'), 2])
            ->setRequired('Musíte vyplnit datum odjezdu!');
        $form['from']->addRule(Rules::DATERANGE, 'Neplatné datum příjezdu!', [date('Y-m-d'), $form['to']]);
        $form->addSubmit('submit', 'Ověřit dostupnost');
        $form->onSuccess[] = [$this, 'vacancyConfirmFormSucceeded'];
        return $form;
    }

    public function vacancyConfirmFormSucceeded($form, $values) {
        if(empty($values->from) || empty($values->to)) {
            $this->flashMessage('Musíte zadat vyplnit datum!', 'error');
            $this->redirect('this');
        } else if (date_create($values->from) >= date_create($values->to)) {
            $this->flashMessage('Neplatné datum příjezdu!', 'error');
            $this->redirect('this');
        } else {
            Debugger::barDump($values->id, 'ID pokoje');
            $this->template->isAvaible = $this->isRoomAvaibleInDates($values->id, $values->from, $values->to);
            if($this->template->isAvaible == true) {$this->template->controlVacancy = false;}
            else $this->template->controlVacancy = true;
            $this->storeFilter($values->from, $values->to);
            $this->redrawControl('room-detail-reservation');
        }
    }

    /**
     * Multiple formulář pro rezervaci (multiple protože rezervování i na listu pokojů)
     * @return Multiplier
     */
    protected function createComponentMultipleReservationForm() {
        $roomManager = '';
        $reservationManager = '';
        $control = new Multiplier(function ($room_id) use ($roomManager, $reservationManager) {
            $form = new Form(NULL, $room_id);
            $form->addHidden('room_id')->setDefaultValue($room_id);
            $form->addSubmit('reservate', 'Rezervovat');
            $form->onSuccess[] = [$this, 'reservationFormSucceeded'];
            return $form;
        });

        return $control;
    }

    /**
     * Rezervace pokoje (odpoveď na formulář)
     * @param $form
     * @param $values
     */
    public function reservationFormSucceeded($form, $values) {
        if ($this->reservationManager->isRoomAvaible($values->room_id, $_SESSION['filter']['from'], $_SESSION['filter']['to'])) {
            $reservation = $this->reservationManager->get_reservation();
            if ($reservation) {
                $this->reservationManager->update_reservation($reservation->id, ['last_change' => date('Y-m-d H:i:s')]);
                $this->reservationManager->new_room_in_reservation($reservation->id, $values->room_id);
            } else {
                $this->reservationManager->new_reservation($_SESSION['filter']['from'], $_SESSION['filter']['to']);
                $reservation = $this->reservationManager->get_reservation();
                $this->reservationManager->new_room_in_reservation($reservation->id, $values->room_id);
            }
            $this->redirect('Reservation:default');
        } else {
            $this->flashMessage('Tento pokoj je v zadaném období již rezervován. Rezervace není možná.', 'error');
            $this->redirect('this');
        }
    }
}