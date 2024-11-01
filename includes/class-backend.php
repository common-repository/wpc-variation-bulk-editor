<?php
defined( 'ABSPATH' ) || exit;

class Wpcvb_Backend {
	protected static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'woocommerce_variable_product_before_variations', [ $this, 'bulk_editor_btn' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'bulk_editor_popup' ] );

		// AJAX
		add_action( 'wp_ajax_wpcvb_filter_count', [ $this, 'ajax_filter_count' ] );
		add_action( 'wp_ajax_wpcvb_filter_form', [ $this, 'ajax_filter_form' ] );
		add_action( 'wp_ajax_wpcvb_bulk_update', [ $this, 'ajax_bulk_update' ] );
		add_action( 'wp_ajax_wpcvb_bulk_remove', [ $this, 'ajax_bulk_remove' ] );
		add_action( 'wp_ajax_wpcvb_bulk_generate', [ $this, 'ajax_bulk_generate' ] );
	}

	function bulk_editor_btn() {
		?>
        <div class="wpcvb-btn-wrapper">
            <button type="button" class="wpcvb-btn wpcvb-btn-generate button">
                <span class="dashicons dashicons-admin-page"></span> <?php esc_html_e( 'Bulk Generate', 'wpc-variation-bulk-editor' ); ?>
            </button>
            <button type="button" class="wpcvb-btn wpcvb-btn-remove button">
                <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Bulk Remove', 'wpc-variation-bulk-editor' ); ?>
            </button>
            <button type="button" class="wpcvb-btn wpcvb-btn-editor button button-primary">
                <span class="dashicons dashicons-edit-page"></span> <?php esc_html_e( 'Variation Bulk Editor', 'wpc-variation-bulk-editor' ); ?>
            </button>
        </div>
		<?php
	}

	function bulk_editor_popup() {
		global $product_object;
		?>
        <div class="wpcvb-popup-wrapper">
            <div class="wpcvb-popup wpcvb-popup-editor">
				<?php
				echo '<div class="wpcvb-filter">';
				echo '<div class="wpcvb-filter-heading">' . esc_html__( 'Filter variations by attributes', 'wpc-variation-bulk-editor' ) . '</div>';
				echo '<div class="wpcvb-filter-form">';
				self::get_filter_form( $product_object, 'editor' );
				echo '</div></div>';

				$loop             = $product_object->get_id();
				$variation_id     = 0;
				$variation        = get_post( $variation_id );
				$variation_object = wc_get_product_object( 'variation', $variation_id );
				$variation_data   = [];
				include dirname( WC_PLUGIN_FILE ) . '/includes/admin/meta-boxes/views/html-variation-admin.php';
				?>
                <div class="wpcvb-submit">
                    <button type="button" class="wpcvb-submit-update button button-primary">
						<?php esc_html_e( 'Update', 'wpc-variation-bulk-editor' ); ?>
                    </button>
                    <div class="wpcvb-filter-count"></div>
                </div>
            </div>
            <div class="wpcvb-popup wpcvb-popup-remove">
				<?php
				echo '<div class="wpcvb-filter">';
				echo '<div class="wpcvb-filter-heading">' . esc_html__( 'Filter variations by attributes', 'wpc-variation-bulk-editor' ) . '</div>';
				echo '<div class="wpcvb-filter-form">';
				self::get_filter_form( $product_object, 'remove' );
				echo '</div></div>';
				?>
                <div class="wpcvb-submit">
                    <button type="button" class="wpcvb-submit-remove button button-primary">
						<?php esc_html_e( 'Remove', 'wpc-variation-bulk-editor' ); ?>
                    </button>
                </div>
            </div>
            <div class="wpcvb-popup wpcvb-popup-generate">
				<?php
				echo '<div class="wpcvb-filter">';
				echo '<div class="wpcvb-filter-heading">' . esc_html__( 'Select variation attributes', 'wpc-variation-bulk-editor' ) . '</div>';
				echo '<div class="wpcvb-filter-form">';
				self::get_filter_form( $product_object, 'generate' );
				echo '</div></div>';
				?>
                <div class="wpcvb-submit">
                    <button type="button" class="wpcvb-submit-generate button button-primary">
						<?php esc_html_e( 'Generate', 'wpc-variation-bulk-editor' ); ?>
                    </button>
                </div>
            </div>
        </div>
		<?php
	}

	function get_filter_form( $product_object, $context = 'editor' ) {
		if ( $product_object && $product_object->is_type( 'variable' ) ) {
			$attributes = $product_object->get_variation_attributes();
			$children   = $product_object->get_children();

			if ( is_array( $attributes ) && ( count( $attributes ) > 0 ) ) {
				foreach ( $attributes as $attribute_name => $options ) {
					echo '<div style="margin-top: 10px">';
					echo '<div>' . wc_attribute_label( $attribute_name ) . '</div>';

					if ( ! empty( $options ) ) {
						$attribute_name_st = sanitize_title( $attribute_name );
						echo '<select class="wpcvb_attribute" name="' . esc_attr( $attribute_name_st ) . '" multiple>';
						echo '<option value="wpcvb_any" ' . ( isset( $terms[ $attribute_name_st ] ) && in_array( 'wpcvb_any', $terms[ $attribute_name_st ] ) ? 'selected' : '' ) . '>' . sprintf( /* translators: attribute */ esc_html__( 'Any %s...', 'wpc-variation-bulk-editor' ), wc_attribute_label( $attribute_name ) ) . '</option>';

						foreach ( $options as $option ) {
							echo '<option value="' . esc_attr( $option ) . '" ' . ( isset( $terms[ $attribute_name_st ] ) && in_array( $option, $terms[ $attribute_name_st ] ) ? 'selected' : '' ) . '>' . esc_html( $option ) . '</option>';
						}

						echo '</select>';
					}

					echo '</div>';
				}
			}

			if ( $context !== 'generate' ) {
				echo '<div class="wpcvb-filter-count">' . sprintf( /* translators: count */ _n( '%s variation will be affected', '%s variations will be affected', count( $children ), 'wpc-variation-bulk-editor' ), '<strong>' . count( $children ) . '</strong>' ) . self::get_ids( $children ) . '</div>';
			}
		}
	}

	function admin_scripts() {
		if ( 'product' === get_post_type() ) {
			// candlestick
			wp_enqueue_style( 'candlestick', WPCVB_URI . 'assets/libs/candlestick/candlestick.min.css', [], WPCVB_VERSION );
			wp_enqueue_script( 'candlestick', WPCVB_URI . 'assets/libs/candlestick/candlestick.min.js', [ 'jquery' ], WPCVB_VERSION, true );

			wp_enqueue_style( 'wpcvb-backend', WPCVB_URI . 'assets/css/backend.css', [], WPCVB_VERSION );
			wp_enqueue_script( 'wpcvb-backend', WPCVB_URI . 'assets/js/backend.js', [
				'jquery',
				'jquery-ui-dialog',
				'selectWoo',
			], WPCVB_VERSION, true );
			wp_localize_script( 'wpcvb-backend', 'wpcvb_vars', [
					'nonce'            => wp_create_nonce( 'wpcvb_nonce' ),
					'editor_title'     => esc_html__( 'Variation Bulk Editor', 'wpc-variation-bulk-editor' ),
					'remove_title'     => esc_html__( 'Bulk Remove', 'wpc-variation-bulk-editor' ),
					'remove_warning'   => esc_html__( 'Are you sure you want to delete these variations? This cannot be undone.', 'wpc-variation-bulk-editor' ),
					'generate_title'   => esc_html__( 'Bulk Generate', 'wpc-variation-bulk-editor' ),
					'generate_warning' => esc_html__( 'Do you want to generate variations? This will create a new variation for each and every possible combination of selected attributes.', 'wpc-variation-bulk-editor' ),
					'no_change'        => esc_html__( 'No change', 'wpc-variation-bulk-editor' ),
				]
			);
		}
	}

	function ajax_filter_count() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcvb_nonce' ) ) {
			die( 'Permissions check failed!' );
		}

		$product_id = absint( sanitize_text_field( $_POST['post_id'] ?? 0 ) );
		$attrs      = self::sanitize_array( $_POST['attrs'] ?? [] );
		$variations = self::get_variations( $product_id, $attrs );

		echo sprintf( /* translators: count */ _n( '%s variation will be affected', '%s variations will be affected', count( $variations ), 'wpc-variation-bulk-editor' ), '<strong>' . count( $variations ) . '</strong>' ) . self::get_ids( $variations );

		wp_die();
	}

	function ajax_filter_form() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcvb_nonce' ) ) {
			die( 'Permissions check failed!' );
		}

		$product_id     = absint( sanitize_text_field( $_POST['post_id'] ?? 0 ) );
		$product_object = wc_get_product_object( 'variable', $product_id );
		self::get_filter_form( $product_object );

		wp_die();
	}

	function ajax_bulk_update() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcvb_nonce' ) ) {
			die( 'Permissions check failed!' );
		}

		$product_id = absint( sanitize_text_field( $_POST['post_id'] ?? 0 ) );
		$attrs      = self::sanitize_array( $_POST['attrs'] ?? [] );
		$variations = self::get_variations( $product_id, $attrs );
		$fields     = sanitize_post( $_POST['fields'] ?? '' );

		if ( ! empty( $variations ) && ! empty( $fields ) ) {
			$_fields = [];
			parse_str( $fields, $_fields );

			foreach ( $_fields as $_fk => $_fv ) {
				if ( is_array( $_fv ) ) {
					$_fields[ $_fk ] = reset( $_fv );
				}
			}

			$date_on_sale_from = '';
			$date_on_sale_to   = '';

			if ( isset( $_fields['variable_sale_price_dates_from'] ) ) {
				$date_on_sale_from = wc_clean( wp_unslash( $_fields['variable_sale_price_dates_from'] ) );

				if ( ! empty( $date_on_sale_from ) ) {
					$date_on_sale_from = date( 'Y-m-d 00:00:00', strtotime( $date_on_sale_from ) );
				}
			}

			if ( isset( $_fields['variable_sale_price_dates_to'] ) ) {
				$date_on_sale_to = wc_clean( wp_unslash( $_fields['variable_sale_price_dates_to'] ) );

				if ( ! empty( $date_on_sale_to ) ) {
					$date_on_sale_to = date( 'Y-m-d 23:59:59', strtotime( $date_on_sale_to ) );
				}
			}

			$props = [
				'date_on_sale_from' => $date_on_sale_from,
				'date_on_sale_to'   => $date_on_sale_to
			];

			if ( ! empty( $_fields['variable_regular_price'] ) ) {
				$props['regular_price'] = wc_clean( wp_unslash( $_fields['variable_regular_price'] ) );
			}

			if ( ! empty( $_fields['variable_sale_price'] ) ) {
				$props['sale_price'] = wc_clean( wp_unslash( $_fields['variable_sale_price'] ) );
			}

			// enable

			if ( $_fields['variable_enabled'] === '1' ) {
				$props['status'] = 'publish';
			}

			if ( $_fields['variable_enabled'] === '0' ) {
				$props['status'] = 'private';
			}

			// virtual

			if ( $_fields['variable_is_virtual'] === '1' ) {
				$props['virtual'] = true;
			}

			if ( $_fields['variable_is_virtual'] === '0' ) {
				$props['virtual'] = false;
			}

			// downloadable

			if ( $_fields['variable_is_downloadable'] === '1' ) {
				$props['downloadable'] = true;
			}

			if ( $_fields['variable_is_downloadable'] === '0' ) {
				$props['downloadable'] = false;
			}

			// manage stock

			if ( $_fields['variable_manage_stock'] === '1' ) {
				$props['manage_stock'] = true;
			}

			if ( $_fields['variable_manage_stock'] === '0' ) {
				$props['manage_stock'] = false;
			}

			// stock

			if ( ! empty( $_fields['variable_stock'] ) ) {
				$props['stock_quantity'] = wc_stock_amount( wp_unslash( $_fields['variable_stock'] ) );
			}

			// stock status

			if ( ! empty( $_fields['variable_stock_status'] ) && ( $_fields['variable_stock_status'] !== 'wpcvb_no_change' ) ) {
				$props['stock_status'] = wc_clean( wp_unslash( $_fields['variable_stock_status'] ) );
			}

			// description

			if ( ! empty( $_fields['variable_description'] ) ) {
				$props['description'] = wp_kses_post( wp_unslash( $_fields['variable_description'] ) );
			}

			// image

			if ( ! empty( $_fields['upload_image_id'] ) ) {
				$props['image_id'] = wc_clean( wp_unslash( $_fields['upload_image_id'] ) );
			}

			// dimension

			if ( ! empty( $_fields['variable_weight'] ) ) {
				$props['weight'] = wc_clean( wp_unslash( $_fields['variable_weight'] ) );
			}

			if ( ! empty( $_fields['variable_length'] ) ) {
				$props['length'] = wc_clean( wp_unslash( $_fields['variable_length'] ) );
			}

			if ( ! empty( $_fields['variable_width'] ) ) {
				$props['width'] = wc_clean( wp_unslash( $_fields['variable_width'] ) );
			}

			if ( ! empty( $_fields['variable_height'] ) ) {
				$props['height'] = wc_clean( wp_unslash( $_fields['variable_height'] ) );
			}

			foreach ( $variations as $variation_id ) {
				$variation = wc_get_product_object( 'variation', $variation_id );
				$errors    = $variation->set_props( $props );

				if ( is_wp_error( $errors ) ) {
					WC_Admin_Meta_Boxes::add_error( $errors->get_error_message() );
				}

				do_action( 'wpcvb_bulk_update_variation', $variation_id, $_fields, $props );

				$variation->save();
			}

			do_action( 'wpcvb_bulk_update_variations', $variations, $_fields, $props );
		}

		wp_die();
	}

	function ajax_bulk_remove() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcvb_nonce' ) ) {
			die( 'Permissions check failed!' );
		}

		$product_id = absint( sanitize_text_field( $_POST['post_id'] ?? 0 ) );
		$attrs      = self::sanitize_array( $_POST['attrs'] ?? [] );
		$variations = self::get_variations( $product_id, $attrs );

		if ( ! empty( $variations ) ) {
			foreach ( $variations as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				$variation->delete( true );
			}

			do_action( 'wpcvb_bulk_remove_variations', $variations );
		}

		wp_die();
	}

	function ajax_bulk_generate() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcvb_nonce' ) ) {
			die( 'Permissions check failed!' );
		}

		$count      = 0;
		$limit      = defined( 'WC_MAX_LINKED_VARIATIONS' ) ? WC_MAX_LINKED_VARIATIONS : 50;
		$attrs      = self::sanitize_array( $_POST['attrs'] ?? [] );
		$product_id = absint( sanitize_text_field( $_POST['post_id'] ?? 0 ) );
		$product    = wc_get_product( $product_id );
		$attributes = [];

		foreach ( $attrs as $attr ) {
			$attr_name  = $attr['name'];
			$attr_value = $attr['value'] === 'wpcvb_any' ? '' : $attr['value'];

			if ( isset( $attributes[ $attr_name ] ) ) {
				$attributes[ $attr_name ][] = $attr_value;
			} else {
				$attributes[ $attr_name ] = (array) $attr_value;
			}
		}

		if ( $product && ! empty( $attributes ) ) {
			// Get existing variations so we don't create duplicates.
			$existing_variations = array_map( 'wc_get_product', $product->get_children() );
			$existing_attributes = [];

			foreach ( $existing_variations as $existing_variation ) {
				$existing_attributes[] = $existing_variation->get_attributes();
			}

			$possible_attributes = array_reverse( wc_array_cartesian( $attributes ) );

			foreach ( $possible_attributes as $possible_attribute ) {
				// Allow any order if key/values -- do not use strict mode.
				if ( in_array( $possible_attribute, $existing_attributes ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					continue;
				}

				$variation = wc_get_product_object( 'variation' );
				$variation->set_parent_id( $product->get_id() );
				$variation->set_attributes( $possible_attribute );
				$variation_id = $variation->save();

				do_action( 'product_variation_linked', $variation_id );

				$count ++;

				if ( $limit > 0 && $count >= $limit ) {
					break;
				}
			}
		}

		do_action( 'wpcvb_bulk_generate_variations', $attrs );

		wp_die();
	}

	function get_variations( $product_id, $attrs ) {
		$variations = [];

		if ( ( $product = wc_get_product( $product_id ) ) && $product->is_type( 'variable' ) ) {
			if ( ( $children = $product->get_children() ) && ! empty( $children ) ) {
				if ( empty( $attrs ) ) {
					$variations = $children;
				} else {
					$new_attrs = [];

					foreach ( $attrs as $attr ) {
						$attr_name = $attr['name'];

						if ( isset( $new_attrs[ $attr_name ] ) ) {
							$new_attrs[ $attr_name ][] = $attr['value'];
						} else {
							$new_attrs[ $attr_name ] = (array) $attr['value'];
						}
					}

					foreach ( $children as $child ) {
						$variation       = wc_get_product_object( 'variation', $child );
						$variation_attrs = $variation->get_attributes();
						$match           = true;

						foreach ( $variation_attrs as $k => $a ) {
							if ( $a === '' ) {
								if ( ! empty( $new_attrs[ $k ] ) && ! in_array( 'wpcvb_any', $new_attrs[ $k ] ) ) {
									$match = false;
								}
							} else {
								if ( ! empty( $new_attrs[ $k ] ) && ! in_array( $a, $new_attrs[ $k ] ) ) {
									$match = false;
								}
							}
						}

						if ( $match ) {
							$variations[] = $child;
						}
					}
				}
			}
		}

		return $variations;
	}

	function get_ids( $variations ) {
		$ids = '';

		if ( is_array( $variations ) ) {
			if ( count( $variations ) > 3 ) {
				$ids = ' (#' . $variations[0] . ', ' . '#' . $variations[1] . ', ' . '#' . $variations[2] . ', ...)';
			} else if ( count( $variations ) == 3 ) {
				$ids = ' (#' . $variations[0] . ', ' . '#' . $variations[1] . ', ' . '#' . $variations[2] . ')';
			} else if ( count( $variations ) == 2 ) {
				$ids = ' (#' . $variations[0] . ', ' . '#' . $variations[1] . ')';
			} else if ( count( $variations ) == 1 ) {
				$ids = ' (#' . $variations[0] . ')';
			}
		}

		return $ids;
	}

	function sanitize_array( $arr ) {
		foreach ( (array) $arr as $k => $v ) {
			if ( is_array( $v ) ) {
				$arr[ $k ] = self::sanitize_array( $v );
			} else {
				$arr[ $k ] = sanitize_text_field( $v );
			}
		}

		return $arr;
	}
}

Wpcvb_Backend::instance();
