<?php
class ModelExtensionPaymentApurata extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/apurata');

		$status = true;

		$landing_config = $this->get_landing_config();

		print_r('Hola rodrigo');

		if (!$this->should_hide_apurata_gateway($total)) {
			$method_data = array(
				'code'       => 'apurata',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => '1'
			);
		}

		return $method_data;
	}

	function should_hide_apurata_gateway($total) {
		$isHttps =
			$_SERVER['HTTPS']
			?? $_SERVER['REQUEST_SCHEME']
			?? $_SERVER['HTTP_X_FORWARDED_PROTO']
			?? null;

		$isHttps = $isHttps && (
			strcasecmp('1', $isHttps) == 0
			|| strcasecmp('on', $isHttps) == 0
			|| strcasecmp('https', $isHttps) == 0
		);

		if (!$this->config->get('payment_apurata_allow_http') && !$isHttps) {
			return true;
		}

		if ($this->session->data['currency'] != 'USD') {
			return true;
		}

		$landing_config = $this->get_landing_config();
		
		if ($landing_config->min_amount > $total || $landing_config->max_amount < $total) {
			return true;
		}
		return false;
	}

	function get_landing_config() {
		list ($httpCode, $landing_config) = $this->make_curl_to_apurata("GET", "/pos/client/landing_config");
		$landing_config = json_decode($landing_config);
		return $landing_config;
	}

	function make_curl_to_apurata($method, $path) {
		// $method: "GET" or "POST"
		// $path: e.g. /pos/client/landing_config
		$ch = curl_init();
		$this->load->language('extension/payment/apurata');

		$url = $this->language->get('apurata_api_domain') . $path;
		curl_setopt($ch, CURLOPT_URL, $url);

		// Timeouts
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);    // seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 2); // seconds

		$headers = array("Authorization: Bearer " . $this->config->get('payment_apurata_client_secret'));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		if (strtoupper($method) == "GET") {
			curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
		} else if (strtoupper($method) == "POST") {
			curl_setopt($ch, CURLOPT_POST, TRUE);
		} else {
			throw new Exception("Method not supported: " . $method);
		}
		$ret = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpCode != 200) {
			error_log("Apurata responded with http_code ". $httpCode . " on " . $method . " to " . $url);
		}
		curl_close($ch);
		return array($httpCode, $ret);
	}
}