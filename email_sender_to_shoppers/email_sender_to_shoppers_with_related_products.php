<?php

function print_all_comerce_orders_related_products($date) {

	$args = array(
        'post_type' => 'shop_order',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'shop_order_status',
                'field' => 'slug',
                'terms' => array('completed')
            )
        ),
        'date_query' => $date
    );

    // var_dump($args);

    $loop = new WP_Query($args);
    $content = "";
    $count = 0;
    $count_items = 0;

    while($loop->have_posts()) {

        $count++;    
    	$loop->the_post();
    	$order_id = $loop->post->ID;
    	$order = new WC_Order($order_id);

        $order_date = $order->order_date;
        $user_id = $order->__get('user_id');
        $user = get_user_by('id', $user_id);
        $user_email = $user->user_email;
        $user_name = $user->display_name;

        $user_name = sanitize_name($user_name);

        $template_products = "";
        $template_header = "";
        $template_footer = "";

        $url_plugin = plugins_url( '', __FILE__ );

        $template_header = file_get_contents($url_plugin.'/template_email_avaliation_header.html');
        $template_header = str_replace('{plugin_url}', $url_plugin, $template_header);
        $template_header = str_replace('{user_name}', $user_name, $template_header);
        $template_header = str_replace('{order_id}', $order_id, $template_header);
            
        foreach($order->get_items() as $item) {
            $item_name = $item['name'];
            $item_id = $item['product_id'];

            $product = new WC_Product($item_id);
            $product_image = $product->get_image();
            $product_link = $product->get_permalink();

            $line = $item_name.' '.$item_id.' <br/>' ;
            $content = $content .' '.$order_id.' '.$user_id.' '.$user_name.' '.$user_email.' '.$order_date.' '.$line;
            $count_items++;

            $template_products = $template_products.''.file_get_contents($url_plugin.'/template_email_avaliation_product.html');
            $template_products = str_replace('{product_image}', $product_image, $template_products);
            $template_products = str_replace('{product_link}', $product_link, $template_products);
            $template_products = str_replace('{product_id}', $item_id, $template_products);
            $template_products = str_replace('{product_name}', $item_name, $template_products);

            $product_relateds = $product->get_related(3);
            
            $template_related_products = "";
            foreach ($product_relateds as $product_related_id) {
                
                $product_related = new WC_Product($product_related_id);
                $product_related_name = $product_related->get_title();
                $product_related_image = $product_related->get_image();
                $product_related_link = $product_related->get_permalink();
                echo '<br/>';
                echo $product_related_id.' '.$product_related_name;
                echo '<br/>';
                $template_related_products = $template_related_products.''.file_get_contents($url_plugin.'/template_email_avaliation_relateds_products.html');
                $template_related_products = str_replace('{related_products_name}', $product_related_name, $template_related_products);
                $template_related_products = str_replace('{related_products_image}', $product_related_image, $template_related_products);
                $template_related_products = str_replace('{related_products_link}', $product_related_link, $template_related_products);
            }
    	}
        
        $template_footer = file_get_contents($url_plugin.'/template_email_avaliation_footer.html');
		
        $template = $template_header.''.$template_products.'<br/> Produtos que talvez lhe interesse <br/>'.$template_related_products.''.$template_footer;
        echo $template;

        $header = "Alberto Bezerra <cursos@albertobezerra.com.br>";
        $to = "jds_tj@hotmail.com";
        $subject = "Agradecimentos - Alberto Bezerra";
        if(wp_mail($to, $subject, $template, $header)) {
            echo 'Email sent sucessfully';
        } else {
            echo 'Error on sent email';
        }

    }


    return $content.' <br/> Numero de orders '.$count .'<br/> Numero de Items '.$count_items.'<br/> Data '.$date['day'].'/'.$date['month'].'/'.$date['year'];
}


add_shortcode('print_vd', 'print_all_comerce_orders_related_products');

?>