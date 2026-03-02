<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_enqueue_scripts', 'eceens_term_color_scripts' );
add_action( 'faq_categorie_add_form_fields', 'eceens_term_color_add_field' );
add_action( 'faq_categorie_edit_form_fields', 'eceens_term_color_edit_field', 10, 2 );
add_action( 'created_faq_categorie', 'eceens_term_color_save' );
add_action( 'edited_faq_categorie', 'eceens_term_color_save' );

function eceens_term_color_scripts( $hook ) {
    if ( ! in_array( $hook, [ 'term.php', 'edit-tags.php' ], true ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || $screen->taxonomy !== 'faq_categorie' ) {
        return;
    }
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_add_inline_script( 'wp-color-picker', "
        jQuery(function($){ $('.eceens-color-picker').wpColorPicker(); });
    " );
}

/* ── "Add new" form field ────────────────────────────────── */

function eceens_term_color_add_field() {
    ?>
    <div class="form-field">
        <label for="eceens_term_color">Categorie kleur</label>
        <input type="text" name="eceens_term_color" id="eceens_term_color"
               class="eceens-color-picker" value="#2A50FF" />
        <p class="description">Kies een kleur voor deze FAQ categorie.</p>
    </div>
    <?php
}

/* ── "Edit" form field ───────────────────────────────────── */

function eceens_term_color_edit_field( $term ) {
    $color = get_term_meta( $term->term_id, 'eceens_term_color', true );
    if ( ! $color ) {
        $color = '#2A50FF';
    }
    ?>
    <tr class="form-field">
        <th scope="row"><label for="eceens_term_color">Categorie kleur</label></th>
        <td>
            <input type="text" name="eceens_term_color" id="eceens_term_color"
                   class="eceens-color-picker" value="<?php echo esc_attr( $color ); ?>" />
            <p class="description">Kies een kleur voor deze FAQ categorie.</p>
        </td>
    </tr>
    <?php
}

/* ── Save ────────────────────────────────────────────────── */

function eceens_term_color_save( $term_id ) {
    if ( ! isset( $_POST['eceens_term_color'] ) ) {
        return;
    }
    $color = sanitize_hex_color( $_POST['eceens_term_color'] );
    if ( $color ) {
        update_term_meta( $term_id, 'eceens_term_color', $color );
    }
}
