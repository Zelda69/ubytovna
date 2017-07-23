<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 23.06.2017
 */

namespace App\FrontModule\Presenters;


use App\Forms\Rules;
use App\FrontModule\Model\RoomManager;
use App\FrontModule\Model\ServiceManager;
use App\Model\ImageManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Tracy\Debugger;

class RoomPresenter extends BasePresenter {

    /** @var RoomManager */
    private $roomManager;
    /** @var ImageManager */
    private $imageManager;
    /** @var  ServiceManager */
    private $serviceManager;
    /** @var false|string @persistent */
    public $filter_from;
    /** @var false|string @persistent */
    public $filter_to;
    /** @var null */
    private $avaibleRooms = NULL;
    /** @var true|false */
    private $isRoomAvaible = NULL;
    /** @var int  @persistent */
    public $filter_persons;
    /** @var string @persistent */
    public $filter_services;
    /** @var  boolean @persistent */
    public $using_filter;

    /**
     * RoomPresenter constructor.
     * @param RoomManager $roomManager
     * @param ImageManager $imageManager
     * @param ServiceManager $serviceManager
     */
    public function __construct(RoomManager $roomManager, ImageManager $imageManager, ServiceManager $serviceManager) {
        parent::__construct();
        $this->roomManager = $roomManager;
        $this->imageManager = $imageManager;
        $this->serviceManager = $serviceManager;
        $this->filter_from = date('Y-m-d');
        $this->filter_to = date('Y-m-d', time() + 86400);
        $this->filter_services = implode(";", $this->serviceManager->getServiceToList(true));
        $this->filter_persons = 1;
        $this->using_filter = false;
    }

    public function handleNoFilter() {
        $this->filter_from = date('Y-m-d');
        $this->filter_to = date('Y-m-d', time() + 86400);
        $this->filter_services = implode(";", $this->serviceManager->getServiceToList(true));
        $this->filter_persons = 1;
        $this->using_filter = false;
        $this->redirect('this');
    }

    public function actionFilter($from, $to) {
        $this->avaibleRooms = $this->roomManager->getAvaibleRooms($from, $to);
        $this->filter_from = $from;
        $this->filter_to = $to;
        $this->setView('default');
    }

    public function renderDefault() {
        if ($this->using_filter) {
            $this->template->rooms = $this->roomManager->getAvaibleRooms($this->filter_from, $this->filter_to, $this->filter_persons, explode(";", $this->filter_services));
        } else $this->template->rooms = $this->roomManager->getRooms();
        $this->template->filter = $this->using_filter;
        $this->template->services = $this->roomManager->getRoomsServices();
        $this->template->from = $this->filter_from;
        $this->template->to = $this->filter_to;
    }

    /**
     * @return Form
     */
    public function createComponentVacancyFilterForm() {
        $form = new Form();
        if (isset($_GET['using_filter']) && $_GET['using_filter'] == 1)
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
        $form->setDefaults(array('services' => explode(";", $this->filter_services)));
        $form->addSubmit('a', 'Filtruj pokoje');
        if (isset($_GET['using_filter']) && $_GET['using_filter'] == 1) {
            $form->addButton('zrus', 'Zrušit filtrování')->setHtmlId('filter-zrus');
        }
        $form->onSuccess[] = [$this, 'vacancyFilterFormSucceeded'];
        return $form;
    }

    public function vacancyFilterFormSucceeded($form, $values) {
        $this->filter_from = $values->from;
        $this->filter_to = $values->to;
        $this->filter_services = implode(";", $values->services);
        $this->filter_persons = $values->person;
        Debugger::barDump($this->filter_services, 'služby');
        $this->using_filter = true;
        $this->redirect('this');
        //$this->redrawControl('vacancyFilterForm');
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
        $this->isRoomAvaible = $this->roomManager->isRoomAvaible($values->id, $values->from, $values->to);
        $this->redrawControl('room-detail-reservation');
    }

    public function renderDetail($id) {
        $this->template->room = $this->roomManager->getRooms($id);
        if(!isset($this->template->room['name'])) {
            $this->flashMessage('Zadaný pokoj neexistuje!', 'error');
            $this->redirect('Room:');
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

    public function renderReservation($id, $from, $to) {
        $this->template->from = $from;
        $this->template->to = $to;
        $this->template->room = $this->roomManager->getRooms($id);
    }

}