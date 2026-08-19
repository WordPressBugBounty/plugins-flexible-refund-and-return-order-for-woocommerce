<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Settings;

use Throwable;
use WC_Admin_Settings;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration\LegacySettingsMapper;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service\FormService;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
final class FormsController
{
    public const NONCE_ACTION = 'fr_save_form';
    public const NONCE_NAME = 'fr_form_nonce';
    public const TAB_SETTINGS = 'settings';
    public const TAB_FORM = 'form';
    private Renderer $renderer;
    private FormRepository $forms;
    private FormService $service;
    private FormConfigurationSanitizer $sanitizer;
    private bool $is_pro;
    public function __construct(Renderer $renderer, FormRepository $forms, FormService $service, FormConfigurationSanitizer $sanitizer, bool $is_pro)
    {
        $this->renderer = $renderer;
        $this->forms = $forms;
        $this->service = $service;
        $this->sanitizer = $sanitizer;
        $this->is_pro = $is_pro;
    }
    public function render_edit(string $request_type, string $active_tab): bool
    {
        $form = $this->forms->find_by_type($request_type);
        if (null === $form && !$this->can_edit_request_type($request_type)) {
            // Keep PRO-only settings visible in Free even before the repair migration runs.
            $form = (new LegacySettingsMapper())->get_system_form($request_type);
        }
        if (null === $form) {
            $this->renderer->output_render('form-unavailable');
            return \false;
        }
        $can_edit = $this->can_edit_request_type($form->get_request_type());
        $this->renderer->output_render('form-edit', ['form' => $form, 'is_pro' => $this->is_pro, 'is_readonly' => !$can_edit, 'active_tab' => $this->get_active_tab($request_type, $active_tab), 'settings' => $this->get_display_settings($form->get_settings()), 'condition_fields' => new ConditionSettingFactory($this->renderer, 'fr_form[settings][visibility_conditions]')]);
        return $can_edit;
    }
    public function can_edit_request_type(string $request_type): bool
    {
        RequestType::assert_valid($request_type);
        return $this->is_pro || RequestType::REFUND === $request_type;
    }
    public function get_active_tab(string $request_type, string $tab): string
    {
        if (!$this->can_edit_request_type($request_type)) {
            return self::TAB_SETTINGS;
        }
        return $this->normalize_tab($tab);
    }
    public function save(string $request_type, string $active_tab): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        $nonce = isset($_POST[self::NONCE_NAME]) ? sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            WC_Admin_Settings::add_error(__('The form could not be saved. Refresh the page and try again.', 'flexible-refund-and-return-order-for-woocommerce'));
            return;
        }
        $form = $this->forms->find_by_type($request_type);
        if (null === $form || !$this->can_edit_request_type($form->get_request_type()) || null === $form->get_id()) {
            WC_Admin_Settings::add_error(__('This form is unavailable.', 'flexible-refund-and-return-order-for-woocommerce'));
            return;
        }
        $input = isset($_POST['fr_form']) && is_array($_POST['fr_form']) ? wp_unslash($_POST['fr_form']) : [];
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (self::TAB_FORM === $this->normalize_tab($active_tab)) {
            $input['enabled'] = $form->is_enabled() ? 'yes' : 'no';
            $input['button_label'] = $form->get_button_label();
            $input['settings'] = $form->get_settings();
        } else {
            $input['schema'] = $form->get_schema();
        }
        if (!$this->is_pro) {
            if (!isset($input['settings']) || !is_array($input['settings'])) {
                $input['settings'] = [];
            }
            $input['settings'] = $this->get_display_settings($input['settings']);
        }
        $data = $this->sanitizer->sanitize($form->get_request_type(), $input, $form->get_settings());
        try {
            $this->service->update($form->get_id(), $data['enabled'], $data['button_label'], $data['schema'], $data['settings']);
            WC_Admin_Settings::add_message(__('Form saved.', 'flexible-refund-and-return-order-for-woocommerce'));
        } catch (Throwable $e) {
            WC_Admin_Settings::add_error(__('The form could not be saved. Please try again.', 'flexible-refund-and-return-order-for-woocommerce'));
        }
    }
    private function get_display_settings(array $settings): array
    {
        if ($this->is_pro) {
            return $settings;
        }
        $settings['visibility_conditions'] = [];
        $settings['auto_approval'] = 'no';
        $settings['auto_hide'] = 'no';
        $settings['auto_hide_settings'] = [];
        $settings['refund_type'] = 'bank';
        return $settings;
    }
    private function normalize_tab(string $tab): string
    {
        return self::TAB_FORM === $tab ? self::TAB_FORM : self::TAB_SETTINGS;
    }
}
