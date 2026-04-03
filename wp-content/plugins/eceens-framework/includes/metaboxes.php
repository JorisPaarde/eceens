<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'add_meta_boxes', 'eceens_add_meta_boxes' );
add_action( 'save_post_faq', 'eceens_save_faq_meta', 10, 2 );
add_action( 'save_post_content', 'eceens_save_content_meta', 10, 2 );
add_action( 'admin_enqueue_scripts', 'eceens_metabox_admin_scripts' );
add_filter( 'manage_faq_posts_columns', 'eceens_add_homepage_featured_column' );
add_filter( 'manage_content_posts_columns', 'eceens_add_homepage_featured_column' );
add_action( 'manage_faq_posts_custom_column', 'eceens_render_homepage_featured_column', 10, 2 );
add_action( 'manage_content_posts_custom_column', 'eceens_render_homepage_featured_column', 10, 2 );
add_action( 'admin_footer-edit.php', 'eceens_render_list_toggle_script' );
add_action( 'wp_ajax_eceens_toggle_homepage_featured', 'eceens_ajax_toggle_homepage_featured' );
add_action( 'admin_init', 'eceens_maybe_backfill_priority_meta', 20 );

function eceens_metabox_admin_scripts( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, [ 'faq', 'content' ], true ) ) {
        return;
    }
    wp_enqueue_media();
}

/* ── Register meta boxes ─────────────────────────────────── */

function eceens_add_meta_boxes() {
    add_meta_box(
        'eceens_faq_fields',
        'FAQ Velden',
        'eceens_render_faq_meta_box',
        'faq',
        'normal',
        'high'
    );

    add_meta_box(
        'eceens_content_fields',
        'Content Velden',
        'eceens_render_content_meta_box',
        'content',
        'normal',
        'high'
    );
}

/* ── Generic render helper ───────────────────────────────── */

function eceens_render_meta_fields( $post, $prefix ) {
    wp_nonce_field( "eceens_{$prefix}_nonce_action", "eceens_{$prefix}_nonce" );

    $teaser          = get_post_meta( $post->ID, "{$prefix}_teaser", true );
    $featured        = get_post_meta( $post->ID, "{$prefix}_featured", true );
    $homepage_featured = get_post_meta( $post->ID, "{$prefix}_homepage_featured", true );
    $priority        = get_post_meta( $post->ID, "{$prefix}_priority", true );
    $manual          = get_post_meta( $post->ID, "{$prefix}_manual_title", true );
    $image_id        = get_post_meta( $post->ID, "{$prefix}_media_image_id", true );
    $video_url       = get_post_meta( $post->ID, "{$prefix}_media_video_url", true );

    $image_url  = $image_id ? wp_get_attachment_image_url( (int) $image_id, 'thumbnail' ) : '';
    ?>
    <table class="form-table">
        <tr>
            <th><label for="<?php echo esc_attr( $prefix ); ?>_teaser">Teaser</label></th>
            <td><input type="text" id="<?php echo esc_attr( $prefix ); ?>_teaser"
                       name="<?php echo esc_attr( $prefix ); ?>_teaser"
                       value="<?php echo esc_attr( $teaser ); ?>" class="large-text" /></td>
        </tr>
        <tr>
            <th><label for="<?php echo esc_attr( $prefix ); ?>_featured">Featured</label></th>
            <td><input type="checkbox" id="<?php echo esc_attr( $prefix ); ?>_featured"
                       name="<?php echo esc_attr( $prefix ); ?>_featured"
                       value="1" <?php checked( $featured, '1' ); ?> /></td>
        </tr>
        <tr>
            <th><label for="<?php echo esc_attr( $prefix ); ?>_homepage_featured">Homepage Featured</label></th>
            <td><input type="checkbox" id="<?php echo esc_attr( $prefix ); ?>_homepage_featured"
                       name="<?php echo esc_attr( $prefix ); ?>_homepage_featured"
                       value="1" <?php checked( $homepage_featured, '1' ); ?> /></td>
        </tr>
        <tr>
            <th><label for="<?php echo esc_attr( $prefix ); ?>_priority">Prioriteit</label></th>
            <td><input type="number" id="<?php echo esc_attr( $prefix ); ?>_priority"
                       name="<?php echo esc_attr( $prefix ); ?>_priority"
                       value="<?php echo esc_attr( $priority ); ?>" min="0" step="1" style="width:80px" /></td>
        </tr>
        <tr>
            <th><label for="<?php echo esc_attr( $prefix ); ?>_manual_title">Handmatige titel</label></th>
            <td><input type="text" id="<?php echo esc_attr( $prefix ); ?>_manual_title"
                       name="<?php echo esc_attr( $prefix ); ?>_manual_title"
                       value="<?php echo esc_attr( $manual ); ?>" class="large-text" /></td>
        </tr>
        <tr>
            <th><label>Media Afbeelding</label></th>
            <td>
                <div id="<?php echo esc_attr( $prefix ); ?>_image_preview">
                    <?php if ( $image_url ) : ?>
                        <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:150px;height:auto;display:block;margin-bottom:8px;" />
                    <?php endif; ?>
                </div>
                <input type="hidden" id="<?php echo esc_attr( $prefix ); ?>_media_image_id"
                       name="<?php echo esc_attr( $prefix ); ?>_media_image_id"
                       value="<?php echo esc_attr( $image_id ); ?>" />
                <button type="button" class="button eceens-upload-image"
                        data-target="#<?php echo esc_attr( $prefix ); ?>_media_image_id"
                        data-preview="#<?php echo esc_attr( $prefix ); ?>_image_preview">
                    Afbeelding kiezen
                </button>
                <button type="button" class="button eceens-remove-image"
                        data-target="#<?php echo esc_attr( $prefix ); ?>_media_image_id"
                        data-preview="#<?php echo esc_attr( $prefix ); ?>_image_preview"
                        <?php echo $image_id ? '' : 'style="display:none"'; ?>>
                    Verwijderen
                </button>
            </td>
        </tr>
        <tr>
            <th><label for="<?php echo esc_attr( $prefix ); ?>_media_video_url">Media Video URL</label></th>
            <td><input type="url" id="<?php echo esc_attr( $prefix ); ?>_media_video_url"
                       name="<?php echo esc_attr( $prefix ); ?>_media_video_url"
                       value="<?php echo esc_url( $video_url ); ?>" class="large-text"
                       placeholder="https://…" /></td>
        </tr>
    </table>

    <script>
    jQuery(function($){
        $('.eceens-upload-image').on('click', function(e){
            e.preventDefault();
            var btn     = $(this),
                target  = $(btn.data('target')),
                preview = $(btn.data('preview')),
                frame   = wp.media({ title:'Kies afbeelding', multiple:false, library:{type:'image'} });

            frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                target.val(att.id);
                var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                preview.html('<img src="'+url+'" style="max-width:150px;height:auto;display:block;margin-bottom:8px;" />');
                btn.next('.eceens-remove-image').show();
            });
            frame.open();
        });

        $('.eceens-remove-image').on('click', function(e){
            e.preventDefault();
            var btn = $(this);
            $(btn.data('target')).val('');
            $(btn.data('preview')).html('');
            btn.hide();
        });
    });
    </script>
    <?php
}

/* ── Render callbacks ────────────────────────────────────── */

function eceens_render_faq_meta_box( $post ) {
    eceens_render_meta_fields( $post, 'faq' );
}

function eceens_render_content_meta_box( $post ) {
    eceens_render_meta_fields( $post, 'content' );
}

/* ── Generic save helper ─────────────────────────────────── */

function eceens_save_meta( $post_id, $post, $prefix ) {
    if ( ! isset( $_POST["eceens_{$prefix}_nonce"] )
         || ! wp_verify_nonce( $_POST["eceens_{$prefix}_nonce"], "eceens_{$prefix}_nonce_action" ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = [
        "{$prefix}_teaser"             => 'text',
        "{$prefix}_featured"           => 'checkbox',
        "{$prefix}_homepage_featured"  => 'checkbox',
        "{$prefix}_priority"           => 'int',
        "{$prefix}_manual_title"    => 'text',
        "{$prefix}_media_image_id"  => 'int',
        "{$prefix}_media_video_url" => 'url',
    ];

    foreach ( $fields as $key => $type ) {
        $raw = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';

        switch ( $type ) {
            case 'text':
                $value = sanitize_text_field( $raw );
                break;
            case 'int':
                if ( $raw !== '' ) {
                    $value = absint( $raw );
                } elseif ( $key === "{$prefix}_priority" ) {
                    // Keep meta so WP_Query with meta_key + orderby does not exclude the post.
                    $value = (int) ECEENS_PRIORITY_DEFAULT;
                } else {
                    $value = '';
                }
                break;
            case 'url':
                $value = esc_url_raw( $raw );
                break;
            case 'checkbox':
                $value = $raw === '1' ? '1' : '';
                break;
            default:
                $value = sanitize_text_field( $raw );
        }

        if ( $value !== '' ) {
            update_post_meta( $post_id, $key, $value );
        } else {
            delete_post_meta( $post_id, $key );
        }
    }
}

/**
 * One-time backfill: posts missing *_priority meta (older saves deleted it when empty).
 */
function eceens_maybe_backfill_priority_meta() {
    if ( get_option( 'eceens_priority_backfill_v1' ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $default = (string) ECEENS_PRIORITY_DEFAULT;

    foreach ( [ 'faq' => 'faq_priority', 'content' => 'content_priority' ] as $post_type => $meta_key ) {
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
                SELECT p.ID, %s, %s
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                WHERE p.post_type = %s AND p.post_status NOT IN ('trash', 'auto-draft') AND pm.meta_id IS NULL",
                $meta_key,
                $default,
                $meta_key,
                $post_type
            )
        );
    }

    update_option( 'eceens_priority_backfill_v1', '1' );
}

/* ── Save hooks ──────────────────────────────────────────── */

function eceens_save_faq_meta( $post_id, $post ) {
    eceens_save_meta( $post_id, $post, 'faq' );
}

function eceens_save_content_meta( $post_id, $post ) {
    eceens_save_meta( $post_id, $post, 'content' );
}

/* ── Admin list: Homepage Featured toggle ────────────────── */

function eceens_add_homepage_featured_column( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['eceens_homepage_featured'] = 'Homepage Featured';
        }
    }
    if ( ! isset( $new['eceens_homepage_featured'] ) ) {
        $new['eceens_homepage_featured'] = 'Homepage Featured';
    }
    return $new;
}

function eceens_render_homepage_featured_column( $column, $post_id ) {
    if ( 'eceens_homepage_featured' !== $column ) {
        return;
    }

    $post_type = get_post_type( $post_id );
    $prefix    = ( 'faq' === $post_type ) ? 'faq' : ( ( 'content' === $post_type ) ? 'content' : '' );
    if ( '' === $prefix ) {
        echo '—';
        return;
    }

    $key     = "{$prefix}_homepage_featured";
    $checked = get_post_meta( $post_id, $key, true ) === '1';
    printf(
        '<label><input type="checkbox" class="eceens-home-toggle" data-post-id="%d" data-prefix="%s" %s /> %s</label>',
        (int) $post_id,
        esc_attr( $prefix ),
        checked( $checked, true, false ),
        esc_html__( 'Home', 'eceens-framework' )
    );
}

function eceens_render_list_toggle_script() {
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, [ 'faq', 'content' ], true ) ) {
        return;
    }
    $nonce = wp_create_nonce( 'eceens_toggle_homepage_featured' );
    ?>
    <script>
    (function($){
        $(document).on('change', '.eceens-home-toggle', function(){
            var $el = $(this);
            var prev = !$el.is(':checked');
            $el.prop('disabled', true);
            $.post(ajaxurl, {
                action: 'eceens_toggle_homepage_featured',
                nonce: '<?php echo esc_js( $nonce ); ?>',
                post_id: $el.data('post-id'),
                prefix: $el.data('prefix'),
                value: $el.is(':checked') ? '1' : ''
            }).done(function(resp){
                if(!resp || !resp.success){
                    $el.prop('checked', prev);
                }
            }).fail(function(){
                $el.prop('checked', prev);
            }).always(function(){
                $el.prop('disabled', false);
            });
        });
    })(jQuery);
    </script>
    <?php
}

function eceens_ajax_toggle_homepage_featured() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error();
    }
    check_ajax_referer( 'eceens_toggle_homepage_featured', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $prefix  = isset( $_POST['prefix'] ) ? sanitize_key( $_POST['prefix'] ) : '';
    $value   = ( isset( $_POST['value'] ) && $_POST['value'] === '1' ) ? '1' : '';

    if ( $post_id <= 0 || ! in_array( $prefix, [ 'faq', 'content' ], true ) ) {
        wp_send_json_error();
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error();
    }

    $key = "{$prefix}_homepage_featured";
    if ( '1' === $value ) {
        update_post_meta( $post_id, $key, '1' );
    } else {
        delete_post_meta( $post_id, $key );
    }

    wp_send_json_success();
}
