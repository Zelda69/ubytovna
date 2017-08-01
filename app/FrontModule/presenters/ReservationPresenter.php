<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 28.06.2017
 */

namespace App\FrontModule\Presenters;


use App\Forms\UserForms;
use App\FrontModule\Model\ReservationManager;
use App\Model\GuestManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Tracy\Debugger;

class ReservationPresenter extends BasePresenter {
    /** @persistent @var array */
    public $reservation;
    /** @var UserForms */
    private $userFormsFactory;
    /** @var ReservationManager */
    private $reservationManager;
    /** @var GuestManager */
    private $guestManager;
    /** @var array */
    private $instructions;

    /**
     * ReservationPresenter constructor.
     * @param UserForms $userFormsFactory
     * @param ReservationManager $reservationManager
     * @param GuestManager $guestManager
     */
    public function __construct(UserForms $userFormsFactory, ReservationManager $reservationManager, GuestManager $guestManager) {
        parent::__construct();
        $this->userFormsFactory = $userFormsFactory;
        $this->reservationManager = $reservationManager;
        $this->guestManager = $guestManager;
        $this->instructions = array('message' => NULL, 'redirection' => NULL);
    }

    public function handleZrusit($room_id) {
        $id = $this->reservationManager->get_reservation()->id;
        $this->reservationManager->delete_room_in_reservation($id, $room_id);

        if ($this->reservationManager->count_rooms_in_reservation($id) > 0) {
            $this->flashMessage('Rezervace pokoje byla úspěšně zrušena.');
            $this->redirect('Reservation:');
        } else {
            $this->reservationManager->delete_reservation($id);
            $this->deleteReservationSessions();
            $this->flashMessage('Zrušili jste rezervaci všech pokojů. Celá rezervace byla zrušena.');
            $this->redirect('Room:');
        }
    }

    public function handleNextStep() {
        if (isset($_SESSION['reservation_id'])) {
            $step = $this->reservationManager->get_reservation()->step;
            if ($step < 2) {
                $step++;
                $this->reservationManager->update_reservation($_SESSION['reservation_id'], ['step' => $step]);
            }
        }
    }

    public function handlePreviousStep() {
        if (isset($_SESSION['reservation_id'])) {
            $step = $this->reservationManager->get_reservation()->step;
            if ($step > 0) {
                $step--;
                $this->reservationManager->update_reservation($_SESSION['reservation_id'], ['step' => $step]);
            }
        }
    }


    /**
     * Data pro default.latte
     */
    public function renderDefault() {
        // Data o uživateli, zda jsou v rezervaci nebo pokud je uživatel přihlášen
        if (!isset($_SESSION['reservation_guest']))
            $_SESSION['reservation_guest'] = 0;
        if ($_SESSION['reservation_guest'] == 0 && $this->getUser()->isLoggedIn() && !is_null($this->getUser()
                ->getIdentity()->guests_id)) {
            $_SESSION['reservation_guest'] = $this->getUser()->getIdentity()->guests_id;
        }
        $this->template->guest = $this->guestManager->get($_SESSION['reservation_guest']);

        // Je už zadaná nějaká rezervace? Pokračuj v ní pokud nevypršela.
        $this->template->reservation = $this->reservationManager->get_reservation();
        if ($this->template->reservation) {
            $this->template->rooms = $this->reservationManager->get_room_in_reservation($this->template->reservation->id);
            $this->template->nights = intval(date_diff(date_create($this->template->reservation->date_from), date_create($this->template->reservation->date_to))->format("%d"));
            $this->template->nights_word = $this->word_of_number_nights($this->template->nights);
            $this->template->step = $this->template->reservation->step;
            $_SESSION['reservation_id'] = $this->template->reservation->id; // pro jistotu
        } else {
            $this->template->rooms = array();
            $this->template->step = 0;
        }

        Debugger::barDump($_SESSION['reservation_id'], 'Reservation ID');
        Debugger::barDump($this->template->rooms, 'Reservation');
        Debugger::barDump($this->template->reservation, 'Reservation');
    }


    /**
     * Data pro my.latte
     */
    public function renderMy() {
        $this->template->reservations = $this->reservationManager->getReservationFromUser($this->user->getIdentity()->guests_id);
        Debugger::barDump($this->template->reservations, 'Reservation');
    }


    /**
     * Vytváří formulář, který slouží pro zrušení celé rezervace
     * @return Form
     */
    protected function createComponentCancelAllForm() {
        $form = new Form();
        $form->addSubmit('cancel', 'Zrušit celou rezervaci');
        $form->onSuccess[] = [$this, 'cancelAllFormSucceeded'];

        return $form;
    }

    public function cancelAllFormSucceeded($form, $values) {
        $id = $this->reservationManager->get_reservation();
        $this->reservationManager->delete_reservation($id);
        $this->deleteReservationSessions();
        $this->flashMessage('Rezervace byla úspěšně zrušena.');
        $this->redirect('Room:default');
    }

    /**
     * Vytvoření formuláře na zadání osobních údajů (krok 2)
     * @return Form
     */
    protected function createComponentAboutUserForm() {
        $form = new Form();
        $form->addEmail('email', 'E-mail')->setRequired('Musíte vyplnit email!');
        $form->addText('name', 'Jméno a příjmení')->setRequired('Musíte vyplnit jméno!');
        $form->addText('birthday', 'Datum narození')->setHtmlType('date');
        $form->addText('birthplace', 'Místo narození');
        $form->addText('phone', 'Telefonní číslo')->setRequired('Musíte vyplnit telefon!');
        $form->addText('street', 'Ulice, č.p.');
        $form->addText('city', 'Město');
        $form->addText('state', 'Stát');
        $form->addSubmit('store', 'Pokračovat v rezervaci (krok 2 / 3)');
        $form->onSuccess[] = [$this, 'aboutUserFormSucceeded'];

        if (isset($_SESSION['reservation_guest']) && $_SESSION['reservation_guest'] > 0) {
            $form->setDefaults($this->guestManager->get($_SESSION['reservation_guest']));
        }

        return $form;
    }

    public function aboutUserFormSucceeded($form, $values) {
        if (isset($_SESSION['reservation_guest']) && $_SESSION['reservation_guest'] > 0) {
            $this->guestManager->update($_SESSION['reservation_guest'], $values);
        } else {
            $_SESSION['reservation_guest'] = $this->guestManager->add($values);
        }
        $this->reservationManager->update_reservation($_SESSION['reservation_id'], ['guests_id' => $_SESSION['reservation_guest']]);
        Debugger::barDump($_SESSION['reservation_guest'], 'GUEST_ID');
        $this->redirect('NextStep!');
    }


    /**
     * Vytvoření potvrzovacího formuláře rezervace (krok 3)
     * @return Form
     */
    protected function createComponentReservationConfirmForm() {
        $form = new Form();
        $form->addTextArea('note', 'Vaše poznámka')
            ->setHtmlAttribute('placeholder', 'Vaše přání, speciální požadavky či jiné upřesnění objednávky.');
        $form->addCheckbox('agree', 'Souhlasím s podmínkami')
            ->setRequired('Musíte souhlasit s podmínkami')
            ->addRule(Form::FILLED, 'Musíte souhlasit s podmínkami');
        $form->addSubmit('confirm', 'Závazně rezervovat');
        $form->onSuccess[] = [$this, 'reservationConfirmFormSucceeded'];
        return $form;
    }

    public function reservationConfirmFormSucceeded($form, $values) {
        try {
            $data = array('step' => 3, 'note' => $values->note);
            $id = $this->reservationManager->update_reservation($_SESSION['reservation_id'], $data);
            $this->flashMessage('Rezervace byla úspěšně dokončena!');
            $this->deleteReservationSessions();
            $this->redirect('my');
        } catch (UniqueConstraintViolationException $ex) {
            Debugger::barDump($ex, 'chyba');
            $this->flashMessage('Bohužel na zadaný termín je pokoj již obsazen. Můžete si vybrat jiný.', 'error');
            $this->redirect('Room:');
        }
    }

    // Pomocné funkce

    private function word_of_number_nights($nights) {
        switch ($nights) {
            case 1:
                return "noc";
            case 2:
            case 3:
            case 4:
                return "noci";
            default:
                return "nocí";
        }
    }

    private function deleteReservationSessions() {
        unset($_SESSION['reservation']);
        unset($_SESSION['reservation_guest']);
        unset($_SESSION['reservation_id']);
    }


}