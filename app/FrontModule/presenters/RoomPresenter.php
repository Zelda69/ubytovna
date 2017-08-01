<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 23.06.2017
 */

namespace App\FrontModule\Presenters;


use App\Forms\Rules;
use App\FrontModule\Model\ReservationManager;
use App\FrontModule\Model\RoomManager;
use App\FrontModule\Model\ServiceManager;
use App\Model\ImageManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
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
    /** @var null */
    private $avaibleRooms = NULL;
    /** @var true|false */
    private $isRoomAvaible = NULL;
    /** @var  int */
    private $filter_from;
    /** @var  int */
    private $filter_to;
    /** @var  int */
    private $filter_persons;
    /** @var  array */
    private $filter_services;

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

        $this->filter_from = $_SESSION['filter']['from'];
        $this->filter_to = $_SESSION['filter']['to'];
        $this->filter_services = $_SESSION['filter']['services'];
        $this->filter_persons = $_SESSION['filter']['person_count'];
    }

    public function handleNoFilter() {
        $_SESSION['filter']['use'] = FALSE;
        $this->storeFilter(date('Y-m-d'), date('Y-m-d', time() + 86400), $this->serviceManager->getServiceToList(true), 1);
        $this->redirect('this');
    }

    public function actionFilter($from, $to) {
        $this->avaibleRooms = $this->roomManager->getAvaibleRooms($from, $to);
        $this->storeFilter($from, $to);
        $this->setView('default');
    }

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

    public function renderDefault() {
        if ($_SESSION['filter']['use']) {
            $this->template->rooms = $this->getAvaibleRooms($this->filter_from, $this->filter_to, $this->filter_persons, $this->filter_services);
        } else $this->template->rooms = $this->roomManager->getRooms();
        $this->reservationManager->get_reservation();
        $this->template->filter = $_SESSION['filter']['use'];
        $this->template->services = $this->roomManager->getRoomsServices();
        $this->template->from = $this->filter_from;
        $this->template->to = $this->filter_to;
        Debugger::barDump($_SESSION['filter'], 'filter use');
    }

    /**
     * @return Form
     */
    public function createComponentVacancyFilterForm() {
        $form = new Form();
        if ($_SESSION['filter']['use'])
            $a = '<span style="color:green;">aktivní</span>'; else $a = '<span style="color:red;">neaktivní</span>';
        $form->getRenderer()->wrappers['group']['label'] = "legend id='room-filter-fieldset'";
        $form->getRenderer()->wrappers['controls']['container'] = "table id='room-filter-table'
        ".(isset($_REQUEST['from']) ? " style='display: block;'" : "")."";
        $form->addGroup(\Nette\Utils\Html::el()->setHtml('Filtrovat pokoje podle požadavků ('.$a.')'));
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
        $form->addText('person', 'Počet osob')
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
        $form->addSubmit('a', 'Filtruj pokoje');
        $form->onSuccess[] = [$this, 'vacancyFilterFormSucceeded'];

        return $form;
    }

    public function vacancyFilterFormSucceeded($form, $values) {
        $this->storeFilter($values->from, $values->to, $values->services, $values->person);
        Debugger::barDump($this->filter_services, 'služby');
        $_SESSION['filter']['use'] = TRUE;
        $this->redirect('this');
        //$this->redrawControl('vacancyFilterForm');
    }

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

    public function createComponentVacancyConfirmForm($id) {
        $form = new Form();
        $form->addHidden('id', $id);
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
        Debugger::barDump($this->isRoomAvaible, 'Avaible');
        $this->isRoomAvaible = $this->reservationManager->isRoomAvaible($values->id, $values->from, $values->to);
        $this->redrawControl('room-detail-reservation');
        $this->filter_from = $values->from;
        $this->filter_to = $values->to;
        $_SESSION['filter']['from'] = $values->from;
        $_SESSION['filter']['to'] = $values->to;
    }

    public function renderDetail($id) {
        $this->template->room = $this->roomManager->getRooms($id);
        if (!isset($this->template->room['name'])) {
            $this->flashMessage('Zadaný pokoj neexistuje!', 'error');
            $this->redirect('Room:');
        }
        if ($_SESSION['filter']['use']) {
            $this->isRoomAvaible = $this->reservationManager->isRoomAvaible($id, $this->filter_from, $this->filter_to);
        }
        $this->template->services = $this->roomManager->getRoomServices($id);
        $this->template->isAvaible = $this->isRoomAvaible;
        $this->template->from = $this->filter_from;
        $this->template->to = $this->filter_to;
        /* Pokud nemá galerii, musíš vložit prázdné pole */
        if (!is_null($this->template->room->photogallery_id)) {
            $this->template->gallery = $this->imageManager->getGallery($this->template->room->photogallery_id);
        } else $this->template->gallery = array();

        Debugger::barDump($this->template->isAvaible, 'Avaible');
    }


// Rezervace

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
     * Rezervace pokoje
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