<?php

	require("classes/api/CryptoCompareApi.class.php");
	require("classes/bot/CryptoCompareBot.class.php");

	class Arbitrage {
		
		protected $objCryptoCompareBot;
		
		public function __construct() {
			$this->objCryptoCompareBot = new CryptoCompareBot();
		}

		public function showPossibleArbitrage($intMinSpread, $blnOnlyGoodCoin) {
			$this->objCryptoCompareBot->checkSpreadCoins($intMinSpread, $blnOnlyGoodCoin);
		}

	}

	$objArbitrage = new Arbitrage();
	$objArbitrage->showPossibleArbitrage(5, false);

?>