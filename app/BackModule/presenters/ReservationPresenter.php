<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 29.06.2017
 */

namespace App\BackModule\Presenters;


use App\FrontModule\Model\ReservationManager;
use App\Presenters\BasePresenter;

class ReservationPresenter extends  BasePresenter {
    /** @var ReservationManager  */
    private $reservationManager;

    public function __construct(ReservationManager $reservationManager) {
        parent::__construct();
        $this->reservationManager = $reservationManager;
    }

    public function renderDefault(){
        $this->template->reservations = $this->reservationManager->get();
    }

}