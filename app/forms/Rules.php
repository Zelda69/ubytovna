<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 27.06.2017
 */

namespace App\Forms;

use Nette;
use Nette\Forms\IControl;
use Nette\Utils\Validators;


class Rules extends Nette\Object {

    const DATERANGE = 'App\Forms\Rules::validateDateRange';

    public static function validateDateRange(IControl $control, array $range) {
        //Debugger::barDump($range, 'Pův:');
        $range[0] = $range[0] instanceof Nette\Utils\DateTime ? $range[0] : new Nette\Utils\DateTime($range[0]);
        $range[1] = $range[1] instanceof Nette\Utils\DateTime ? $range[1] : new Nette\Utils\DateTime($range[1]);
        if (isset($range[2]) && is_numeric($range[2]) && $range[2] > 0)
            $range[1]->modify('+'.$range[2].' year');// minDay +X year
        //Debugger::barDump($range, 'Roky:');

        $date = $control->getValue();
        $date = $date instanceof Nette\Utils\DateTime ? $date : new Nette\Utils\DateTime($date);
        $date->modify('+ 12 hour');
        //Debugger::barDump($date, 'Ha, datum!');
        return (Validators::isInRange($date, $range));
    }
}