<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 19.06.2017
 */

namespace App\Model;

use App\Model\BaseManager;
use Nette\Database\Context;

class AccommodationManager extends BaseManager {

    /** @var string */
    private $name;
    /** @var string */
    private $about;
    /** @var array */
    private $adress;
    /** @var string */
    private $phone;
    /** @var string */
    private $email;
    /** @var array */
    private $reception_hours;
    /** @var array */
    private $operator;
    /** @var  string */
    private $homepage;
    /** @var integer */
    private $dph;

    const TABLE_NAME = 'service_information';
    const TRANSFER_DIRECTION_TO_DTB = 0, TRANSFER_DIRECTION_FROM_DTB = 1;

    public function __construct(Context $database) {
        parent::__construct($database);
        $this->getInfo();
    }

    private function getInfo() {
        $result = $this->database->table(self::TABLE_NAME)->fetch();
        $this->name = $result->name;
        $this->about = $result->about;
        $this->adress = $this->transferAddress($result->adress);
        $this->phone = $result->phone;
        $this->email = $result->email;
        $this->reception_hours = $this->transferOfficeHour($result->reception_hours);
        $this->operator = $this->transferAddress($result->operator);
        $this->homepage = $result->homepage;
        $this->dph = $result->DPH;
    }

    private function transferAddress($address, $direction = self::TRANSFER_DIRECTION_FROM_DTB) {
        if ($direction == self::TRANSFER_DIRECTION_FROM_DTB)
            return explode(';', $address); else
            return implode(';', $address);
    }

    private function transferOfficeHour($hours, $direction = self::TRANSFER_DIRECTION_FROM_DTB) {
        if ($direction == self::TRANSFER_DIRECTION_FROM_DTB) {
            $days = explode(';', $hours);
            for ($i = 0; $i < count($days); $i++) {
                $days[$i] = explode('-', $days[$i]);
            }
        } else {
            for ($i = 0; $i < count($hours); $i++) {
                $hours[$i] = implode("-", $hours[$i]);
            }
            $days = implode(";", $hours);
        }

        return $days;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getAbout(): string {
        return $this->about;
    }

    /**
     * @return array
     */
    public function getAdress(): array {
        return $this->adress;
    }

    /**
     * @return string
     */
    public function getPhone(): string {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getEmail(): string {
        return $this->email;
    }

    /**
     * @return array
     */
    public function getReceptionHours(): array {
        return $this->reception_hours;
    }

    /**
     * @return array
     */
    public function getOperator(): array {
        return $this->operator;
    }

    /**
     * @return string
     */
    public function getHomepage(): string {
        return $this->homepage;
    }

    /**
     * @return int
     */
    public function getDPH(): int {
        return $this->dph;
    }

    public function getAllInformation() {
        return array('name' => $this->getName(), 'adress' => $this->getAdress(), 'about' => $this->getAbout(), 'phone' => $this->getPhone(), 'reception_hours' => $this->getReceptionHours(), 'operator' => $this->getOperator(), 'email' => $this->getEmail(), 'homepage' => $this->getHomepage(), 'DPH' => $this->getDPH());
    }

    public function updateInformation($information) {
        if(isset($information['reception_hours'])) $information['reception_hours'] = $this->transferOfficeHour($information['reception_hours'], self::TRANSFER_DIRECTION_TO_DTB);;
        if(isset($information['operator'])) $information['operator'] = $this->transferAddress($information['operator'], self::TRANSFER_DIRECTION_TO_DTB);
        if(isset($information['adress'])) $information['adress'] = $this->transferAddress($information['adress'], self::TRANSFER_DIRECTION_TO_DTB);

        $this->database->table(self::TABLE_NAME)->update($information);
    }


}