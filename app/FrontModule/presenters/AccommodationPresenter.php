<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 23.06.2017
 */

namespace App\FrontModule\Presenters;


use App\FrontModule\Model\AccommodationManager;
use App\Model\ImageManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Nette\InvalidStateException;
use Nette\Mail\SendmailMailer;
use Nette\Utils\ArrayHash;
use Nette\Mail\Message;

class AccommodationPresenter extends BasePresenter {

    /** @var AccommodationManager  */
    private $accommodationManager;
    /** @var ImageManager  */
    private $imageManager;
    /** Email administrátora, na který se budou posílat emaily z kontaktního formuláře. */
    const EMAIL = 'zbynek.mlcak@seznam.cz';

    /**
     * AccommodationPresenter constructor.
     * @param AccommodationManager $accommodationManager
     * @param ImageManager $imageManager
     */
    public function __construct(AccommodationManager $accommodationManager, ImageManager $imageManager){
        parent::__construct();
        $this->accommodationManager = $accommodationManager;
        $this->imageManager = $imageManager;
    }

    public function renderDefault() {
        $this->template->gallery = $this->imageManager->getGallery(1);
        $this->template->info = $this->accommodationManager->getAllInformation();
    }

    public function createComponentTestForm() {
        $form = new Form();
        $form->elementPrototype->addAttributes(array('novalidate' => 'novalidate'));
        $form->addEmail('email', 'émajl')->setRequired('Musíš zadat platný email!');
        $form->addText('text', 'textík')->setRequired();
        $form->addReCaptcha('recaptcha', $label = 'Captcha')->setRequired('Are you bot?');
        $form->addSubmit('sub', 'submájnt');
        return $form;
    }



    /**
     * Vytváří a vrací komponentu kontaktního formuláře.
     * @return Form kontaktní formulář
     */
    protected function createComponentContactForm() {
        $form = new Form;
        $form->addProtection('Vypršel časový limit, odešlete formulář znovu');
        $form->addText('email', 'Vaše emailová adresa')->setType('email')->setRequired();
        $form->addTextArea('message', 'Zpráva')->setRequired()->addRule(Form::MIN_LENGTH, 'Zpráva musí být minimálně %d znaků dlouhá.', 10);
        $form->addReCaptcha('recaptcha', 'Antispamová ochrana')->setRequired('Musíš potvrdit antispamovou kontrolu!');
        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = [$this, 'contactFormSucceeded'];
        return $form;
    }

    /**
     * Funkce se vykonaná při úspěsném odeslání kontaktního formuláře a odešle email.
     * @param Form $form kontaktní formulář
     * @param ArrayHash $values odeslané hodnoty formuláře
     */
    public function contactFormSucceeded($form, $values) {
        try {
            $mail = new Message;
            $mail->setFrom($values->email)->addTo(self::EMAIL)->setSubject('Email z webu')->setBody($values->message);
            $mailer = new SendmailMailer;
            $mailer->send($mail);
            $this->flashMessage('Email byl úspěšně odeslán.');
            $this->redirect('this');
        } catch (InvalidStateException $ex) {
            $this->flashMessage('Email se nepodařilo odeslat.', 'error');
        }
    }

    public function renderContact() {
        $this->template->info = $this->accommodationManager->getAllInformation();
    }
}