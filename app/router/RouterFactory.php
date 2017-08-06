<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
	use Nette\StaticClass;

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList();
        $router[] = new Route('data/<id>', 'Back:Data:default');
        $router[] = new Route('tisk/[<search>]', 'Back:Print:default');
        $router[] = new Route('administrace/hoste/<action>', array(
            'presenter' => 'Back:Guests',
            'action' => array(
                Route::FILTER_TABLE => array(
                    'prehled' => 'default',
                    'detail' => 'detail',
                ),
                Route::FILTER_STRICT => true
            )
        ));
        $router[] = new Route('administrace/rezervace/<action>', array(
            'presenter' => 'Back:Reservation',
            'action' => array(
                Route::FILTER_TABLE => array(
                    'prehled' => 'default',
                    'detail' => 'detail',
                ),
                Route::FILTER_STRICT => true
            )
        ));
        $router[] = new Route('administrace/pokoje/<action>', array(
            'presenter' => 'Back:Room',
            'action' => array(
                Route::FILTER_TABLE => array(
                    'vypis' => 'default',
                    'detail' => 'detail',
                    'typy' => 'type',
                ),
                Route::FILTER_STRICT => true
            )
        ));
        $router[] = new Route('admin', 'Back:Homepage:default');
        $router[] = new Route('administrace/<action>', array(
            'presenter' => 'Back:Homepage',
            'action' => array(
                Route::FILTER_TABLE => array(
                    'home' => 'default',
                    'domovska-stranka' => 'homeEdit',
                    'zakladni-informace' => 'edit'
                ),
                Route::FILTER_STRICT => true
            )
        ));
        $router[] = new Route('profil/<action>/', array(
            'presenter' => 'Front:Administration',
            'action' => array(
                Route::FILTER_TABLE => array(
                    'administrace' => 'default',
                    'prihlaseni' => 'login',
                    'odhlasit' => 'logout',
                    'registrace' => 'register',
                    'info' => 'profil'
                ),
                Route::FILTER_STRICT => true
            )
        ));
        $router[] = new Route('ubytovani/[<action>/]', array(
            'presenter' => 'Front:Accommodation',
            'action' => array(
                Route::VALUE => 'default',
                Route::FILTER_TABLE => array(
                    // řetězec v URL => akce presenteru
                    'informace' => 'info',
                    'kontakt' => 'contact',
                    'galerie' => 'gallery'
                ),
                Route::FILTER_STRICT => true
            )
        ));
        // 'Front:Room:reservation'
        $router[] = new Route('rezervace/[<action>/][<id>/][<from>/<to>]',  array(
            'presenter' => 'Front:Reservation',
            'action' => array(
                Route::VALUE => 'default',
                Route::FILTER_TABLE => array(
                    'nova' => 'new',
                    'moje' => 'my'
                ),
                Route::FILTER_STRICT => true
            )
        ));
        $router[] = new Route('pokoj/[<action>/][<id>]', array(
            'presenter' => 'Front:Room',
            'action' => array(
                Route::FILTER_TABLE => array(
                    // řetězec v URL => akce presenteru
                    'vyber' => 'default',
                    'filtr' => 'filter',
                    'detail' => 'detail'
                ),
                Route::FILTER_STRICT => true
            )
        ));
        $router[] = new Route('', 'Front:Homepage:default');
		return $router;
	}

}