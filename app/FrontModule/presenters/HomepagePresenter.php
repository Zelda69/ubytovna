<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 18.06.2017
 */

namespace App\FrontModule\Presenters;

use App\Forms\Rules;
use App\Model\AccommodationManager;
use App\Model\ImageManager;
use App\Presenters\BasePresenter;
use Nette\Application\UI\Form;


class HomepagePresenter extends BasePresenter {
    private $accommodationManager;
    private $imageManager;

    public function __construct(AccommodationManager $accommodationManager, ImageManager $imageManager) {
        parent::__construct();
        $this->accommodationManager = $accommodationManager;
        $this->imageManager = $imageManager;
    }

    public function createComponentVacancyForm() {
        $form = new Form();
        // Úprava tříd pro obalení formu
        $form->getRenderer()->wrappers['group']['container'] = "fieldset class='homepage-fieldset'";
        $form->getRenderer()->wrappers['controls']['container'] = "table class='homepage-table'";
        $form->addGroup('Ověřit dostupnost ubytování');
        $form->addText('from', 'Datum příjezdu')
            ->setDefaultValue(date('Y-m-d'))
            ->setHtmlAttribute('min', date('Y-m-d'))
            ->setType('date')
            ->setRequired('Musíte vyplnit datum příjezdu!');
        $form->addText('to', 'Datum odjezdu')
            ->setDefaultValue(date('Y-m-d', time() + 86400))
            ->setHtmlAttribute('min', date('Y-m-d'))
            ->setType('date')
            ->addRule(Rules::DATERANGE, 'Neplatné datum odjezdu!', [$form['from'], date('Y-m-d'), 2])
            ->setRequired('Musíte vyplnit datum odjezdu!');
        $form['from']->addRule(Rules::DATERANGE, 'Neplatné datum příjezdu!', [date('Y-m-d'), $form['to']]);
        $form->addSubmit('submit', 'Vyhledej volné pokoje');
        $form->onSuccess[] = [$this, 'vacancyFormSucceeded'];
        return $form;
    }

    public function vacancyFormSucceeded($form, $values) {
        /* Pokud je formulář validní, přesměruj jej na výběr pokojů */
        $_SESSION['filter']['use'] = TRUE;
        $_SESSION['filter']['from'] = $values->from;
        $_SESSION['filter']['to'] = $values->to;
        $this->redirect(':Front:Room:');
    }

    public function renderDefault() {
        $this->template->info = $this->accommodationManager->getAllInformation();
        $this->template->img = $this->imageManager->getOneImage(2);
    }
}