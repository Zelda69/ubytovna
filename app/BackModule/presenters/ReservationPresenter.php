<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 29.06.2017
 */

namespace App\BackModule\Presenters;


use App\Model\GuestManager;
use App\Model\ReservationManager;
use App\Model\RoomManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;

class ReservationPresenter extends BasePresenter {
    /** @var ReservationManager */
    private $reservationManager;
    /** @var RoomManager */
    private $roomManager;
    /** @var GuestManager */
    private $guestManager;

    public function __construct(ReservationManager $reservationManager, RoomManager $roomManager, GuestManager $guestManager) {
        parent::__construct();
        $this->reservationManager = $reservationManager;
        $this->roomManager = $roomManager;
        $this->guestManager = $guestManager;
    }

    public function handleZobrazit($id) {
        if ($id == 0)
            $_SESSION['admin_rezervace'] = 0; else $_SESSION['admin_rezervace'] = 1;
    }

    public function handleUdaje($id, $reservation) {
        $this->template->guest = $this->guestManager->get($id);
        $this->handleDetail($reservation);
        $this->redrawControl('reservation');
    }

    public function handleZadneUdaje($reservation) {
        $this->template->guest = NULL;
        $this->handleDetail($reservation);
        $this->redrawControl('reservation');
    }

    public function handlePotvrdit($id) {
        $this->reservationManager->update_reservation($id, ['confirm' => 1]);
        $this->redrawControl('table');
        $this->redrawControl('reservation');
        $this->handleDetail($id);
    }

    public function handleZrusit($id) {
        $this->reservationManager->delete_reservation($id);
        $this->reservationManager->delete_rooms_in_reservation($id);
        $this->flashMessage('Rezervace č. '.$id.' byla úspěšně zrušena.');
        $this->redirect('this');
    }

    public function handleZaplatit($id) {
        $this->reservationManager->update_reservation($id, ['paid' => 1]);
        $this->redrawControl('table');
        $this->redrawControl('reservation');
        $this->handleDetail($id);
    }

    public function handleZrusitPotvrdit($id) {
        $this->reservationManager->update_reservation($id, ['confirm' => 0]);
        $this->redrawControl('table');
        $this->redrawControl('reservation');
        $this->handleDetail($id);
    }

    public function handleZrusitZaplatit($id) {
        $this->reservationManager->update_reservation($id, ['paid' => 0]);
        $this->redrawControl('table');
        $this->redrawControl('reservation');
        $this->handleDetail($id);
    }

    public function handleFakturuj($id) {
        $this->generatePDF($id);
    }

    public function handlePreviousWeek() {
        $_SESSION['reservation_filter_date'] -= 1;
        $this->redrawControl('table');
    }

    public function handleNextWeek() {
        $_SESSION['reservation_filter_date'] += 1;
        $this->redrawControl('table');
    }

    public function handleTodayWeek() {
        $_SESSION['reservation_filter_date'] = 0;
        $this->redrawControl('table');
    }

    public function handleDetail($id) {
        $reservation = $this->reservationManager->getReservationById($id);
        if (is_null($reservation)) {
            $this->flashMessage('Rezervace neexistuje!', 'error');
            $this->redirect('this');
        }

        $this->template->reservations = $reservation;
        $this->template->reservationDetail = $this->reservationManager->get_rooms_in_reservation($id);
        $this->template->nights = intval(date_diff(date_create($reservation->date_from), date_create($reservation->date_to))->format("%d"));
        $this->template->nights_word = $this->reservationManager->word_of_number_nights($this->template->nights);
        $this->redrawControl('reservation');
    }

    protected function createComponentSelectDateForm() {
        $dates = $this->getWeekDays();;
        $form = new Form();
        $form->addText('date')->setType('date')->setDefaultValue(date('Y-m-d', $dates[0]));
        $form->addSubmit('submit');
        $form->onSuccess[] = [$this, 'selectDateFormSucceeded'];

        return $form;
    }

    public function selectDateFormSucceeded($form, $values) {
        $rozdil = intval(date('W', strtotime($values->date))) - intval(date('W'));
        $_SESSION['reservation_filter_date'] = $rozdil;
        $this->redirect('this');
    }

    public function renderDefault() {
        if (!isset($_SESSION['admin_rezervace']))
            $_SESSION['admin_rezervace'] = 0;
        $this->template->zobrazeni = $_SESSION['admin_rezervace'];
        // Dle zobrazení
        if ($_SESSION['admin_rezervace'] == 1) {
            $this->template->reservations = $this->reservationManager->get_all_reservations();
        } else {
            $this->template->rooms = $this->roomManager->getRooms();
            if (!isset($_SESSION['reservation_filter_date'])) {
                $_SESSION['reservation_filter_date'] = 0;
            }

            $this->template->dates = $this->getWeekDays();
            $this->template->reservationInDays = $this->getReservationInDays();
            $this->template->reservationNights = $this->getNightsOfReservation();
        }
        if (!isset($this->template->reservationDetail))
            $this->template->reservationDetail = NULL;
        if (!isset($this->template->guest)) {
            $this->template->guest = NULL;
        }
    }

    /**
     * Pomocná funkce, určuje zda je datum v rozsahu
     * @param $date
     * @param $from
     * @param $to
     * @return bool
     */
    private function isDateInRange($date, $from, $to) {
        return $date >= $from && $date < $to;
    }

    /**
     * Vrátí rezervace v zadaných termínech
     * @return array
     */
    private function getReservationInDays() {
        $result = array();
        $dates = $this->getWeekDays();
        $reservation = $this->reservationManager->getReservationInRange($dates[0], $dates[count($dates) - 1]);

        foreach ($reservation as $r) {
            $from = strtotime($r->reservation->date_from);
            $to = strtotime($r->reservation->date_to);

            foreach ($dates as $d) {
                if ($this->isDateInRange($d, $from, $to)) {
                    $result[$d][$r->room_id] = $r;
                }
            }
        }
        return $result;
    }

    /**
     * Počet nocí rezervace
     * @return array
     */
    private function getNightsOfReservation() {
        $result = array();
        foreach ($this->getReservationInDays() as $day) {
            foreach ($day as $room => $reservation) {
                if (isset($result[$room][$reservation->reservation->id])) {
                    $result[$room][$reservation->reservation->id] += 1;
                } else $result[$room][$reservation->reservation->id] = 1;
            }
        }
        return $result;
    }

    /**
     * Vrací pole datumů v týdnu
     * @return array
     */
    private function getWeekDays() {
        $todayWeekMonday = strtotime('monday this week') + $_SESSION['reservation_filter_date'] * 7 * 86400;
        $days = array();
        for ($i = 0; $i < 7; $i++) {
            $days[] = $todayWeekMonday + 86400 * $i;
        }

        return $days;
    }

    /**
     * Vygeneruje PDF dokument
     */
    public function generatePDF($id) {
        $info = $this->serviceInformationManager->getAllInformation();
        $reservation = $this->reservationManager->getReservationById($id);
        $rooms = $this->reservationManager->get_rooms_in_reservation($id);
        $nights = intval(date_diff(date_create($reservation->date_from), date_create($reservation->date_to))->format("%d"));
        $noci = $this->reservationManager->word_of_number_nights($nights);
        $cena = 0;
        $cena_bez = 0;
        foreach ($rooms as $room) {
            $cena_bez += $room->price;
            $cena += $room->price * (1 + $room->dph / 100);
        }
        $adresa = '';
        $first = true;
        foreach ($info['adress'] as $a) {
            if ($first) {
                $first = false;
                continue;
            }
            $adresa .= ''.($adresa == '' ? '' : ', ').''.$a;
        }

        $mpdf = new \mPDF();
        $stylesheet = file_get_contents('invoice/style.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML('<header class="clearfix">
      <div id="company">
        <h2 class="name">'.$info['name'].'</h2>
        <div>'.$adresa.'</div>
        <div>'.$info['phone'].'</div>
        <div><a href="mailto:'.$info['email'].'">'.$info['email'].'</a></div>
      </div>
      </div>
    </header>
    <main>
      <div id="details" class="clearfix">
        <div id="client">
          <div class="to">Zákazník:</div>
          <h2 class="name">'.$reservation->guests->name.'</h2>
          <div class="address">'.$reservation->guests->street.'<br />'.$reservation->guests->city.'<br />'.$reservation->guests->state.'</div>
          <div class="email"><a href="mailto:'.$reservation->guests->email.'">'.$reservation->guests->email.'</a></div>
        </div>
        <div id="invoice">
          <h1>FAKTURA č. '.$reservation->id.'</h1>
          <div class="date">Datum vystavení: '.date('j.m.Y').'</div>
          <div class="date">Datum zdan. plnění: '.date('j.m.Y').'</div>
        </div>
      </div>
      <table border="0" cellspacing="0" cellpadding="0">
        <thead>
          <tr>
            <th class="no">#</th>
            <th class="desc">POPIS</th>
            <th class="unit">JEDNOT. CENA</th>
            <th class="qty">POČET</th>
            <th class="total">CELKEM</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="no">01</td>
            <td class="desc"><h3>Ubytování</h3>Ubytování v termínu '.date('j.m.Y', strtotime($reservation->date_from)).' - '.date('j.m.Y', strtotime($reservation->date_to)).'.</td>
            <td class="unit">'.number_format($cena_bez, 0, ',', ' ').' Kč</td>
            <td class="qty">1</td>
            <td class="total">'.number_format($cena, 0, ',', ' ').' Kč</td>
          </tr>        
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">CELKEM bez DPH</td>
            <td>'.number_format($cena_bez, 0, ',', ' ').' Kč</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">DPH '.$info['DPH'].'%</td>
            <td>'.number_format($cena - $cena_bez, 0, ',', ' ').' Kč</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">CELKEM s DPH</td>
            <td>'.number_format($cena, 0, ',', ' ').' Kč</td>
          </tr>
        </tfoot>
      </table>
      <div id="thanks">Děkujeme a těšíme se na Vaši příští návštěvu.</div>
    </main>
');
        $mpdf->Output();
    }

}