<?php

use Journal3\Opencart\Controller;

class ControllerExtensionModuleAvaliacoesYotPo extends Controller {

	public function eventAddOrderHistory($route,$data) {	

    	// Carrega o modelo de configurações dos módulos do opencart
    	$this->load->model('setting/setting');
    	// se o modulo de avaliações YotPo estiver ativo
    	If ($this->config->get('module_avaliacoesyotpo_status')){
			$this->load->model('checkout/order'); // call this only if this model is not yet instantiated!
			$meu_pedido = $this->model_checkout_order->getOrder($data[0]);	
			// verifica se a loja deve enviar avaliações pelo YotPo
			If ( $this->LojaHabilidataAvaliacoesYotPo($meu_pedido['store_id'])) {
				// se o status do pedido estiver configurado para processar a avaliação pelo YotPo
				if ( $this->AvaliacoesYotPoProcessaOrderStatus($data[1])) {
					// carrego o modelo de dados
					$this->load->model('extension/module/avaliacoesyotpo');
					// processa o pedido enviando para avaliação pelo YotPo
					$this->model_extension_module_avaliacoesyotpo->make_single_map($data[0],$meu_pedido['store_id']);
				}
			}
    	}
	}

	public function mostrarAvaliacoesYotPo() {		
    	// Carrega o modelo de configurações dos módulos do opencart
    	$this->load->model('setting/setting');
    	// se o modulo de avaliações YotPo estiver ativo
    	If ($this->config->get('module_avaliacoesyotpo_status')){
 			if (isset($this->request->get['product_id'])) {
				$this->load->model('catalog/product');
				$this->load->model('tool/image');
				$product = $this->model_catalog_product->getProduct($this->request->get['product_id']);
				return '<div class="yotpo yotpo-main-widget" data-lang="pt" data-product-id="'.$product['product_id'].'" data-name="'.$product['name'].'" data-url="'.$this->url->link('product/product', 'product_id='.$this->request->get['product_id'],true).'" data-description="'.preg_replace( "/\s+/", " ",(trim(substr(strip_tags(html_entity_decode($product['description'])),0,1000)))).'" data-image-url="'.$this->model_tool_image->resize($product['image'],500,500).'"> </div>';
			};
		}
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

	public function AvaliacoesYotPoProcessaOrderStatus($IdStatus){
    	$this->load->model('setting/setting');
    	// verifica se o status de pedido informado deve acionar o processamento da avaliação YotPo
        if (!in_array($IdStatus, $this->config->get('module_avaliacoesyotpo_order_statuses'))) {
            return false;
        } else {
        	return true;
        }
   	}   	

}