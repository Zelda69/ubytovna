<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 29.06.2017
 */

namespace App\BackModule\Presenters;

use App\FrontModule\Model\RoomManager;
use App\FrontModule\Model\ServiceManager;
use App\Model\ImageManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Tracy\Debugger;

class RoomPresenter extends BasePresenter {
    /** @var RoomManager */
    private $roomManager;
    /** @var null|int */
    private $selectedRoom = NULL;
    /** @var ImageManager */
    private $myimageManager;
    /** @var ServiceManager */
    private $serviceManager;

    /** @var \Carrooi\ImagesManager\ImagesManager @inject */
    public $imagesManager;

    public function __construct(RoomManager $roomManager, ImageManager $myimageManager, ServiceManager $serviceManager) {
        parent::__construct();
        $this->roomManager = $roomManager;
        $this->myimageManager = $myimageManager;
        $this->serviceManager = $serviceManager;
    }

    public function actionDetail($id) {
        $this->selectedRoom = $id;
    }

    public function renderDefault() {
        $this->template->rooms = $this->roomManager->getRooms();
    }

    public function renderDetail($id) {
        $room_test = $this->roomManager->getRooms($id);
        if (!isset($room_test['name'])) {
            $this->flashMessage('Zadaný pokoj neexistuje!', 'error');
            $this->redirect('Room:');
        }

        // Pokud obrázek nemá galerii, vytvoř ji !
        if (is_null($room_test['photogallery_id'])) {
            $result = array();
            $result['photogallery_id'] = $this->myimageManager->makeGallery($room_test['name']);
            $this->roomManager->editRoom($id, $result); // ulož do databáze
            $room_test = $this->roomManager->getRooms($id); // znovu načti info
            $this->flashMessage('Byla vytvořena galerie pro daný pokoj!');
        }

        $this->template->services = $this->roomManager->getRoomServices($id);
        $this->template->gallery = $this->myimageManager->getGallery($room_test['photogallery_id']);
        // Ještě zjisti jak je to s obrázkem, pokud nějaký je v galerii a není nastaven, nastav!
        if (is_null($room_test['image_id']) && !is_null($room_test['photogallery_id']) && isset($this->template->gallery[$room_test['photogallery_id']]['img'][0]['id'])) {
            $result = array('image_id' => $this->template->gallery[$room_test['photogallery_id']]['img'][0]['id']);
            $this->roomManager->editRoom($id, $result);
            $this->flashMessage('Hlavní obrázek byl nastaven.');
        }
        $this->template->room = $this->roomManager->getRooms($id);
    }

    protected function createComponentRoomForm() {

        $form = new Form();
        $form->addText('name', 'Název pokoje')->setRequired('Musíš vyplnit název pokoje!');
        $form->addTextArea('description', 'Popis pokoje')->setRequired('Musíš vyplnit popis pokoje!');
        $form->addSelect('type_id', 'Typ pokoje', $this->roomManager->getRoomTypes()->fetchPairs('id', 'name'))
            ->setRequired('Musíš zvolit');
        $form->addText('extra_beds', 'Počet přistýlek')
            ->setRequired('Musíš vyplnit počet přistýlke')
            ->addRule(Form::INTEGER, 'Počet přistýlek musí být číslo!');
        $form->addText('price', 'Cena za noc')
            ->setRequired('Musíš vyplnit cenu!')
            ->setHtmlType('number')
            ->addRule(Form::INTEGER, 'Cena musí být číslo!');

        if (!is_null($this->selectedRoom)) {
            $room = $this->roomManager->getRooms($this->selectedRoom);
            $values = array('description' => $room->description, 'name' => $room->name, 'type_id' => $room->type_id, 'extra_beds' => $room->extra_beds, 'price' => $room->price);
            $form->setDefaults($values);
            $form->addSubmit('edit', 'Upravit informace');
        } else $form->addSubmit('add', 'Přidat pokoj');

        $form->onSuccess[] = [$this, 'RoomFormSucceeded'];
        return $form;
    }

    public function roomFormSucceeded($form, $values) {
        if (!is_null($this->selectedRoom)) {
            $this->roomManager->editRoom($this->selectedRoom, $values);
            $this->flashMessage('Pokoj byl úspěšně editován.');
        } else {
            $this->roomManager->addRoom($values);
            $this->flashMessage('Pokoj byl úspěšně přidán.');
        }

        $this->redirect('this');
    }

    protected function createComponentRoomGalleryForm() {
        $form = new Form();
        $form->addHidden('id', $this->selectedRoom);
        $form->addHidden('gallery_id', $this->roomManager->getRooms($this->selectedRoom)->photogallery_id);
        $form->addUpload('image', 'Image')->addRule(Form::IMAGE)->setRequired('Zvol obrázek!');
        $form->addSubmit('save', 'Upload');
        $form->onSuccess[] = [$this, 'roomGalleryFormSucceeded'];

        return $form;
    }

    /***
     * Zpracuje formulář a nahraje obrázek, přidá do databáze a do galerie
     * @param $form
     * @param $values
     */
    public function roomGalleryFormSucceeded($form, $values) {
        if ($values->image->isOk()) {
            $image = $values->image->toImage();
            Debugger::barDump($values->image, 'imidž');
            Debugger::barDump($values, 'imidž');
            $namespace = 'room/'.$values->id;
            $name = 'room_'.time().'_'.$values->image->name;
            $this->myimageManager->makePath(ImageManager::IMG_DEFAULT_DIR.$namespace);
            $this->imagesManager->upload($image, $namespace, $name); // nahraje obrázek
            $idcko = $this->myimageManager->saveImage($namespace.'/'.$name, ''); // uloží jej do DB
            $this->myimageManager->addToGallery($idcko, $values->gallery_id); // uloží jej do galerie
            $this->flashMessage('Obrázek byl úspěšně nahrán');
            $this->redirect('this');

        } else {
            $this->flashMessage('Při nahrávání obrázku došlo k chybě.', 'error');
            $this->redirect('this');
        }
    }
}
