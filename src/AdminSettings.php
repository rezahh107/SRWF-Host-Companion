<?php

namespace SRWF\HostCompanion;

final class AdminSettings {
	const PAGE_SLUG     = 'srwf-host';
	const ACTION        = 'srwf_host_companion_save_apply';
	const NONCE_ACTION  = 'srwf_host_companion_save_apply';
	const NONCE_NAME    = '_srwf_host_nonce';
	const FIELD_PAGE_ID = 'srwf_registration_page_id';

	/**
	 * Attach the bounded V0 Owner settings workflow to native wp-admin hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_save_apply' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/srwf-host-companion.php' ), array( __CLASS__, 'add_settings_link' ) );
	}

	/**
	 * Register Settings → SRWF Host.
	 *
	 * @return string|false
	 */
	public static function register_menu() {
		return add_options_page(
			__( 'SRWF Host', 'srwf-host-companion' ),
			__( 'SRWF Host', 'srwf-host-companion' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Add a native Settings link to the Plugins row.
	 *
	 * @param array<int,string> $links Existing plugin action links.
	 * @return array<int,string>
	 */
	public static function add_settings_link( $links ) {
		$url = add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'options-general.php' ) );
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html__( 'تنظیمات', 'srwf-host-companion' )
			)
		);

		return $links;
	}

	/**
	 * Render the single V0 settings screen without changing stored state.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'شما اجازه دسترسی به تنظیمات SRWF Host را ندارید.', 'srwf-host-companion' ),
				esc_html__( 'دسترسی غیرمجاز', 'srwf-host-companion' ),
				array( 'response' => 403 )
			);
		}

		$current_page_id = Configuration::get_registration_page_id();
		$result_code     = '';

		if ( isset( $_GET['srwf_result'] ) && is_scalar( $_GET['srwf_result'] ) ) {
			$result_code = sanitize_key( wp_unslash( (string) $_GET['srwf_result'] ) );
		}
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'SRWF Host', 'srwf-host-companion' ); ?></h1>

			<?php self::render_result_notice( $result_code ); ?>

			<?php if ( 0 === $current_page_id ) : ?>
				<div class="notice notice-info inline">
					<p><strong><?php esc_html_e( 'راه‌اندازی اولیه', 'srwf-host-companion' ); ?></strong></p>
					<p><?php esc_html_e( 'این افزونه باعث می‌شود صفحه ثبت‌نام انتخاب‌شده از پوسته میزبان تمام‌عرض SRWF استفاده کند. فیلدهای Gravity Forms، طراحی فرم و داده‌های دانشجو را تغییر نمی‌دهد. فقط صفحه ثبت‌نام را انتخاب کنید و قالب را با دکمه زیر به‌صورت صریح اعمال کنید.', 'srwf-host-companion' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( 'صفحه‌ای را که نقش ثبت‌نام SRWF دارد انتخاب کنید. برای این کار نیازی به Site Editor، نام قالب یا ویرایش کد نیست.', 'srwf-host-companion' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::FIELD_PAGE_ID ); ?>"><?php esc_html_e( 'صفحه ثبت‌نام', 'srwf-host-companion' ); ?></label>
						</th>
						<td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => self::FIELD_PAGE_ID,
									'id'                => self::FIELD_PAGE_ID,
									'selected'          => $current_page_id,
									'show_option_none'  => __( '— یک صفحه انتخاب کنید —', 'srwf-host-companion' ),
									'option_none_value' => '0',
									'post_status'       => array( 'publish', 'private', 'draft', 'pending', 'future' ),
									'sort_column'       => 'post_title',
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'صفحه‌های منتشرشده و منتشرنشدهٔ معتبر قابل انتخاب‌اند؛ وضعیت انتشار قبل از اعمال بررسی می‌شود.', 'srwf-host-companion' ); ?></p>
						</td>
					</tr>
				</table>

				<p><?php esc_html_e( 'این کار صفحه انتخاب‌شده را به قالب «SRWF — Registration Full Width» متصل می‌کند. محتوای صفحه و داده‌های فرم را تغییر نمی‌دهد.', 'srwf-host-companion' ); ?></p>
				<p class="description"><?php esc_html_e( 'اگر صفحه ثبت‌نام را عوض کنید، صفحه جدید مرجع تنظیمات می‌شود و قالب به همان صفحه اعمال می‌شود. قالب صفحه قبلی به‌صورت خودکار بازنویسی یا بازیابی نمی‌شود.', 'srwf-host-companion' ); ?></p>

				<?php submit_button( __( 'ذخیره و اعمال قالب تمام‌عرض', 'srwf-host-companion' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the explicit mutation request, then return to the settings screen.
	 *
	 * @return void
	 */
	public static function handle_save_apply() {
		$result = self::process_save_apply( $_POST );

		if ( 'insufficient_manage_options' === $result['code'] ) {
			wp_die(
				esc_html__( 'شما اجازه تغییر تنظیمات SRWF Host را ندارید.', 'srwf-host-companion' ),
				esc_html__( 'دسترسی غیرمجاز', 'srwf-host-companion' ),
				array( 'response' => 403 )
			);
		}

		$url = add_query_arg(
			array(
				'page'        => self::PAGE_SLUG,
				'srwf_result' => $result['code'],
			),
			admin_url( 'options-general.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Validate, authorize and execute the explicit save/apply operation.
	 *
	 * @param array<string,mixed> $request Submitted request data.
	 * @return array<string,mixed>
	 */
	public static function process_save_apply( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return self::result( false, 'insufficient_manage_options' );
		}

		$nonce = '';
		if ( isset( $request[ self::NONCE_NAME ] ) && is_scalar( $request[ self::NONCE_NAME ] ) ) {
			$nonce = sanitize_text_field( wp_unslash( (string) $request[ self::NONCE_NAME ] ) );
		}

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return self::result( false, 'nonce_invalid' );
		}

		$page_id = self::normalize_page_id( $request[ self::FIELD_PAGE_ID ] ?? null );
		if ( $page_id <= 0 ) {
			return self::result( false, 'invalid_page_id' );
		}

		$page_state = self::classify_page( $page_id );
		if ( 'PAGE_MISSING' === $page_state['code'] ) {
			return self::result( false, 'page_missing', $page_id, $page_state['code'] );
		}
		if ( 'PAGE_TRASHED' === $page_state['code'] ) {
			return self::result( false, 'page_trashed', $page_id, $page_state['code'] );
		}
		if ( 'PAGE_TYPE_INVALID' === $page_state['code'] ) {
			return self::result( false, 'page_type_invalid', $page_id, $page_state['code'] );
		}

		if ( ! current_user_can( 'edit_post', $page_id ) ) {
			return self::result( false, 'target_edit_denied', $page_id, $page_state['code'] );
		}

		$previous_page_id = Configuration::get_registration_page_id();
		if ( ! Configuration::set_registration_page_id( $page_id ) ) {
			return self::result( false, 'configuration_save_failed', $page_id, $page_state['code'], false, false, $previous_page_id );
		}

		$assignment = PageTemplateAssignment::assign( $page_id );
		if ( empty( $assignment['success'] ) ) {
			return self::result(
				false,
				'configuration_saved_template_failed',
				$page_id,
				$page_state['code'],
				true,
				false,
				$previous_page_id,
				isset( $assignment['code'] ) ? (string) $assignment['code'] : 'assignment_failed'
			);
		}

		$readback = PageTemplateAssignment::read( $page_id );
		if ( TemplateRegistrar::TEMPLATE_SLUG !== $readback ) {
			return self::result(
				false,
				'configuration_saved_template_failed',
				$page_id,
				$page_state['code'],
				true,
				false,
				$previous_page_id,
				'readback_mismatch'
			);
		}

		$code = 'PAGE_NOT_PUBLISHED' === $page_state['code'] ? 'success_non_published' : 'success';

		return self::result( true, $code, $page_id, $page_state['code'], true, true, $previous_page_id, 'assigned' );
	}

	/**
	 * Classify a candidate target page without changing state.
	 *
	 * @param int $page_id Candidate object ID.
	 * @return array<string,string>
	 */
	public static function classify_page( $page_id ) {
		$post = get_post( $page_id );

		if ( ! $post instanceof \WP_Post ) {
			return array( 'code' => 'PAGE_MISSING', 'post_status' => '' );
		}

		if ( 'page' !== $post->post_type ) {
			return array( 'code' => 'PAGE_TYPE_INVALID', 'post_status' => (string) $post->post_status );
		}

		if ( 'trash' === $post->post_status ) {
			return array( 'code' => 'PAGE_TRASHED', 'post_status' => 'trash' );
		}

		if ( 'publish' === $post->post_status ) {
			return array( 'code' => 'PAGE_VALID', 'post_status' => 'publish' );
		}

		return array( 'code' => 'PAGE_NOT_PUBLISHED', 'post_status' => (string) $post->post_status );
	}

	/**
	 * @param string $result_code Result code supplied after an explicit action.
	 * @return void
	 */
	private static function render_result_notice( $result_code ) {
		$notices = array(
			'success' => array(
				'notice-success',
				__( 'تنظیم ذخیره شد و قالب تمام‌عرض به صفحه انتخاب‌شده اعمال و بازخوانی شد. این پیام فقط موفقیت همین عملیات را تأیید می‌کند و به معنی تأیید کامل سایت یا هندسه مرورگر نیست.', 'srwf-host-companion' ),
			),
			'success_non_published' => array(
				'notice-warning',
				__( 'تنظیم ذخیره شد و قالب تمام‌عرض اعمال و بازخوانی شد، اما صفحه انتخاب‌شده در حال حاضر منتشرشده نیست. اگر قرار است کاربران عمومی آن را ببینند، وضعیت انتشار صفحه را در WordPress بررسی کنید.', 'srwf-host-companion' ),
			),
			'nonce_invalid' => array(
				'notice-error',
				__( 'درخواست امنیتی معتبر نبود؛ هیچ تنظیم یا قالبی تغییر نکرد. صفحه را دوباره باز کنید و عملیات را تکرار کنید.', 'srwf-host-companion' ),
			),
			'invalid_page_id' => array(
				'notice-error',
				__( 'یک صفحه ثبت‌نام معتبر انتخاب نشده است؛ هیچ تغییری انجام نشد.', 'srwf-host-companion' ),
			),
			'page_missing' => array(
				'notice-error',
				__( 'صفحه انتخاب‌شده دیگر پیدا نشد؛ تنظیم قبلی حفظ شد و هیچ قالبی اعمال نشد. یک صفحه موجود را انتخاب کنید.', 'srwf-host-companion' ),
			),
			'page_trashed' => array(
				'notice-error',
				__( 'صفحه انتخاب‌شده در زباله‌دان است؛ تنظیم قبلی حفظ شد و هیچ قالبی اعمال نشد. ابتدا صفحه را بازیابی کنید یا صفحه دیگری را انتخاب کنید.', 'srwf-host-companion' ),
			),
			'page_type_invalid' => array(
				'notice-error',
				__( 'شناسه ارسال‌شده مربوط به یک «برگه» WordPress نیست؛ تنظیم قبلی حفظ شد و هیچ قالبی اعمال نشد.', 'srwf-host-companion' ),
			),
			'target_edit_denied' => array(
				'notice-error',
				__( 'شما اجازه ویرایش برگه انتخاب‌شده را ندارید؛ تنظیم قبلی حفظ شد و هیچ قالبی اعمال نشد. از مدیر سایت بخواهید دسترسی این برگه را بررسی کند.', 'srwf-host-companion' ),
			),
			'configuration_save_failed' => array(
				'notice-error',
				__( 'ذخیره تنظیم صفحه ثبت‌نام تأیید نشد؛ قالب اعمال نشد. دوباره تلاش کنید و اگر مشکل ادامه داشت، وضعیت WordPress را بررسی کنید.', 'srwf-host-companion' ),
			),
			'configuration_saved_template_failed' => array(
				'notice-error',
				__( 'صفحه ثبت‌نام در تنظیمات ذخیره شد، اما اعمال یا بازخوانی قالب تمام‌عرض تأیید نشد. محتوای صفحه تغییر نکرد؛ پیش از ادامه، این صفحه را دوباره بررسی کنید.', 'srwf-host-companion' ),
			),
		);

		if ( ! isset( $notices[ $result_code ] ) ) {
			return;
		}

		list( $class, $message ) = $notices[ $result_code ];
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> inline" role="status">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Reject malformed identifiers instead of relying on permissive numeric coercion.
	 *
	 * @param mixed $value Submitted value.
	 * @return int
	 */
	private static function normalize_page_id( $value ) {
		if ( is_int( $value ) ) {
			return $value > 0 ? $value : 0;
		}

		if ( ! is_string( $value ) || ! preg_match( '/^[1-9][0-9]*$/D', $value ) ) {
			return 0;
		}

		return absint( $value );
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function result( $success, $code, $page_id = 0, $page_state = '', $configuration_saved = false, $template_applied = false, $previous_page_id = 0, $assignment_code = '' ) {
		return array(
			'success'             => (bool) $success,
			'code'                => (string) $code,
			'page_id'             => (int) $page_id,
			'page_state'          => (string) $page_state,
			'configuration_saved' => (bool) $configuration_saved,
			'template_applied'    => (bool) $template_applied,
			'previous_page_id'    => (int) $previous_page_id,
			'assignment_code'     => (string) $assignment_code,
		);
	}
}
