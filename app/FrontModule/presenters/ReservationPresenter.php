<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 28.06.2017
 */

namespace App\FrontModule\Presenters;


use App\Forms\UserForms;
use App\Model\GuestManager;
use App\Model\ReservationManager;
use App\Model\ReviewManager;
use App\Model\RoomManager;
use App\Presenters\BasePresenter;
use Latte\Engine;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Database\UniqueConstraintViolationException;
use Nette\InvalidStateException;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
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
    /** @var ReviewManager */
    private $reviewManager;
    /** @var RoomManager */
    private $roomManager;
    /** @var array */
    private $instructions;

    /**
     * ReservationPresenter constructor.
     * @param UserForms $userFormsFactory
     * @param ReservationManager $reservationManager
     * @param GuestManager $guestManager
     * @param ReviewManager $reviewManager
     * @param RoomManager $roomManager
     */
    public function __construct(UserForms $userFormsFactory, ReservationManager $reservationManager, GuestManager $guestManager, ReviewManager $reviewManager, RoomManager $roomManager) {
        parent::__construct();
        $this->userFormsFactory = $userFormsFactory;
        $this->reservationManager = $reservationManager;
        $this->guestManager = $guestManager;
        $this->reviewManager = $reviewManager;
        $this->roomManager = $roomManager;
        $this->instructions = array('message' => NULL, 'redirection' => NULL);
    }

    public function handleStorno($reservation_id) {
        $r = $this->reservationManager->getReservationById($reservation_id);
        if ($r && $r->guests_id === $this->getUser()->getIdentity()->guests_id) {
            if ($this->reservationManager->delete_reservation($reservation_id) === 1) {
                $this->flashMessage('Rezervace byla úspěšně zrušena.');
                $this->redirect('this');
            } else {
                $this->flashMessage('Neplatná rezervace! Nebylo provedeno zrušení.', 'error');
                $this->redirect('this');
            }
        }

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
            if (strtotime($_SESSION['reservation']) < time())
                $this->redirect(':Front:Homepage:');
            $step = $this->reservationManager->get_reservation()->step;
            if ($step == 0)
                $this->reservationManager->store_room_info($_SESSION['reservation_id']);
            if ($step < 2) {
                $step++;
                $this->reservationManager->update_reservation($_SESSION['reservation_id'], ['step' => $step]);
            }
        }
    }

    public function handlePreviousStep() {
        if (isset($_SESSION['reservation_id'])) {
            if (strtotime($_SESSION['reservation']) < time())
                $this->redirect(':Front:Homepage:');
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
            $this->template->rooms = $this->reservationManager->get_rooms_in_reservation($this->template->reservation->id);
            $this->template->nights = intval(date_diff(date_create($this->template->reservation->date_from), date_create($this->template->reservation->date_to))->format("%d"));
            $this->template->nights_word = $this->reservationManager->word_of_number_nights($this->template->nights);
            $this->template->step = $this->template->reservation->step;
            $_SESSION['reservation_id'] = $this->template->reservation->id; // pro jistotu
        } else {
            $this->flashMessage('Momentálně nemáte rozpracovanou žádnou rezervaci.', 'error');
            $this->redirect('Homepage:');
        }

        Debugger::barDump($_SESSION['reservation_id'], 'Reservation ID');
        Debugger::barDump($this->template->rooms, 'Reservation');
        Debugger::barDump($this->template->reservation, 'Reservation');
        $this->template->dph = 1 + $this->serviceInformationManager->getDPH() / 100;
    }


    /**
     * Data pro my.latte
     */
    public function renderMy() {
        $this->template->reservations = $this->reservationManager->getAllReservations($this->user->getIdentity()->guests_id);
        $this->template->reviews = $this->reviewManager->getAllByGuest($this->user->getIdentity()->guests_id);
        Debugger::barDump($this->template->reservations, 'Reservation');
        $this->template->info = $this->serviceInformationManager->getAllInformation();
    }


    protected function createComponentMultipleReviewForm() {
        $control = new Multiplier(function ($reservation_id) {
            $form = new Form(NULL, $reservation_id);
            $form->addHidden('reservation_id')->setDefaultValue($reservation_id);
            $form->addSelect('stars', 'Hodnocení:', [1, 2, 3, 4, 5])->setRequired('Musíte zvolit počet hvězdiček');
            $form->addTextArea('text', '')
                ->setHtmlAttribute('rows', 3)
                ->setHtmlAttribute('placeholder', 'Vaše hodnocení');
            $form->addSubmit('new_review', 'Ohodnotit');
            $form->onSuccess[] = [$this, 'reviewFormSucceeded'];
            return $form;
        });

        return $control;
    }

    public function reviewFormSucceeded($form, $values) {
        $this->reviewManager->add($values);
        $this->flashMessage('Hodnocení bylo úspěšně uloženo. Děkujeme.');
        $this->redirect('this');
    }

    private function getCountOfPeople($max) {
        $array = array();
        for ($i = 1; $i <= $max; $i++) {
            $array[$i] = $i;
        }

        return $array;
    }

    protected function createComponentMultipleSelectForm() {
        $roomManager = $this->roomManager;
        $reservationManager = $this->reservationManager;
        $control = new Multiplier(function ($arg) use ($roomManager, $reservationManager) {
            $arg = explode("_", $arg);
            $reservation_id = $arg[0];
            $room_id = $arg[1];
            $form = new Form(NULL, $room_id);
            Debugger::barDump($room_id, 'room_id');
            Debugger::barDump($reservation_id, 'res_id');
            $form->addHidden('reservation_id')->setDefaultValue($reservation_id);
            $form->addHidden('room_id')->setDefaultValue($room_id);
            $form->addSelect('people', 'Osob', $this->getCountOfPeople($roomManager->getPeopleInRoom($room_id)->pocet))
                ->setRequired('Musíte zvolit počet osob!');
            $osob = $reservationManager->get_room_in_reservation($reservation_id, $room_id)->people;
            if ($osob == 0) {
                $form['people']->setDefaultValue($roomManager->getPeopleInRoom($room_id, FALSE)->pocet);
            } else {
                $form['people']->setDefaultValue($osob);
            }

            $form->addSubmit('submit', 'Ulož');
            $form->onSuccess[] = [$this, 'selectFormSucceeded'];
            return $form;
        });

        return $control;
    }

    public function selectFormSucceeded($form, $values) {
        $this->reservationManager->update_room_in_reservation($values->reservation_id, $values->room_id, ['people' => $values->people]);
        $this->redirect('this#table');
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
        $form->addText('phone', 'Telefonní číslo')
            ->setRequired('Musíte vyplnit telefon!')
            ->addRule($form::PATTERN, 'Neplatné telefonní číslo. Zadejte pouze číslice. Pro mezinárodní formát předvolbu s 00 místo +.', '[0-9]{9,14}');
        $form->addText('street', 'Ulice, č.p.');
        $form->addText('city', 'Město');
        $form->addText('state', 'Stát');
        $form->addSubmit('submit', 'Pokračovat v rezervaci (krok 2 / 3)');
        $form->onSuccess[] = [$this, 'aboutUserFormSucceeded'];

        if (isset($_SESSION['reservation_guest']) && $_SESSION['reservation_guest'] > 0) {
            $form->setDefaults($this->guestManager->get($_SESSION['reservation_guest']));
        }

        return $form;
    }

    public function aboutUserFormSucceeded($form, $values) {
        if (empty(trim($values->email)) || empty(trim($values->name)) || empty(trim($values->phone))) {
            $this->flashMessage('Musíte vyplnit všechny povinné položky!', 'error');
        } else if (!preg_match("/^0{2}[0-9]{12}$|^[0-9]{9}$/", $values->phone)) {
            $this->flashMessage('Bylo zadáno neplatné číslo!', 'error');
        } else {
            try {
                if (isset($_SESSION['reservation_guest']) && $_SESSION['reservation_guest'] > 0) {
                    $this->guestManager->update($_SESSION['reservation_guest'], $values);
                } else {
                    $_SESSION['reservation_guest'] = $this->guestManager->add($values);
                }
            } catch (UniqueConstraintViolationException $e) {
                // Pokud již v databázi je, vyber jej a aktualizuj údaje
                $_SESSION['reservation_guest'] = $this->guestManager->getByEmail($values->email)->id;
                $this->guestManager->update($_SESSION['reservation_guest'], $values);
            }
            $this->reservationManager->update_reservation($_SESSION['reservation_id'], ['guests_id' => $_SESSION['reservation_guest']]);
            Debugger::barDump($_SESSION['reservation_guest'], 'GUEST_ID');
            $this->redirect('NextStep!');
        }
    }


    /**
     * Vytvoření potvrzovacího formuláře rezervace (krok 3)
     * @return Form
     */
    protected function createComponentReservationConfirmForm() {
        $form = new Form();
        $form->addTextArea('note', 'Vaše poznámka')
            ->setHtmlAttribute('placeholder', 'Vaše přání, speciální požadavky či jiné upřesnění objednávky.');
        $form->addCheckbox('agree', ' Souhlasím s podmínkami')
            ->setRequired('Musíte souhlasit s podmínkami')
            ->addRule(Form::FILLED, 'Musíte souhlasit s podmínkami');
        $form->addSubmit('submit', 'Závazně rezervovat');
        $form->onSuccess[] = [$this, 'reservationConfirmFormSucceeded'];
        return $form;
    }

    public function reservationConfirmFormSucceeded($form, $values) {
        try {
            // Aktualizuj informace o rezervaci
            $data = array('step' => 3, 'note' => $values->note);
            $this->reservationManager->update_reservation($_SESSION['reservation_id'], $data);
            $reservation = $this->reservationManager->getReservationById($_SESSION['reservation_id']);
            $message = 'Rezervace byla úspěšně dokončena!<br />';
            $this->deleteReservationSessions();

            // Odešli EMAIL o potvrzení
            try {
                $latte = new Engine();
                $nights = intval(date_diff(date_create($reservation->date_from), date_create($reservation->date_to))->format("%d"));
                $params = ['reservation' => $reservation,
                    'nights' => $nights,
                    'nights_word' =>  $this->reservationManager->word_of_number_nights($nights),
                    'rooms' => $this->reservationManager->get_rooms_in_reservation($reservation->id),
                    'info' => $this->serviceInformationManager->getAllInformation()];
                $mail = new Message;
                $mail->setFrom($this->serviceInformationManager->getEmail())
                    ->addTo($reservation->guests->email)
                    ->setSubject('Potvrzení rezervace')
                    ->setHtmlBody($latte->renderToString(__DIR__.'/../templates/Reservation/email.latte', $params));
                $mailer = new SendmailMailer;
                $mailer->send($mail);
                $message .= 'Na email <strong>'.$reservation->guests->email.'</strong> bylo odesláno potrzení o rezervaci.';
            } catch (InvalidStateException $ex) {
                $message .= 'Email s potvrzením rezervace se bohužel nepodařilo odeslat.';
            }

            // Informační zpráva o konci.
            $this->flashMessage($message);

            if ($this->getUser()->isLoggedIn())
                $this->redirect('my'); else $this->redirect('Room:');
        } catch (UniqueConstraintViolationException $ex) {
            Debugger::barDump($ex, 'chyba');
            $this->flashMessage('Bohužel na zadaný termín je pokoj již obsazen. Můžete si vybrat jiný.', 'error');
            $this->redirect('Room:');
        }
    }

    // Pomocné funkce
    private function deleteReservationSessions() {
        unset($_SESSION['reservation']);
        unset($_SESSION['reservation_guest']);
        unset($_SESSION['reservation_id']);
    }


}