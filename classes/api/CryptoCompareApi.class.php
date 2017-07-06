<?php

	class CryptoCompareApi {

		protected $strMinApiUrl = "https://min-api.cryptocompare.com/data";
		protected $strPublicApiUrl = "https://www.cryptocompare.com/api/data";
		
		private function query(array $strReq = array()) {
	 
			// generate a nonce to avoid problems with 32bit systems
			$intMicroTime = explode(" ", microtime());
			$strReq["nonce"] = $intMicroTime[1].substr($intMicroTime[0], 2, 6);
		 
			// generate the POST data string
			$strPostData = http_build_query($strReq, "", "&");
			$strSign = hash_hmac("sha512", $strPostData, $strSecret);
		 
			// curl handle (initialize if required)
			static $objCurlHandle = null;
			if (is_null($objCurlHandle)) {
				$objCurlHandle = curl_init();
				curl_setopt($objCurlHandle, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($objCurlHandle, CURLOPT_USERAGENT, 
					"Mozilla/4.0 (compatible; Poloniex PHP bot; ".php_uname('a')."; PHP/".phpversion().")"
				);
			}
			curl_setopt($objCurlHandle, CURLOPT_URL, $this->strTradingUrl);
			curl_setopt($objCurlHandle, CURLOPT_POSTFIELDS, $strPostData);
			curl_setopt($objCurlHandle, CURLOPT_HTTPHEADER, $objHeadersArray);
			curl_setopt($objCurlHandle, CURLOPT_SSL_VERIFYPEER, FALSE);

			// run the query
			$objCurlSession = curl_exec($objCurlHandle);

			if ($objCurlSession === false) 
				throw new Exception("Curl error: ".curl_error($objCurlHandle));

			$objDecSession = json_decode($objCurlSession, true);
			if (!$objDecSession)
				return false;
			else
				return $objDecSession;
		}
		
		protected function retrieveJSON($strUrl) {
			$strOptionArray = array('http' =>
				array(
					'method'  => 'GET',
					'timeout' => 10 
				)
			);
			$objContext = stream_context_create($strOptionArray);
			$feed = file_get_contents($strUrl, false, $objContext);
			if ($feed === false) {
				$error = error_get_last();
				$feed = "HTTP request failed. Error was: " . $error['message'];
			}

			$json = json_decode($feed, true);
			return $json;
		}
		
		public function getPrice($strFrom, $strTo) {
			$objPriceArray = $this->retrieveJSON($this->strMinApiUrl . "/price?fsym=" . strtoupper($strFrom) . "&tsyms=" . strtoupper($strTo));
			return $objPriceArray;
		}

		public function getCoinSnapShot($strFrom, $strTo) {
			$objSnapShotArray = $this->retrieveJSON($this->strPublicApiUrl . "/coinsnapshot/?fsym=" . strtoupper($strFrom) . "&tsym=" . strtoupper($strTo));
			return $objSnapShotArray;
		}

		public function getCoinList() {
			$objCoinListArray = $this->retrieveJSON($this->strPublicApiUrl . "/coinlist/");
			return $objCoinListArray;
		}		

	}
?>
