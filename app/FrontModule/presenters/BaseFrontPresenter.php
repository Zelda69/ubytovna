<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 13.06.2017
 */

namespace App\FrontModule\Presenters;

use App\Presenters\BasePresenter;

abstract class BaseFrontPresenter extends BasePresenter
{
    /** @var null|string Adresa presenteru pro logování uživatele v rámci CoreModule. */
    protected $loginPresenter = ':Front:Administration:login';
}