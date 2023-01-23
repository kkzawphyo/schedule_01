<?php
/**
 * @package MC_Export_Bookings_WC_to_CSV
 * @version 1.0.2
 */

/**
*
* Escape is someone tries to access directly
*
**/
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

/**
* Main plugin class
*
* @since 1.0
**/
if ( !class_exists( 'MC_Export_Bookings' ) ) {
	class MC_Export_Bookings {

		/**
		* Class contructor
		*
		* @since 1.0
		**/
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mc_wcb_csv_register_script' ) );
			add_action( 'wp_ajax_mc_wcb_find_booking', array( $this, 'mc_wcb_find_booking' ) );
			add_action( 'wp_ajax_mc_wcb_export', array( $this, 'mc_wcb_export' ) );
		}

		public function mc_wcb_csv_register_script( $hook ) {
			// Load only on export bookings pages
	        if( $hook != 'wc_booking_page_export-bookings-to-csv' ) {
	            return;
	        }

			wp_register_script( 'mc-wcb-script', MC_WCB_CSV . 'assets/mc-wcb-script.js', array( 'jquery' ), '1.0', true );
			wp_enqueue_script( 'mc-wcb-script' );
			wp_localize_script( 'mc-wcb-script', 'mc_wcb_params', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'security' => wp_create_nonce( 'mc-wcb-nonce' ) ) );

			wp_register_style('mc-wcb-css', MC_WCB_CSV . 'assets/mc-wcb-css.css');
			wp_enqueue_style( 'mc-wcb-css' );
		}

		/**
		* Add administration menus
		*
		* @since 0.1
		**/
		public function add_admin_pages() {
			add_submenu_page(
	            'edit.php?post_type=wc_booking',
//	            __( 'Export bookings', 'export-bookings-to-csv' ),
//	            __( 'Export bookings', 'export-bookings-to-csv' ),
	            __( '予約エキスポート', 'export-bookings-to-csv' ),
	            __( '予約エキスポート', 'export-bookings-to-csv' ),
	            'manage_options',
	            'export-bookings-to-csv',
	            array( $this,'mc_wcb_main_screen')
	        );
		}

		/**
		* Main plugin screen
		*/
		public function mc_wcb_main_screen() {

			$args = array(
			    'post_type' => 'product',
			    'posts_per_page' => -1,
			    'tax_query' => array(
		    		array(
		    			'taxonomy' => 'product_type',
		    			'field'    => 'slug',
		    			'terms'    => 'booking',
		    		),
		    	),
			);
			$products = get_posts($args);  // kim, ここで、All Productを取り出すから、
			// Query all products for display them in the select in the backoffice
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( '予約内容エキスポート' , 'export-bookings-to-csv' ); ?></h1>
				<div class="mc-wcb-export-box postbox">
					<form method="post" name="csv_exporter_form" action="" enctype="multipart/form-data">
						<?php wp_nonce_field( 'export-bookings-bookings_export', '_wpnonce-export-bookings' ); ?>
						<h2>1. <?php esc_html_e( '商品を選択してください :', 'export-bookings-to-csv' ); ?></h2>

						<label for="mc-wcb-product-select"><?php esc_html_e( '商品 : ', 'export-bookings-to-csv' ); ?></label>
						<select name="mc-wcb-product-select" id="mc-wcb-product-select" multiple> <!-- KIm -->
<!--							<option value=""><?php esc_html_e( '商品選択', 'export-bookings-to-csv' ); ?></option> -->
							<?php foreach($products as $product) {?>
								<option value="<?php echo $product->ID;?>" name="event" selected><?php echo $product->post_title; ?></option>
							<?php }?>
						</select>
						
						<div class="mc-wcb-dates">
							<label for="mc-wcb-dates"><?php esc_html_e( '予約日付の期間選択は、チェックをクリックしてください : ', 'export-bookings-to-csv' ); ?></label>
							<input type="checkbox" name="mc-wcb-dates" id="mc-wcb-dates" >
							<div class="mc-wcb-date-picker">
								<label for="mc_wcv_start_date"><?php esc_html_e( '開始日', 'export-bookings-to-csv' ); ?> :</label>
								    <input type="date" id="mc_wcv_start_date" name="mc_wcv_start_date" value="<?php echo date('Y-m-d') ; ?>" />
								<label for="mc_wcv_end_date"><?php esc_html_e( '終了日', 'export-bookings-to-csv' ); ?> :</label>
								    <input type="date" id="mc_wcv_end_date" name="mc_wcv_end_date" value="<?php echo date('Y-m-d') ; ?>" />
							</div>
						</div>
						<input type="submit" name="mc-wcb-fetch" id="mc-wcb-fetch" class="button button-secondary" value="<?php esc_html_e( '予約検索', 'export-bookings-to-csv' ); ?>" />
						<div class="mc-wcb-response">
							<img src="<?php echo MC_WCB_CSV ?>img/loader.svg" class="mc-wcb-loader"/>
							<div class="mc-wcb-result"></div>
						</div>
						<div class="mc-wcb-export">
							<h2>2. <?php esc_html_e( 'クリックしてCSVファイルダウンエキスポート開始 :', 'export-bookings-to-csv' ); ?></h2>
							<input type="submit" name="mc-wcb-submit" id="mc-wcb-submit" class="button button-primary" value="<?php esc_html_e( 'エキスポート', 'export-bookings-to-csv' ); ?>" />
						</div>
						<div class="mc-wcb-export-result">
							<p><?php esc_html_e( 'Be patient, export is in progress, please do not close this page.' , 'export-bookings-to-csv' ); ?></p>
							<p><?php esc_html_e( 'A download link will be displayed below at the end of the process.' , 'export-bookings-to-csv' ); ?></p>
						</div>
						<div class="mc-wcb-download">
							<h2>3. <?php esc_html_e( 'CSVファイルのダウンロード :', 'export-bookings-to-csv' ); ?></h2>
							<a href="#" class="mc-wcb-link"><?php _e( 'ダウンロード', 'export-bookings-to-csv' ); ?></a>
						</div>
					</form>
				</div>
				<?php
				$exports_list = $this->mc_wcb_list_exports();
				if ( $exports_list ) {
				?>
					<div class="mc-wcb-exports-list postbox">
						<?php
						$upload_dir = wp_upload_dir();
						echo '<h2>' . __( '前回エキスポート :', 'export-bookings-to-csv' ) . '</h2>';
						echo '<ul>';
						foreach ( $exports_list as $file ) {
							echo '<li><a href="' . $upload_dir['baseurl'] . '/woocommerce-bookings-exports/' . $file . '" class="mc-wcb-link"><span class="dashicons dashicons-download"></span>' . $file . '</a></li>';
						}
						echo '</ul>';
						?>
					</div>
				<?php } ?>
			</div>
			<?php
		}

		/**
		* mc_wcb_list_exports
		* List exports in uploads/woocommerce-bookings-exports/ folder
		* @since 1.0.2
		*/
		public function mc_wcb_list_exports() {

			// DEBUG
//			$data['message'] =  'mc_wcb_list_exports........';
//			wp_send_json_success( $data );


			$upload_dir = wp_upload_dir();
			$files  = @scandir( $upload_dir['basedir'] . '/woocommerce-bookings-exports' );

			$result = array();

			if ( ! empty( $files ) ) {

				foreach ( $files as $key => $value ) {

					if ( ! in_array( $value, array( '.', '..' ) ) ) {
						if ( ! is_dir( $value ) && strstr( $value, '.csv' ) ) {
							$result[ sanitize_title( $value ) ] = $value;
						}
					}
				}
			}

			return $result;
		}

		/**
		* Get bookings by product id
		* @since 1.0.2
		* @param $product_id int
		* @return $bookinds_ids array
		*/
		public function mc_wcb_get_bookings( $data_search ) {

			// DEBUG
//			$data['message'] =  'mc_wcb_get_bookings........';
//			wp_send_json_success( $data );

			if ( $data_search ) {

				$booking_data = new WC_Booking_Data_Store();

				$args = array(
					'object_id'   => $data_search['product_id'],
					'object_type' => 'product',
					'order_by' => 'start_date',
					'status'      => array( 'confirmed', 'paid', 'complete' ),
					'limit'        => -1,
				);

				if ( isset( $data_search['date_start'] ) && !empty( $data_search['date_start'] ) ) {
					$args['date_after'] = strtotime( $data_search['date_start'] );
				}

				if ( isset( $data_search['date_end'] ) && !empty( $data_search['date_end'] ) ) {
					$args['date_before'] = strtotime(  $data_search['date_end'] );
				}

				$bookings_ids = $booking_data->get_booking_ids_by( $args );

				return $bookings_ids;
			}

			return false;
		}

		/**
		* mc_wcb_find_booking
		* Find booking when select a product
		* @since 1.0.2
		**/
		public function mc_wcb_find_booking() {

// DEBUG
// $data['message'] =  'mc_wcb_find_booking........';
// wp_send_json_success( $data );

			$query_data = $_GET;

			$data = array();

			// verify nonce
			if ( ! wp_verify_nonce( $_GET['security'], 'mc-wcb-nonce' ) ) {
			    $error = -1;
			    wp_send_json_error( $error );
			    exit;
			}

			if ( isset( $_GET['selected_product_id'] ) && !empty( $_GET['selected_product_id'] ) ) {

				$data_search = array();

				$product_id = $_GET['selected_product_id'];

				$data_search['product_id'] = $product_id;

				if ( ! class_exists( 'WC_Booking_Data_Store' ) ) {
					$error = 0;
					$error['message'] = __( 'Can\'t found WC_Booking_Data_Store class.', 'export-bookings-to-csv' );
					wp_send_json_error( $error );
					exit;
				}

				if ( isset( $_GET['date_start'] ) && !empty( $_GET['date_start'] ) ) {
						$data_start = $_GET['date_start'];

					$data_search['date_start'] = $_GET['date_start'];
				}

				if ( isset( $_GET['date_end'] ) && !empty( $_GET['date_end'] ) ) {
					$data_end = $_GET['date_end'];

					$data_search['date_end'] = $_GET['date_end'];
				}

				$bookings_ids = $this->mc_wcb_get_bookings( $data_search );

				if ( $bookings_ids ) {
					$booking_count = count( $bookings_ids );
					$data['message'] =  sprintf( __( '<b>%d</b> 件存在.', 'export-bookings-to-csv' ), $booking_count );
					wp_send_json_success( $data );
				} else {
					$data['message'] =  __( 'データが存在しません。検索し直してください', 'export-bookings-to-csv' );
					wp_send_json_error( $data );
				}
			} else {
				$error['code'] = 1;
				$error['message'] =  __( '商品を選択して下さい.', 'export-bookings-to-csv' );
				wp_send_json_error( $error );
				exit;
			}

			wp_die();
		}

		/**
		* mc_wcb_export
		* Contruct PHP data array for CSV export
		*
		* @since 0.1
		**/
		public function mc_wcb_export(){

			// DEBUG
//			$data['message'] =  'mc_wcb_export........';
//			wp_send_json_success( $data );


			// verify nonce
			if ( ! wp_verify_nonce( $_GET['security'], 'mc-wcb-nonce' ) ) {
			    $error = -1;
			    wp_send_json_error( $error );
			    exit;
			}

			if ( isset( $_GET['selected_product_id'] ) && !empty( $_GET['selected_product_id'] ) ) {

				$product_id = $_GET['selected_product_id'];

				$data_search = array();

				$data_search['product_id'] = $product_id;

				if ( ! class_exists( 'WC_Booking_Data_Store' ) ) {
					$error = 0;
					$error['message'] = __( 'Can\'t found WC_Booking_Data_Store class.', 'export-bookings-to-csv' );
					wp_send_json_error( $error );
					exit;
				}

				if ( isset( $_GET['date_start'] ) && !empty( $_GET['date_start'] ) ) {
					$data_search['date_start'] = $_GET['date_start'];
				}

				if ( isset( $_GET['date_end'] ) && !empty( $_GET['date_end'] ) ) {
					$data_search['date_end'] = $_GET['date_end'];
				}

				$product_slug = get_post_field( 'post_name', $product_id );
				$file_name = $product_slug . '-' . date('d-m-Y-h-i');

				if ( ! class_exists( 'WC_Booking_Data_Store' ) ) {
					$error = 0;
					$error['message'] = __( 'Can\'t found WC_Booking_Data_Store class.', 'export-bookings-to-csv' );
					wp_send_json_error( $error );
					exit;
				}

				$bookings_ids = $this->mc_wcb_get_bookings( $data_search );

				if ( $bookings_ids ) {

					$json = array();

					$data = array();

					foreach ( $bookings_ids as $booking_id ) {

						$booking = new WC_Booking( $booking_id );

// var_dump($booking);

						$product_name = $booking->get_product()->get_title();

						$product_id = $booking->get_product_id();  // For test kim
// debug kim
//						$error['message'] = $product_id;
//						wp_send_json_error( $error );

				    	$resource = $booking->get_resource();
				    	if ( $booking->has_resources() && $resource ) {
				    		$booking_ressource = $resource->post_title;
				    	} else {
				    		$booking_ressource = 'N/A';
				    	}

						$start_date_timestamp = $booking->get_start();
						if ( $start_date_timestamp ) {
							$start_date = date( 'Y-m-d', $start_date_timestamp ); // kim
						} else {
							$start_date = 'N/A';
						}

						$end_date_timestamp = $booking->get_end();
						if ( $end_date_timestamp ) {
							$end_date = date( 'Y-m-d', $end_date_timestamp ); // kim
						} else {
							$end_date = 'N/A';
						}

						$person_count = $booking->get_persons_total();
						if ( !empty( $person_count ) ) {
							$booking_person_count = $person_count;
						} else {
							$booking_person_count = '0';
						}

						$order = $booking->get_order();
						if ( $order ) {

							$customer_name = ( '' !== $order->get_billing_first_name() ? $order->get_billing_first_name() : 'N/A' );
							$customer_last_name = ( $order->get_billing_last_name() ? $order->get_billing_last_name() : 'N/A' );
							$customer_mail = ( $order->get_billing_email() ? $order->get_billing_email() : 'N/A' );
							$customer_phone = ( $order->get_billing_phone() ? $order->get_billing_phone() : 'N/A' );
							$price = ( $order->get_total() ? $order->get_total() : 'N/A' );
						} else {
							$customer_name = $customer_last_name = $customer_mail = $customer_phone = $price = 'N/A';
						}

				    	if ( $start_date && $end_date ) { // check if there are a start date and end date
							$data[] = array($booking_id, $product_name, $start_date, $end_date, $booking_ressource, $customer_name, $customer_last_name, $customer_mail, $customer_phone, $price, $booking_person_count);
							// here we construct the array to pass informations to export CSV
						}
					}

//ここが、CSV export
					if ( $data && is_array( $data ) && !empty( $data ) ) {

						$delimiter = apply_filters( 'mc_wcb_csv_delimiter', ',' );
						$file_url = $this->array_to_csv_download( $data, $file_name, $delimiter ); // pass $data to array_to_csv_download function

						if ( $file_url ) {
							$json['file_url'] = $file_url;
							wp_send_json_success( $json );
						}
					}
				}
			}

			wp_die();
		}

		/**
		* array_to_csv_download
		* Process PHP array to CSV file
		* @param $data array
		* @param $filename string
		* @param $delimiter string
		* @return $file_url string
		* @since 1.0.0
		*/
		function array_to_csv_download( $data, $filename, $delimiter ) {

			ob_start();
			$upload_dir = wp_upload_dir();
			//$f = fopen( 'php://output', 'w');
			$f = fopen( $upload_dir['basedir'] . '/woocommerce-bookings-exports/' . $filename . '.csv', 'w' );
			$header = array(
	            __( 'Booking ID', 'export-bookings-to-csv' ),
	            __( 'Product', 'export-bookings-to-csv' ),
	            __( 'Start', 'export-bookings-to-csv' ),
	            __( 'End', 'export-bookings-to-csv' ),
	            __( 'Ressource', 'export-bookings-to-csv' ),
	            __( 'Last name', 'export-bookings-to-csv' ),
	            __( 'First name', 'export-bookings-to-csv' ),
	            __( 'Email', 'export-bookings-to-csv' ),
	            __( 'Phone', 'export-bookings-to-csv' ),
	            __( 'Paid price', 'export-bookings-to-csv' ),
	            __( 'Persons', 'export-bookings-to-csv' )
	        );
  		$str = mb_convert_encoding($header,"sjis","utf-8"); // kim

//			fputcsv($f, $header, $delimiter);
      fputcsv($f, $str, $delimiter);

			// loop over the input array
			foreach ($data as $line) {

				// generate csv lines from the inner arrays
				$str = mb_convert_encoding($line,"sjis","utf-8"); // kim

//				fputcsv($f, $line, $delimiter);
				fputcsv($f, $str, $delimiter);

			}
			fclose($f);

			$file_url = $upload_dir['baseurl'] . '/woocommerce-bookings-exports/' . $filename . '.csv';

			return $file_url;
		}
	}

	global $mc_wcb_csv;

	if ( ! isset( $mc_wcb_csv ) ) {

	    $mc_wcb_csv = new MC_Export_Bookings();

	}
}
