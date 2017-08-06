<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 29.06.2017
 */

namespace App\BackModule\Presenters;


use App\Model\AccommodationManager;
use App\Model\ImageManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class HomepagePresenter extends BasePresenter {
    /** @var AccommodationManager */
    private $accommodationManager;
    /** @var ImageManager */
    private $myimageManager;

    /** @var \Carrooi\ImagesManager\ImagesManager @inject */
    public $imagesManager;

    public function __construct(AccommodationManager $accommodationManager, ImageManager $imageManager) {
        parent::__construct();
        $this->accommodationManager = $accommodationManager;
        $this->myimageManager = $imageManager;
    }

    public function renderDefault() {
        $this->template->info = $this->accommodationManager->getName();
    }

    public function renderEdit() {
        $this->template->info = $this->accommodationManager->getAllInformation();
        $this->template->gallery = $this->myimageManager->getGallery(1);
    }

    public function renderHomeEdit() {
        $this->template->home = $this->accommodationManager->getHomepage();
    }

    protected function createComponentInfoEditForm() {
        $form = new Form();
        $form->addText('name', 'Název zařízení:')
            ->setRequired('Musíte vypnit název zářizení')
            ->setDefaultValue($this->accommodationManager->getName());
        $form->addEmail('email', 'E-mail:')
            ->setRequired('Musíte vyplnit email!')
            ->setDefaultValue($this->accommodationManager->getEmail());
        $form->addText('phone', 'Telefon')
            ->setRequired('Musíte vyplnit telefon!')
            ->setDefaultValue($this->accommodationManager->getPhone());
        $form->addTextArea('adress', 'Adresa:')
            ->setRequired('Musíte vyplnit adresu')
            ->setDefaultValue($this->br2nl(implode('<br>', $this->accommodationManager->getAdress())));
        $form->addTextArea('operator', 'Provozovatel')
            ->setRequired('Musíte vyplnit provozovatele')
            ->setDefaultValue($this->br2nl(implode('<br>', $this->accommodationManager->getOperator())));
        $form->addTextArea('content', 'O zařízení')->setDefaultValue($this->accommodationManager->getAbout());
        $form->addText('DPH', 'Sazba DPH:')
            ->setHtmlType('number')
            ->setRequired('Musíte zadat hodnotu DPH!')
            ->addRule(Form::INTEGER, 'DPH musí být číslo!')
            ->addRule(Form::RANGE, 'Sazba DPH musí být od %d do %d!', [0, 100])
            ->setDefaultValue($this->accommodationManager->getDPH());
        $form->addSubmit('save', 'Uložit informace');
        $form->onSuccess[] = [$this, 'infoEditFormSucceeded'];

        return $form;
    }

    /**
     * Return string replaced from <br> to \r\n
     * @param $text
     * @return mixed
     */
    private function br2nl($text) {
        $breaks = array("<br />", "<br>", "<br/>");
        $text = str_ireplace($breaks, "\r\n", $text);
        return $text;
    }

    /**
     * Return array of text
     * @param $text
     * @return array
     */
    private function arrayNl2br($text) {
        $text = preg_replace("/\r\n|\r|\n/", '<br />', $text);
        $array = explode("<br />", $text);
        return $array;
    }

    public function infoEditFormSucceeded($form, $values) {
        $values->adress = $this->arrayNl2br($values->adress);
        $values->operator = $this->arrayNl2br($values->operator);
        $values->about = $values->content;
        unset($values->content);
        $this->accommodationManager->updateInformation($values);
        $this->flashMessage('Informace byly úspěšně uloženy');
        $this->redirect('this');
    }

    protected function createComponentEditorForm() {
        $form = new Form();
        $form->addTextArea('content')->setDefaultValue($this->accommodationManager->getHomepage());
        $form->addSubmit('submit', 'Upravit stránku');
        $form->onSuccess[] = [$this, 'editorFormSucceeded'];
        return $form;
    }

    /**
     * Funkce se vykonaná při úspěsném odeslání formuláře; zpracuje hodnoty formuláře.
     * @param Form $form        formulář editoru
     * @param ArrayHash $values odeslané hodnoty formuláře
     */
    public function editorFormSucceeded($form, $values) {
        $this->accommodationManager->updateInformation(array('homepage' => $values->content));
        $this->flashMessage('Domovská stránka byla úspěšně změněna.');
        $this->redirect('this');
    }


    protected function createComponentGalleryForm() {
        $form = new Form();
        $form->addUpload('image', 'Image')->addRule(Form::IMAGE)->setRequired('Zvol obrázek!');
        $form->addSubmit('save', 'Upload');
        $form->onSuccess[] = [$this, 'galleryFormSucceeded'];

        return $form;
    }

    public function galleryFormSucceeded($form, $values) {
        if ($values->image->isOk()) {
            $image = $values->image->toImage();
            $namespace = 'accommodation';
            $name = 'acc'.time().'_'.$values->image->name;
            $this->imagesManager->upload($image, $namespace, $name); // nahraje obrázek
            $idcko = $this->myimageManager->saveImage($namespace.'/'.$name, ''); // uloží jej do DB
            $this->myimageManager->addToGallery($idcko, 1); // uloží jej do galerie
            $this->flashMessage('Obrázek byl úspěšně nahrán');
            $this->redirect('this');

        } else {
            $this->flashMessage('Při nahrávání obrázku došlo k chybě.', 'error');
            $this->redirect('this');
        }
    }

}