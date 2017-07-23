<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 28.06.2017
 */

namespace App\FrontModule\Presenters;


use App\Forms\UserForms;
use App\FrontModule\Model\ReservationManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

class ReservationPresenter extends BasePresenter {
    /** @persistent @var array */
    public $reservation;
    /** @var UserForms */
    private $userFormsFactory;
    /** @var ReservationManager */
    private $reservationManager;
    /** @var array */
    private $instructions;

    /**
     * ReservationPresenter constructor.
     * @param UserForms $userFormsFactory
     * @param ReservationManager $reservationManager
     */
    public function __construct(UserForms $userFormsFactory, ReservationManager $reservationManager) {
        parent::__construct();
        $this->userFormsFactory = $userFormsFactory;
        $this->reservationManager = $reservationManager;
        $this->instructions = array('message' => NULL, 'redirection' => NULL);
    }

    public function renderNew($room_id, $from, $to) {
        $_SESSION['reservation'] = array('start' => time(), 'from' => $from, 'to' => $to, 'room' => $room_id);
        $this->template->room = $room_id;
        $this->template->from = $from;
        $this->template->to = $to;
        Debugger::barDump($_SESSION['reservation'], 'rezervace');
    }

    public function renderDefault() {
        $this->template->reservation = $_SESSION['reservation'];
    }

    public function renderMy() {
        $this->template->reservations = $this->reservationManager->get(NULL, $this->user->getId());
        Debugger::barDump( $this->template->reservations, 'Reservation');
    }

    /**
     * Vrací komponentu přihlašovacího formuláře z továrničky.
     *
     * @return Form přihlašovací formulář
     */
    protected function createComponentLoginForm() {
        $this->instructions['message'] = 'Byl jste úspěšně přihlášen.';
        $this->instructions['redirection'] = 'this';
        return $this->userFormsFactory->createLoginForm(ArrayHash::from($this->instructions));
    }

    /**
     * Vrací komponentu registračního formuláře z továrničky.
     * @return Form registrační formulář
     */
    protected function createComponentRegisterForm() {
        $this->instructions['message'] = 'Byl jste úspěšně zaregistrován.';
        $this->instructions['redirection'] = 'this';
        return $this->userFormsFactory->createRegisterForm(ArrayHash::from($this->instructions));
    }

    protected function createComponentMultipleReservationForm() {
        $roomManager = '';
        $reservationManager = '';
        $control = new Multiplier(function ($room_id) use ($roomManager, $reservationManager) {
            $form = new Form(NULL, $room_id);
            $form->addText('from', 'Datum příjezdu:')->setHtmlType('date');
            return $form;
        });

        return $control;
    }

    protected function createComponentReservationConfirmForm() {
        $form = new Form();
        $form->addTextArea('note', 'Vaše poznámka')
            ->setHtmlAttribute('placeholder', 'Vaše přání, speciální požadavky či jiné upřesnění objednávky.');
        $form->addCheckbox('agree', 'Souhlasím s podmínkami')
            ->setRequired('Musíte souhlasit s podmínkami')
            ->addRule(Form::FILLED, 'Musíte souhlasit s podmínkami');
        $form->addSubmit('confirm', 'Závazně rezervovat');
        $form->addButton('cancel', 'Zrušit rezervaci');
        $form->onSuccess[] = [$this, 'reservationConfirmFormSucceeded'];
        return $form;
    }

    public function reservationConfirmFormSucceeded($form, $values) {
        try {
            $id = $this->reservationManager->add($_SESSION['reservation']['room'], $_SESSION['reservation']['from'], $_SESSION['reservation']['to'], $this->user->getIdentity()
                ->getId(), $values->note);
            $this->flashMessage('Rezervace id  '.$id.' byla úspěšně vyřízena!');
            $this->redirect('my');
        } catch(UniqueConstraintViolationException $ex) {
            Debugger::barDump($ex, 'chyba');
            $this->flashMessage('Bohužel na zadaný termín je pokoj již obsazen. Můžete si vybrat jiný.', 'error');
            $this->redirect('Room:');
        }
    }

    public function handleZrusit($id) {
        // Zjisti komu rezervace patří, patří li tobě, zruš ji
    }


}