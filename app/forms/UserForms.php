<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 13.06.2017
 */

namespace App\Forms;


use App\Model\Exception\DuplicateNameException;
use Nette\Application\UI\Form;
use Nette\Object;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

class UserForms extends Object {

    /** @var  User uživatel */
    private $user;


    /**
     * UserForms constructor.
     *
     * @param User $user
     */
    public function __construct(User $user) {
        $this->user = $user;
    }

    /**
     * Přihlašuje uživatele.
     *
     * @param Form $form formulář, ze kterého se metoda volá
     * @param $instructions
     */
    private function login($form, $instructions) {
        $presenter = $form->getPresenter(); // Získej presenter ve kterém je formulář umístěn.
        try {
            // Extrakce hodnot z formuláře.
            $username = $form->getValues()->email;
            $password = $form->getValues()->password;

            $this->user->login($username, $password); // Zkusíme se přihlásit.
            // Pokud jsou zadány uživatelské instrukce a formulář je umístěn v presenteru.
            if ($instructions && $presenter) {
                // Pokud instrukce obsahují zprávu, tak ji pošli do příslušného presenteru.
                if (isset($instructions->message))
                    $presenter->flashMessage($instructions->message);

                // Pokud instrukce obsahují přesměrování, tak ho proveď na příslušném presenteru.
                if (isset($instructions->redirection))
                    $presenter->redirect($instructions->redirection);
            }
        } catch (AuthenticationException $ex) {
            if ($presenter) { // Pokud je formulář v presenteru.
                $presenter->flashMessage($ex->getMessage(), 'error'); // Pošli chybovou zprávu tam.
                $presenter->redirect('this'); // Proveď přesměrování.
            } else { // Jinak přidej chybovou zprávu alespoň do samotného formuláře.
                $form->addError($ex->getMessage());
            }
        }
    }
    /* OLD ONE @deprecated
    private function login($form) {
        $presenter = $form->getPresenter(); // Získej presenter ve kterém je formulář umístěn.
        try {
            // Extrakce hodnot z formuláře.
            $username = $form->getValues()->username;
            $password = $form->getValues()->password;

            // Zkusíme zaregistrovat nového uživatele.
            if ($register)
                $this->user->getAuthenticator()->register($username, $password);
            $this->user->login($username, $password); // Zkusíme se přihlásit.

            // Pokud jsou zadány uživatelské instrukce a formulář je umístěn v presenteru.
            if ($instructions && $presenter) {
                // Pokud instrukce obsahují zprávu, tak ji pošli do příslušného presenteru.
                if (isset($instructions->message))
                    $presenter->flashMessage($instructions->message);

                // Pokud instrukce obsahují přesměrování, tak ho proveď na příslušném presenteru.
                if (isset($instructions->redirection))
                    $presenter->redirect($instructions->redirection);
            }
        } catch (AuthenticationException $ex) { // Registrace nebo přihlášení selhali.
            if ($presenter) { // Pokud je formulář v presenteru.
                $presenter->flashMessage($ex->getMessage()); // Pošli chybovou zprávu tam.
                $presenter->redirect('this'); // Proveď přesměrování.
            } else { // Jinak přidej chybovou zprávu alespoň do samotného formuláře.
                $form->addError($ex->getMessage());
            }
        }
    }*/

    /***
     * Registruje nové uživatele
     *
     * @param Form $form
     * @param null $instructions
     */
    private function register($form, $instructions) {
        $presenter = $form->getPresenter(); // Získej presenter ve kterém je formulář umístěn.
        try {
            // Extrakce dat
            $data = $form->getValues();
            // Odeber ty data, které se neukládají do DB
            unset($data['passwordVerify']);
            unset($data['recaptcha']);

            $this->user->getAuthenticator()->register($data);
            $this->login($form, $instructions); // a hned přihlas
        } catch (AuthenticationException $e) {
            if ($presenter) { // Pokud je formulář v presenteru.
                $presenter->flashMessage($e->getMessage(), 'error'); // Pošli chybovou zprávu tam.
                $presenter->redirect('this'); // Proveď přesměrování.
            } else { // Jinak přidej chybovou zprávu alespoň do samotného formuláře.
                $form->addError($e->getMessage());
            }
        }
    }

    /**
     * Vrací formulář se společným základem.
     * @deprecated
     * @param null|Form $form formulář, který se má rozšířit o společné prky, nebo null, pokud se má vytvořit nový
     *                        formulář
     * @param null $instructions
     * @return Form formulář se společným základem

    private function createBasicForm(Form $form = NULL, $instructions = NULL) {
        $form = $form ? $form : new Form;
        $form->addText('username', 'Jméno')->setRequired();
        $form->addPassword('password', 'Heslo');
        //$form->getRenderer()->wrappers['pair']['container'] = "table class='center'";
        $form->getRenderer()->wrappers['label']['container'] = "th align='center'";
        $form->onSuccess[] = function (Form $form) use ($instructions) {
            $this->login($form, $instructions);
        };
        return $form;
    }*/

    /**
     * Vrací komponentu formuláře s přihlašovacími prvky a zpracování přihlašování podle uživatelských instrukcí.
     *
     * @param null|ArrayHash $instructions uživatelské instrukce pro zpracování registrace
     * @return Form komponenta formuláře s přihlašovacími prky
     * @internal param Form|null $form komponenta formuláře, která se má rozšířit o přihlašovací prvky, nebo null,
     *                                     pokud se má vytvořit nový formulář
     */
    public function createLoginForm($instructions = NULL) {
        $form = new Form();
        $form->addText('email', 'E-mail')->setRequired('Musíte zadat email!');
        $form->addPassword('password', 'Heslo')->setRequired('Zadejte heslo');
        $form->addSubmit('submit', 'Přihlásit');
        $form->onSuccess[] = function (Form $form) use ($instructions) {
            $this->login($form, $instructions);
        };
        return $form;
    }

    /**
     * Vrací komponentu formuláře s registračními prvky a zpracování registrace podle uživatelských instrukcí.
     *
     * @param null|ArrayHash $instructions uživatelské instrukce pro zpracování registrace
     * @return Form komponenta formuláře s registračními prky
     * @internal param Form|null $form komponenta formuláře, která se má rozšířit o registrační prvky, nebo null,
     *                                     pokud se má vytvořit nový formulář
     */
    public function createRegisterForm($instructions = NULL) {
        $form = new Form();
        $form->addEmail('email', 'E-mail')->setRequired('Musíte vyplnit email!');
        $form->addPassword('password', 'Heslo')
            ->setRequired('Zvolte si heslo')
            ->addRule(Form::MIN_LENGTH, 'Heslo musí obsahovat alespoň %d znaků', 7)
            ->addRule(Form::PATTERN, 'Heslo musí obsahovat číslici', '.*[0-9].*');
        $form->addPassword('passwordVerify', 'Heslo pro kontrolu:')
            ->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu')
            ->addRule(Form::EQUAL, 'Hesla se neshodují!', $form['password']);
        $form->addText('name', 'Jméno a Příjmení')->setRequired('Musíte vyplnit jméno!');
        $form->addText('phone', 'Telefonní číslo')->setRequired('Musíte vyplnit telefon!');
        //->setOption('description', 'Toto číslo zůstane skryté');
        $form->addReCaptcha('recaptcha', $label = 'Captcha')->setRequired('Proveďte prosím ověření proti spam botům.');
        $form->addSubmit('register', 'Registrovat');
        $form->onSuccess[] = function (Form $form) use ($instructions) {
            $this->register($form, $instructions);
        };
        return $form;
    }

}