<?php

/**
 * Plugin Name: WhatsApp Lead Widget
 * Plugin URI:  https://github.com/seu-usuario/whatsapp-lead-widget
 * Description: Botão flutuante do WhatsApp com formulário de captura de leads. Envia leads por e-mail e salva no painel administrativo.
 * Version:     1.0.0
 * Author:      Leonardo Pang
 * License:     GPL-2.0+
 * Text Domain: whatsapp-lead-widget
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WLW_VERSION', '1.1.0');
define('WLW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WLW_PLUGIN_URL', plugin_dir_url(__FILE__));

// ─── Activation / Deactivation ────────────────────────────────────────────────

register_activation_hook(__FILE__, 'wlw_activate');
function wlw_activate()
{
    wlw_create_leads_table();
    update_option('wlw_db_version', WLW_VERSION);
    // Default options
    add_option('wlw_whatsapp_number', '');
    add_option('wlw_notification_email', get_option('admin_email'));
    add_option('wlw_button_message', 'Fale conosco no WhatsApp!');
    add_option('wlw_modal_title', 'Falar no WhatsApp');
    add_option('wlw_modal_subtitle', 'Preencha para iniciar a conversa');
    add_option('wlw_whatsapp_message', 'Olá! Vim pelo site e gostaria de mais informações.');
    add_option('wlw_show_company_field', '1');
    add_option('wlw_button_position', 'right');
    add_option('wlw_widget_mode', 'form');
    add_option('wlw_recaptcha_site_key', '');
    add_option('wlw_recaptcha_secret_key', '');
    // Style options
    add_option('wlw_header_color',          '#1e2a3b');
    add_option('wlw_header_title_color',    '#e8edf5');
    add_option('wlw_header_subtitle_color', '#7a8fa6');
    add_option('wlw_header_icon_color',     '#25d366');
    add_option('wlw_header_icon_svg_color', '');
    add_option('wlw_close_bg',              '');
    add_option('wlw_close_color',           '');
    add_option('wlw_tooltip_bg',            '');
    add_option('wlw_tooltip_color',         '');
    add_option('wlw_bg_color',              '#1a2230');
    add_option('wlw_label_color',           '#e8edf5');
    add_option('wlw_input_color',           '#e8edf5');
    add_option('wlw_input_bg',              '#1a2636');
    add_option('wlw_btn_bg',                '#25d366');
    add_option('wlw_btn_color',             '#ffffff');
    add_option('wlw_text_color',            '#e8edf5');
    add_option('wlw_font_family',           '');
    add_option('wlw_use_site_font',         '0');
    // Form fields
    add_option('wlw_form_fields', wlw_default_fields_json());
}

function wlw_default_fields_json()
{
    return json_encode([
        ['id' => 'full_name',     'label' => 'Nome completo',                    'placeholder' => 'Seu nome',           'type' => 'text',   'required' => true,  'enabled' => true,  'system' => true,  'options' => ''],
        ['id' => 'email',         'label' => 'E-mail',                            'placeholder' => 'voce@empresa.com.br', 'type' => 'email',  'required' => true,  'enabled' => true,  'system' => true,  'options' => ''],
        ['id' => 'clinic',        'label' => 'Nome da clínica em que atua',       'placeholder' => 'Nome da clínica',    'type' => 'text',   'required' => true,  'enabled' => true,  'system' => false, 'options' => ''],
        ['id' => 'collaborators', 'label' => 'Quantidade de colaboradores',        'placeholder' => '',                   'type' => 'select', 'required' => true,  'enabled' => true,  'system' => false, 'options' => "1 a 5\n6 a 20\n21 a 50\n51 a 100\nMais de 100"],
    ]);
}

register_deactivation_hook(__FILE__, 'wlw_deactivate');
function wlw_deactivate() {}

// ─── DB Upgrade on Plugin Load ────────────────────────────────────────────────

add_action('plugins_loaded', 'wlw_maybe_upgrade_db');
function wlw_maybe_upgrade_db()
{
    if (get_option('wlw_db_version') !== WLW_VERSION) {
        wlw_create_leads_table();
        update_option('wlw_db_version', WLW_VERSION);
    }
}

// ─── Database ─────────────────────────────────────────────────────────────────

function wlw_create_leads_table()
{
    global $wpdb;
    $table = $wpdb->prefix . 'wlw_leads';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        full_name     VARCHAR(200) NOT NULL,
        email         VARCHAR(200) NOT NULL,
        phone         VARCHAR(50)  NOT NULL,
        company       VARCHAR(200) DEFAULT '',
        extra_fields  TEXT         DEFAULT '',
        created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_address    VARCHAR(50)  DEFAULT '',
        PRIMARY KEY (id)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// ─── Admin Menu ───────────────────────────────────────────────────────────────

add_action('admin_menu', 'wlw_admin_menu');
function wlw_admin_menu()
{
    add_menu_page(
        'WhatsApp Lead Widget',
        'WhatsApp Leads',
        'manage_options',
        'wlw-leads',
        'wlw_leads_page',
        'dashicons-whatsapp',
        58
    );
    add_submenu_page(
        'wlw-leads',
        'Leads Capturados',
        'Leads Capturados',
        'manage_options',
        'wlw-leads',
        'wlw_leads_page'
    );
    add_submenu_page(
        'wlw-leads',
        'Configurações',
        'Configurações',
        'manage_options',
        'wlw-settings',
        'wlw_settings_page'
    );
}

// ─── Admin Styles ─────────────────────────────────────────────────────────────

add_action('admin_enqueue_scripts', 'wlw_admin_styles');
function wlw_admin_styles($hook)
{
    if (strpos($hook, 'wlw') === false) return;
    wp_enqueue_style('wlw-admin', WLW_PLUGIN_URL . 'assets/admin.css', ['wp-color-picker'], WLW_VERSION);
    wp_enqueue_script('wlw-admin', WLW_PLUGIN_URL . 'assets/admin.js', ['jquery', 'wp-color-picker'], WLW_VERSION, true);
    wp_localize_script('wlw-admin', 'wlwAdmin', [
        'fields' => json_decode(get_option('wlw_form_fields', wlw_default_fields_json()), true) ?: [],
    ]);
}

// ─── Settings Page ────────────────────────────────────────────────────────────

function wlw_settings_page()
{
    if (! current_user_can('manage_options')) return;

    if (isset($_POST['wlw_save_settings']) && check_admin_referer('wlw_settings_nonce')) {
        $text_fields = [
            'wlw_whatsapp_number',
            'wlw_notification_email',
            'wlw_button_message',
            'wlw_modal_title',
            'wlw_modal_subtitle',
            'wlw_whatsapp_message',
            'wlw_button_position',
            'wlw_recaptcha_site_key',
            'wlw_recaptcha_secret_key',
            'wlw_font_family',
        ];
        // Widget mode
        $allowed_modes = ['form', 'icon_only'];
        $submitted_mode = sanitize_text_field($_POST['wlw_widget_mode'] ?? 'form');
        update_option('wlw_widget_mode', in_array($submitted_mode, $allowed_modes, true) ? $submitted_mode : 'form');
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, sanitize_text_field($_POST[$field]));
            }
        }
        // Color options (sanitize_hex_color returns null for invalid values)
        foreach (
            [
                'wlw_header_color',
                'wlw_header_title_color',
                'wlw_header_subtitle_color',
                'wlw_header_icon_color',
                'wlw_header_icon_svg_color',
                'wlw_close_bg',
                'wlw_close_color',
                'wlw_tooltip_bg',
                'wlw_tooltip_color',
                'wlw_bg_color',
                'wlw_label_color',
                'wlw_input_color',
                'wlw_input_bg',
                'wlw_btn_bg',
                'wlw_btn_color',
                'wlw_text_color',
            ] as $color_field
        ) {
            if (isset($_POST[$color_field])) {
                $color = sanitize_hex_color($_POST[$color_field]);
                // allow saving empty string (color cleared by user)
                if ($color) update_option($color_field, $color);
                elseif ($_POST[$color_field] === '') update_option($color_field, '');
            }
        }
        update_option('wlw_show_company_field', isset($_POST['wlw_show_company_field']) ? '1' : '0');
        update_option('wlw_use_site_font',      isset($_POST['wlw_use_site_font'])      ? '1' : '0');
        // Form fields JSON
        if (isset($_POST['wlw_form_fields_json'])) {
            $raw     = stripslashes($_POST['wlw_form_fields_json']);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $allowed_types = ['text', 'email', 'tel', 'number', 'select', 'textarea'];
                $sanitized = array_values(array_map(function ($f) use ($allowed_types) {
                    return [
                        'id'          => preg_replace('/[^a-z0-9_]/', '', strtolower($f['id'] ?? '')),
                        'label'       => sanitize_text_field($f['label'] ?? ''),
                        'placeholder' => sanitize_text_field($f['placeholder'] ?? ''),
                        'type'        => in_array($f['type'] ?? 'text', $allowed_types, true) ? $f['type'] : 'text',
                        'required'    => ! empty($f['required']),
                        'enabled'     => ! empty($f['enabled']),
                        'system'      => ! empty($f['system']),
                        'options'     => sanitize_textarea_field($f['options'] ?? ''),
                    ];
                }, $decoded));
                update_option('wlw_form_fields', json_encode($sanitized));
            }
        }
        echo '<div class="notice notice-success"><p>Configurações salvas com sucesso!</p></div>';
    }

    $number        = get_option('wlw_whatsapp_number', '');
    $email         = get_option('wlw_notification_email', get_option('admin_email'));
    $btn_msg       = get_option('wlw_button_message', 'Fale conosco no WhatsApp!');
    $modal_title   = get_option('wlw_modal_title', 'Falar no WhatsApp');
    $modal_sub     = get_option('wlw_modal_subtitle', 'Preencha para iniciar a conversa');
    $wa_msg        = get_option('wlw_whatsapp_message', 'Olá! Vim pelo site e gostaria de mais informações.');
    $show_company   = get_option('wlw_show_company_field', '1');
    $position       = get_option('wlw_button_position', 'right');
    $widget_mode    = get_option('wlw_widget_mode', 'form');
    $rc_site_key    = get_option('wlw_recaptcha_site_key', '');
    $rc_secret_key  = get_option('wlw_recaptcha_secret_key', '');
    // Style options — Header
    $header_color          = get_option('wlw_header_color',          '#1e2a3b');
    $header_title_color    = get_option('wlw_header_title_color',    '#e8edf5');
    $header_subtitle_color = get_option('wlw_header_subtitle_color', '#7a8fa6');
    $header_icon_color     = get_option('wlw_header_icon_color',     '#25d366');
    $header_icon_svg_color = get_option('wlw_header_icon_svg_color', '');
    $close_bg              = get_option('wlw_close_bg',              '');
    $close_color           = get_option('wlw_close_color',           '');
    $tooltip_bg            = get_option('wlw_tooltip_bg',            '');
    $tooltip_color         = get_option('wlw_tooltip_color',         '');
    // Style options — Form
    $bg_color              = get_option('wlw_bg_color',              '#1a2230');
    $label_color           = get_option('wlw_label_color',           '#e8edf5');
    $input_color           = get_option('wlw_input_color',           '#e8edf5');
    $input_bg              = get_option('wlw_input_bg',              '#1a2636');
    $btn_bg                = get_option('wlw_btn_bg',                '#25d366');
    $btn_color             = get_option('wlw_btn_color',             '#ffffff');
    // Font
    $font_family           = get_option('wlw_font_family',           '');
    $use_site_font         = get_option('wlw_use_site_font',         '0');
?>
    <div class="wrap wlw-wrap">
        <h1>Configurações — WhatsApp Lead Widget</h1>
        <form method="post" action="">
            <?php wp_nonce_field('wlw_settings_nonce'); ?>

            <nav class="nav-tab-wrapper wlw-tab-nav" id="wlw-tab-nav">
                <a href="#tab-geral" class="nav-tab" data-tab="tab-geral">Geral</a>
                <a href="#tab-botao" class="nav-tab" data-tab="tab-botao">Botão &amp; Modal</a>
                <a href="#tab-aparencia" class="nav-tab" data-tab="tab-aparencia">Aparência</a>
                <a href="#tab-formulario" class="nav-tab" data-tab="tab-formulario">Formulário</a>
                <a href="#tab-antispam" class="nav-tab" data-tab="tab-antispam">Anti-Spam</a>
            </nav>

            <!-- ═══ Tab: Geral ═══ -->
            <div id="tab-geral" class="wlw-tab-panel">
                <div class="wlw-card">
                    <h2>WhatsApp</h2>
                    <table class="form-table">
                        <tr>
                            <th>Número do WhatsApp <span class="wlw-required">*</span></th>
                            <td>
                                <input type="text" id="wlw-phone-display" inputmode="numeric" autocomplete="tel" class="regular-text" placeholder="+55 (11) 99999-9999" />
                                <input type="hidden" name="wlw_whatsapp_number" id="wlw-phone-raw" value="<?php echo esc_attr($number); ?>" />
                                <p class="description">Formato internacional sem espaços ou símbolos. Ex: 5511999999999</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Mensagem padrão no WhatsApp</th>
                            <td>
                                <textarea name="wlw_whatsapp_message" rows="3" class="large-text"><?php echo esc_textarea($wa_msg); ?></textarea>
                                <p class="description">Mensagem enviada automaticamente ao abrir o WhatsApp. Use {nome} para inserir o nome do lead.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="wlw-card">
                    <h2>Notificações</h2>
                    <table class="form-table">
                        <tr>
                            <th>E-mail para receber leads <span class="wlw-required">*</span></th>
                            <td>
                                <input type="email" name="wlw_notification_email" value="<?php echo esc_attr($email); ?>" class="regular-text" />
                                <p class="description">Cada novo lead será enviado para este e-mail.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- ═══ Tab: Botão & Modal ═══ -->
            <div id="tab-botao" class="wlw-tab-panel">
                <div class="wlw-card">
                    <h2>Botão &amp; Modal</h2>
                    <table class="form-table">
                        <tr>
                            <th>Modo do widget</th>
                            <td>
                                <label style="display:block;margin-bottom:8px">
                                    <input type="radio" name="wlw_widget_mode" value="form" <?php checked($widget_mode, 'form'); ?> />
                                    Com formulário de captura de leads (exibe tooltip e formulário)
                                </label>
                                <label style="display:block">
                                    <input type="radio" name="wlw_widget_mode" value="icon_only" <?php checked($widget_mode, 'icon_only'); ?> />
                                    Somente ícone (link direto para o WhatsApp, sem tooltip e sem formulário)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Posição do botão</th>
                            <td>
                                <select name="wlw_button_position">
                                    <option value="right" <?php selected($position, 'right'); ?>>Direita</option>
                                    <option value="left" <?php selected($position, 'left'); ?>>Esquerda</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Texto do tooltip</th>
                            <td><input type="text" name="wlw_button_message" value="<?php echo esc_attr($btn_msg); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th>Título do modal</th>
                            <td><input type="text" name="wlw_modal_title" value="<?php echo esc_attr($modal_title); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th>Subtítulo do modal</th>
                            <td><input type="text" name="wlw_modal_subtitle" value="<?php echo esc_attr($modal_sub); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- ═══ Tab: Aparência ═══ -->
            <div id="tab-aparencia" class="wlw-tab-panel">
                <div class="wlw-card">
                    <h2>Header</h2>
                    <p class="description" style="margin-bottom:16px">Cores da faixa superior do modal.</p>
                    <table class="form-table">
                        <tr>
                            <th>Background do header</th>
                            <td><input type="text" name="wlw_header_color" value="<?php echo esc_attr($header_color); ?>" class="wlw-color-picker" data-default-color="#1e2a3b" /></td>
                        </tr>
                        <tr>
                            <th>Cor do título</th>
                            <td><input type="text" name="wlw_header_title_color" value="<?php echo esc_attr($header_title_color); ?>" class="wlw-color-picker" data-default-color="#e8edf5" /></td>
                        </tr>
                        <tr>
                            <th>Cor da descrição</th>
                            <td><input type="text" name="wlw_header_subtitle_color" value="<?php echo esc_attr($header_subtitle_color); ?>" class="wlw-color-picker" data-default-color="#7a8fa6" /></td>
                        </tr>
                        <tr>
                            <th>Background do ícone</th>
                            <td><input type="text" name="wlw_header_icon_color" value="<?php echo esc_attr($header_icon_color); ?>" class="wlw-color-picker" data-default-color="#25d366" /></td>
                        </tr>
                        <tr>
                            <th>Cor do SVG do ícone</th>
                            <td>
                                <input type="text" name="wlw_header_icon_svg_color" value="<?php echo esc_attr($header_icon_svg_color); ?>" class="wlw-color-picker" data-default-color="#ffffff" />
                                <p class="description">Cor do símbolo dentro do círculo. Padrão: branco.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Background do botão fechar</th>
                            <td>
                                <input type="text" name="wlw_close_bg" value="<?php echo esc_attr($close_bg); ?>" class="wlw-color-picker" data-default-color="#243044" />
                                <p class="description">Deixe em branco para usar o padrão translúcido.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Cor da fonte do botão fechar</th>
                            <td>
                                <input type="text" name="wlw_close_color" value="<?php echo esc_attr($close_color); ?>" class="wlw-color-picker" data-default-color="#7a8fa6" />
                                <p class="description">Deixe em branco para usar o padrão.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Background do tooltip</th>
                            <td>
                                <input type="text" name="wlw_tooltip_bg" value="<?php echo esc_attr($tooltip_bg); ?>" class="wlw-color-picker" data-default-color="#1a2230" />
                            </td>
                        </tr>
                        <tr>
                            <th>Cor do texto do tooltip</th>
                            <td>
                                <input type="text" name="wlw_tooltip_color" value="<?php echo esc_attr($tooltip_color); ?>" class="wlw-color-picker" data-default-color="#e8edf5" />
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="wlw-card">
                    <h2>Formulário &amp; Botão de Envio</h2>
                    <p class="description" style="margin-bottom:16px">Cores dos campos, botão e tipografia.</p>
                    <table class="form-table">
                        <tr>
                            <th>Background do modal</th>
                            <td><input type="text" name="wlw_bg_color" value="<?php echo esc_attr($bg_color); ?>" class="wlw-color-picker" data-default-color="#1a2230" /></td>
                        </tr>
                        <tr>
                            <th>Cor das labels</th>
                            <td><input type="text" name="wlw_label_color" value="<?php echo esc_attr($label_color); ?>" class="wlw-color-picker" data-default-color="#e8edf5" /></td>
                        </tr>
                        <tr>
                            <th>Cor da fonte dos inputs</th>
                            <td><input type="text" name="wlw_input_color" value="<?php echo esc_attr($input_color); ?>" class="wlw-color-picker" data-default-color="#e8edf5" /></td>
                        </tr>
                        <tr>
                            <th>Background dos inputs</th>
                            <td><input type="text" name="wlw_input_bg" value="<?php echo esc_attr($input_bg); ?>" class="wlw-color-picker" data-default-color="#1a2636" /></td>
                        </tr>
                        <tr>
                            <th>Background do botão</th>
                            <td><input type="text" name="wlw_btn_bg" value="<?php echo esc_attr($btn_bg); ?>" class="wlw-color-picker" data-default-color="#25d366" /></td>
                        </tr>
                        <tr>
                            <th>Cor da fonte + ícone do botão</th>
                            <td><input type="text" name="wlw_btn_color" value="<?php echo esc_attr($btn_color); ?>" class="wlw-color-picker" data-default-color="#ffffff" /></td>
                        </tr>
                        <tr>
                            <th>Usar fonte do site</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="wlw_use_site_font" value="1" <?php checked($use_site_font, '1'); ?> id="wlw-use-site-font" />
                                    Herdar a fonte do tema instalado
                                </label>
                            </td>
                        </tr>
                        <tr id="wlw-custom-font-row" <?php echo $use_site_font === '1' ? 'style="display:none"' : ''; ?>>
                            <th>Fonte personalizada</th>
                            <td>
                                <input type="text" name="wlw_font_family" value="<?php echo esc_attr($font_family); ?>" class="regular-text" placeholder="Ex: Roboto, Inter, Lato" />
                                <p class="description">Nome da fonte (Google Fonts ou sistema). Deixe em branco para usar a padrão do widget.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- ═══ Tab: Formulário ═══ -->
            <div id="tab-formulario" class="wlw-tab-panel">
                <div class="wlw-card">
                    <h2>Campos do Formulário</h2>
                    <p class="description" style="margin-bottom:16px">Configure quais campos aparecem no formulário. Arraste para reordenar. Os campos <strong>Nome</strong> e <strong>E-mail</strong> são obrigatórios e não podem ser removidos.</p>
                    <input type="hidden" id="wlw-fields-json" name="wlw_form_fields_json" value="<?php echo esc_attr(get_option('wlw_form_fields', wlw_default_fields_json())); ?>" />
                    <div id="wlw-fields-list"></div>
                    <button type="button" id="wlw-add-field" class="button button-secondary" style="margin-top:12px">+ Adicionar campo</button>
                </div>
            </div>

            <!-- ═══ Tab: Anti-Spam ═══ -->
            <div id="tab-antispam" class="wlw-tab-panel">
                <div class="wlw-card">
                    <h2>Anti-Spam</h2>
                    <p class="description" style="margin-bottom:12px">O campo honeypot já está ativo por padrão. Para proteção extra, configure o reCAPTCHA v3 do Google.</p>
                    <table class="form-table">
                        <tr>
                            <th>reCAPTCHA v3 — Chave do site</th>
                            <td>
                                <input type="text" name="wlw_recaptcha_site_key" value="<?php echo esc_attr($rc_site_key); ?>" class="regular-text" placeholder="6Le..." />
                            </td>
                        </tr>
                        <tr>
                            <th>reCAPTCHA v3 — Chave secreta</th>
                            <td>
                                <input type="text" name="wlw_recaptcha_secret_key" value="<?php echo esc_attr($rc_secret_key); ?>" class="regular-text" placeholder="6Le..." />
                                <p class="description">Obtenha as chaves em <a href="https://www.google.com/recaptcha/admin" target="_blank">google.com/recaptcha</a>. Escolha reCAPTCHA v3.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <p class="wlw-submit-row"><input type="submit" name="wlw_save_settings" class="button button-primary button-large" value="Salvar Configurações" /></p>
        </form>
    </div>
<?php
}

// ─── Leads Page ───────────────────────────────────────────────────────────────

function wlw_leads_page()
{
    if (! current_user_can('manage_options')) return;

    // Handle delete
    if (isset($_GET['delete_lead']) && check_admin_referer('wlw_delete_lead')) {
        global $wpdb;
        $id = intval($_GET['delete_lead']);
        $wpdb->delete($wpdb->prefix . 'wlw_leads', ['id' => $id]);
        echo '<div class="notice notice-success"><p>Lead removido.</p></div>';
    }

    // Handle CSV export
    if (isset($_GET['export_csv']) && check_admin_referer('wlw_export_csv')) {
        wlw_export_csv();
        exit;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wlw_leads';
    $per_page = 20;
    $page = max(1, intval($_GET['paged'] ?? 1));
    $offset = ($page - 1) * $per_page;
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $leads = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset));
    $pages = ceil($total / $per_page);

    $export_url = wp_nonce_url(admin_url('admin.php?page=wlw-leads&export_csv=1'), 'wlw_export_csv');
?>
    <div class="wrap wlw-wrap">
        <h1>Leads Capturados <span class="wlw-badge"><?php echo intval($total); ?> total</span></h1>
        <p>
            <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">Exportar CSV</a>
        </p>
        <?php if (empty($leads)) : ?>
            <div class="wlw-empty">
                <p>Nenhum lead capturado ainda. O botão do WhatsApp está ativo no seu site!</p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped wlw-leads-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Informações</th>
                        <th>Data</th>
                        <th>IP</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead) :
                        $delete_url  = wp_nonce_url(admin_url('admin.php?page=wlw-leads&delete_lead=' . $lead->id), 'wlw_delete_lead');
                        $extra_items = ! empty($lead->extra_fields) ? json_decode($lead->extra_fields, true) : [];
                        if (! is_array($extra_items)) $extra_items = [];
                    ?>
                        <tr>
                            <td><?php echo esc_html($lead->id); ?></td>
                            <td><strong><?php echo esc_html($lead->full_name); ?></strong></td>
                            <td><a href="mailto:<?php echo esc_attr($lead->email); ?>"><?php echo esc_html($lead->email); ?></a></td>
                            <td>
                                <?php if ($extra_items) : ?>
                                    <ul class="wlw-extra-fields">
                                        <?php foreach ($extra_items as $item) : ?>
                                            <li><span class="wlw-field-label"><?php echo esc_html($item['label']); ?>:</span> <?php echo esc_html($item['value'] ?: '—'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($lead->created_at))); ?></td>
                            <td><?php echo esc_html($lead->ip_address ?: '—'); ?></td>
                            <td>
                                <a href="<?php echo esc_url($delete_url); ?>" class="wlw-delete-link" onclick="return confirm('Remover este lead?')">Remover</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base'    => admin_url('admin.php?page=wlw-leads&paged=%#%'),
                            'format'  => '',
                            'current' => $page,
                            'total'   => $pages,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php
}

// ─── CSV Export ───────────────────────────────────────────────────────────────

function wlw_export_csv()
{
    global $wpdb;
    $table = $wpdb->prefix . 'wlw_leads';
    $leads = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads-whatsapp-' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['ID', 'Nome', 'E-mail', 'Informações adicionais', 'Data', 'IP'], ';');
    foreach ($leads as $row) {
        $extra_items  = ! empty($row['extra_fields']) ? json_decode($row['extra_fields'], true) : [];
        $extra_string = '';
        if (is_array($extra_items)) {
            $parts = array_map(fn($i) => ($i['label'] ?? '') . ': ' . ($i['value'] ?? ''), $extra_items);
            $extra_string = implode(' | ', $parts);
        }
        fputcsv($out, [
            $row['id'],
            $row['full_name'],
            $row['email'],
            $extra_string,
            date_i18n('d/m/Y H:i', strtotime($row['created_at'])),
            $row['ip_address'],
        ], ';');
    }
    fclose($out);
}

// ─── AJAX Handler ─────────────────────────────────────────────────────────────

add_action('wp_ajax_wlw_submit_lead',        'wlw_submit_lead');
add_action('wp_ajax_nopriv_wlw_submit_lead', 'wlw_submit_lead');

function wlw_submit_lead()
{
    check_ajax_referer('wlw_nonce', 'nonce');

    // ── Honeypot check ────────────────────────────────────────────────────────
    if (! empty($_POST['wlw_website'])) {
        wp_send_json_error(['message' => 'Erro de validação.']);
    }

    // ── reCAPTCHA v3 verification ─────────────────────────────────────────────
    $secret_key = get_option('wlw_recaptcha_secret_key', '');
    if (! empty($secret_key)) {
        $token = sanitize_text_field($_POST['recaptcha_token'] ?? '');
        if (empty($token)) {
            wp_send_json_error(['message' => 'Verificação anti-spam falhou. Tente novamente.']);
        }
        $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => $secret_key,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
        ]);
        if (! is_wp_error($verify)) {
            $result = json_decode(wp_remote_retrieve_body($verify), true);
            if (empty($result['success']) || (isset($result['score']) && $result['score'] < 0.5)) {
                wp_send_json_error(['message' => 'Verificação anti-spam falhou. Tente novamente.']);
            }
        }
    }

    $name          = sanitize_text_field($_POST['full_name'] ?? '');
    $email         = sanitize_email($_POST['email'] ?? '');
    $clinic        = sanitize_text_field($_POST['clinic'] ?? '');
    $collaborators = sanitize_text_field($_POST['collaborators'] ?? '');

    if (empty($name) || empty($email) || empty($clinic) || empty($collaborators)) {
        wp_send_json_error(['message' => 'Por favor, preencha todos os campos obrigatórios.']);
    }
    if (! is_email($email)) {
        wp_send_json_error(['message' => 'Por favor, informe um e-mail válido.']);
    }

    // Save to DB
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'wlw_leads',
        [
            'full_name'  => $name,
            'email'      => $email,
            'phone'      => $collaborators,
            'company'    => $clinic,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ],
        ['%s', '%s', '%s', '%s', '%s']
    );

    // Send email notification
    $to      = get_option('wlw_notification_email', get_option('admin_email'));
    $subject = 'Novo Lead WhatsApp: ' . $name;
    $body    = wlw_build_email($name, $email, $clinic, $collaborators);
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    wp_mail($to, $subject, $body, $headers);

    // Build WhatsApp URL
    $number  = preg_replace('/\D/', '', get_option('wlw_whatsapp_number', ''));
    $message = get_option('wlw_whatsapp_message', 'Olá! Vim pelo site e gostaria de mais informações.');
    $message = str_replace('{nome}', $name, $message);
    $wa_url  = 'https://wa.me/' . $number . '?text=' . rawurlencode($message);

    wp_send_json_success(['whatsapp_url' => $wa_url]);
}

function wlw_build_email($name, $email, $extra_data)
{
    $site  = get_bloginfo('name');
    $date  = date_i18n('d/m/Y \à\s H:i');
    $rows  = "<tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888;width:160px'>Nome</td><td style='padding:10px 0;border-bottom:1px solid #eee;font-weight:bold'>" . esc_html($name) . "</td></tr>";
    $rows .= "<tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888'>E-mail</td><td style='padding:10px 0;border-bottom:1px solid #eee'><a href='mailto:" . esc_attr($email) . "'>" . esc_html($email) . "</a></td></tr>";
    foreach ($extra_data as $i => $item) {
        $is_last = ($i === count($extra_data) - 1);
        $border  = $is_last ? '' : "border-bottom:1px solid #eee;";
        $rows   .= "<tr><td style='padding:10px 0;{$border}color:#888'>" . esc_html($item['label']) . "</td><td style='padding:10px 0;{$border}'>" . esc_html($item['value']) . "</td></tr>";
    }
    return "
    <div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;background:#f4f4f4;padding:20px;border-radius:8px'>
      <div style='background:#25D366;padding:20px;border-radius:8px 8px 0 0;text-align:center'>
        <h2 style='color:#fff;margin:0'>Novo Lead via WhatsApp</h2>
        <p style='color:#d4f5e9;margin:4px 0 0'>$site — $date</p>
      </div>
      <div style='background:#fff;padding:24px;border-radius:0 0 8px 8px'>
        <table style='width:100%;border-collapse:collapse'>$rows</table>
        <p style='margin-top:20px;text-align:center'>
          <a href='mailto:" . esc_attr($email) . "' style='background:#25D366;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:bold'>Responder por e-mail</a>
        </p>
      </div>
    </div>";
}

// ─── Frontend Assets ──────────────────────────────────────────────────────────

add_action('wp_enqueue_scripts', 'wlw_frontend_assets');
function wlw_frontend_assets()
{
    $rc_site_key = get_option('wlw_recaptcha_site_key', '');

    wp_enqueue_style('wlw-widget', WLW_PLUGIN_URL . 'assets/widget.css', [], WLW_VERSION);
    wp_enqueue_script('wlw-widget', WLW_PLUGIN_URL . 'assets/widget.js', [], WLW_VERSION, true);

    if (! empty($rc_site_key)) {
        wp_enqueue_script(
            'google-recaptcha',
            'https://www.google.com/recaptcha/api.js?render=' . esc_attr($rc_site_key),
            [],
            null,
            true
        );
    }

    // ── Dynamic CSS Variables ────────────────────────────────────────────────
    // Header
    $header_color          = get_option('wlw_header_color',          '#1e2a3b');
    $header_title_color    = get_option('wlw_header_title_color',    '#e8edf5');
    $header_subtitle_color = get_option('wlw_header_subtitle_color', '#7a8fa6');
    $header_icon_color     = get_option('wlw_header_icon_color',     '#25d366');
    $header_icon_svg_color = get_option('wlw_header_icon_svg_color', '');
    $close_bg              = get_option('wlw_close_bg',              '');
    $close_color           = get_option('wlw_close_color',           '');
    $tooltip_bg            = get_option('wlw_tooltip_bg',            '');
    $tooltip_color         = get_option('wlw_tooltip_color',         '');
    // Form
    $bg_color              = get_option('wlw_bg_color',              '#1a2230');
    $label_color           = get_option('wlw_label_color',           '#e8edf5');
    $input_color           = get_option('wlw_input_color',           '#e8edf5');
    $input_bg              = get_option('wlw_input_bg',              '#1a2636');
    $btn_bg                = get_option('wlw_btn_bg',                '#25d366');
    $btn_color             = get_option('wlw_btn_color',             '#ffffff');
    // Font
    $font_family           = get_option('wlw_font_family',           '');
    $use_site_font         = get_option('wlw_use_site_font',         '0');

    $css_vars = '';
    // Header vars
    if ($header_color !== '#1e2a3b') {
        $css_vars .= '--wlw-header-bg:'       . sanitize_hex_color($header_color) . ';';
    }
    if ($header_title_color !== '#e8edf5') {
        $css_vars .= '--wlw-header-title:'    . sanitize_hex_color($header_title_color) . ';';
    }
    if ($header_subtitle_color !== '#7a8fa6') {
        $css_vars .= '--wlw-header-subtitle:' . sanitize_hex_color($header_subtitle_color) . ';';
    }
    if ($header_icon_color !== '#25d366') {
        $css_vars .= '--wlw-header-icon-bg:'  . sanitize_hex_color($header_icon_color) . ';';
    }
    if (! empty($header_icon_svg_color)) {
        $css_vars .= '--wlw-header-icon-svg:' . sanitize_hex_color($header_icon_svg_color) . ';';
    }
    if (! empty($close_bg)) {
        $css_vars .= '--wlw-close-bg:'        . sanitize_hex_color($close_bg) . ';';
    }
    if (! empty($close_color)) {
        $css_vars .= '--wlw-close-color:'     . sanitize_hex_color($close_color) . ';';
    }
    if (! empty($tooltip_bg)) {
        $css_vars .= '--wlw-tooltip-bg:'      . sanitize_hex_color($tooltip_bg) . ';';
    }
    if (! empty($tooltip_color)) {
        $css_vars .= '--wlw-tooltip-color:'   . sanitize_hex_color($tooltip_color) . ';';
    }
    // Form vars
    if ($bg_color !== '#1a2230') {
        $css_vars .= '--wlw-bg:'       . sanitize_hex_color($bg_color) . ';';
        $css_vars .= '--wlw-bg2:'      . wlw_lighten_color($bg_color, 8) . ';';
        $css_vars .= '--wlw-surface:'  . wlw_lighten_color($bg_color, 14) . ';';
    }
    if ($label_color !== '#e8edf5') {
        $css_vars .= '--wlw-label-color:'   . sanitize_hex_color($label_color) . ';';
    }
    if ($input_color !== '#e8edf5') {
        $css_vars .= '--wlw-input-color:'   . sanitize_hex_color($input_color) . ';';
    }
    if ($input_bg !== '#1a2636') {
        $css_vars .= '--wlw-input-bg:'      . sanitize_hex_color($input_bg) . ';';
    }
    if ($btn_bg !== '#25d366') {
        $css_vars .= '--wlw-btn-bg:'        . sanitize_hex_color($btn_bg) . ';';
    }
    if ($btn_color !== '#ffffff') {
        $css_vars .= '--wlw-btn-color:'     . sanitize_hex_color($btn_color) . ';';
    }
    // Font var
    if ($use_site_font === '1') {
        $css_vars .= '--wlw-font:inherit;';
    } elseif (! empty($font_family)) {
        $safe_font  = preg_replace('/[^a-zA-Z0-9\s,\-]/', '', $font_family);
        $css_vars  .= '--wlw-font:"' . esc_attr($safe_font) . '",sans-serif;';
    }
    if ($css_vars) {
        wp_add_inline_style('wlw-widget', '#wlw-root{' . $css_vars . '}');
    }

    // ── Pass data to JS ──────────────────────────────────────────────────────
    $form_fields = json_decode(get_option('wlw_form_fields', wlw_default_fields_json()), true);
    if (! is_array($form_fields)) $form_fields = [];
    $frontend_fields = array_values(array_filter($form_fields, function ($f) {
        return ! empty($f['enabled']);
    }));

    wp_localize_script('wlw-widget', 'wlwData', [
        'ajaxUrl'          => admin_url('admin-ajax.php'),
        'nonce'            => wp_create_nonce('wlw_nonce'),
        'title'            => get_option('wlw_modal_title', 'Falar no WhatsApp'),
        'subtitle'         => get_option('wlw_modal_subtitle', 'Preencha para iniciar a conversa'),
        'buttonMsg'        => get_option('wlw_button_message', 'Fale conosco no WhatsApp!'),
        'showCompany'      => get_option('wlw_show_company_field', '1'),
        'position'         => get_option('wlw_button_position', 'right'),
        'recaptchaSiteKey' => $rc_site_key,
        'fields'           => $frontend_fields,
        'mode'             => get_option('wlw_widget_mode', 'form'),
        'waNumber'         => preg_replace('/\D/', '', get_option('wlw_whatsapp_number', '')),
        'waMessage'        => get_option('wlw_whatsapp_message', 'Olá! Vim pelo site e gostaria de mais informações.'),
    ]);
}

// ── Color helpers ──────────────────────────────────────────────────────────────

function wlw_hex_to_rgb($hex)
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function wlw_darken_color($hex, $pct)
{
    list($r, $g, $b) = wlw_hex_to_rgb($hex);
    return sprintf(
        '#%02x%02x%02x',
        max(0, (int)($r * (100 - $pct) / 100)),
        max(0, (int)($g * (100 - $pct) / 100)),
        max(0, (int)($b * (100 - $pct) / 100))
    );
}

function wlw_lighten_color($hex, $pct)
{
    list($r, $g, $b) = wlw_hex_to_rgb($hex);
    return sprintf(
        '#%02x%02x%02x',
        min(255, (int)($r + (255 - $r) * $pct / 100)),
        min(255, (int)($g + (255 - $g) * $pct / 100)),
        min(255, (int)($b + (255 - $b) * $pct / 100))
    );
}

// ─── Inject HTML ──────────────────────────────────────────────────────────────

add_action('wp_footer', 'wlw_render_widget');
function wlw_render_widget()
{
    echo '<div id="wlw-root"></div>';
}
