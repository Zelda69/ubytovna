<?php

namespace App\Presenters;

use App\Model\AccommodationManager;
use Nette\Application\UI\Presenter;
use Nette\Application\BadRequestException;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter {

    /** @var null|string Adresa presenteru pro logování uživatele. */
    protected $loginPresenter = ':Front:Administration:login';
    /** @var AccommodationManager @inject */
    public $serviceInformationManager;

    /**
     * Volá se na začátku každé akce a kontroluje uživatelská oprávnění k této akci.
     * @throws BadRequestException Jestliže je uživatel přihlášen, ale nemá oprávnění k této akci.
     */
    protected function startup() {
        parent::startup();
        if (!$this->getUser()->isAllowed($this->getName(), $this->getAction())) {
            $this->flashMessage('Nejste přihlášený nebo nemáte dostatečná oprávnění. Do této sekce aktuálně nemáte přístup.');
            if ($this->loginPresenter)
                $this->redirect($this->loginPresenter);
        }
    }

    /** Volá se před vykreslením každého presenteru a předává společné proměné do celkového layoutu webu. */
    protected function beforeRender() {
        parent::beforeRender();
        $this->template->admin = $this->getUser()->isInRole('admin');
    }

}
