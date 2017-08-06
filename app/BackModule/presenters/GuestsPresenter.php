<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 01.08.2017
 */

namespace App\BackModule\Presenters;


use App\Model\GuestManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\Table\Selection;
use Nette\Database\UniqueConstraintViolationException;
use Tracy\Debugger;

class GuestsPresenter extends BasePresenter {
    /** @var GuestManager */
    private $guestManager;
    /** @var null|Selection */
    private $selectedGuest = NULL;
    /** @var null|string */
    private $searchGuest = NULL;


    public function __construct(GuestManager $guestManager) {
        parent::__construct();
        $this->guestManager = $guestManager;
    }

    public function renderDefault() {
        $this->selectedGuest = NULL;
        Debugger::barDump($this->searchGuest, 'Searched');
        if (is_null($this->searchGuest)) {
            $this->template->guests = $this->guestManager->get_all();
        } else {
            $this->template->guests = $this->guestManager->search($this->searchGuest);
        }
        $this->template->searchGuest = $this->searchGuest;
    }

    public function renderDetail($id) {
        $this->template->guest = $this->guestManager->get($id);
        if (!$this->template->guest) {
            $this->flashMessage('Zadaný host neexistuje!', 'error');
            $this->redirect('default');
        }
        $this->selectedGuest = $id;
    }

    protected function createComponentGuestForm() {
        $form = new Form();
        $form->addEmail('email', 'Email')->setRequired('Musíte vyplnit email!');
        $form->addText('name', 'Jméno a příjmení')->setRequired('Musíte vyplnit jméno!');
        $form->addText('birthday', 'Datum narození')->setHtmlType('date');
        $form->addText('birthplace', 'Místo narození');
        $form->addText('phone', 'Telefonní číslo');
        $form->addText('street', 'Ulice, č.p.');
        $form->addText('city', 'Město');
        $form->addText('state', 'Stát');
        if (!is_null($this->selectedGuest)) {
            $guest = $this->guestManager->get($this->selectedGuest);
            $data = array('email' => $guest->email, 'name' => $guest->name, 'birthday' => $guest->birthday, 'birthplace' => $guest->birthplace, 'phone' => $guest->phone, 'street' => $guest['street'], 'city' => $guest->city, 'state' => $guest->state);
            $form->setDefaults($data);
            $form->addSubmit('changeGuest', 'Upravit hosta');
        } else {
            $form->addSubmit('newGuest', 'Založit hosta');
        }
        $form->onSuccess[] = [$this, 'guestFormSucceeded'];

        return $form;
    }

    public function guestFormSucceeded($form, $values) {
        if (!is_null($this->selectedGuest)) {
            $this->guestManager->update($this->selectedGuest, $values);
            $this->flashMessage('Údaje byly úspěšně změněny.');
        } else {
            try {
                $this->guestManager->add($values);
                $this->flashMessage('Host byl úspěšně přidán.');
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Host se zadaným emailem již existuje!', 'error');
            }
        }
        $this->redirect('this');
    }

    protected function createComponentSearchGuestForm() {
        $form = new Form();
        $form->addText('search', 'Vyhledat (dle jména či emailu)')->setDefaultValue($this->searchGuest);
        $form->addSubmit('find', 'Hledat');
        $form->onSuccess[] = [$this, 'searchGuestFormSucceeded'];
        return $form;
    }

    public function searchGuestFormSucceeded($form, $values) {
        if ($values->search == '') {
            $this->searchGuest = NULL;
        } else {
            $this->searchGuest = $values->search;
        }
        $this->template->searchGuest = $this->searchGuest;
        $this->redrawControl('list');
    }
}