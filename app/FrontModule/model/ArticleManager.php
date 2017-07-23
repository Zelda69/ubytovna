<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 12.06.2017
 */

namespace App\FrontModule\Model;

use App\Model\BaseManager;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;


class ArticleManager extends BaseManager {
    /** Konstanty pro manipulaci s modelem. */
    const
        TABLE_NAME = 'article',
        COLUMN_ID = 'article_id',
        COLUMN_URL = 'url';

    /**
     * Vrátí seznam článků v databázi.
     * @return Selection seznam článků
     */
    public function getArticles()
    {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');
    }

    /**
     * Vrátí článek z databáze podle jeho URL.
     * @param string $url URl článku
     * @return bool|mixed|IRow první článek, který odpovídá URL nebo false při neúspěchu
     */
    public function getArticle($url)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->fetch();
    }

    /**
     * Uloží článek do systému. Pokud není nastaveno ID, vloží nový, jinak provede editaci.
     * @param array|ArrayHash $article článek
     */
    public function saveArticle($article)
    {
        if (!$article[self::COLUMN_ID])
            $this->database->table(self::TABLE_NAME)->insert($article);
        else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $article[self::COLUMN_ID])->update($article);
    }

    /**
     * Odstraní článek.
     * @param string $url URL článku
     */
    public function removeArticle($url)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->delete();
    }
}