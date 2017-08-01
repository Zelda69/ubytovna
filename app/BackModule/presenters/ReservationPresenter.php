<?php
/**
 * Created by @author Zbyněk Mlčák
 * Date: 29.06.2017
 */

namespace App\BackModule\Presenters;


use App\FrontModule\Model\ReservationManager;
use App\Presenters\BasePresenter;

class ReservationPresenter extends  BasePresenter {
    /** @var ReservationManager  */
    private $reservationManager;

    public function __construct(ReservationManager $reservationManager) {
        parent::__construct();
        $this->reservationManager = $reservationManager;
    }

    public function renderDefault(){
        $this->template->reservations = $this->reservationManager->get_all_reservations();
    }

    public function generatePDF() {
        $dodavatel=array(
            "firma"=>"ITnetwork Pro Web design",
            "adresa"=>"345 Park Ave, San Jose, CA 95110, United States",
            "ico"=>"00112233"
        );
        $odberatel=array(
            "firma"=>"Tuning ponorek, s.r.o.",
            "adresa"=>"Mesto, ulice 1/3, 12345",
            "ico"=>"12345678"
        );

        $web=array(
            "pocet"=>1,
            "polozka"=>"Vytvoření webové prezentace",
            "cena"=>12000
        );
        $polozky_k_fakturaci=array($web);

        $css = "<style>
        body{
                font-family:sans-serif;
        }
        h1{
                text-align:right;
                margin:0px;
        }
        h2{
                font-weight:normal;
                font-size:23px;
        }
        .dodavatel{
                float:left;
                border:1px solid black;
                width:300px;
                height:300px;
                padding:5px;
        }
        .odberatel{
                float:right;
                border:1px solid black;
                width:300px;
                height:300px;
                padding:5px;
        }
        div p span{
                font-weight:bold;
        }
        table{
                width:100%;
        }
        th{
                text-align:left;
        }
        h3{
                text-align:right;
                margin-top:50px;
        }
</style>";


        /*
                $mpdf=new \mPDF();
                $mpdf->SetHeader('Hello');
                $mpdf->WriteHTML($css); //Načtení CSS
                $mpdf->WriteHTML("
        <h1>Faktura číslo: 4647</h1>
        <hr>
        <div style='width:100%'>
                <div class='dodavatel'>
                        <h2>Dodavatel</h2>
                        <p>
                                <span>Obchodní název:</span>
                                $dodavatel[firma]
                        </p>
                        <p>
                                <span>Adresa:</span>
                                $dodavatel[adresa]
                        </p>
                        <p>
                                <span>IČO:</span>
                                $dodavatel[ico]
                        </p>
                </div>
                <div class='odberatel'>
                        <h2>Odběratel</h2>
                        <p>
                                <span>Obchodní název:</span>
                                $odberatel[firma]
                        </p>
                        <p>
                                <span>Adresa:</span>
                                $odberatel[adresa]
                        </p>
                        <p>
                                <span>IČO:</span>
                                $odberatel[ico]
                        </p>
                </div>
        </div>
        <hr>
        <table>
        <tr>
                <td>Datum vystavení</td>
                <td>11.11.2014</td>
                <td>Datum splatnosti</td>
                <td>12.12.2014</td>
        </tr>
        </table>
        <hr>
        <h2>Položky:</h2>
        <table border='1' cellspacing='0' cellpadding='2'>
                <tr>
                        <th>Množství</th>
                        <th>Název</th>
                        <th>Cena</th>
                </tr>
        ");
                $cena_celkem=0;
                foreach($polozky_k_fakturaci AS $polozka){
                    $mpdf->WriteHTML("
                <tr>
                        <td>$polozka[pocet] x</td>
                        <td>$polozka[polozka]</td>
                        <td>$polozka[cena],- CZK</td>
                </tr>
                ");
                    $cena_celkem+=$polozka['cena'];
                }
                $mpdf->WriteHTML("</table>");
                $mpdf->WriteHTML("<h3>Celkem $cena_celkem,- CZK</h3>");
                $mpdf->Output();*/

        $mpdf = new \mPDF();
        $stylesheet = file_get_contents('invoice/style.css');
        $mpdf->WriteHTML($stylesheet,1);
        $mpdf->WriteHTML('   <header class="clearfix">
      <div id="company">
        <h2 class="name">Company Name</h2>
        <div>455 Foggy Heights, AZ 85004, US</div>
        <div>(602) 519-0450</div>
        <div><a href="mailto:company@example.com">company@example.com</a></div>
      </div>
      </div>
    </header>
    <main>
      <div id="details" class="clearfix">
        <div id="client">
          <div class="to">INVOICE TO:</div>
          <h2 class="name">John Doe</h2>
          <div class="address">796 Silver Harbour, TX 79273, US</div>
          <div class="email"><a href="mailto:john@example.com">john@example.com</a></div>
        </div>
        <div id="invoice">
          <h1>INVOICE 3-2-1</h1>
          <div class="date">Date of Invoice: 01/06/2014</div>
          <div class="date">Due Date: 30/06/2014</div>
        </div>
      </div>
      <table border="0" cellspacing="0" cellpadding="0">
        <thead>
          <tr>
            <th class="no">#</th>
            <th class="desc">DESCRIPTION</th>
            <th class="unit">UNIT PRICE</th>
            <th class="qty">QUANTITY</th>
            <th class="total">TOTAL</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="no">01</td>
            <td class="desc"><h3>Website Design</h3>Creating a recognizable design solution based on the company\'s existing visual identity</td>
            <td class="unit">$40.00</td>
            <td class="qty">30</td>
            <td class="total">$1,200.00</td>
          </tr>
          <tr>
            <td class="no">02</td>
            <td class="desc"><h3>Website Development</h3>Developing a Content Management System-based Website</td>
            <td class="unit">$40.00</td>
            <td class="qty">80</td>
            <td class="total">$3,200.00</td>
          </tr>
          <tr>
            <td class="no">03</td>
            <td class="desc"><h3>Search Engines Optimization</h3>Optimize the site for search engines (SEO)</td>
            <td class="unit">$40.00</td>
            <td class="qty">20</td>
            <td class="total">$800.00</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">SUBTOTAL</td>
            <td>$5,200.00</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">TAX 25%</td>
            <td>$1,300.00</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">GRAND TOTAL</td>
            <td>$6,500.00</td>
          </tr>
        </tfoot>
      </table>
      <div id="thanks">Thank you!</div>
      <div id="notices">
        <div>NOTICE:</div>
        <div class="notice">A finance charge of 1.5% will be made on unpaid balances after 30 days.</div>
      </div>
    </main>
<!--
    <footer>
      Invoice was created on a computer and is valid without the signature and seal.
    </footer>-->
');
        //$mpdf->Output();
    }

}