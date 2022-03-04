public function process_autoship(){
        
        global $wpdb;
       
        $active_affiliates = $wpdb->get_results("select mlm.* from wp_wpmlm_users mlm JOIN wp_users wu ON wu.ID = mlm.user_id where mlm.banned = 0 AND mlm.user_key NOT IN ($this->root_users)");
        
        if(!empty($active_affiliates)){
            $total_aff =  count($active_affiliates);
            $this->log("Total Affilaites to process:: {$total_aff}");
            
            foreach ($active_affiliates as $affiliate){
                
                $this->log("Processing Affilaite: {$affiliate->user_key} :: WP ID: {$affiliate->user_id}");
                
                $data = array(
                    'user_id'           => $affiliate->user_id,
                    'user_key'          => $affiliate->user_key,
                    'commission_period' =>  $this->commission_period_id
                );
                
                $sub_next_amount = '';
                $sub_next_date = NULL;
                
                $autoship_orders = autoship_search_all_scheduled_orders($affiliate->user_id, 1, array('statusNames' => array('active')));
                
                if(!is_wp_error($autoship_orders) && !empty($autoship_orders)){
                    
                    $data['active_subscription'] = 'Yes';
                    
                    foreach ($autoship_orders as $order) {
                        $totals = autoship_get_calculated_scheduled_order_object_totals($order);
                        $totals = autoship_get_formatted_order_display_totals($totals, $order->currencyIso);

                        $sub_next_datetime = autoship_get_local_date($order->nextOccurrenceUtc);

                        if (!isset($sub_next_date) || $sub_next_datetime < $sub_next_date) {
                            $sub_next_date = $sub_next_datetime;
                            $sub_next_amount = $totals['total']['value'];
                            $sub_next_date = autoship_get_formatted_local_date($sub_next_datetime);
                        }
                    }
                    
                    if (!empty($sub_next_amount)) {
                        $data['next_sub_date'] = $sub_next_date;
                        $data['next_sub_amount'] = $sub_next_amount;
                    }
                    
                    $this->log("Active Subscription :: Next Date:$sub_next_date :: Next Amount:$sub_next_amount");
                }else{
                    $this->log("Does not have an active Subscription");
                    $data['active_subscription'] = 'No';
                }
                
                $wpdb->insert("wp_autoship",$data);
            }
        }else{
            $this->log("No Affilaite to process autoship");
        }
    }
