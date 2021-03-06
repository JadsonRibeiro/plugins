<?php
/*
Plugin Name: Email Sender to Shoppers
Plugin URI: http://judicia.com.br
Description: A plugin to send scheduled emails to shoppers avaluate the purchased products
Version: 1.0
Author: Jadson Ribeiro da Silva
*/
?>

<?php

register_activation_hook( __FILE__, 'email_sender_to_shoppers_activate' );
add_action('send_email_event', 'print_order_on_period');

/**
* Function to Activate plugin
*/
function email_sender_to_shoppers_activate() {
    if(!wp_next_scheduled('send_email_event')){
        $time = rand(10, 20);
        update_option('email_sender_time_activation', $time_activation);
        wp_schedule_event(time()+$time, 'daily', 'send_email_event');
    } 
}

function email_sender_to_shoppers_deactivate() {
    if(wp_next_scheduled('send_email_event')){
        wp_clear_scheduled_hook( 'send_email_event' );
    }

}
register_deactivation_hook( __FILE__, 'email_sender_to_shoppers_deactivate');

// SET EMAIL TYPE TO HTML
function set_content_type( $content_type ) {
    return 'text/html';
}

add_filter( 'wp_mail_content_type', 'set_content_type' );

// SET EMAIL FROM ADDRESS
function change_mail_from() {
    return "email_tal@email.com.br";
}
 
add_filter ("wp_mail_from", "change_mail_from");
 
// SET EMAIL FROM NAME
function change_from_name() {
    return "Prof. Fulano de Tal";
}
 
add_filter ("wp_mail_from_name", "change_from_name");

/**
 * Function to print all comerce orders
 */

function print_all_comerce_orders($date) {

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

    $loop = new WP_Query($args);
    $content = "";

    wp_reset_postdata();

    while($loop->have_posts()) {
  
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

        $url_plugin = plugins_url( '', __FILE__ ).'/includes';

        $template_header = file_get_contents($url_plugin.'/template_email_avaliation_header.html');
        $template_header = str_replace('{plugin_url}', $url_plugin, $template_header);
        $template_header = str_replace('{user_name}', $user_name, $template_header);
        $template_header = str_replace('{order_id}', $order_id, $template_header);
            
        foreach($order->get_items() as $item) {
            $item_name = $item['name'];
            $item_id = $item['product_id'];

            $product = new WC_Product($item_id);
            $product_image = $product->get_image(array(300,300));
            $product_link = $product->get_permalink();
            
            $template_products = $template_products.''.file_get_contents($url_plugin.'/template_email_avaliation_product.html');
            $template_products = str_replace('{product_image}', $product_image, $template_products);
            $template_products = str_replace('{product_link}', $product_link, $template_products);
            $template_products = str_replace('{product_id}', $item_id, $template_products);
            $template_products = str_replace('{product_name}', $item_name, $template_products);

        }
        
        $template_footer = file_get_contents($url_plugin.'/template_email_avaliation_footer.html');
        
        $template = $template_header.''.$template_products.''.$template_footer;
        //echo $template;

        //email para teste
        $to = array("email@emailteste.com");
        $subject = "[TEST] O que achou do nosso produto?";
        $data = getdate();

        //LOG
        $plugin_path = plugin_dir_path( __FILE__ );
        $log_file = fopen($plugin_path.'log.txt', 'a+');
        chmod ($plugin_path.'log.txt', 0755);

        if(wp_mail($to, $subject, $template)) {
            fwrite($log_file, 'o.id: '.$order_id.' email sent sucessfully to '.$user_email.'on '.$data['day'].'/'.$data['month'].'/'.$data['year'].' '. $data['hours'].':'.$data['minutes'].':'.$data['seconds']."\n");            
        } else {
            fwrite($log_file, 'o.id: '.$order_id.' error on sent email to '.$user_email.'on '.$data['mday'].'/'.$data['mon'].'/'.$data['year'].' '. $data['hours'].':'.$data['minutes'].':'.$data['seconds']."\n");
        }

    }
}

function print_all_comerce_orders_emails($date) {

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

    $loop = new WP_Query($args);
    $content = "";

    wp_reset_postdata();

    while($loop->have_posts()) {
  
        $loop->the_post();
        $order_id = $loop->post->ID;
        $order = new WC_Order($order_id);

        $order_date = $order->order_date;
        $user_id = $order->__get('user_id');
        $user = get_user_by('id', $user_id);
        $user_email = $user->user_email;
        $user_name = $user->display_name;

        $user_name = sanitize_name($user_name);

        $template_thank_email = "";

        $url_plugin = plugins_url( '', __FILE__ ).'/includes';

        $template_thank_email = file_get_contents($url_plugin.'/template_email_thank.html');

        $template_email_thank = str_replace('{user_name}', $user_name, $template_thank_email);

        foreach($order->get_items() as $item) {
            $item_name = $item['name'];
        }
        $template_thank_email = str_replace('{product_name}', $item_name, $template_thank_email);

        $to = 'email@emailteste.com';
        $subject = '[TEST] Agredecimentos pela compra';
        wp_mail($to, $subject, $template_thank_email);
    }
}

function print_date($period){


    $before = '';

    if($period == 'monthly') {
        $before = "-1 month";
    } elseif ($period == 'fortnightly') {
        $before = "-15 days";
    }
    $today = getdate(strtotime($before));
    
    $date = array(
        'day'  => $today['mday'],
        'month'=> $today['mon'],
        'year' => $today['year'],
    );

    return $date; 
}

function print_order_on_period() {
    $period_email_coment = print_date('monthly');
    $period_email_thank = print_date('fortnightly');

    print_all_comerce_orders_emails($period_email_thank);
    
    return print_all_comerce_orders($period_email_coment);
}

function sanitize_name($user_name) {
   
    if($name = strstr($user_name, "@", TRUE)) {
        return $name;
    }
    return $user_name;
}
?>

