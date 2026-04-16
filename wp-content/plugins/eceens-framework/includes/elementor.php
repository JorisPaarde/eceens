<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'elementor/query/eceens_faq_featured', 'eceens_elementor_faq_featured' );
add_action( 'elementor/query/eceens_content_featured', 'eceens_elementor_content_featured' );
add_action( 'elementor/query/eceens_homepage_faq_featured', 'eceens_elementor_homepage_faq_featured' );
add_action( 'elementor/query/eceens_homepage_content_featured', 'eceens_elementor_homepage_content_featured' );
add_action( 'elementor/query/eceens_faq_current_category', 'eceens_elementor_faq_current_category' );
add_action( 'elementor/query/eceens_content_current_category', 'eceens_elementor_content_current_category' );
add_action( 'elementor/dynamic_tags/register', 'eceens_elementor_register_dynamic_tags' );

function eceens_elementor_faq_featured( $query ) {
    $query->set( 'post_type', 'faq' );
    $query->set( 'meta_query', [
        [
            'key'   => 'faq_featured',
            'value' => '1',
        ],
    ]);
    $query->set( 'meta_key', 'faq_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_content_featured( $query ) {
    $query->set( 'post_type', 'content' );
    $query->set( 'meta_query', [
        [
            'key'   => 'content_featured',
            'value' => '1',
        ],
    ]);
    $query->set( 'meta_key', 'content_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_homepage_faq_featured( $query ) {
    $query->set( 'post_type', 'faq' );
    $query->set( 'meta_query', [
        [
            'key'   => 'faq_homepage_featured',
            'value' => '1',
        ],
    ]);
    $query->set( 'meta_key', 'faq_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_homepage_content_featured( $query ) {
    $query->set( 'post_type', 'content' );
    $query->set( 'meta_query', [
        [
            'key'   => 'content_homepage_featured',
            'value' => '1',
        ],
    ]);
    $query->set( 'meta_key', 'content_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_faq_current_category( $query ) {
    $term = get_queried_object();
    if ( ! $term || ! isset( $term->taxonomy ) || $term->taxonomy !== 'faq_categorie' ) {
        return;
    }
    $query->set( 'post_type', 'faq' );
    $query->set( 'tax_query', [
        [
            'taxonomy' => 'faq_categorie',
            'field'    => 'term_id',
            'terms'    => [ $term->term_id ],
        ],
    ]);
    $query->set( 'meta_key', 'faq_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

function eceens_elementor_content_current_category( $query ) {
    $term = get_queried_object();
    if ( ! $term || ! isset( $term->taxonomy ) || $term->taxonomy !== 'content_categorie' ) {
        return;
    }
    $query->set( 'post_type', 'content' );
    $query->set( 'tax_query', [
        [
            'taxonomy' => 'content_categorie',
            'field'    => 'term_id',
            'terms'    => [ $term->term_id ],
        ],
    ]);
    $query->set( 'meta_key', 'content_priority' );
    $query->set( 'orderby', [
        'meta_value_num' => 'ASC',
        'date'           => 'DESC',
    ]);
}

/**
 * Dynamic Tags group in Elementor.
 */
function eceens_elementor_register_dynamic_group( $dynamic_tags_manager ) {
    if ( ! is_object( $dynamic_tags_manager ) || ! method_exists( $dynamic_tags_manager, 'register_group' ) ) {
        return;
    }

    $dynamic_tags_manager->register_group(
        'eceens',
        [
            'title' => 'Eceens',
        ]
    );
}

/**
 * Resolve current post context (supports Elementor Loop Item context).
 */
function eceens_elementor_resolve_media_context( $source = 'auto' ) {
    $post_id   = get_the_ID();
    $post_type = $post_id ? get_post_type( $post_id ) : '';

    if ( ! in_array( $post_type, [ 'faq', 'content' ], true ) ) {
        if ( ! empty( $GLOBALS['eceens_loop_post_id'] ) ) {
            $post_id = (int) $GLOBALS['eceens_loop_post_id'];
        }
        if ( ! empty( $GLOBALS['eceens_loop_post_type'] ) ) {
            $post_type = (string) $GLOBALS['eceens_loop_post_type'];
        }
    }

    if ( $source === 'faq' || $source === 'content' ) {
        $post_type = $source;
    }

    if ( ! in_array( $post_type, [ 'faq', 'content' ], true ) || $post_id <= 0 ) {
        return [ '', 0 ];
    }

    return [ $post_type, (int) $post_id ];
}

/**
 * Shared control for FAQ/Content source selection.
 */
function eceens_elementor_add_source_control( $tag ) {
    if ( ! is_object( $tag ) || ! method_exists( $tag, 'add_control' ) ) {
        return;
    }

    $tag->add_control(
        'eceens_source',
        [
            'label'   => 'Source',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => [
                'auto'    => 'Auto (current post type)',
                'faq'     => 'FAQ',
                'content' => 'Content',
            ],
        ]
    );
}

/**
 * Register Elementor Dynamic Tags for Eceens media fields.
 */
function eceens_elementor_register_dynamic_tags( $dynamic_tags_manager ) {
    if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) || ! class_exists( '\Elementor\Core\DynamicTags\Data_Tag' ) ) {
        return;
    }

    // Register custom group within the same hook Elementor actually fires.
    eceens_elementor_register_dynamic_group( $dynamic_tags_manager );

    if ( ! class_exists( 'Eceens_Elementor_Media_Image_Tag' ) ) {
        class Eceens_Elementor_Media_Image_Tag extends \Elementor\Core\DynamicTags\Data_Tag {
            public function get_name() {
                return 'eceens-media-image';
            }

            public function get_title() {
                return 'Eceens Media Image';
            }

            public function get_group() {
                return 'eceens';
            }

            public function get_categories() {
                return [ \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ];
            }

            protected function register_controls() {
                eceens_elementor_add_source_control( $this );
            }

            public function get_value( array $options = [] ) {
                $settings = $this->get_settings();
                $source   = isset( $settings['eceens_source'] ) ? $settings['eceens_source'] : 'auto';
                list( $prefix, $post_id ) = eceens_elementor_resolve_media_context( $source );

                if ( ! $prefix || ! $post_id ) {
                    return [];
                }

                $image_id = (int) get_post_meta( $post_id, "{$prefix}_media_image_id", true );
                if ( $image_id <= 0 ) {
                    return [];
                }

                $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                if ( ! $image_url ) {
                    return [];
                }

                return [
                    'id'  => $image_id,
                    'url' => $image_url,
                ];
            }
        }
    }

    if ( ! class_exists( 'Eceens_Elementor_Media_Video_Url_Tag' ) ) {
        class Eceens_Elementor_Media_Video_Url_Tag extends \Elementor\Core\DynamicTags\Tag {
            public function get_name() {
                return 'eceens-media-video-url';
            }

            public function get_title() {
                return 'Eceens Media Video URL';
            }

            public function get_group() {
                return 'eceens';
            }

            public function get_categories() {
                return [
                    \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
                    \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
                ];
            }

            protected function register_controls() {
                eceens_elementor_add_source_control( $this );
            }

            public function render() {
                $settings = $this->get_settings();
                $source   = isset( $settings['eceens_source'] ) ? $settings['eceens_source'] : 'auto';
                list( $prefix, $post_id ) = eceens_elementor_resolve_media_context( $source );

                if ( ! $prefix || ! $post_id ) {
                    return;
                }

                $url = get_post_meta( $post_id, "{$prefix}_media_video_url", true );
                if ( ! $url ) {
                    return;
                }

                echo esc_url( $url );
            }
        }
    }

    if ( ! class_exists( 'Eceens_Elementor_Teaser_Tag' ) ) {
        class Eceens_Elementor_Teaser_Tag extends \Elementor\Core\DynamicTags\Tag {
            public function get_name() {
                return 'eceens-teaser';
            }

            public function get_title() {
                return 'Eceens Teaser';
            }

            public function get_group() {
                return 'eceens';
            }

            public function get_categories() {
                return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
            }

            protected function register_controls() {
                eceens_elementor_add_source_control( $this );
            }

            public function render() {
                $settings = $this->get_settings();
                $source   = isset( $settings['eceens_source'] ) ? $settings['eceens_source'] : 'auto';
                list( $prefix, $post_id ) = eceens_elementor_resolve_media_context( $source );
                if ( ! $prefix || ! $post_id ) {
                    return;
                }

                $value = get_post_meta( $post_id, "{$prefix}_teaser", true );
                if ( $value === '' ) {
                    return;
                }

                echo esc_html( $value );
            }
        }
    }

    if ( ! class_exists( 'Eceens_Elementor_Manual_Title_Tag' ) ) {
        class Eceens_Elementor_Manual_Title_Tag extends \Elementor\Core\DynamicTags\Tag {
            public function get_name() {
                return 'eceens-manual-title';
            }

            public function get_title() {
                return 'Eceens Manual Title';
            }

            public function get_group() {
                return 'eceens';
            }

            public function get_categories() {
                return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
            }

            protected function register_controls() {
                eceens_elementor_add_source_control( $this );
            }

            public function render() {
                $settings = $this->get_settings();
                $source   = isset( $settings['eceens_source'] ) ? $settings['eceens_source'] : 'auto';
                list( $prefix, $post_id ) = eceens_elementor_resolve_media_context( $source );
                if ( ! $prefix || ! $post_id ) {
                    return;
                }

                $value = get_post_meta( $post_id, "{$prefix}_manual_title", true );
                if ( $value === '' ) {
                    return;
                }

                echo esc_html( $value );
            }
        }
    }

    if ( ! class_exists( 'Eceens_Elementor_Priority_Tag' ) ) {
        class Eceens_Elementor_Priority_Tag extends \Elementor\Core\DynamicTags\Tag {
            public function get_name() {
                return 'eceens-priority';
            }

            public function get_title() {
                return 'Eceens Priority';
            }

            public function get_group() {
                return 'eceens';
            }

            public function get_categories() {
                return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
            }

            protected function register_controls() {
                eceens_elementor_add_source_control( $this );
            }

            public function render() {
                $settings = $this->get_settings();
                $source   = isset( $settings['eceens_source'] ) ? $settings['eceens_source'] : 'auto';
                list( $prefix, $post_id ) = eceens_elementor_resolve_media_context( $source );
                if ( ! $prefix || ! $post_id ) {
                    return;
                }

                $value = get_post_meta( $post_id, "{$prefix}_priority", true );
                if ( $value === '' ) {
                    return;
                }

                echo esc_html( $value );
            }
        }
    }

    if ( ! class_exists( 'Eceens_Elementor_Featured_Tag' ) ) {
        class Eceens_Elementor_Featured_Tag extends \Elementor\Core\DynamicTags\Tag {
            public function get_name() {
                return 'eceens-featured';
            }

            public function get_title() {
                return 'Eceens Featured';
            }

            public function get_group() {
                return 'eceens';
            }

            public function get_categories() {
                return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
            }

            protected function register_controls() {
                eceens_elementor_add_source_control( $this );
            }

            public function render() {
                $settings = $this->get_settings();
                $source   = isset( $settings['eceens_source'] ) ? $settings['eceens_source'] : 'auto';
                list( $prefix, $post_id ) = eceens_elementor_resolve_media_context( $source );
                if ( ! $prefix || ! $post_id ) {
                    return;
                }

                $value = get_post_meta( $post_id, "{$prefix}_featured", true ) === '1' ? 'Ja' : 'Nee';
                echo esc_html( $value );
            }
        }
    }

    if ( ! class_exists( 'Eceens_Elementor_Homepage_Featured_Tag' ) ) {
        class Eceens_Elementor_Homepage_Featured_Tag extends \Elementor\Core\DynamicTags\Tag {
            public function get_name() {
                return 'eceens-homepage-featured';
            }

            public function get_title() {
                return 'Eceens Homepage Featured';
            }

            public function get_group() {
                return 'eceens';
            }

            public function get_categories() {
                return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
            }

            protected function register_controls() {
                eceens_elementor_add_source_control( $this );
            }

            public function render() {
                $settings = $this->get_settings();
                $source   = isset( $settings['eceens_source'] ) ? $settings['eceens_source'] : 'auto';
                list( $prefix, $post_id ) = eceens_elementor_resolve_media_context( $source );
                if ( ! $prefix || ! $post_id ) {
                    return;
                }

                $value = get_post_meta( $post_id, "{$prefix}_homepage_featured", true ) === '1' ? 'Ja' : 'Nee';
                echo esc_html( $value );
            }
        }
    }

    $dynamic_tags_manager->register( new \Eceens_Elementor_Media_Image_Tag() );
    $dynamic_tags_manager->register( new \Eceens_Elementor_Media_Video_Url_Tag() );
    $dynamic_tags_manager->register( new \Eceens_Elementor_Teaser_Tag() );
    $dynamic_tags_manager->register( new \Eceens_Elementor_Manual_Title_Tag() );
    $dynamic_tags_manager->register( new \Eceens_Elementor_Priority_Tag() );
    $dynamic_tags_manager->register( new \Eceens_Elementor_Featured_Tag() );
    $dynamic_tags_manager->register( new \Eceens_Elementor_Homepage_Featured_Tag() );
}
