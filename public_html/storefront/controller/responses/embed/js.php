	<?php  
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2015 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/
if (! defined ( 'DIR_CORE' )) {
	header ( 'Location: static_pages/' );
}
class ControllerResponsesEmbedJS extends AController {

	public $data = array();

	/**
	 * NOTE: main() is bootup method
	 */
	public function main() {
		$this->extensions->hk_InitData($this, __FUNCTION__);

		//check is third-party cookie allowed
		if(!isset($this->request->cookie[SESSION_ID])){
			$this->data['test_cookie'] = true;
		}

		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$this->view->assign('base', HTTPS_SERVER);
		} else {
			$this->view->assign('base', HTTP_SERVER);
		}

		$this->view->assign('store_name', $this->config->get('store_name'));
		$icon_rl = $this->config->get('config_icon');
		if($icon_rl){		
			//see if we have a resource ID or path
			if (is_numeric($icon_rl)) {
				$resource = new AResource('image');
			    $image_data = $resource->getResource( $icon_rl );
			    if ( is_file(DIR_RESOURCE . $image_data['image']) ) {
			    	$icon_rl = 'resources/'.$image_data['image'];
			    } else {
			    	$icon_rl = $image_data['resource_code'];
			    }
			} else	
			if(!is_file(DIR_RESOURCE.$icon_rl)){
				$icon_rl ='';
			}
		}
		$this->view->assign('icon', $icon_rl);
		
		$this->data['abc_embed_test_cookie_url'] = $this->html->getURL('r/embed/js/testcookie','&timestamp='.time());

		$this->loadLanguage('common/header');
    	$this->data['account'] =  $this->html->getSecureURL('account/account');
		$this->data['logged'] =  $this->customer->isLogged();
		$this->data['login'] =  $this->html->getSecureURL('account/login');
		$this->data['logout'] =  $this->html->getURL('account/logout');
    	$this->data['cart'] =  $this->html->getURL('checkout/cart');
		$this->data['checkout'] =  $this->html->getSecureURL('checkout/shipping');

		$this->view->setTemplate( 'embed/js.tpl' );
		$this->view->batchAssign($this->data);
		$this->_set_js_http_headers();
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);		
	}

	/**
	 * Method fill data into embedded block with single product
	 */
	public function product() {
		$this->extensions->hk_InitData($this, __FUNCTION__);

		$product_id = (int)$this->request->get['product_id'];
		if(!$product_id){
			return null;
		}

		$this->data['target'] = $this->request->get['target'];
		if(!$this->data['target']){
			return null;
		}

		$this->loadModel('catalog/product');
		$product_info = $this->model_catalog_product->getProduct($product_id);

		//can not locate product? get out
		if (!$product_info) { 
			return null;
		}

		$resource = new AResource('image');
		$product_info['thumbnail'] =  $resource->getMainThumb('products',
				$product_id,
			(int)$this->config->get('config_image_thumb_width'),
			(int)$this->config->get('config_image_thumb_height'),
		    true);

		$product_info['price'] = $this->currency->format($product_info['price']);

		if ($product_info['final_price'] && $product_info['final_price']!=$product_info['price']) {
			$product_info['special'] = $this->currency->format($product_info['final_price']);
		}


		$product_info['button_addtocart'] = $this->html->buildElement(
				array(
						'type' => 'button',
						'name' => 'addtocart'.$product_id,
						'text' => $this->language->get('button_add_to_cart'),

						'attr' => 'data-product-id="'.$product_id.'" data-href = "'. $this->html->getURL('r/embed/js/addtocart', '&product_id='.$product_id).'"'
					)
		);

		$product_info['quantity'] = $this->html->buildElement(
				array(
						'type' => 'input',
						'name' => 'quantity',
						'value' => $product_info['minimum'],
						'style' => 'short'
					)
		);




		$this->data['product'] = $product_info;
		$this->data['product_details_url'] = $this->html->getURL(
															'r/product/product',
															'&product_id=' . $product_id);

		$this->view->setTemplate( 'embed/js_product.tpl' );

		$this->view->batchAssign($this->language->getASet('product/product'));
		$this->view->batchAssign($this->data);
		$this->_set_js_http_headers();
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);		
	}

	public function testCookie() {
		$this->extensions->hk_InitData($this, __FUNCTION__);

		$this->data['allowed'] = $this->request->cookie[SESSION_ID] ? true : false;
		$this->data['abc_token'] = session_id();


		$this->view->setTemplate( 'embed/js_cookie_check.tpl' );
		$this->_set_js_http_headers();
		$this->view->batchAssign($this->data);
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
	}
	
	public function cart() {
		$this->extensions->hk_InitData($this, __FUNCTION__);

		$this->loadLanguage('blocks/cart');
		
		$this->data['cart_count'] = $this->cart->countProducts();
		$this->data['cart_url'] = $this->html->getSecureURL('r/checkout/cart/embed');
	
		$this->view->setTemplate( 'embed/js_cart.tpl' );
		$this->_set_js_http_headers();
		$this->view->batchAssign($this->data);
        $this->processTemplate();

        //init controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
	}
	public function addtocart() {
		//init controller data
		$this->extensions->hk_InitData($this, __FUNCTION__);

		$this->loadModel('catalog/product');
		$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
		if($product_info){
			$this->cart->add($this->request->get['product_id'], ($product_info['minimum'] ? $product_info['minimum'] : 1));
		}

		$this->extensions->hk_UpdateData($this, __FUNCTION__);
	}

	private function _set_js_http_headers(){
		$this->response->addHeader('Content-Type: text/javascript; charset=UTF-8');
		//$this->response->addHeader('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() - 10));
	}
  	
}