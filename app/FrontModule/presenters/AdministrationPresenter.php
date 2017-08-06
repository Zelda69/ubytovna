<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 13.06.2017
 */

namespace App\FrontModule\Presenters;

use App\Forms\UserForms;
use App\Model\GuestManager;
use App\Model\UserManager;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use Nette\Utils\ArrayHash;


use Tracy\Debugger;

/**
 * Class AdministrationPresenter
 * @package app\FrontModule\presenters
 */
class AdministrationPresenter extends BaseFrontPresenter {


    /** @var UserForms Továrnička na uživatelské formuláře. */
    private $userFormsFactory;
    private $userManager;
    private $guestManager;

    /** @var array Společné instrukce pro přihlašovací a registrační formuláře. */
    private $instructions;

    /**
     * Konstruktor s injektovanou továrničkou na uživatelské formuláře.
     * @param UserForms $userForms automaticky injektovaná třída továrničky na uživatelské formuláře
     * @param UserManager $userManager
     * @param GuestManager $guestManager
     */
    public function __construct(UserForms $userForms, UserManager $userManager, GuestManager $guestManager) {
        parent::__construct();
        $this->userManager = $userManager;
        $this->userFormsFactory = $userForms;
        $this->guestManager = $guestManager;
    }

    /** Volá se před každou akcí presenteru a inicializuje společné proměnné. */
    public function startup() {
        parent::startup();
        $this->instructions = array('message' => NULL, 'redirection' => ':Front:Homepage:');
    }

    /** Přesměrování do administrace, pokud je uživatel již přihlášen. */
    public function actionLogin() {
        if ($this->getUser()->isLoggedIn() && $this->getUser()->isInRole('admin'))
            $this->redirect(':Back:Homepage:');
    }

    /** Odhlášení uživatele. */
    public function actionLogout() {
        $this->getUser()->logout();
        $this->redirect($this->loginPresenter);
    }

    /** Vykreslí administrační stránku. */
    public function renderDefault() {
        $identity = $this->getUser()->getIdentity();
        if ($identity)
            $this->template->username = $identity->getData()['username'];
    }

    public function renderProfil() {
        if(is_null($this->getUser()->getIdentity()->guests_id)) {
            $this->redirect(':Back:Homepage:');
        }
        $this->template->user_data = $this->guestManager->get($this->getUser()->getIdentity()->guests_id);
    }

    /**
     * Formulář pro změnu hesla k profilu
     * @return Form
     */
    protected function createComponentEditPasswordForm() {
        $form = new Form();
        $form->addPassword('oldPassword', 'Aktuální heslo:')->setRequired('Musíte vyplnit staré heslo!');
        $form->addPassword('password', 'Nové heslo:')
            ->setRequired('Zvolte si nové heslo')
            ->addRule(Form::MIN_LENGTH, 'Heslo musí obsahovat alespoň %d znaků', 7)
            ->addRule(Form::PATTERN, 'Heslo musí obsahovat číslici', '.*[0-9].*');
        $form->addPassword('passwordVerify', 'Heslo pro kontrolu:')
            ->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu')
            ->addRule(Form::EQUAL, 'Hesla se neshodují!', $form['password']);
        $form->addSubmit('changePassword', 'Změnit heslo');
        $form->onSuccess[] = [$this, 'editPasswordFormSucceeded'];

        return $form;
    }

    public function editPasswordFormSucceeded($form, $values) {
        if (Passwords::verify($values->oldPassword, $this->userManager->getPassword($this->getUser()->getId()))) {
            $this->userManager->changePassword($this->getUser()->getId(), $values->password);
            $this->flashMessage('Heslo bylo úspěšně změněno. Z bezpečnostních důvodů jste byli odhlášeni.');
            $this->redirect('logout');
        } else {
            $this->flashMessage('Zadané neplatné aktuální heslo!', 'error');
            $this->redirect('this');
        }
    }

    /**
     * Formulář pro editaci informací o profilu
     * @return Form
     */
    protected function createComponentEditProfilForm() {
        $user_data = $this->guestManager->get($this->getUser()->getIdentity()->guests_id);
        Debugger::barDump($user_data, 'data');
        $form = new Form();
        //$form->addEmail('email', 'E-mail')->setRequired('Musíte vyplnit email!')->setDefaultValue($user_data->email);
        $form->addText('name', 'Jméno a příjmení')
            ->setRequired('Musíte vyplnit jméno!')
            ->setDefaultValue($user_data->name);
        $form->addText('birthday', 'Datum narození')->setHtmlType('date')->setDefaultValue($user_data->birthday);
        $form->addText('birthplace', 'Místo narození')->setDefaultValue($user_data->birthplace);
        $form->addText('phone', 'Telefonní číslo')
            ->setRequired('Musíte vyplnit telefon!')
            ->setDefaultValue($user_data->phone);
        $form->addText('street', 'Ulice, č.p.')->setDefaultValue($user_data->street);
        $form->addText('city', 'Město')->setDefaultValue($user_data->city);
        $form->addText('state', 'Stát')->setDefaultValue($user_data->state);

        $form->addSubmit('changeProfil', 'Změnit údaje');
        $form->onSuccess[] = [$this, 'editProfilFormSucceeded'];

        return $form;
    }

    public function editProfilFormSucceeded($form, $values) {
        $this->guestManager->update($this->getUser()->getIdentity()->guests_id, $values);
        $this->flashMessage('Změny byly úspěšně uloženy');
        $this->redirect('this');
    }

    /**
     * Vrací komponentu přihlašovacího formuláře z továrničky.
     *
     * @return Form přihlašovací formulář
     */
    protected function createComponentLoginForm() {
        $this->instructions['message'] = 'Byl jste úspěšně přihlášen.';
        return $this->userFormsFactory->createLoginForm(ArrayHash::from($this->instructions));
    }

    /**
     * Vrací komponentu registračního formuláře z továrničky.
     * @return Form registrační formulář
     */
    protected function createComponentRegisterForm() {
        $this->instructions['message'] = 'Byl jste úspěšně zaregistrován.';
        return $this->userFormsFactory->createRegisterForm(ArrayHash::from($this->instructions));
    }

}