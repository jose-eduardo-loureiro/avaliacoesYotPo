<?php
class ModelExtensionModuleAvaliacoesYotpo extends Model {
	const BULK_SIZE = 1000;
	const HTTP_REQUEST_TIMEOUT = 3;
	const PAST_ORDERS_LIMIT = 10000;
	const PAST_ORDERS_DAYS_BACK = 90;
	const YOTPO_API_URL = 'https://api.yotpo.com';
	const YOTPO_OAUTH_TOKEN_URL = 'https://api.yotpo.com/oauth/token';

	public function __construct($registry){
		parent::__construct($registry);
		$this->config->load('isenselabs/yotpo');

	}


	public function getReviews($product_id = ''){
		// carrega as condigurações padrão do modulo
		$this->load->model('setting/setting');
		// carrega as chaves do yotpo
		$app_key = $this->config->get('module_avaliacoesyotpo_chave');
		// seleciona a chave correspondente a loja do pedido
		$app_key = $app_key[$this->config->get('config_store_id')];
		// Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		curl_setopt_array($curl, [
		   CURLOPT_RETURNTRANSFER => 1,
		   CURLOPT_URL => 'https://api.yotpo.com/v1/widget/'. $app_key .'/products/'.$product_id.'/reviews.json',
		]);
		// Send the request & save response to $resp
		$data = curl_exec($curl);
		$data = json_decode($data);
		// Close request to clear up some resources
		curl_close($curl);
		return $data->response->bottomline;
	}

	public function LojaHabilidataAvaliacoesYotPo($Idloja){
    	$this->load->model('setting/setting');
    	// se o modulo de avaliações YotPo estiver ativo		
        if (!in_array($Idloja,$this->config->get('module_avaliacoesyotpo_stores'))) {
            return false;
        } else {
        	return true;
        }
   	}

	public function make_single_map($order_id, $idLoja) {
		// carrega as condigurações padrão do modulo
		$this->load->model('setting/setting');
		// carrega as chaves do yotpo
		$app_key = $this->config->get('module_avaliacoesyotpo_chave');
		// seleciona a chave correspondente a loja do pedido
		$app_key = $app_key[$idLoja];
		// carrega os segredos das chaves do yotpo
		$secret_token = $this->config->get('module_avaliacoesyotpo_secret');
		// seleciona o segredo da chave correspondente a loja do pedido
		$secret_token = $secret_token[$idLoja];

		// caso a chave e o segredo não estejam em branco		
		if(!empty($app_key) && !empty($secret_token)) {
			$this->load->model('checkout/order');
			$order = $this->model_checkout_order->getOrder($order_id);
			$token = $this->grantOauthAccess($app_key, $secret_token);

			if(!empty($token) && is_string($token)) {
				$data = $this->get_map_data($order);
				$data['utoken'] = $token;
				$this->makePostRequest(self::YOTPO_API_URL . '/apps/' . $app_key . "/purchases/", $data);
			}	
		}
	}

	private function get_map_data($order) {
		$data = array();
		$customer = NULL;
		$data["order_date"] = $order['date_added'];
		$data["email"] = $order['email'];
		$data["customer_name"] = $order['firstname'] . ' ' . $order['lastname'];
		$data["order_id"] = $order['order_id'];
		$data['platform'] = 'opencart';
		$data["currency_iso"] = $order['currency_code'];

		$products_arr = array();

		$this->load->model('checkout/order');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$products = $this->model_checkout_order->getOrderProducts($order['order_id']);
		foreach ($products as $order_product) {

			$full_product = $this->model_catalog_product->getProduct($order_product['product_id']);
			$product_data = array();
			if ($full_product['image']) {
				$product_data['image'] = $this->model_tool_image->resize($full_product['image'], 500, 500);
			} else {
				$product_data['image'] = '';
			}
			$product_data['url'] = $this->url->link('product/product', 'product_id='. $order_product['product_id'],true);			
			
			$product_data['name'] = strip_tags(html_entity_decode($full_product['name']));
			$product_data['description'] = strip_tags(html_entity_decode($full_product['description']));
			$product_data['price'] = $order_product['price'];

			$products_arr[$order_product['product_id']] = $product_data;
		}
		$data['products'] = $products_arr;
		return $data;
	}

	public function makePostRequest($url, $data)
	{
		$ch = curl_init($url);
		$json_data = json_encode($data);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,self::HTTP_REQUEST_TIMEOUT);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-length: '.strlen($json_data)));
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		curl_close ($ch);
		return json_decode($result, true);
	}

    public function makeGetRequest($url, $vars = array())
    {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,self::HTTP_REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close ($ch);
        return json_decode($result, true);
    }

	public function grantOauthAccess($app_key, $secret_token)
	{
		$yotpo_options = array('client_id' => $app_key, 'client_secret' => $secret_token, 'grant_type' => 'client_credentials');
		$result = $this->makePostRequest(self::YOTPO_OAUTH_TOKEN_URL, $yotpo_options, self::HTTP_REQUEST_TIMEOUT, false);
		return isset($result['access_token']) ? $result['access_token'] : null;
	}
}

