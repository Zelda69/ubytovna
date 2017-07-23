<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 13.06.2017
 */

namespace App\FrontModule\Presenters;

use Nette\Application\UI\Form;
use Nette\InvalidStateException;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Utils\ArrayHash;

/**
 * Zpracovává kontaktní formulář.
 * @package App\CoreModule\Presenters
 */
class ContactPresenter extends BaseFrontPresenter {


    public function renderDefault() {
        $this->template->page = 'contact';
    }
}