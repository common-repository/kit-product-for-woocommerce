<?php
/**
 * Surfer
 */

 function wckp_get_kit_edit_params($param){
    $edit_kit_params = sanitize_text_field($_REQUEST[$param]);
    if($edit_kit_params){
        return $edit_kit_params;
    }else{
        return false;
    }   
 }

 function wckp_get_posted_array_params($key){
    $params = array_map( 'sanitize_text_field', wp_unslash( $_REQUEST[$key] ) );
    return $params;
 }