<?php
/*
Plugin Name: Email Sender to Shoppers
Plugin URI: http://judicia.com.br
Description: A plugin to send scheduled emails to albertobezerra.com.br/judicia.com.br to allow de shoppers avaluate the purchased books
Version: 1.0
Author: Jadson Ribeiro da Silva
Author URI: http://exemplo.org
License: GPLv2
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
        $time_activation = time();
        update_option('email_sender_time_activation', $time_activation);
        //http://holisticnetworking.net/blog/2008/10/18/scheduling-with-wordpress-cron-functions/
        wp_schedule_event(time(), 'hourly', 'send_email_event');
    } 
}

function email_sender_to_shoppers_deactivate() {
    if(wp_next_scheduled('send_email_event')){
        update_option('email_sender_time_activation', '0000');
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
    return "cursos@albertobezerra.com.br";
}
 
add_filter ("wp_mail_from", "change_mail_from");
 
// SET EMAIL FROM NAME
function change_from_name() {
    return "Prof. Alberto Bezerra";
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

    // var_dump($args);

    $loop = new WP_Query($args);
    $content = "";
    $count_items = 0;

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
            
            $template_products = $template_products.''.file_get_contents($url_plugin.'/template_email_avaliation_product.html');
            $template_products = str_replace('{product_image}', $product_image, $template_products);
            $template_products = str_replace('{product_link}', $product_link, $template_products);
            $template_products = str_replace('{product_id}', $item_id, $template_products);
            $template_products = str_replace('{product_name}', $item_name, $template_products);

            $count_items++;
        }
        
        $template_footer = file_get_contents($url_plugin.'/template_email_avaliation_footer.html');
        
        $template = $template_header.''.$template_products.''.$template_footer;
        echo $template;

        $header = "Alberto Bezerra <cursos@albertobezerra.com.br>";
        $to = "jds_tj@hotmail.com";
        $subject = "Agradecimentos - Alberto Bezerra";
        $data = getdate();

        if(wp_mail($to, $subject, $template, $header)) {
            update_option('email_sender_activeted', 'email sent sucessfully on '.$data['hours'].':'.$data['minutes']);
            echo 'Email sent sucessfully';
        } else {
            echo 'Error on sent email';
            update_option('email_sender_activeted', 'error on sent email on '.$data['hours'].':'.$data['minutes']);
        }

    }

    return $content.'<br/> Numero de Items '.$count_items.'<br/> Data '.$date['day'].'/'.$date['month'].'/'.$date['year'];
}

function print_date($period){
    
    $today = getdate();
    
    $day = $today['mday']+6;
    $month = $today['mon']-1;
    $year = $today['year'];
    
    if($period == 'monthly') {
        if($month == 1) {
            $month = 12;
            $year--;
        } else {
            $month--;
        }
    } elseif($period == 'yearly') {
        $year--;
    }
    
    $date = array(
            'year'  => $year,
            'month' => $month,
            'day'   => $day,
        );

    return $date; 
}

function print_order_on_period() {
    $period = print_date('monthly');
    
    return print_all_comerce_orders($period);
}

add_shortcode('print_order_on_period','print_order_on_period');

function sanitize_name($user_name) {
   
    if($name = strstr($user_name, "@", TRUE)) {
        return $name;
    }
    return $user_name;
}
?>

