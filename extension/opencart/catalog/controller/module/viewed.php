<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Viewed
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Module
 */
class Viewed extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $setting
	 *
	 * @return string
	 */
	public function index(array $setting): string {
		$this->load->language('extension/opencart/module/viewed');

		$data['axis'] = $setting['axis'];

		$data['products'] = [];

		$filter_data = [
			'sort'  => 'pd.name',
			'order' => 'ASC',
			'start' => 0,
			'limit' => $setting['limit']
		];

        // Product
        $this->load->model('catalog/product');

        // Image
        $this->load->model('tool/image');

        $products = array();


        if (isset($this->request->cookie['viewed'])) {
            $products = explode(',', $this->request->cookie['viewed']);
        } else if (isset($this->session->data['viewed'])) {
            $products = $this->session->data['viewed'];
        }

        if (isset($this->request->get['route']) && $this->request->get['route'] == 'product/product') {
            $product_id = $this->request->get['product_id'];
            $products = array_diff($products, array($product_id));
            array_unshift($products, $product_id);
            $domain = $this->request->server['SERVER_NAME'];
            setcookie('viewed', implode(',',$products), time() + 60 * 60 * 24 * 30, '/', $domain);
        }
        if(isset($this->request->get['product_id'])){
            $products = array_diff($products, array($this->request->get['product_id']));
        }

        if (empty($setting['limit'])) {
            $setting['limit'] = 4;
        }
        $products = array_slice($products, 0, (int)$setting['limit']);
        foreach ($products as $product_id){
            $product_info = $this->model_catalog_product->getProduct($product_id);
            if($product_info){
                if ($product_info['image']) {
                    $image = $this->model_tool_image->resize(html_entity_decode($product_info['image'], ENT_QUOTES, 'UTF-8'), $setting['width'], $setting['height']);
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                }

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }

                if ((float)$product_info['special']) {
                    $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $special = false;
                }

                if ($this->config->get('config_tax')) {
                    $tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
                } else {
                    $tax = false;
                }


                $product_data = [
                    'product_id'  => $product_info['product_id'],
                    'thumb'       => $image,
                    'name'        => $product_info['name'],
                    'description' => oc_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('config_product_description_length')) . '..',
                    'price'       => $price,
                    'special'     => $special,
                    'tax'         => $tax,
                    'minimum'     => $product_info['minimum'] > 0 ? $product_info['minimum'] : 1,
                    'rating'      => $product_info['rating'],
                    'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product_info['product_id'])
                ];

                $data['products'][] = $this->load->controller('product/thumb', $product_data);

            }
        }


		if ($data['products']) {
			return $this->load->view('extension/opencart/module/Viewed', $data);
		} else {
			return '';
		}
	}
}
