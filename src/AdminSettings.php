<?php

namespace SRWF\HostCompanion;

final class AdminSettings {
	const PAGE_SLUG         = 'srwf-host';
	const ACTION            = 'srwf_host_companion_save_apply';
	const NONCE_ACTION      = 'srwf_host_companion_save_apply';
	const NONCE_NAME        = '_srwf_host_nonce';
	const RESULT_NONCE_NAME = '_srwf_result_nonce';
	const FIELD_PAGE_ID     = 'srwf_registration_page_id';

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

		if ( isset( $_GET['srwf_result'], $_GET[ self::RESULT_NONCE_NAME ] ) && is_scalar( $_GET['srwf_result'] ) && is_scalar( $_GET[ self::RESULT_NONCE_NAME ] ) ) {
			$candidate_result = sanitize_key( wp_unslash( (string) $_GET['srwf_result'] ) );
			$result_nonce     = sanitize_text_field( wp_unslash( (string) $_GET[ self::RESULT_NONCE_NAME ] ) );

			if ( wp_verify_nonce( $result_nonce, self::result_nonce_action( $candidate_result ) ) ) {
				$result_code = $candidate_result;
			}
		}

		$diagnostics = TemplateDiagnostics::inspect();
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'SRWF Host', 'srwf-host-companion' ); ?></h1>

			<?php self::render_result_notice( $result_code ); ?>
			<?php self::render_diagnostics( $diagnostics ); ?>

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
				'page'                   => self::PAGE_SLUG,
				'srwf_result'            => $result['code'],
				self::RESULT_NONCE_NAME => wp_create_nonce( self::result_nonce_action( $result['code'] ) ),
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
		if ( TemplateDiagnostics::PAGE_MISSING === $page_state['code'] ) {
			return self::result( false, 'page_missing', $page_id, $page_state['code'] );
		}
		if ( TemplateDiagnostics::PAGE_TRASHED === $page_state['code'] ) {
			return self::result( false, 'page_trashed', $page_id, $page_state['code'] );
		}
		if ( TemplateDiagnostics::PAGE_TYPE_INVALID === $page_state['code'] ) {
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

		$code = TemplateDiagnostics::PAGE_NOT_PUBLISHED === $page_state['code'] ? 'success_non_published' : 'success';

		return self::result( true, $code, $page_id, $page_state['code'], true, true, $previous_page_id, 'assigned' );
	}

	/**
	 * Keep the WU-03 public page-classification seam while using the WU-04 model.
	 *
	 * @param int $page_id Candidate object ID.
	 * @return array<string,mixed>
	 */
	public static function classify_page( $page_id ) {
		return TemplateDiagnostics::classify_page( $page_id );
	}

	/**
	 * Render a compact practical status followed by optional technical evidence.
	 *
	 * @param array<string,mixed> $diagnostics Read-only diagnostic result.
	 * @return void
	 */
	private static function render_diagnostics( $diagnostics ) {
		$copy       = self::diagnostic_copy( $diagnostics );
		$page       = is_array( $diagnostics['page'] ?? null ) ? $diagnostics['page'] : array();
		$assignment = is_array( $diagnostics['assignment'] ?? null ) ? $diagnostics['assignment'] : array();
		$resolution = is_array( $diagnostics['resolution'] ?? null ) ? $diagnostics['resolution'] : array();
		$evidence   = is_array( $resolution['evidence'] ?? null ) ? $resolution['evidence'] : array();
		$check_url  = add_query_arg(
			array(
				'page'       => self::PAGE_SLUG,
				'srwf_check' => '1',
			),
			admin_url( 'options-general.php' )
		);
		?>
		<h2><?php esc_html_e( 'وضعیت فعلی SRWF Host', 'srwf-host-companion' ); ?></h2>
		<div class="notice <?php echo esc_attr( $copy['class'] ); ?> inline" role="status">
			<p><strong><?php echo esc_html( $copy['title'] ); ?></strong></p>
			<p><?php echo esc_html( $copy['message'] ); ?></p>
			<p><strong><?php esc_html_e( 'وضعیت صفحه ثبت‌نام:', 'srwf-host-companion' ); ?></strong> <?php echo esc_html( self::page_summary( $page ) ); ?></p>
			<p><strong><?php esc_html_e( 'اتصال صفحه به قالب:', 'srwf-host-companion' ); ?></strong> <?php echo esc_html( self::assignment_summary( $assignment ) ); ?></p>
			<p><strong><?php esc_html_e( 'اقدام بعدی:', 'srwf-host-companion' ); ?></strong> <?php echo esc_html( $copy['action'] ); ?></p>
		</div>
		<p>
			<a class="button button-secondary" href="<?php echo esc_url( $check_url ); ?>"><?php esc_html_e( 'بررسی دوباره', 'srwf-host-companion' ); ?></a>
			<span class="description"><?php esc_html_e( 'این بررسی فقط وضعیت فعلی را دوباره می‌خواند و هیچ تنظیم، محتوا، اتصال قالب یا سفارشی‌سازی را تغییر نمی‌دهد.', 'srwf-host-companion' ); ?></span>
		</p>
		<details>
			<summary><strong><?php esc_html_e( 'جزئیات فنی', 'srwf-host-companion' ); ?></strong></summary>
			<table class="widefat striped">
				<tbody>
					<?php self::render_technical_row( __( 'وضعیت تشخیصی', 'srwf-host-companion' ), $diagnostics['primary_state'] ?? TemplateDiagnostics::UNKNOWN ); ?>
					<?php self::render_technical_row( __( 'شناسه صفحه', 'srwf-host-companion' ), $page['page_id'] ?? 0 ); ?>
					<?php self::render_technical_row( __( 'وضعیت صفحه', 'srwf-host-companion' ), $page['code'] ?? TemplateDiagnostics::UNKNOWN ); ?>
					<?php self::render_technical_row( __( 'قالب مورد انتظار', 'srwf-host-companion' ), TemplateRegistrar::TEMPLATE_NAME ); ?>
					<?php self::render_technical_row( __( 'اتصال فعلی صفحه', 'srwf-host-companion' ), $assignment['actual_slug'] ?? '' ); ?>
					<?php self::render_technical_row( __( 'وضعیت resolve قالب', 'srwf-host-companion' ), $resolution['state'] ?? TemplateDiagnostics::UNKNOWN ); ?>
					<?php self::render_technical_row( __( 'Source', 'srwf-host-companion' ), $evidence['source'] ?? '' ); ?>
					<?php self::render_technical_row( __( 'Origin', 'srwf-host-companion' ), $evidence['origin'] ?? '' ); ?>
					<?php self::render_technical_row( __( 'Plugin', 'srwf-host-companion' ), $evidence['plugin'] ?? '' ); ?>
					<?php self::render_technical_row( __( 'WP template ID', 'srwf-host-companion' ), $evidence['wp_id'] ?? 0 ); ?>
					<?php self::render_technical_row( __( 'اثر انگشت نسخه مرجع', 'srwf-host-companion' ), $evidence['canonical_fingerprint'] ?? '' ); ?>
					<?php self::render_technical_row( __( 'اثر انگشت نسخه resolve‌شده', 'srwf-host-companion' ), $evidence['resolved_fingerprint'] ?? '' ); ?>
				</tbody>
			</table>
			<p><label for="srwf-host-diagnostic-report"><strong><?php esc_html_e( 'گزارش فنی امن برای پشتیبانی', 'srwf-host-companion' ); ?></strong></label></p>
			<textarea id="srwf-host-diagnostic-report" class="large-text code" rows="15" readonly dir="ltr"><?php echo esc_textarea( TemplateDiagnostics::build_report( $diagnostics ) ); ?></textarea>
			<p class="description"><?php esc_html_e( 'این گزارش شامل محتوای فرم، داده دانشجو، فایل آپلودشده، nonce، cookie یا اطلاعات ورود نیست.', 'srwf-host-companion' ); ?></p>
		</details>
		<?php
	}

	/**
	 * @param string $label Row label.
	 * @param mixed  $value Technical value.
	 * @return void
	 */
	private static function render_technical_row( $label, $value ) {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td><code dir="ltr"><?php echo esc_html( (string) $value ); ?></code></td>
		</tr>
		<?php
	}

	/**
	 * @param array<string,mixed> $page Page evidence.
	 * @return string
	 */
	private static function page_summary( $page ) {
		$code = (string) ( $page['code'] ?? TemplateDiagnostics::PAGE_MISSING );
		switch ( $code ) {
			case TemplateDiagnostics::PAGE_VALID:
				return __( 'برگه موجود و منتشرشده است.', 'srwf-host-companion' );
			case TemplateDiagnostics::PAGE_NOT_PUBLISHED:
				return __( 'برگه معتبر است اما در حال حاضر منتشرشده نیست.', 'srwf-host-companion' );
			case TemplateDiagnostics::PAGE_TRASHED:
				return __( 'برگه در زباله‌دان است.', 'srwf-host-companion' );
			case TemplateDiagnostics::PAGE_TYPE_INVALID:
				return __( 'شناسه ذخیره‌شده به یک برگه WordPress اشاره نمی‌کند.', 'srwf-host-companion' );
			case TemplateDiagnostics::PAGE_UNCONFIGURED:
				return __( 'هنوز برگه‌ای انتخاب نشده است.', 'srwf-host-companion' );
			case TemplateDiagnostics::PAGE_MISSING:
			default:
				return __( 'برگه ذخیره‌شده پیدا نشد.', 'srwf-host-companion' );
		}
	}

	/**
	 * @param array<string,mixed> $assignment Assignment evidence.
	 * @return string
	 */
	private static function assignment_summary( $assignment ) {
		$state = (string) ( $assignment['state'] ?? TemplateDiagnostics::ASSIGNMENT_UNAVAILABLE );
		if ( TemplateDiagnostics::ASSIGNMENT_EXPECTED === $state ) {
			return __( 'صفحه به قالب مورد انتظار SRWF متصل است.', 'srwf-host-companion' );
		}
		if ( TemplateDiagnostics::WRONG_PAGE_ASSIGNMENT === $state ) {
			return __( 'اتصال فعلی صفحه با قالب مورد انتظار SRWF یکسان نیست.', 'srwf-host-companion' );
		}
		return __( 'به‌دلیل وضعیت فعلی صفحه، اتصال قالب قابل ارزیابی نیست.', 'srwf-host-companion' );
	}

	/**
	 * Map machine evidence to practical Persian Owner guidance.
	 *
	 * @param array<string,mixed> $diagnostics Diagnostic result.
	 * @return array<string,string>
	 */
	private static function diagnostic_copy( $diagnostics ) {
		$state = (string) ( $diagnostics['primary_state'] ?? TemplateDiagnostics::UNKNOWN );
		$page  = is_array( $diagnostics['page'] ?? null ) ? $diagnostics['page'] : array();
		$res   = is_array( $diagnostics['resolution'] ?? null ) ? $diagnostics['resolution'] : array();
		$ev    = is_array( $res['evidence'] ?? null ) ? $res['evidence'] : array();

		switch ( $state ) {
			case TemplateDiagnostics::CANONICAL:
				return array(
					'class'   => 'notice-success',
					'title'   => __( 'اتصال قالب مطابق انتظار است', 'srwf-host-companion' ),
					'message' => __( 'صفحه ثبت‌نام معتبر است، اتصال صفحه به قالب SRWF درست است و WordPress همان نسخه ثبت‌شده توسط افزونه را resolve می‌کند. این نتیجه هندسه Full Width در مرورگر یا آمادگی Production را تأیید نمی‌کند.', 'srwf-host-companion' ),
					'action'  => __( 'در این بخش اقدامی لازم نیست. در صورت نیاز «بررسی دوباره» را بزنید.', 'srwf-host-companion' ),
				);
			case 'NEEDS_SETUP':
				return array(
					'class'   => 'notice-info',
					'title'   => __( 'هنوز صفحه ثبت‌نام انتخاب نشده است', 'srwf-host-companion' ),
					'message' => __( 'بدون انتخاب صفحه، افزونه نمی‌تواند اتصال قالب Registration را برای یک صفحه مشخص ارزیابی کند.', 'srwf-host-companion' ),
					'action'  => __( 'صفحه ثبت‌نام را انتخاب کنید و فقط در صورت قصد تغییر، دکمه «ذخیره و اعمال قالب تمام‌عرض» را بزنید.', 'srwf-host-companion' ),
				);
			case 'PAGE_INVALID':
				if ( TemplateDiagnostics::PAGE_TRASHED === ( $page['code'] ?? '' ) ) {
					$message = __( 'صفحه ثبت‌نام ذخیره‌شده اکنون در زباله‌دان است؛ بررسی وضعیت قالب روی این هدف قابل اتکا نیست.', 'srwf-host-companion' );
				} elseif ( TemplateDiagnostics::PAGE_TYPE_INVALID === ( $page['code'] ?? '' ) ) {
					$message = __( 'شناسه ذخیره‌شده دیگر به یک «برگه» WordPress اشاره نمی‌کند؛ این هدف برای نقش Registration معتبر نیست.', 'srwf-host-companion' );
				} else {
					$message = __( 'صفحه ثبت‌نام ذخیره‌شده پیدا نشد؛ تنظیم موجود به‌صورت خودکار تغییر یا جایگزین نشده است.', 'srwf-host-companion' );
				}
				return array(
					'class'   => 'notice-error',
					'title'   => __( 'صفحه ثبت‌نام نیاز به اصلاح دارد', 'srwf-host-companion' ),
					'message' => $message,
					'action'  => __( 'یک برگه معتبر را انتخاب کنید و تغییر را با دکمه اصلی به‌صورت صریح اعمال کنید.', 'srwf-host-companion' ),
				);
			case TemplateDiagnostics::WRONG_PAGE_ASSIGNMENT:
				return array(
					'class'   => 'notice-warning',
					'title'   => __( 'قالب مورد انتظار به صفحه ثبت‌نام متصل نیست', 'srwf-host-companion' ),
					'message' => __( 'تنظیم Registration به این صفحه اشاره می‌کند، اما اتصال قالب صفحه با قالب SRWF یکسان نیست. ممکن است WordPress صفحه را با پوسته دیگری نمایش دهد؛ هندسه واقعی مرورگر در WU-05 بررسی می‌شود.', 'srwf-host-companion' ),
					'action'  => __( 'اگر می‌خواهید اتصال اصلاح شود، همان صفحه را انتخاب کنید و دکمه «ذخیره و اعمال قالب تمام‌عرض» را بزنید.', 'srwf-host-companion' ),
				);
			case TemplateDiagnostics::CUSTOMIZED_DB_OVERRIDE:
				$match = true === ( $ev['content_matches_canonical'] ?? null );
				return array(
					'class'   => 'notice-warning',
					'title'   => __( 'یک نسخه سفارشی‌شده در پایگاه داده بر قالب مرجع مقدم است', 'srwf-host-companion' ),
					'message' => $match
						? __( 'WordPress برای این نام قالب یک نسخه ذخیره‌شده در پایگاه داده را resolve می‌کند. محتوای نرمال‌شده فعلاً با نسخه افزونه برابر است، اما منبع اجرایی همان override پایگاه داده است.', 'srwf-host-companion' )
						: __( 'WordPress برای این نام قالب یک نسخه ذخیره‌شده در پایگاه داده را resolve می‌کند و محتوای نرمال‌شده آن با نسخه مرجع افزونه تفاوت دارد.', 'srwf-host-companion' ),
					'action'  => __( 'جزئیات فنی را بررسی کنید. WU-04 این سفارشی‌سازی را حذف یا بازنویسی نمی‌کند و بازیابی خودکار ندارد.', 'srwf-host-companion' ),
				);
			case TemplateDiagnostics::THEME_OVERRIDE:
				$match = true === ( $ev['content_matches_canonical'] ?? null );
				return array(
					'class'   => 'notice-warning',
					'title'   => __( 'پوسته فعال نسخه‌ای با همین نام قالب دارد', 'srwf-host-companion' ),
					'message' => $match
						? __( 'WordPress فایل قالب پوسته فعال را مقدم بر نسخه ثبت‌شده افزونه resolve می‌کند. محتوای نرمال‌شده فعلاً برابر است، اما منبع اجرایی پوسته است.', 'srwf-host-companion' )
						: __( 'WordPress فایل قالب پوسته فعال را مقدم بر نسخه ثبت‌شده افزونه resolve می‌کند و محتوای نرمال‌شده آن با نسخه مرجع افزونه تفاوت دارد.', 'srwf-host-companion' ),
					'action'  => __( 'جزئیات فنی را بررسی کنید. این افزونه فایل پوسته را تغییر یا حذف نمی‌کند.', 'srwf-host-companion' ),
				);
			case TemplateDiagnostics::MISSING_TEMPLATE:
				return array(
					'class'   => 'notice-error',
					'title'   => __( 'قالب مرجع SRWF در حال حاضر resolve نمی‌شود', 'srwf-host-companion' ),
					'message' => __( 'اتصال صفحه ممکن است نام قالب SRWF را نگه داشته باشد، اما WordPress اکنون هیچ template قابل resolve برای آن پیدا نکرد.', 'srwf-host-companion' ),
					'action'  => __( 'وضعیت فعال بودن افزونه را بررسی کنید و سپس «بررسی دوباره» را بزنید. این صفحه هیچ تعمیر خودکاری انجام نمی‌دهد.', 'srwf-host-companion' ),
				);
			case TemplateDiagnostics::PAGE_NOT_PUBLISHED:
				return array(
					'class'   => 'notice-warning',
					'title'   => __( 'صفحه معتبر است اما منتشرشده نیست', 'srwf-host-companion' ),
					'message' => __( 'صفحه Registration و اتصال قالب مطابق انتظار هستند، اما وضعیت انتشار صفحه برای دسترسی عمومی آماده نیست.', 'srwf-host-companion' ),
					'action'  => __( 'اگر کاربران عمومی باید صفحه را ببینند، وضعیت انتشار برگه را در WordPress بررسی کنید.', 'srwf-host-companion' ),
				);
			case TemplateDiagnostics::UNKNOWN:
			default:
				return array(
					'class'   => 'notice-warning',
					'title'   => __( 'منبع قالب با اطمینان قابل طبقه‌بندی نیست', 'srwf-host-companion' ),
					'message' => __( 'WordPress یک نتیجه برای قالب برگردانده است، اما شواهد منبع آن با قراردادهای اثبات‌شده plugin، theme یا database override منطبق نیست. این وضعیت به‌عنوان خطای قطعی یا موفقیت نمایش داده نمی‌شود.', 'srwf-host-companion' ),
					'action'  => __( 'جزئیات فنی را بررسی کنید و «بررسی دوباره» را بزنید. اگر وضعیت باقی ماند، بدون شواهد بیشتر علت خاصی فرض نکنید.', 'srwf-host-companion' ),
				);
		}
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
	 * Bind an Owner-facing result notice to the actual explicit action response.
	 *
	 * @param string $code Internal result code.
	 * @return string
	 */
	private static function result_nonce_action( $code ) {
		return 'srwf_host_companion_result_' . sanitize_key( $code );
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
