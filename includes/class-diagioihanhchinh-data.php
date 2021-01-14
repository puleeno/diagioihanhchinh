<?php
class Diagioihanhchinh_Data {
    public function __construct() {
        $this->init_hooks();
    }

    public function init_hooks() {
        add_action( 'init', array( $this, 'register_cache_locations_post_type' ) );
    }

    public function register_cache_locations_post_type() {
        register_post_type(
            'dghc_cache_locations',
            array(
                'name'     => 'Diagioihanhchinh cache',
                'public'   => false,
                '_builtin' => true,
            )
        );
    }
}

new Diagioihanhchinh_Data();
