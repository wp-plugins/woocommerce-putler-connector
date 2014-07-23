<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_Putler_Connector' ) ) {
    
    class WooCommerce_Putler_Connector {
        
        private $name = 'woocommerce';

        public function __construct() {
            add_filter('putler_connector_get_order_count', array( &$this, 'get_order_count') );
            add_filter('putler_connector_get_orders', array( &$this, 'get_orders') );
        }

        public function get_order_count( $count )  {
            global $wpdb;
            $order_count = 0;
            
            $query_to_fetch_order_count = "SELECT COUNT(posts.ID) as id
                                            FROM {$wpdb->prefix}posts AS posts 
                                            WHERE posts.post_type LIKE 'shop_order' 
                                                AND posts.post_status IN ('publish','draft') ";
            
            $order_count_result = $wpdb->get_col( $query_to_fetch_order_count );
            
            if( !empty( $order_count_result ) ) {
                    $order_count = $order_count_result[0];
            }
            
            return $count + $order_count;
        }

        public function get_orders( $params )  {
            global $wpdb;
            $orders = array();

            //Code to get the last order sent
            
            $cond = '';

            if ( empty($params['order_id']) ) {
                $start_limit = (isset($params[ $this->name ]['start_limit'])) ? $params[ $this->name ]['start_limit'] : 0;
                $batch_limit = (isset($params['limit'])) ? $params['limit'] : 50;    
            } else {
                $start_limit = 0;
                $batch_limit = 1;
                $cond = 'AND posts.ID IN(' .intval($params['order_id']). ')'; 
            }
            
            
            //Code to get all the term_names along with the term_taxonomy_id in an array
            $query_order_status = "SELECT terms.name as order_status,
                                    term_taxonomy.term_taxonomy_id 
                                    FROM {$wpdb->prefix}term_taxonomy AS term_taxonomy
                                    JOIN {$wpdb->prefix}terms AS terms ON (terms.term_id = term_taxonomy.term_id)
                                    WHERE taxonomy LIKE 'shop_order_status'";
                                    
            $results_order_status = $wpdb->get_results( $query_order_status, 'ARRAY_A' );

            $order_status = array();

            foreach ($results_order_status as $results_order_status1) {
                    $order_status[$results_order_status1['term_taxonomy_id']] = $results_order_status1['order_status'];
            }
            
            $query_order_details = "SELECT posts.ID as id,
                                        posts.post_excerpt as order_note,
                                        date_format(posts.post_date_gmt,'%Y-%m-%d %T') AS date,
                                        date_format(posts.post_modified_gmt,'%Y-%m-%d %T') AS modified_date,
                                        term_relationships.term_taxonomy_id AS term_taxonomy_id
                                        FROM {$wpdb->prefix}posts AS posts 
                                            JOIN {$wpdb->prefix}term_relationships AS term_relationships 
                                                ON term_relationships.object_id = posts.ID 
                                        WHERE posts.post_type LIKE 'shop_order' 
                                                AND posts.post_status IN ('publish','draft')
                                                $cond
                                        GROUP BY posts.ID
                                        LIMIT ". $start_limit .",". $batch_limit;

             $results_order_details = $wpdb->get_results( $query_order_details, 'ARRAY_A' );
             $results_order_details_count = $wpdb->num_rows;
             
             if ( $results_order_details_count > 0 ) {
                 
                 $order_ids = array(); 
                 
                 foreach ( $results_order_details as $results_order_detail ) {
                     $order_ids[] = $results_order_detail['id'];
                 }
                 


                    //Query to get the Order_items
                    
                    $item_details = array();
                 
                    $query_cart_items = "SELECT orderitems.order_id,
                                                orderitems.order_item_name,
                                                orderitems.order_item_id,
                                                itemmeta.meta_value AS meta_value,
                                                itemmeta.meta_key AS meta_key
                                        FROM {$wpdb->prefix}woocommerce_order_items AS orderitems 
                                                JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta 
                                                    ON (orderitems.order_item_id = itemmeta.order_item_id)
                                        WHERE orderitems.order_item_type LIKE 'line_item'
                                            AND orderitems.order_id IN (". implode(",",$order_ids) .")
                                        GROUP BY orderitems.order_id, orderitems.order_item_id, meta_key";
                    $results_cart_items = $wpdb->get_results ( $query_cart_items, 'ARRAY_A' );
                    $results_cart_items_count = $wpdb->num_rows;

                    $variation_ids = array();
                    $product_ids = array();
                    
                    if ( $results_cart_items_count > 0 ) {
                        
                        foreach ( $results_cart_items as $cart_item ) {
                            $order_id = $cart_item['order_id']; 
                            $order_item_id = $cart_item['order_item_id'];
                            
                            if( !isset( $item_details[$order_id] ) ){
                                $item_details[$order_id] = array();
                                $item_details[$order_id]['tot_qty'] = 0;
                                $item_details[$order_id]['cart_items'] = array();
                            }
                            
                            if ( !isset($item_details[$order_id]['cart_items'][$order_item_id]) ) {
                                $item_details[$order_id]['cart_items'][$order_item_id] = array();
                                $item_details[$order_id]['tot_qty']++;
                                $item_details[$order_id]['cart_items'][$order_item_id]['product_name'] = $cart_item['order_item_name'];
                            } 
                            
                            $item_details[$order_id]['cart_items'][$order_item_id][$cart_item['meta_key']] = $cart_item['meta_value'];
                    
                            if ( $cart_item['meta_key'] == "_variation_id" && !empty($cart_item['meta_value']) ) {
                                $variation_ids [] = $cart_item['meta_value'];
                                $product_ids [] = $cart_item['meta_value'];
                            }

                            if ( $cart_item['meta_key'] == "_product_id" && !empty($cart_item['meta_value']) ) {
                                $product_ids [] = $cart_item['meta_value'];
                            }
                        }  
                    }

                    
                    //Query to get the SKU for the products

                    $query_sku = "SELECT postmeta.post_id AS id,
                                   postmeta.meta_value AS sku
                                  FROM {$wpdb->prefix}posts AS posts
                                    JOIN {$wpdb->prefix}postmeta AS postmeta ON (posts.id = postmeta.post_id)
                                  WHERE posts.id IN (". implode(",", array_unique($product_ids)) .")
                                    AND postmeta.meta_key IN ('_sku')
                                    AND postmeta.meta_value <> ''
                                  GROUP BY posts.id";
                    $results_sku  = $wpdb->get_results ( $query_sku , 'ARRAY_A');
                    $results_sku_count = $wpdb->num_rows;

                    $products_sku = array();

                    if ( $results_sku_count > 0 ) {
                        
                        foreach ($results_sku as $product_sku) {
                            $products_sku [$product_sku['id']] = $product_sku['sku'];
                        }
                        
                    }

                    //Query to get the variation Attributes

                    if (!empty($variation_ids)) {
                        $query_variation_att = "SELECT post_id AS id,
                                                   meta_value AS meta_value,
                                                   meta_key AS meta_key
                                                FROM {$wpdb->prefix}postmeta
                                                WHERE meta_key LIKE 'attribute_%'
                                                    AND post_id IN (". implode(",",array_unique($variation_ids)) .")
                                                GROUP BY id,meta_key";

                        $results_variation_att  = $wpdb->get_results ( $query_variation_att , 'ARRAY_A');
                        $results_variation_att_count = $wpdb->num_rows;

                        if ( $results_variation_att_count > 0) {

                            $i = 0;

                            $query_terms = "SELECT terms.slug as slug, terms.name as term_name
                                          FROM {$wpdb->prefix}terms AS terms
                                            JOIN {$wpdb->prefix}postmeta AS postmeta 
                                                ON ( postmeta.meta_value = terms.slug 
                                                        AND postmeta.meta_key LIKE 'attribute_%' ) 
                                          GROUP BY terms.slug";
                            $attributes_terms = $wpdb->get_results( $query_terms, 'ARRAY_A' );

                            $attributes = array();
                            foreach ( $attributes_terms as $attributes_term ) {
                                $attributes[$attributes_term['slug']] = $attributes_term['term_name'];
                            }

                            $variations = array();
                            // Formatting of the Variations Names
                            foreach( $results_variation_att as $variation_att ){

                                $att_name = '';
                                $att_val = '';

                                if ( strpos($variation_att['meta_key'],'custom') === false ) { 
                                    $variation_att['meta_value'] = $attributes[$variation_att['meta_value']];
                                }


                                if ( empty($variations[$variation_att['id']]) ) {
                                    $i = 0;
                                    $variations[$variation_att['id']] = array();
                                }

                                if ( strpos($variation_att['meta_key'],'pa') !== false ) {
                                     $att_name = substr($variation_att['meta_key'], strpos($variation_att['meta_key'],'pa')+3);
                                     $att_val = $variation_att['meta_value'];
                                } else if ( strpos($variation_att['meta_key'],'custom') !== false ) {
                                    $att_name = 'custom';
                                    $att_val = $variation_att['meta_value'];
                                }    

                                if ( $i > 1 ) {

                                    if ( $i == 2 ) {
                                        $variations[$variation_att['id']][0]['option1_value'] = $variations[$variation_att['id']][0]['option1_name'] . ' : ' . $variations[$variation_att['id']][0]['option1_value'] . ', '
                                                                                                . $variations[$variation_att['id']][1]['option1_name'] . ' : ' . $variations[$variation_att['id']][1]['option1_value'];
                                        unset($variations[$variation_att['id']][1]);
                                    }

                                    $variations[$variation_att['id']][0]['option1_name'] = '';
                                    $variations[$variation_att['id']][0]['option1_value'] = $variations[$variation_att['id']][0]['option1_value'] . ', ' 
                                                                                            . $att_name . ' : ' . $att_val;

                                } else {
                                    $variations[$variation_att['id']][$i]['option1_name'] = $att_name;
                                    $variations[$variation_att['id']][$i]['option1_value'] = $att_val;
                                }

                                $i++;
                            }

                        }
                    }
                                        
                    
                    //Query to get the Order Details
                    $query_order_item_details = "SELECT post_id as id,
                                            meta_value as meta_value,
                                            meta_key as meta_key	
                                            FROM {$wpdb->prefix}postmeta
                                            WHERE post_id IN (". implode(",",$order_ids) .")
                                            AND meta_key IN ('_billing_first_name' , '_billing_last_name' , '_billing_email',
                                                '_shipping_first_name', '_shipping_last_name', '_billing_address_1', '_billing_address_2',
                                                '_billing_city', '_billing_state', '_billing_country','_billing_postcode',
                                                '_shipping_method', '_payment_method', '_order_items', '_order_total',
                                                '_shipping_method_title', '_payment_method_title','_customer_user','_billing_phone',
                                                '_order_shipping', '_order_discount', '_cart_discount', '_order_tax', '_order_shipping_tax', '_order_currency', 'coupons')
                                            GROUP BY id,meta_key";

                    $results_order_item_details = $wpdb->get_results( $query_order_item_details, 'ARRAY_A' );
                    $results_order_item_details_count = $wpdb->num_rows;

                    if( $results_order_item_details_count > 0 ){
                        
                        $order_items = array();
                        // Structuring the order items
                        foreach( $results_order_item_details as $detail ){
                            
                            if( !isset( $order_items[$detail['id']] ) ){
                                $order_items[$detail['id']] = array();
                            }
                            
                            $order_items[$detail['id']][$detail['meta_key']] = $detail['meta_value'];
                        }
                        
                        
                        //Code for Data Mapping as per Putler
                        foreach( $results_order_details as $order_detail ){

                            $order_id = $order_detail['id'];
                            $order_total = round ( $order_items[$order_id]['_order_total'], 2 );
                            $date_gmt  = $order_detail['date'];
                            $dateInGMT = date('m/d/Y', (int)strtotime($date_gmt));
                            $timeInGMT = date('H:i:s', (int)strtotime($date_gmt));
                            
                            if ($order_status[$order_detail['term_taxonomy_id']] == "on-hold" || $order_status[$order_detail['term_taxonomy_id']] == "pending" || $order_status[$order_detail['term_taxonomy_id']] == "failed") {
                                    $order_status_display = 'Pending';
                            } else if ($order_status[$order_detail['term_taxonomy_id']] == "completed" || $order_status[$order_detail['term_taxonomy_id']] == "processing" || $order_status[$order_detail['term_taxonomy_id']] == "refunded") {
                                    $order_status_display = 'Completed';
                            } else if ($order_status[$order_detail['term_taxonomy_id']] == "cancelled") {
                                    $order_status_display = 'Cancelled';
                            }

                            // $response['date_time'] = $date_gmt;
                            $response ['Date'] = $dateInGMT;
                            $response ['Time'] = $timeInGMT;
                            $response ['Time_Zone'] = 'GMT';
                            
                            $response ['Source'] = $this->name;
                            $response ['Name'] = $order_items[$order_id]['_billing_first_name'] . ' ' . $order_items[$order_id]['_billing_last_name'];
                            // $response ['Type'] = ( $status == "refunded") ? 'Refund' : 'Shopping Cart Payment Received';
                            $response ['Type'] = 'Shopping Cart Payment Received';



                            $response ['Status'] = ucfirst( $order_status_display );

                            $response ['Currency'] = $order_items[$order_id]['_order_currency'];

                            $response ['Gross'] = $order_total;
                            $response ['Fee'] = 0.00;
                            $response ['Net'] = $order_total;

                            $response ['From_Email_Address'] = $order_items[$order_id]['_billing_email'] ;
                            $response ['To_Email_Address'] = '';
                            $response ['Transaction_ID'] = $order_id ;
                            $response ['Counterparty_Status'] = '';
                            $response ['Address_Status'] = '';
                            $response ['Item_Title'] = 'Shopping Cart';
                            $response ['Item_ID'] = 0; // Set to 0 for main Order Transaction row
                            $response ['Shipping_and_Handling_Amount'] = ( isset( $order_items[$order_id]['_order_shipping'] ) ) ? round ( $order_items[$order_id]['_order_shipping'], 2 ) : 0.00;
                            $response ['Insurance_Amount'] = '';
                            $response ['Discount'] = isset( $order_items[$order_id]['_order_discount'] ) ? round ( $order_items[$order_id]['_order_discount'], 2 ) : 0.00;
                            
                            $response ['Sales_Tax'] = isset( $order_items[$order_id]['_order_tax'] ) ? round ( $order_items[$order_id]['_order_tax'], 2 ) : 0.00;

                            $response ['Option_1_Name'] = '';
                            $response ['Option_1_Value'] = '';
                            $response ['Option_2_Name'] = '';
                            $response ['Option_2_Value'] = '';
                            
                            $response ['Auction_Site'] = '';
                            $response ['Buyer_ID'] = '';
                            $response ['Item_URL'] = '';
                            $response ['Closing_Date'] = '';
                            $response ['Escrow_ID'] = '';
                            $response ['Invoice_ID'] = '';
                            $response ['Reference_Txn_ID'] = '';
                            $response ['Invoice_Number'] = '';
                            $response ['Custom_Number'] = '';
                            $response ['Quantity'] = $item_details[$order_id]['tot_qty']; 
                            $response ['Receipt_ID'] = '';

                            $response ['Balance'] = '';
                            $response ['Note'] = $order_detail['order_note'] ;
                            $response ['Address_Line_1'] = ( isset( $order_items[$order_id]['_billing_address_1'] ) ) ? $order_items[$order_id]['_billing_address_1'] : '';
                            $response ['Address_Line_2'] = isset( $order_items[$order_id]['_billing_address_2'] ) ? $order_items[$order_id]['_billing_address_2'] : '';
                            $response ['Town_City'] = isset( $order_items[$order_id]['_billing_city'] ) ? $order_items[$order_id]['_billing_city'] : '' ;
                            $response ['State_Province'] = $order_items[$order_id]['_billing_state'];
                            $response ['Zip_Postal_Code'] = isset( $order_items[$order_id]['_billing_postcode'] ) ? $order_items[$order_id]['_billing_postcode'] : '';
                            $response ['Country'] = isset( $order_items[$order_id]['_billing_country'] ) ? $order_items[$order_id]['_billing_country'] : '';
                            $response ['Contact_Phone_Number'] = isset( $order_items[$order_id]['_billing_phone']) ? $order_items[$order_id]['_billing_phone'] : '';
                            $response ['Subscription_ID'] = '';

                            if((! empty($params['order_id'])) && $order_status[$order_detail['term_taxonomy_id']] == "refunded") {

                                $date_gmt_modified = $order_detail['modified_date'];

                                $response ['Date'] = date('m/d/Y', (int)strtotime($date_gmt_modified));
                                $response ['Time'] = date('H:i:s', (int)strtotime($date_gmt_modified));

                                $response ['Type'] = 'Refund';
                                $response ['Status'] = 'Completed';
                                $response ['Gross'] = -$order_total;
                                $response ['Net'] = -$order_total;
                                $response ['Transaction_ID'] = $order_id . '_R';
                                $response ['Reference_Txn_ID'] = $order_id;

                                $transactions [] = $response;

                            } else {

                                $transactions [] = $response;

                                foreach( $item_details[$order_id]['cart_items'] as $cart_item ) {

                                    $order_item = array();
                                    $order_item ['Type'] = 'Shopping Cart Item';
                                    $order_item ['Item_Title'] = $cart_item['product_name'];
                                    

                                    if ( $cart_item['_variation_id'] != '' ) {
                                        $order_item ['Item_ID'] = (isset( $products_sku[$cart_item['_variation_id']] )) ? $products_sku[$cart_item['_variation_id']] : $cart_item['_variation_id'];
                                        $product_id = $cart_item['_variation_id'];
                                    } else {
                                        $order_item ['Item_ID'] = (isset( $products_sku[$cart_item['_product_id']] )) ? $products_sku[$cart_item['_product_id']] : $cart_item['_product_id'];
                                        $product_id = $cart_item['_product_id'];
                                    }

                                    
                                    $order_item ['Gross'] = round ( $cart_item['_line_total'], 2 );
                                    $order_item ['Quantity'] = $cart_item['_qty'];

                                    if ( isset($variations[$product_id]) ) {
                                        if ( $variations[$product_id][0]['option1_name'] != 'attributes' ) {
                                            $order_item ['Option_1_Name'] = $variations[$product_id][0]['option1_name'];
                                            $order_item ['Option_1_Value'] = $variations[$product_id][0]['option1_value'];
                                            $order_item ['Option_2_Name'] = $variations[$product_id][1]['option1_name'];
                                            $order_item ['Option_2_Value'] = $variations[$product_id][1]['option1_value'];
                                        } else {
                                            $order_item ['Option_1_Name'] = $variations[$product_id][0]['option1_name'];
                                            $order_item ['Option_1_Value'] = $variations[$product_id][0]['option1_value'];
                                        }    
                                    }
                                    
                                    $transactions [] = array_merge ( $response, $order_item );

                                    if( $order_status[$order_detail['term_taxonomy_id']] == "refunded"){
                                        $date_gmt_modified = $order_detail['modified_date'];

                                        $response ['Date'] = date('m/d/Y', (int)strtotime($date_gmt_modified));
                                        $response ['Time'] = date('H:i:s', (int)strtotime($date_gmt_modified));

                                        $response ['Type'] = 'Refund';
                                        $response ['Status'] = 'Completed';
                                        $response ['Gross'] = -$order_total;
                                        $response ['Net'] = -$order_total;
                                        $response ['Transaction_ID'] = $order_id . '_R';
                                        $response ['Reference_Txn_ID'] = $order_id;

                                        $transactions [] = $response;

                                    }
                                }
                            }
                        }
                    } else {
                        
                    }
            
                    if ( empty($params['order_id']) ) {
                        $order_count = (is_array($results_order_details)) ? count($results_order_details) : 0 ;              
                        $params[ $this->name ] = array('count' => $order_count, 'last_start_limit' => $start_limit, 'data' => $transactions );
                    } else {
                        $params['data'] = $transactions;
                    }
                    
             } else {
                
             }

            return $params;
        }
    }
}