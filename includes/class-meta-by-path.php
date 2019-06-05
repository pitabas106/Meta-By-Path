<?php
/**
 * Author: NetTantra
 * @package Meta By Path
 */


if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class WPMetaByPath {

	public $wpmbp_table;

  function __construct() {
    add_action( 'admin_menu', array( $this, 'wpmbp_create_setting_page') );
		add_action( 'wp_ajax_wpmbp_save_metainfo_data', array( $this, 'wpmbp_save_metainfo_data' ) );
		add_action( 'wp_ajax_wpmbp_get_all_data', array( $this, 'wpmbp_get_all_meta_data' ) );
		add_action( 'wp_ajax_wpmbp_delete_metainfo_data', array( $this, 'wpmbp_delete_metainfo_data' ) );

		add_action('wp_loaded',  array( $this, 'wpmbp_obstart_page'));
		add_action('wp_footer',  array( $this, 'wpmbp_obclean_page'));

		global $wpdb;
		$this->wpmbp_table = $wpdb->prefix . "meta_by_path";
  }

  public function wpmbp_create_setting_page() {
    add_options_page(__('Meta By Path', 'meta-by-path'), __('Meta By Path', 'meta-by-path'), 'manage_options', 'meta-by-path', array($this, 'wpmbp_metainfo_page'));
    add_action( 'admin_enqueue_scripts', array($this, 'wpmbp_load_wp_admin_style'));
	}


	public function wpmbp_delete_metainfo_data() {
		global $wpdb;

		if ( ! wp_verify_nonce( $_POST['nonce'], 'wp_meta_by_path_delete_nonce' ) ) {
			die( 'Security check!' );
		}

		if($_POST['type'] == 'delete' && $_POST['item_id']) {
			$item_id = intval($_POST['item_id']);
			$result = $wpdb->delete( $this->wpmbp_table, array( 'id' => $item_id ) );
		}

		if($result) {
			$result_data = json_encode(array(
				'error' 	=> false,
				'msg' 		=>  __('Item ID #'.$item_id.' was deleted succesfully!', 'meta-by-path'),
				'data' 		=> ''
			 ));
		} else {
				$result_data = json_encode(array(
					'error' 	=> true,
					'msg' 		=>  __('Error occured!', 'meta-by-path'),
					'data' 		=> ''
				 ));
		}
		 echo $result_data;
		 wp_die();
	}

	public function wpmbp_get_single_metainfo_data($item_id) {
		global $wpdb;
		$result = '';
		$item_id = intval($item_id);
		if($item_id) {
			$result = $wpdb->get_row( "SELECT * FROM $this->wpmbp_table WHERE ID = $item_id");
		}
		return $result;
	}

	public function wpmbp_get_all_meta_data() {
		global $wpdb;

		$result = $wpdb->get_results ( "SELECT * FROM $this->wpmbp_table ORDER BY id DESC");
		if($result) {
			$meta_info_result = '';
			$meta_info_wrap_result = '';
			$data = '<div class="all-data"><h3>'.esc_html__("All Meta Info", "meta-by-path").'</h3>';
			foreach($result as $key => $value) {
				if($value->all_page) {
					$page_url =  esc_html__("[Apply to all pages]");
				} else {
					$page_url = esc_url($value->pageurl);
				}

				$meta_info_header_section = '<table class="mbp-form-table wp-list-table widefat fixed striped ">';
				$met_info_page_url_section = '<tr>
					<th style="width: 100px;"><strong>'.esc_html__('Page URL').':</strong></th>
					<td>'.$page_url.'</td>
					<td style="text-align: right;">
						<a href="'.admin_url().'options-general.php?page=meta-by-path&id='.$value->id.'&action=edit" class="dashicons dashicons-edit"></a> | <span onclick="WPMBPApp.deleteMetaInfo(\''.$value->id.'\')" class="dashicons dashicons-trash"></span>
					</td>
				</tr>';

				if($value->meta_info) {
					$met_info_page_url_section .= '<tr>
						<td colspan="3">';
					$meta_info = json_decode(stripslashes($value->meta_info));
					$meta_info_result = '<table class="wp-list-table widefat fixed striped" style="width: 100%;">';
					$meta_info_result .= '<thead><tr class="desktop-view" style="background: #eee;">
								<th style="width: 50%;"><strong>'.esc_html__('Name').'</strong></th>
								<th style="width: 50%;"><strong>'.esc_html__('Value').'</strong></th>
							</tr>
						</thead>
						<tbody>';
					foreach ($meta_info as $k => $meta_value) {
						$meta_info_result .= '<tr>
								<td>
									<div class="mobile-view"><strong>'.esc_html__('Name').'</strong></div>
									'.$meta_value->keyword.'
								</td>
								<td>
									<div class="mobile-view"><strong>'.esc_html__('Value').'</strong></div>
									'.$meta_value->description.'
								</td>
							</tr>';
					}
					$meta_info_result .= '</tbody></table></td>
				</tr>';
				}

				$meta_info_footer_section = '</table><hr>';

				$data .=  $meta_info_header_section . $met_info_page_url_section. $meta_info_result . $meta_info_footer_section;
			}
			$data .= '</div>';

			$result_data = json_encode(array(
				'error' 	=> false,
				'msg' 		=>  __('Data feched succesfully!', 'meta-by-path'),
				'data' 		=> $data
			 ));
			} else {
				$result_data = json_encode(array(
					'error' 	=> true,
					'msg' 		=>  __('No data found!', 'meta-by-path'),
					'data' 		=> ''
				 ));
			}
		 echo $result_data;
		 wp_die();
	}


  public function wpmbp_save_metainfo_data() {
		global $wpdb;

		if ( ! wp_verify_nonce( $_POST['nonce'], 'wp_meta_by_path_nonce' ) ) {
			die( 'Security check!' );
		}

		if($_POST['form_data']['meta_info']) {
			$page_url = esc_url_raw($_POST['form_data']['page_url']);
			$all_page = intval(sanitize_key($_POST['form_data']['all_page']));
			$meta_info = sanitize_text_field($_POST['form_data']['meta_info']);
			$request_type = sanitize_text_field($_POST['type']);
			$id = intval(($_POST['id'])) ? intval($_POST['id']) : '';

			if($request_type == 'insert') {
				$wpdb->INSERT($this->wpmbp_table,
				      array(
								'pageurl' => $page_url,
								'all_page' => $all_page,
				        'meta_info' => $meta_info,
								'creation_timestamp' =>current_time( 'mysql'),
				        'created_by' => get_current_user_id(),
				      )
				);
			}

			if ($request_type == 'update'){
				$wpdb->UPDATE($this->wpmbp_table,
					 array(
						'pageurl' => $page_url,
						'all_page' => $all_page,
						'meta_info' => $meta_info,
						'modification_timestamp' => get_current_user_id(),
						'modified_by' => get_current_user_id(),
					 ),
					array(
							'id' => $id
					)
				);
			}

			$this->wpmbp_get_all_meta_data();

		} else {
			$result_data = json_encode(array(
				'error' 	=> true,
				'msg' 		=>  __('Please fill all the required fields!', 'meta-by-path'),
				'data' 		=> ''
			 ));
			 echo $result_data;
		}
		wp_die();
	}


  public function wpmbp_load_wp_admin_style($hook) {

    if($hook != 'settings_page_meta-by-path') {
        return;
    }
    $ajax_nonce = wp_create_nonce( "wp_meta_by_path_nonce" );
		$delete_nonce = wp_create_nonce( "wp_meta_by_path_delete_nonce" );
		$get_data_nonce = wp_create_nonce( "wp_meta_by_path_get_nonce" );

    wp_enqueue_style( 'main-css',  plugin_dir_url( __FILE__ ) . '../assets/css/main.css', '1.0' );
    wp_register_script( 'wpmbp-main-js',  plugin_dir_url( __FILE__ ) . '../assets/js/main.js', '1.0' );
    wp_localize_script( 'wpmbp-main-js', 'WP_Meta_By_Path', array(
      'ajax_url' 		=> admin_url( 'admin-ajax.php' ),
			'nonce'       => $ajax_nonce,
			'delete_nonce'       => $delete_nonce,
			'get_nonce'       => $get_data_nonce,
			'admin_url'  	=> admin_url(),
     ) );
    wp_enqueue_script( 'wpmbp-main-js' );
  }


  public function wpmbp_metainfo_page($form_data) {
		global $wpdb;


		if( isset($_GET['action'])  && $_GET['action'] == 'edit' && $_GET['id']) {
			$get_action = sanitize_text_field($_GET['action']);
			$item_id = intval($_GET['id']);
			$result = $this->wpmbp_get_single_metainfo_data($item_id);
			$form_data = $result;
		}

		$request_type = 'insert';
		$item_id = '';
		$pageurl = '';
		$all_page = '';
		$meta_info = '';
		if($form_data) {
			$item_id = intval(($form_data->id)) ? intval($form_data->id) : '';
			$pageurl = esc_url_raw(($form_data->pageurl)) ? esc_url_raw($form_data->pageurl) : '';
			$all_page = esc_html(($form_data->all_page)) ? 'checked' : '';
			$meta_info = sanitize_text_field(($form_data->meta_info)) ? json_decode(stripslashes(maybe_unserialize($form_data->meta_info))) : '';
			$request_type = 'update';
		}
    ?>
    <div class="wrap">
      <h1><?php echo esc_html__("Meta By Path Form", "meta-by-path");  ?></h1>
			<form action="" id="meta_info_form">
				<?php wp_nonce_field('wp_meta_by_path_nonce'); ?>
				<table class="mbp-form-table form-table">
					<tr>
						<th><label for="all_page"><?php echo esc_html__('Apply to all page', 'meta-by-path'); ?></label></th>
						<td>
							<input name="all_page" type="checkbox" id="all_page" value="1" <?php echo esc_html($all_page); ?>> <?php echo esc_html__('Apply to all page', 'meta-by-path'); ?><br>
							<p class="description"><?php echo esc_html__('It is up to apply the meta info for all pages.', 'meta-by-path'); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="page_url"><?php echo esc_html__('Page URL', 'meta-by-path'); ?></label></th>
						<td>
							<input type="text" name="page_url" id="page_url" value="<?php echo esc_url_raw($pageurl); ?>" class="regular-text" placeholder="<?php echo esc_url_raw(get_site_url()); ?>">
							<p class="description"><?php echo esc_html__('Enter the absolute page URL', 'meta-by-path'); ?>.</p>
						</td>
					</tr>
					<tr>
						<th><?php echo esc_html__('Meta Info', 'meta-by-path'); ?></th>
						<td>
							<table class="mbp-inner-form-table" id="meta_info_table">
								<thead>
									<tr class="desktop-view">
										<th style="width: 45%;"><?php echo esc_html__('Name', 'meta-by-path'); ?></th>
										<th style="width: 45%;"><?php echo esc_html__('Value', 'meta-by-path'); ?></th>
										<th style="width: 10%;">&nbsp;</th>
									</tr>
								</thead>

								<tbody>
									<?php if($meta_info): ?>
										<?php foreach($meta_info as $key => $value) : ?>
										<tr>
											<td><input class="regular-text" type="text" value="<?php echo sanitize_text_field($value->keyword);?>"></td>
											<td><input class="regular-text" type="text" value="<?php echo sanitize_text_field($value->description);?>"></td>
											<td class="nt-text-center"><span class="dashicons dashicons-trash" onclick="WPMBPApp.Remove(this);"></span></td>
										</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>

								<tfoot>
								<tr>
									<td>
										<div class="mobile-view"><strong><?php echo esc_html__('Name', 'meta-by-path'); ?></strong></div>
										<input id="meta_name" type="text" name="meta-name" class="regular-text" placeholder="<?php esc_html__('Meta Name/Property', 'meta-by-path'); ?>">
										<p class="description"><?php echo esc_html__('Enter the Meta Name/Property: {viewport} <meta name="viewport"', 'meta-by-path'); ?></p>
									</td>
									<td>
										<div class="mobile-view"><strong><?php echo esc_html__('Value', 'meta-by-path'); ?></strong></div>
										<input id="meta_value" type="text" name="meta-value" class="regular-text" placeholder="<?php esc_html__('Meta Content/Value', 'meta-by-path'); ?>">
										<p class="description"><?php echo esc_html__('Enter the Meta content: {content} content="initial-scale=1"/>', 'meta-by-path'); ?>.</p></p>
									</td>
									<td class="nt-text-center"><span onclick="WPMBPApp.Add()" class="dashicons dashicons-plus-alt"></span>
									</td>
								</tr>
								</tfoot>

							</table>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="hidden" name="id" id="item_id" value="<?php echo intval($item_id); ?>">
							<input type="hidden" name="type" value="<?php echo sanitize_text_field($request_type); ?>" id="request_type">
							<input type="button" name="submit" id="submit" class="button button-primary" value="<?php echo __('Save Meta Infos', 'meta-by-path'); ?>">
						</td>
					</tr>
				</table>
			</form>
			<div id="all-meta-data"></div>
    </div>

    <?php
  }

	public function wpmbp_obstart_page() {
	  ob_start(array($this, 'wpmbp_ob_process'));
	}

	public function wpmbp_ob_process($data) {
		$http = (is_ssl()) ? 'https' : 'http';
		$current_page_link = $http."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		global $wpdb;
		$all_page_result = $wpdb->get_row ( "SELECT * FROM $this->wpmbp_table WHERE all_page = 1 ORDER BY id DESC LIMIT 1");

		if($all_page_result) {
			$result = $all_page_result;
		} else {
			$result = $wpdb->get_row ( "SELECT * FROM $this->wpmbp_table WHERE pageurl= '$current_page_link' ORDER BY id DESC LIMIT 1");
		}
		if($result) {
			foreach ($result as $key => $value) {
				if($key == "meta_info") {
					$meta_info = json_decode(stripslashes(maybe_unserialize($value)));
					foreach($meta_info as $info) {

						if($info->keyword == 'title') {
							$pattern= '/(<title>)(.*)(<\/title>)/i';
							$replacement = '${1}'.$info->description.'$3';
							$data =  preg_replace($pattern, $replacement, $data);
						} else {
							$pattern= '/(<meta\s*(property|name)=(\'|\")'.$info->keyword.'(\'|\")\s*content=(\'|\"))(.*)((\'|\")\s*(\/)?>)/i';
							if (! preg_match($pattern,$data)){
								$pattern3='/<\/head>/i';
								$replacement = '<meta name="'.$info->keyword.'" content="'.$info->description.'" /> </head>';
								$data =  preg_replace($pattern3, $replacement, $data);
							} else{
								$replacement = '${1}'.$info->description.'$7';
								$data =  preg_replace($pattern, $replacement, $data);
							}
						}
					}
				}
			}
		}

		 return $data;
	}

	public function wpmbp_obclean_page() {
		ob_end_flush();
	}

}
