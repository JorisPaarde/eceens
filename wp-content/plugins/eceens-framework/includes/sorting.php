<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'pre_get_posts', 'eceens_default_sorting' );

function eceens_default_sorting( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->is_post_type_archive( 'faq' ) || $query->is_tax( 'faq_categorie' ) || $query->is_tax( 'faq_subcategorie' ) ) {
        $query->set( 'meta_key', 'faq_priority' );
        $query->set( 'orderby', [
            'meta_value_num' => 'ASC',
            'date'           => 'DESC',
        ]);
        return;
    }

    if ( $query->is_post_type_archive( 'content' ) || $query->is_tax( 'content_categorie' ) || $query->is_tax( 'content_subcategorie' ) ) {
        $query->set( 'meta_key', 'content_priority' );
        $query->set( 'orderby', [
            'meta_value_num' => 'ASC',
            'date'           => 'DESC',
        ]);
    }
}
