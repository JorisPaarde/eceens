<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'add_meta_boxes', 'eceens_add_meta_boxes' );
add_action( 'save_post_faq', 'eceens_save_faq_meta', 10, 2 );
add_action( 'save_post_content', 'eceens_save_content_meta', 10, 2 );
add_action( 'admin_enqueue_scripts', 'eceens_metabox_admin_scripts' );

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

    $teaser     = get_post_meta( $post->ID, "{$prefix}_teaser", true );
    $featured   = get_post_meta( $post->ID, "{$prefix}_featured", true );
    $priority   = get_post_meta( $post->ID, "{$prefix}_priority", true );
    $manual     = get_post_meta( $post->ID, "{$prefix}_manual_title", true );
    $image_id   = get_post_meta( $post->ID, "{$prefix}_media_image_id", true );
    $video_url  = get_post_meta( $post->ID, "{$prefix}_media_video_url", true );

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
        "{$prefix}_teaser"          => 'text',
        "{$prefix}_featured"        => 'checkbox',
        "{$prefix}_priority"        => 'int',
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
                $value = $raw !== '' ? absint( $raw ) : '';
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

/* ── Save hooks ──────────────────────────────────────────── */

function eceens_save_faq_meta( $post_id, $post ) {
    eceens_save_meta( $post_id, $post, 'faq' );
}

function eceens_save_content_meta( $post_id, $post ) {
    eceens_save_meta( $post_id, $post, 'content' );
}
