<?php
class ControllerExtensionModuleAvaliacoesYotpo extends Controller {

	private $error = array();
    
    // instala o modulo 
	public function install() {
		//carrega o modelo de configurações para eventos
        $this->load->model('setting/event');
        //adiciona um evento para ser chamado após alguma mudança no historico do pedido.
        $this->model_setting_event->addEvent('avaliacoesyotpo','catalog/model/checkout/order/addOrderHistory/after','extension/module/avaliacoesyotpo/eventAddOrderHistory');
    }
	
	// desinstala o módulo 
	public function uninstall() {
		// remove o evento que era chamado após alguma mudança no historico do pedido
		$this->model_setting_event->deleteEventByCode('avaliacoesyotpo');
	}

	public function index() {
		// carrega as informações de linguagem
		$this->load->language('extension/module/avaliacoesyotpo');
    	// Define o nome do módulo
    	$this->document->setTitle($this->language->get('heading_title'));
    	// Carrega o modelo de configurações dos módulos do opencart
    	$this->load->model('setting/setting');
    	// Se a chamada for POST e a validação estiver ok
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// Salvas as configurações para o módulo de avaliaçoes YotPo
			$this->model_setting_setting->editSetting('module_avaliacoesyotpo', $this->request->post);
			// carrega o texto de sucesso da chamada POST	
			$this->session->data['success'] = $this->language->get('text_success');
			// redireciona a chamada
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = false;
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
	  		'text' => $this->language->get('text_extension'),
	  		'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
			);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/avaliacoesyotpo', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);			

		$data['action'] = $this->url->link('extension/module/avaliacoesyotpo', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		
		// se a chamada for do tipo post, grava o status do modulo. Senão lê o status do modulo
		if (isset($this->request->post['module_avaliacoesyotpo_status'])) {
			$data['module_avaliacoesyotpo_status'] = $this->request->post['module_avaliacoesyotpo_status'];
		} else {
			$data['module_avaliacoesyotpo_status'] = $this->config->get('module_avaliacoesyotpo_status');
		}

		$campos = array(
            'chave' => array(0),
            'secret' => array(0),
            'stores' => array(0),
            'order_statuses' => array(0)
        );

		foreach ($campos as $campo => $valor) {
            if (!empty($valor)) {
                if (isset($this->request->post['module_avaliacoesyotpo_'.$campo])) {
                    $data['module_avaliacoesyotpo_'.$campo] = $this->request->post['module_avaliacoesyotpo_'.$campo];
                } else if ($this->config->get('module_avaliacoesyotpo_'.$campo)) {
                    $data['module_avaliacoesyotpo_'.$campo] = $this->config->get('module_avaliacoesyotpo_'.$campo);
                } else {
                    $data['module_avaliacoesyotpo_'.$campo] = $valor;
                }
            } else {
                if (isset($this->request->post['module_avaliacoesyotpo_'.$campo])) {
                    $data['module_avaliacoesyotpo_'.$campo] = $this->request->post['module_avaliacoesyotpo_'.$campo];
                } else {
                    $data['module_avaliacoesyotpo_'.$campo] = $this->config->get('module_avaliacoesyotpo_'.$campo);
                }
            }
        }
        // carrega as informações sobre a loja principal
		$data['store_default'] = $this->config->get('config_name');
		// carrega as informações das lojas adicionais caso existam
        $this->load->model('setting/store');
        $data['stores'] = $this->model_setting_store->getStores();

        // carrega as informações dos status dos pedidos que devem chamar
        $this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// carrega as demais informações
		$data['user_token'] = $this->session->data['user_token'];
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/avaliacoesyotpo', $data));		
	}	

	// valida o modulo
	protected function validate() {
		// Se o usuario não tiver permissão, gera um erro;
		// if (!$this->user->hasPermission('modify', 'extension/module/avaliacoesyotpo')) {
		//	$this->error['warning'] = $this->language->get('error_permission');
		//}
		//return !$this->error;
		return true;
	}

}