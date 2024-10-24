<?php
/*
	Plugin Name: Conditional Gutenberg
	Plugin URI: https://ultimatelybetter.com/blog/conditionally-allow-gutenberg-woocommerce-products/
	Description: Conditionally allow Gutenberg on WooCommerce products
	Version: 1
	Author: Jem Turner
	Author URI: https://ultimatelybetter.com
	License: GPL
*/

if ( !defined( 'ABSPATH' ) )
	exit;

class ConditionalWooGB {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'conditionally_allow_gutenberg' ) );

		add_filter( 'woocommerce_taxonomy_args_product_cat', array( $this, 'enable_taxonomy_in_rest' ) );
		add_filter( 'woocommerce_taxonomy_args_product_tag', array( $this, 'enable_taxonomy_in_rest' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_woogb_tickbox' ) );
		add_action( 'save_post', array( $this, 'save_woogb_tickbox' ) );
	}

	function add_woogb_tickbox() {
		add_meta_box(
			'ub_woogb_tickbox',
			'Enable Gutenberg Editor',
			array( $this, 'display_woogb_tickbox' ),
			'product',
			'normal',
			'high'
		);
	}

	function display_woogb_tickbox( $post ) {
		wp_nonce_field( basename(__FILE__), 'ub_woogb_tickbox_nonce' );

		$can_use_gutenberg = get_post_meta( $post->ID, '_gutenberg_product', true );
?>
		<p>
			<label for="_gutenberg_product">Enable Gutenberg:</label>
			<input type="checkbox" name="_gutenberg_product" id="_gutenberg_product" value="1" <?php checked( $can_use_gutenberg, '1' ); ?>><br>
			<span class="description">You may need to refresh to see the editor change when changing this option</span>
		</p>
		<?php
	}

	function save_woogb_tickbox( $post_id ) {
		if ( !isset( $_POST['ub_woogb_tickbox_nonce'] ) || !wp_verify_nonce( $_POST['ub_woogb_tickbox_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		if ( 'product' != $_POST['post_type'] ) {
			return $post_id;
		}
		if ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( isset( $_POST['_gutenberg_product'] ) && $_POST['_gutenberg_product'] == '1' ) {
			update_post_meta( $post_id, '_gutenberg_product', 1 );
		} else {
			delete_post_meta( $post_id, '_gutenberg_product' );
		}

	}

	function conditionally_allow_gutenberg() {
		/*
			I would have liked to have used get_current_screen here but it's not available yet
		*/
		if ( !isset( $_REQUEST['action'] ) || $_REQUEST['action'] != 'edit' ) {
			return;
		}

		/*
		 * $post isn't setup yet either, so we're relying on getting the ID from the query string
		 */
		if ( !isset( $_REQUEST['post'] ) || is_int( $_REQUEST['post'] ) ) {
			return;
		}

		/* is our custom post meta key present */
		$can_use_gutenberg = get_post_meta( $_REQUEST['post'], '_gutenberg_product', true );

		if ( $can_use_gutenberg ) {
			add_filter( 'use_block_editor_for_post_type', function( $can_edit, $post_type ) {
				/* only on products */
				if ( $post_type == 'product' ) {
					$can_edit = true;
				}
				return $can_edit;
			}, 10, 2 );
		}
	}

	/*
	 * ideally we'd do this bit conditionally as well
	 * but currently noticing no harm from just being enabled regardless?
	 */
	function enable_taxonomy_in_rest( $args ) {
		$args['show_in_rest'] = true;
		return $args;
	}

}
$conditionally_allow_gutenberg = new ConditionalWooGB();