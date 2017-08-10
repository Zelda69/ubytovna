Toto je instuktážní soubor pro instalaci a spuštění webové aplikace Ubytovna.

Požadavky
----------
Vyvíjená aplikace představuje webovou aplikaci, kterou je nutné spustit na serveru. Pro spuštění serveru potřebujeme:
• Apache server (aplikace byla vyvíjena na verzi 2.4.25)
• MySQL databázi (aplikace byla vyvíjena na verzi 15.1)
• PHP 7.0 a vyšší (aplikace byla vyvíjena na verzi PHP 7.1.4)
•  Splnění požadavků pro provoz Nette Frameworku (aplikace vyvíjena na verzi 2.4), dostupných na stránce https://doc.nette.org/en/2.4/requirements
• Nainstalovaný a nastavený HTTPS certifikát na localhostu případně webhostingu. (aplikace může běžet i na pouhém HTTP protokolu, je ovšem nutné upravit konfiguraci)
První tři požadavky mohou být jednoduše splněny použitím LAMPP balíčku, který nainstaluje potřebné nástroje. Pro vývoj aplikace byl použit balíček XAMPP verze 3.2.2 z oficiálních stránek https://www.apachefriends.org/download.html

Instalace aplikace
------------------
Nainstalovat aplikace je možné dvěma jednoduchými způsoby.
•  První způsob je překopírovat veškeré soubory z přiloženého CD ze složky app/ do webového adresáře. (při použití LAMPP balíku se jedná o složku htdocs)
• Druhý způsob není o nic složitější. Jedná se o využití nástroje Composer. Poté stačí jednoduchý příkaz composer create-project zmlcak/ubytovna ubytovna


Po úspěšném nainstalování aplikace je nutné provést následující kroky:
• vytvořit novou databázi
• provést import do databáze všech sql scriptu ze složky sql/ (1. struktura_databaze.sql, 2. testovaci_data.sql)
• soubor /config.local.neon.example ve složce app/config přejmenovat na config.local.neon a upravit prístupové údaje do databáze

Pokud je aplikace nainstalována na místním počítači, je poté přístupná přes webový prohlížeč na adrese localhost/ubytovna

HTTPS a HTTP
------------
Aplikace má po instalaci nastaveno automatické používání HTTPS protokolu. Toto chování je možné změnit úpravou .htaccess souboru ve složce app/www. Stačí zakomentovat řádky č. 14 a 15. Poté nebude vyžadováno přesměrování a používání HTTPS protokolu, ale standartního HTTP.