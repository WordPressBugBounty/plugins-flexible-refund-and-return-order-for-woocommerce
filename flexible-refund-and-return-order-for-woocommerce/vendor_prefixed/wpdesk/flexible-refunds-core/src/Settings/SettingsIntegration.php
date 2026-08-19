<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings;

use WC_Admin_Settings;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\OrderReferenceResolver;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Plugin;
use FRFreeVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
class SettingsIntegration implements Hookable
{
    public const PAGE_REFUND = 'flexible-refunds';
    public const PAGE_RECLAMATION = 'flexible-refunds-reclamation';
    public const PAGE_REPAIR = 'flexible-refunds-repair';
    public const PAGE_GLOBAL = 'flexible-refunds-global';
    private const GLOBAL_NONCE_ACTION = 'fr_save_global_settings';
    private const MENU_POSITION = 55.7;
    private FormsController $forms_controller;
    private Renderer $renderer;
    public function __construct(FormsController $forms_controller, Renderer $renderer)
    {
        $this->forms_controller = $forms_controller;
        $this->renderer = $renderer;
    }
    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'save']);
    }
    public function admin_menu(): void
    {
        $menu_title = esc_html__('Flexible Refund', 'flexible-refund-and-return-order-for-woocommerce');
        add_menu_page($menu_title, $menu_title, 'manage_woocommerce', self::PAGE_REFUND, [$this, 'output'], 'dashicons-image-rotate', self::MENU_POSITION);
        add_submenu_page(self::PAGE_REFUND, esc_html__('Refund', 'flexible-refund-and-return-order-for-woocommerce'), esc_html__('Refund', 'flexible-refund-and-return-order-for-woocommerce'), 'manage_woocommerce', self::PAGE_REFUND, [$this, 'output']);
        add_submenu_page(self::PAGE_REFUND, esc_html__('Reclamation', 'flexible-refund-and-return-order-for-woocommerce'), esc_html__('Reclamation', 'flexible-refund-and-return-order-for-woocommerce'), 'manage_woocommerce', self::PAGE_RECLAMATION, [$this, 'output']);
        add_submenu_page(self::PAGE_REFUND, esc_html__('Repair', 'flexible-refund-and-return-order-for-woocommerce'), esc_html__('Repair', 'flexible-refund-and-return-order-for-woocommerce'), 'manage_woocommerce', self::PAGE_REPAIR, [$this, 'output']);
        add_submenu_page(self::PAGE_REFUND, esc_html__('Global settings', 'flexible-refund-and-return-order-for-woocommerce'), esc_html__('Global settings', 'flexible-refund-and-return-order-for-woocommerce'), 'manage_woocommerce', self::PAGE_GLOBAL, [$this, 'output']);
    }
    public static function is_settings_page(): bool
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return in_array($page, self::get_page_slugs(), \true);
    }
    /** @return string[] */
    public static function get_page_slugs(): array
    {
        return [self::PAGE_REFUND, self::PAGE_RECLAMATION, self::PAGE_REPAIR, self::PAGE_GLOBAL];
    }
    public function output(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        $request_type = $this->get_current_request_type();
        $editor_tab = null === $request_type ? FormsController::TAB_SETTINGS : $this->forms_controller->get_active_tab($request_type, $this->get_current_editor_tab());
        ?>
		<div class="wrap woocommerce fr-settings-page">
			<?php 
        $this->output_page_header($request_type);
        ?>
			<?php 
        WC_Admin_Settings::show_messages();
        ?>
			<?php 
        if (null !== $request_type) {
            ?>
				<?php 
            $this->output_editor_tabs($request_type, $editor_tab);
            ?>
			<?php 
        }
        ?>
			<form method="post" id="mainform" action="" enctype="multipart/form-data">
				<?php 
        if (null === $request_type) {
            ?>
					<?php 
            WC_Admin_Settings::output_fields($this->get_global_settings());
            ?>
					<?php 
            wp_nonce_field(self::GLOBAL_NONCE_ACTION);
            ?>
					<?php 
            submit_button();
            ?>
				<?php 
        } else {
            ?>
					<?php 
            wp_nonce_field(FormsController::NONCE_ACTION, FormsController::NONCE_NAME);
            ?>
					<table class="form-table">
						<tbody>
							<?php 
            $can_save = $this->forms_controller->render_edit($request_type, $editor_tab);
            ?>
						</tbody>
					</table>
					<?php 
            if ($can_save) {
                ?>
						<?php 
                submit_button();
                ?>
					<?php 
            }
            ?>
				<?php 
        }
        ?>
			</form>
		</div>
		<?php 
    }
    private function output_page_header(?string $request_type): void
    {
        $is_global_settings = null === $request_type;
        $title = $is_global_settings ? __('Global settings', 'flexible-refund-and-return-order-for-woocommerce') : RequestType::get_settings_title($request_type);
        $description = $is_global_settings ? __('Configure settings shared by all request types and public request forms.', 'flexible-refund-and-return-order-for-woocommerce') : __('Define the settings for the refund process and its form fields.', 'flexible-refund-and-return-order-for-woocommerce');
        $docs_url = add_query_arg(['utm_source' => 'wp-admin-plugins', 'utm_medium' => 'link', 'utm_campaign' => 'flexible-refund-docs', 'utm_content' => $is_global_settings ? 'global-settings' : 'request-settings'], Plugin::get_url_to_docs());
        ?>
		<header class="fr-settings-header">
			<h1><?php 
        echo esc_html($title);
        ?></h1>
			<p><?php 
        echo esc_html($description);
        ?></p>
			<p class="fr-settings-docs">
				<?php 
        echo wp_kses_post(sprintf(
            /* translators: 1: opening documentation link tag, 2: closing link tag. */
            __('Read more in the %1$splugin documentation →%2$s', 'flexible-refund-and-return-order-for-woocommerce'),
            '<a href="' . esc_url($docs_url) . '" target="_blank" rel="noopener noreferrer">',
            '</a>'
        ));
        ?>
			</p>
		</header>
		<?php 
    }
    private function output_editor_tabs(string $request_type, string $active_tab): void
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : self::PAGE_REFUND;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $can_edit_form = $this->forms_controller->can_edit_request_type($request_type);
        $labels = [FormsController::TAB_SETTINGS => esc_html__('Settings', 'flexible-refund-and-return-order-for-woocommerce'), FormsController::TAB_FORM => esc_html__('Edit form', 'flexible-refund-and-return-order-for-woocommerce')];
        $tabs = [];
        foreach ($labels as $tab => $label) {
            $is_disabled = FormsController::TAB_FORM === $tab && !$can_edit_form;
            $url = '';
            if (!$is_disabled) {
                $url = add_query_arg(['page' => $page, 'view' => $tab], admin_url('admin.php'));
            }
            $tabs[] = ['label' => $label, 'url' => $url, 'is_active' => $active_tab === $tab, 'is_disabled' => $is_disabled];
        }
        $this->renderer->output_render('editor-tabs', ['tabs' => $tabs]);
    }
    private function get_global_settings(): array
    {
        $is_pro = \FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration::is_super();
        $cancel_option_id = 'fr_refund_refund_cancel_button';
        return [['title' => '', 'type' => 'title', 'id' => 'fr_refund_global_settings'], ['title' => esc_html__('Search by visible order number', 'flexible-refund-and-return-order-for-woocommerce'), 'id' => 'fr_refund_' . OrderReferenceResolver::SEARCH_BY_ORDER_NUMBER_OPTION, 'desc' => esc_html__('Enable', 'flexible-refund-and-return-order-for-woocommerce'), 'desc_tip' => esc_html__('Use the order number visible to the customer in the public request form.', 'flexible-refund-and-return-order-for-woocommerce'), 'default' => 'no', 'type' => 'checkbox'], ['title' => esc_html__('Cancel unpaid order button', 'flexible-refund-and-return-order-for-woocommerce'), 'id' => $cancel_option_id, 'desc' => esc_html__('Enable', 'flexible-refund-and-return-order-for-woocommerce'), 'desc_tip' => esc_html__("Show the cancel order button for unpaid orders in the customer's My Account.", 'flexible-refund-and-return-order-for-woocommerce'), 'default' => 'no', 'value' => $is_pro ? WC_Admin_Settings::get_option($cancel_option_id, 'no') : 'no', 'type' => 'checkbox', 'custom_attributes' => $is_pro ? [] : ['disabled' => 'disabled']], ['type' => 'sectionend', 'id' => 'fr_refund_global_settings']];
    }
    public function save(): void
    {
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        if (!self::is_settings_page() || !current_user_can('manage_woocommerce') || 'POST' !== $request_method) {
            return;
        }
        $request_type = $this->get_current_request_type();
        if (null !== $request_type) {
            $this->forms_controller->save($request_type, $this->get_current_editor_tab());
            return;
        }
        check_admin_referer(self::GLOBAL_NONCE_ACTION);
        WC_Admin_Settings::save_fields($this->get_global_settings());
        WC_Admin_Settings::add_message(__('Your settings have been saved.', 'flexible-refund-and-return-order-for-woocommerce'));
    }
    private function get_current_request_type(): ?string
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $types = [self::PAGE_REFUND => RequestType::REFUND, self::PAGE_RECLAMATION => RequestType::RECLAMATION, self::PAGE_REPAIR => RequestType::REPAIR];
        return $types[$page] ?? null;
    }
    private function get_current_editor_tab(): string
    {
        $tab = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : FormsController::TAB_SETTINGS;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return FormsController::TAB_FORM === $tab ? FormsController::TAB_FORM : FormsController::TAB_SETTINGS;
    }
}
