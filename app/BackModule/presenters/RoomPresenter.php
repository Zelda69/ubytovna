<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 29.06.2017
 */

namespace App\BackModule\Presenters;

use App\Model\RoomManager;
use App\Model\ServiceManager;
use App\Model\ImageManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
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

    public function handleDeleteType($id) {
        $arrayUsed = $this->roomManager->getUsedRoomTypes(TRUE);
        if (!in_array($id, $arrayUsed)) {
            $this->roomManager->deleteRoomType($id);
            $this->flashMessage('Typ byl úspěšně smazán.');
        } else {
            $this->flashMessage('Nemůžeme smazat typ, který se používá!', 'error');
        }
        $this->redirect('this');
    }

    public function renderDefault() {
        $this->template->rooms = $this->roomManager->getRooms();
    }

    public function renderType() {
        $this->template->types = $this->roomManager->getRoomTypes();
        $this->template->usedTypes = $this->roomManager->getUsedRoomTypes(TRUE);
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
        $form->addText('price', 'Cena za noc (bez DPH)')
            ->setRequired('Musíš vyplnit cenu!')
            ->setHtmlType('number')
            ->addRule(Form::INTEGER, 'Cena musí být číslo!');
        $form->addCheckboxList('services', 'Služby:', $this->serviceManager->getServiceToList())
            ->getSeparatorPrototype()
            ->setName(NULL);

        if (!is_null($this->selectedRoom)) {
            $room = $this->roomManager->getRooms($this->selectedRoom);
            $values = array('description' => $room->description, 'name' => $room->name, 'type_id' => $room->type_id, 'extra_beds' => $room->extra_beds, 'price' => $room->price, 'services' => $this->roomManager->getRoomServices($this->selectedRoom, TRUE));
            $form->setDefaults($values);
            $form->addSubmit('edit', 'Upravit informace');
        } else $form->addSubmit('add', 'Přidat pokoj');

        $form->onSuccess[] = [$this, 'RoomFormSucceeded'];
        return $form;
    }

    public function roomFormSucceeded($form, $values) {
        if (!is_null($this->selectedRoom)) {
            $this->roomManager->editRoomServices($this->selectedRoom, $values->services);
            unset($values->services);
            $this->roomManager->editRoom($this->selectedRoom, $values);
            $this->flashMessage('Pokoj byl úspěšně editován.');
        } else {
            $services = $values->services;
            unset($values->services);
            try {
                $id = $this->roomManager->addRoom($values);
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Pokoj se zadaným názvem již existuje!', 'error');
                $this->redirect('this');
            }
            $this->roomManager->addRoomServices($id, $services);
            $this->flashMessage('Pokoj byl úspěšně přidán.');
        }

        $this->redirect('this');
    }

    protected function createComponentRoomGalleryForm() {
        $form = new Form();
        $form->addHidden('id', $this->selectedRoom);
        $form->addHidden('gallery_id', $this->roomManager->getRooms($this->selectedRoom)->photogallery_id);
        $form->addUpload('image', 'Obrázek')->addRule(Form::IMAGE)->setRequired('Zvol obrázek!');
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

    protected function createComponentNewTypeForm() {
        $form = new Form();
        $form->addText('name', 'Název typu:')->setRequired('Musíte zvolit název!');
        $form->addText('single_bed', 'Počet jednolůžek:')
            ->setHtmlType('number')
            ->addRule(Form::INTEGER, 'Počet postelí musí být číslo!')
            ->setHtmlAttribute('min', 0)
            ->setHtmlAttribute('max', 10)
            ->setRequired('Musíte vyplnit počet postelí')
            ->setDefaultValue(0);
        $form->addText('double_bed', 'Počet dvoulůžek:')
            ->setHtmlType('number')
            ->addRule(Form::INTEGER, 'Počet postelí musí být číslo!')
            ->setHtmlAttribute('min', 0)
            ->setHtmlAttribute('max', 10)
            ->setRequired('Musíte vyplnit počet postelí')
            ->setDefaultValue(0);
        $form->addSubmit('new_type', 'Přidat typ');
        $form->onSuccess[] = [$this, 'newTypeFormSucceeded'];

        return $form;
    }

    public function newTypeFormSucceeded($form, $values) {
        if ($values->single_bed + $values->double_bed == 0) {
            $this->flashMessage('Musíte vyplnit počet lůžek!', 'error');
        } else {
            try {
                if ($this->roomManager->newRoomType($values)) {
                    $this->flashMessage('Nový typ byl úspěšně přidán');
                } else $this->flashMessage('Nový typ se nepodařilo přidat.');
                $this->redirect('this');
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Typ zadaného názvu již existuje!', 'error');
            }
        }
    }
}
