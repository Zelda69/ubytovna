var tlacitko = $('#tlacitko-telefon'); //tlačítko pro rozbalení a sbalení navigace na telefonu.
var menu = $('.menu__telefon'); //menu
var overeni = $('.menu__pc-telefon--overeni'); //div pro ověřovaní navigace.

//klikací funkce.. :D
tlacitko.click(function() {

	//kvůli animacím jsem to dělal takle  né přes toggle. 
	if(menu.hasClass('menu__telefon--aktivni') === true) {

		menu.slideUp(function() {
			menu.removeClass('menu__telefon--aktivni');
		});

		tlacitko.fadeOut(function() {
			tlacitko.html('<i class="fa fa-bars"></i>');
			tlacitko.addClass('menu__TelefonTlacitko--aktivni');
			tlacitko.fadeIn(function() {
				tlacitko.css('display', ''); //aby tlačítko nezůstalo zobrazeno i na větším rozlišení.
				overeni.show();
			});
		});

	} else {

		menu.slideDown();
		menu.addClass('menu__telefon--aktivni');

		tlacitko.fadeOut(function() {
			tlacitko.html('<i class="fa fa-times"></i>');
			tlacitko.removeClass('menu__TelefonTlacitko--aktivni');
			tlacitko.fadeIn(function() {
				tlacitko.css('display', ''); //aby tlačítko nezůstalo zobrazeno i na větším rozlišení.
				overeni.show();
			});
		});

	}

});

//funkce která obstarává aby když na pc zmenšíš rozlišení a vysuneš menu, a zasuneš a opět zvětšíš rozlíšení tak aby se menu zase zobrazilo.
setInterval(function(){
  if (overeni.is(":visible") === true) {
  	if (tlacitko.is(":visible") === false) {
		menu.show();
		if(tlacitko.hasClass('menu__TelefonTlacitko--aktivni') === true) {
			menu.css('display', '');
		} 
  	};
  };
}, 50)