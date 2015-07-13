<?php

/**
 * Search Engine
 *
 * Search all necessary data on database
 */

interface interfaceExportEmailSearch {
	static function getLoopOrderPosts($status, $date);
	static function getOrderByProduct($product_name, $status, $date);
	static function getOrdersbyCustomerEmail($email);
	static function getEmailByProduct($product_name, $status, $date);
}

class ExportEmailSearch implements interfaceExportEmailSearch {

	/**
	 * Get WP_Query object whith all Orders Posts (successfuly)
	 *
	 * @param array $status - order status
	 * @param array $date   - last order date
	 *
	 * @return WC_query object
 	 */
	static function getLoopOrderPosts($status = array('completed'), $date = array()) {
		$args = array();

		$args = array(
			'post_type' => 'shop_order',
	        'post_status' => 'publish',
	        'posts_per_page' => -1,
	        'tax_query' => array(
	            array(
	                'taxonomy' => 'shop_order_status',
	                'field' => 'slug',
	                'terms' => $status
	            )
	        ),
	        'date_query' => $date
		);

		$loop = new WP_Query($args);

		wp_reset_postdata();

		return $loop;
	}


	/**
	 * Get Orders with an specific product (successfuly)
	 *
	 * @param string   $product_name - The product name
	 * @param array    $status       - Order status
	 * @param array    $date         - Last date order
	 * 
	 * @return array                 - Product Orders
	 */
	static function getOrderByProduct($product_name, $status = array('completed'), $date = array()) {
		// Get all orders
		$loop = self::getLoopOrderPosts($status, $date);
		$array_orders = array();

		while($loop->have_posts()){

			$loop->the_post();

			$order_id = $loop->post->ID;
			$order = new WC_Order($order_id);
			
			// Verifies product is in this order
			if(self::productInOrder($product_name, $order))
				$array_orders[] = $order;
		}		

		return $array_orders;

	}
	
	static function getOrdersbyCustomerEmail($email) {

	}

	/**
	 * Get Emails of orders with a specific product
	 * 
	 * @param string $product_name - Name of the product
	 * @param array  $status       - Order Status
	 * @param array  $date         - Date of the last order
	 *
	 * @return array               - Emails
	 */
	static function getEmailByProduct($product_name, $status = array('completed'), $date = array()) {
		// Get orders with this product
		$product_orders = self::getOrderByProduct($product_name, $status, $date);
		$emails = array();
		$count = 0;

		foreach ($product_orders as $order) {
			$user_id = $order->__get('user_id');
			$user = get_user_by('id', $user_id);
			$emails[] = $user->user_email;
		}

		return $emails;
	}
	
	static function searchEmails($product, $status = 'completed') {
		$emails = array();
		return $emails;
	}

	/**
	 * Verifies that book is in order (successfuly)
	 *
	 * @param string   $product_name - Name of the product
	 * @param WC_Order $order        - An  WC order
	 *
	 * @return boolean
	 */
	private static function productInOrder($product_name, $order){
		foreach ($order->get_items() as $item) {
			if($item['name'] == $product_name)
				return TRUE;
		}
		return FALSE;
	}
}