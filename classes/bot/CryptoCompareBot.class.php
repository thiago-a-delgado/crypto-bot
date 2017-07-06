<?php
	class CryptoCompareBot {

		protected $objCryptoCompareApi;
		
		public function __construct() {
			$this->objCryptoCompareApi = new CryptoCompareApi();
		}

		// Generic function to remove undesired values
		protected function removeElementWithValue($mixArray, $strKey, $strValue, $strOperator = "=="){
			foreach($mixArray as $subKey => $subArray){
				if ($strOperator == "==") {
					if ($subArray[$strKey] == $strValue){
						unset($mixArray[$subKey]);
					}
				}
				elseif ($strOperator == "<") {
					if ($subArray[$strKey] < $strValue){
						unset($mixArray[$subKey]);
					}					
				}
			}
			return $mixArray;
		}

		// Remove from array undesired exchanges
		protected function removeShitExchanges($mixArray){
			$strTrustExchangeArray = array(	"HitBTC", "BitTrex", "Poloniex", "Cryptopia", "Bleutrade", "Yobit", "Bitfinex", "Bitstamp", "Coinbase", "Kraken", "MercadoBitcoin");
			foreach($mixArray as $subKey => $subArray){
				if (!in_array($subArray["MARKET"], $strTrustExchangeArray)){
					unset($mixArray[$subKey]);
				}
			}
			return $mixArray;
		}

		// check if a specific currency is undesired
		protected function isShitCoin($strCoinName){
			$strShitCoinArray = array("EXP", "GRS", "SC", "RADS", "DCR", "GAME", "BCY", "STEEM", "LBC", "PPC", "SBD", "ZEC", "ARDR", "NXC", "LTC", "BLK", "BTCD", "CLAM", "DOGE", "EMC2", "NAUT", "NAV", "POT", "XLM", "SYS", "VRC", "VTC", "XRP", "XMR", "BCN", "XDN", "XEM", "BURST", "XVC", "GRC", "ETC", "FLO", "FLDC", "NEOS", "PINK", "EDG", "DASH", "SJCX", "BNT", "ANT", "GNO", "RLC", "MLN", "GNT", "WINGS", "ICN", "MYST", "CFI", "PTOY", "MAID", "STRAT", "WAVES", "1ST", "DGB", "HMQ", "SNGLS", "AMP", "REP", "TRST", "LSK", "TIME", "LUN", "INCNT", "ETH", "SNT", "QRL", "XCP", "GUP", "TKN", "MCO", "OMNI", "FCT", "NXT", "BTS", "VIA", "EOS");
			return (!in_array($strCoinName, $strShitCoinArray));
		}

		protected function getImagePath($strCurrencyArray){
			$strCurrencyImage = "/media/none";
			if (array_key_exists("ImageUrl", $strCurrencyArray))
				$strCurrencyImage = $strCurrencyArray["ImageUrl"];
			return sprintf("https://www.cryptocompare.com%s?anchor=center&mode=crop&width=32&height=32", $strCurrencyImage);
		}

		protected function isCommandLineInterface() {
			return (php_sapi_name() === "cli");
		}

		protected function removeUndesiredFeatures($strDataExchangeArray) {
			if (is_array($strDataExchangeArray) && array_key_exists("Data", $strDataExchangeArray) && array_key_exists("Exchanges", $strDataExchangeArray["Data"])) {

				$intLastHour = time() - (60 * 60);
				$strSnapShotArray = $this->removeElementWithValue($strDataExchangeArray["Data"]["Exchanges"], "LASTUPDATE", $intLastHour);
				$strSnapShotArray = $this->removeElementWithValue($strSnapShotArray, "PRICE", 0);
				$strSnapShotArray = $this->removeElementWithValue($strSnapShotArray, "VOLUME24HOURTO", 2, "<");
				$strSnapShotArray = $this->removeShitExchanges($strSnapShotArray);
			}
			else {
				$strSnapShotArray =	array();
			}

			return $strSnapShotArray;
		}

		protected function hasExchangesAndVolume($strDataExchangeArray) {
			return (is_array($strDataExchangeArray) && count($strDataExchangeArray) > 1);
		}

		public function checkSpreadCoins($intMinPercent = 10, $blnIgnorePreCheckedShitCoins = true) {

			// Get all coins existent in all markets
			$objCoinListArray = $this->objCryptoCompareApi->getCoinList();
			
			if (is_array($objCoinListArray) && array_key_exists("Data", $objCoinListArray)) {
				foreach($objCoinListArray["Data"] as $strCurrencyArray) {
					if (is_array($strCurrencyArray) && array_key_exists("Name", $strCurrencyArray)) {
						
						// Ignore some currencies
						if ($blnIgnorePreCheckedShitCoins && $this->isShitCoin($strCurrencyArray["Name"])) {
							continue;
						}

						$objSnapShotArray = $this->objCryptoCompareApi->getCoinSnapShot($strCurrencyArray["Name"], "BTC");
						$objClearSnapShotArray = $this->removeUndesiredFeatures($objSnapShotArray);

						if ($this->hasExchangesAndVolume($objClearSnapShotArray)) {

							$fltLowVal = 0.0;
							$fltHighVal = 0.0;
							$strLowMarket = '';
							$strHighMarket = '';
							foreach ($objClearSnapShotArray as $strExchangeArray) {
								$fltPrice = floatval($strExchangeArray["PRICE"]);

								if ($fltLowVal == 0 && $fltHighVal == 0) {
									$fltLowVal = $fltPrice;
									$fltHighVal = $fltPrice;
									$strLowMarket = $strExchangeArray["MARKET"];
									$strHighMarket = $strExchangeArray["MARKET"];									
								}

								if ($fltPrice < $fltLowVal) {
									$fltLowVal = $fltPrice;
									$strLowMarket = $strExchangeArray["MARKET"];
								}
								
								if ($fltPrice > $fltHighVal) {
									$fltHighVal = $fltPrice;
									$strHighMarket = $strExchangeArray["MARKET"];
								}
							}

							if ($fltLowVal != 0 && $fltHighVal != 0 && ($strLowMarket != $strHighMarket)) {
								$fltPercentDiff = round((($fltHighVal - $fltLowVal) / $fltLowVal), 4) * 100;
								if ($fltPercentDiff > $intMinPercent) {
									if ($this->isCommandLineInterface()) {
										printf("%s - %f (%s) vs. %f (%s) %01.2f %%\r\n", $strCurrencyArray["Name"], $fltHighVal, $strHighMarket, $fltLowVal, $strLowMarket, $fltPercentDiff);
									}
									else {
										$strImageUrl = $this->getImagePath($strCurrencyArray);
										printf("<p style='font-family:Verdana;font-size:10px'><img src='%s' style='width:16px' /> %s - %f (%s) %f vs. (%s) %01.2f %%</p>", $strImageUrl, $strCurrencyArray["Name"], $fltHighVal, $strHighMarket, $fltLowVal, $strLowMarket, $fltPercentDiff);
									}
								}
							}
						}
					}
				}
			}
		}

	}

?>	