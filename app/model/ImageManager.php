<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 23.06.2017
 */

namespace App\Model;

use App\Model\Exception\ImageIsUsedException;
use App\Model\Exception\NotEmptyGalleryException;
use App\Model\Exception\SomethingIsMissingException;
use Nette\Database\ForeignKeyConstraintViolationException;
use Nette\Utils\Image;

class ImageManager extends BaseManager {

    const IMG_DEFAULT_DIR = 'images/';
    const IMG_THUMBS_DIR = 'images/thumbs/';
    const THUMB_WIDTH = 250, THUMB_HEIGHT = 160;


    /**
     * @param $id
     * @return array
     */
    public function getOneImage($id) {
        $img = array();
        $result = $this->database->table('image')->where('id = ?', $id)->fetch();
        // Kontrola existence obrázku
        if (file_exists(self::IMG_DEFAULT_DIR.$result->path)) {
            // Kontrola existence náhledu
            if (!file_exists(self::IMG_THUMBS_DIR.$result->path)) {
                $this->createThumb($result->path);
            }
            $img['path'] = self::IMG_DEFAULT_DIR.$result->path;
            $img['desc'] = $result->description;
            $img['thumb'] = self::IMG_THUMBS_DIR.$result->path;
        }

        return $img;
    }

    /***
     * @param $gallery_id
     * @return array
     */
    public function getImgFromGallery($gallery_id) {
        $images = array();
        $query = $this->database->table('photogallery_images')->where('photogallery_id = ?', $gallery_id);
        foreach ($query as $result) {

            // Tady proveď kontrolu, zda existuje obrázek, pokud ne, přeskoč ho
            if (file_exists(self::IMG_DEFAULT_DIR.$result->image->path)) {

                // Pokud neexistuje náhled, vytvoř jej
                if (!file_exists(self::IMG_THUMBS_DIR.$result->image->path)) {
                    $this->createThumb($result->image->path);
                }
                $images[] = array('id' => $result->image->id, 'path' => self::IMG_DEFAULT_DIR.$result->image->path, 'desc' => $result->image->description, 'thumb' => self::IMG_THUMBS_DIR.$result->image->path);
            }
        }

        return $images;
    }

    /***
     * @param null $gallery_id
     * @return array
     */
    public function getGallery($gallery_id = NULL) {
        $gallery = array();
        if ($gallery_id)
            $galleries = $this->database->table('photogallery')->where('id = ?', $gallery_id); else
            $galleries = $this->database->table('photogallery');

        foreach ($galleries as $g) {
            $gallery[$g->id] = array('name' => $g->name, 'img' => $this->getImgFromGallery($g->id));
        }

        return $gallery;
    }

    private function createThumb($path) {
        $image = Image::fromFile(self::IMG_DEFAULT_DIR.$path);
        $image->resize(self::THUMB_WIDTH, self::THUMB_HEIGHT, Image::EXACT);
        $help_path = explode('/', $path);
        unset($help_path[count($help_path) - 1]);
        $help_path = implode('/', $help_path);
        $this->makePath(self::IMG_THUMBS_DIR.$help_path); // ověř že existuje cesta, jinak vytvoř
        $image->save(self::IMG_THUMBS_DIR.$path);
    }

    public function makePath($path) {
        if (@(dirname(mkdir($path))) or file_exists($path))
            return true;
        return mkdir(dirname($path));
    }

    /**
     * @param $path
     * @param string $description
     * @return string
     */
    public function saveImage($path, $description = '') {
        return $this->database->table('image')->insert(array('path' => $path, 'description' => $description))->id;
    }

    public function makeGallery($name) {
        return $this->database->table('photogallery')->insert(array('name' => $name))->id;
    }

    public function addToGallery($img, $gallery) {
        try {
        $this->database->table('photogallery_images')->insert(array('image_id' => $img, 'photogallery_id' => $gallery));
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new SomethingIsMissingException();
        }
    }

    public function deleteFromGallery($img, $gallery) {
        $this->database->table('photogallery_images')->where('image_id = ?', $img)->where('photogallery_id = ?', $gallery)->delete();
    }

    public function deleteGallery($gallery) {
        try {
            $this->database->table('photogallery')->where('id = ?', $gallery)->delete();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new NotEmptyGalleryException();
        }
    }

    public function deleteImage($id) {
        try {
            $this->database->table('image')->where('id = ?', $id)->delete();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new ImageIsUsedException();
        }
    }

}