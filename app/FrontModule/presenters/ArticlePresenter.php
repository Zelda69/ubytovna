<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 13.06.2017
 */

namespace App\FrontModule\Presenters;

use App\FrontModule\Model\ArticleManager;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;


/**
 * Class ArticlePresenter
 * @package app\FrontModule\presenters
 */
class ArticlePresenter extends BaseFrontPresenter {
    /** Konstanta s hodnotou URL výchozího článku. */
    const DEFAULT_ARTICLE_URL = 'uvod';

    /** @var ArticleManager Instance třídy modelu pro práci s články. */
    protected $articleManager;

    /**
     * Konstruktor s injektovaným modelem pro práci s články.
     * @param ArticleManager $articleManager automaticky injektovaná třída modelu pro práci s články
     */
    public function __construct(ArticleManager $articleManager) {
        parent::__construct();
        $this->articleManager = $articleManager;
    }

    /** Načte a vykreslí článek článek do šablony podle jeho URL.
     * @param string $url URL článku
     * @throws BadRequestException Jestliže článek s danou URL nebyl nalezen.
     */
    public function renderDefault($url) {
        $this->template->page = 'article';
        if (!$url)
            $url = self::DEFAULT_ARTICLE_URL; // Pokud není zadaná URL, vykreslí se výchozí článek.
        // Pokusí se načíst článek s danou URL a pokud nebude nalezen, vyhodí chybu 404.
        if (!($article = $this->articleManager->getArticle($url)))
            throw new BadRequestException();
        $this->template->article = $article; // Předá článek do šablony.
    }

    /** Vykreslí seznam článků do šablony. */
    public function renderList() {
        $this->template->articles = $this->articleManager->getArticles();
    }

    /**
     * Odstraní článek.
     * @param string $url
     */
    public function actionRemove($url) {
        $this->articleManager->removeArticle($url);
        $this->flashMessage('Článek byl úspěšně odstraněn.');
        $this->redirect(':Front:Article:list');
    }

    /**
     * Vykresluje editaci článku podle jeho URL.
     * @param string $url URL adresa článku, který editujeme, pokud není zadána, vytvoří se nový
     */
    public function actionEditor($url) {
        // Pokud byla zadána URL, pokusí se článek načíst a předat jeho hodnoty do editačního formuláře, jinak vypíše chybovou hlášku.
        if ($url)
            ($article = $this->articleManager->getArticle($url)) ? $this['editorForm']->setDefaults($article) : $this->flashMessage('Článek nebyl nalezen.');
    }

    /**
     * Vrátí formulář pro editor článků.
     * @return Form formulář pro editor článků
     */
    protected function createComponentEditorForm() {
        $form = new Form;
        $form->addHidden('article_id');
        $form->addText('title', 'Titulek')->setRequired();
        $form->addText('url', 'URL')->setRequired();
        $form->addText('description', 'Popisek')->setRequired();
        $form->addTextArea('content', 'Obsah');
        $form->addSubmit('submit', 'Uložit článek');
        $form->onSuccess[] = [$this, 'editorFormSucceeded'];
        return $form;
    }

    /**
     * Funkce se vykonaná při úspěsném odeslání formuláře; zpracuje hodnoty formuláře.
     * @param Form $form formulář editoru
     * @param ArrayHash $values odeslané hodnoty formuláře
     */
    public function editorFormSucceeded($form, $values) {
        try {
            $this->articleManager->saveArticle($values);
            $this->flashMessage('Článek byl úspěšně uložen.');
            $this->redirect(':Front:Article:', $values->url);
        } catch (UniqueConstraintViolationException $ex) {
            $this->flashMessage('Článek s touto URL adresou již existuje.');
        }
    }
}