<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 03.08.2017
 */

namespace App\BackModule\Presenters;


use App\Model\GuestManager;
use App\Presenters\BasePresenter;

class PrintPresenter extends BasePresenter {
    /** @var GuestManager */
    private $guestManager;

    public function __construct(GuestManager $guestManager) {
        parent::__construct();
        $this->guestManager = $guestManager;
    }

    public function renderDefault($filtered) {
        if(is_null($filtered)) $filtered = '';
        $this->template->guests = $this->guestManager->search($filtered);
    }

}