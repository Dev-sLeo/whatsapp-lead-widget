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

define('WLW_VERSION', '1.0.0');
define('WLW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WLW_PLUGIN_URL', plugin_dir_url(__FILE__));

// ─── Activation / Deactivation ────────────────────────────────────────────────

register_activation_hook(__FILE__, 'wlw_activate');
function wlw_activate()
{
    wlw_create_leads_table();
    // Default options
    add_option('wlw_whatsapp_number', '');
    add_option('wlw_notification_email', get_option('admin_email'));
    add_option('wlw_button_message', 'Fale conosco no WhatsApp!');
    add_option('wlw_modal_title', 'Falar no WhatsApp');
    add_option('wlw_modal_subtitle', 'Preencha para iniciar a conversa');
    add_option('wlw_whatsapp_message', 'Olá! Vim pelo site e gostaria de mais informações.');
    add_option('wlw_show_company_field', '1');
    add_option('wlw_button_position', 'right');
    add_option('wlw_recaptcha_site_key', '');
    add_option('wlw_recaptcha_secret_key', '');
}

register_deactivation_hook(__FILE__, 'wlw_deactivate');
function wlw_deactivate() {}

// ─── Database ─────────────────────────────────────────────────────────────────

function wlw_create_leads_table()
{
    global $wpdb;
    $table = $wpdb->prefix . 'wlw_leads';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        full_name    VARCHAR(200) NOT NULL,
        email        VARCHAR(200) NOT NULL,
        phone        VARCHAR(50)  NOT NULL,
        company      VARCHAR(200) DEFAULT '',
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_address   VARCHAR(50)  DEFAULT '',
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
    wp_enqueue_style('wlw-admin', WLW_PLUGIN_URL . 'assets/admin.css', [], WLW_VERSION);
}

// ─── Settings Page ────────────────────────────────────────────────────────────

function wlw_settings_page()
{
    if (! current_user_can('manage_options')) return;

    if (isset($_POST['wlw_save_settings']) && check_admin_referer('wlw_settings_nonce')) {
        $fields = [
            'wlw_whatsapp_number',
            'wlw_notification_email',
            'wlw_button_message',
            'wlw_modal_title',
            'wlw_modal_subtitle',
            'wlw_whatsapp_message',
            'wlw_button_position',
            'wlw_recaptcha_site_key',
            'wlw_recaptcha_secret_key',
        ];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, sanitize_text_field($_POST[$field]));
            }
        }
        update_option('wlw_show_company_field', isset($_POST['wlw_show_company_field']) ? '1' : '0');
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
    $rc_site_key    = get_option('wlw_recaptcha_site_key', '');
    $rc_secret_key  = get_option('wlw_recaptcha_secret_key', '');
?>
    <div class="wrap wlw-wrap">
        <h1>Configurações — WhatsApp Lead Widget</h1>
        <form method="post" action="">
            <?php wp_nonce_field('wlw_settings_nonce'); ?>

            <div class="wlw-card">
                <h2>WhatsApp</h2>
                <table class="form-table">
                    <tr>
                        <th>Número do WhatsApp <span class="wlw-required">*</span></th>
                        <td>
                            <input type="text" name="wlw_whatsapp_number" value="<?php echo esc_attr($number); ?>" class="regular-text" placeholder="5511999999999" />
                            <p class="description">Formato internacional sem espaços ou símbolos. Ex: 5511999999999</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Mensagem padrão no WhatsApp</th>
                        <td>
                            <textarea name="wlw_whatsapp_message" rows="3" class="large-text"><?php echo esc_textarea($wa_msg); ?></textarea>
                            <p class="description">Mensagem que será enviada automaticamente ao abrir o WhatsApp. Você pode usar {nome} para inserir o nome do lead.</p>
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

            <div class="wlw-card">
                <h2>Aparência do Botão e Modal</h2>
                <table class="form-table">
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
                        <th>Texto do tooltip do botão</th>
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
                    <tr>
                        <th>Exibir campo "Empresa"</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wlw_show_company_field" value="1" <?php checked($show_company, '1'); ?> />
                                Mostrar campo opcional de empresa no formulário
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

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

            <p><input type="submit" name="wlw_save_settings" class="button button-primary button-large" value="Salvar Configurações" /></p>
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
            <a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">Exportar CSV</a>
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
                        <th>Clínica</th>
                        <th>Colaboradores</th>
                        <th>Data</th>
                        <th>IP</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead) :
                        $delete_url = wp_nonce_url(admin_url('admin.php?page=wlw-leads&delete_lead=' . $lead->id), 'wlw_delete_lead');
                    ?>
                        <tr>
                            <td><?php echo esc_html($lead->id); ?></td>
                            <td><strong><?php echo esc_html($lead->full_name); ?></strong></td>
                            <td><a href="mailto:<?php echo esc_attr($lead->email); ?>"><?php echo esc_html($lead->email); ?></a></td>
                            <td><?php echo esc_html($lead->company ?: '—'); ?></td>
                            <td><?php echo esc_html($lead->phone ?: '—'); ?></td>
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
    fputcsv($out, ['ID', 'Nome', 'E-mail', 'Clínica', 'Colaboradores', 'Data', 'IP'], ';');
    foreach ($leads as $row) {
        fputcsv($out, [
            $row['id'],
            $row['full_name'],
            $row['email'],
            $row['phone'],
            $row['company'],
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
    if ( ! empty( $_POST['wlw_website'] ) ) {
        wp_send_json_error( [ 'message' => 'Erro de validação.' ] );
    }

    // ── reCAPTCHA v3 verification ─────────────────────────────────────────────
    $secret_key = get_option( 'wlw_recaptcha_secret_key', '' );
    if ( ! empty( $secret_key ) ) {
        $token = sanitize_text_field( $_POST['recaptcha_token'] ?? '' );
        if ( empty( $token ) ) {
            wp_send_json_error( [ 'message' => 'Verificação anti-spam falhou. Tente novamente.' ] );
        }
        $verify = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => $secret_key,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
        ] );
        if ( ! is_wp_error( $verify ) ) {
            $result = json_decode( wp_remote_retrieve_body( $verify ), true );
            if ( empty( $result['success'] ) || ( isset( $result['score'] ) && $result['score'] < 0.5 ) ) {
                wp_send_json_error( [ 'message' => 'Verificação anti-spam falhou. Tente novamente.' ] );
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

function wlw_build_email($name, $email, $clinic, $collaborators)
{
    $site = get_bloginfo('name');
    $date = date_i18n('d/m/Y \à\s H:i');
    $clin = $clinic ?: 'Não informado';
    $cols = $collaborators ?: 'Não informado';
    return "
    <div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;background:#f4f4f4;padding:20px;border-radius:8px'>
      <div style='background:#25D366;padding:20px;border-radius:8px 8px 0 0;text-align:center'>
        <h2 style='color:#fff;margin:0'>Novo Lead via WhatsApp</h2>
        <p style='color:#d4f5e9;margin:4px 0 0'>$site — $date</p>
      </div>
      <div style='background:#fff;padding:24px;border-radius:0 0 8px 8px'>
        <table style='width:100%;border-collapse:collapse'>
          <tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888;width:160px'>Nome</td><td style='padding:10px 0;border-bottom:1px solid #eee;font-weight:bold'>$name</td></tr>
          <tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888'>E-mail</td><td style='padding:10px 0;border-bottom:1px solid #eee'><a href='mailto:$email'>$email</a></td></tr>
          <tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888'>Clínica</td><td style='padding:10px 0;border-bottom:1px solid #eee'>$clin</td></tr>
          <tr><td style='padding:10px 0;color:#888'>Colaboradores</td><td style='padding:10px 0'>$cols</td></tr>
        </table>
        <p style='margin-top:20px;text-align:center'>
          <a href='mailto:$email' style='background:#25D366;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:bold'>Responder por e-mail</a>
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

    if ( ! empty( $rc_site_key ) ) {
        wp_enqueue_script(
            'google-recaptcha',
            'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $rc_site_key ),
            [],
            null,
            true
        );
    }

    wp_localize_script('wlw-widget', 'wlwData', [
        'ajaxUrl'          => admin_url('admin-ajax.php'),
        'nonce'            => wp_create_nonce('wlw_nonce'),
        'title'            => get_option('wlw_modal_title', 'Falar no WhatsApp'),
        'subtitle'         => get_option('wlw_modal_subtitle', 'Preencha para iniciar a conversa'),
        'buttonMsg'        => get_option('wlw_button_message', 'Fale conosco no WhatsApp!'),
        'showCompany'      => get_option('wlw_show_company_field', '1'),
        'position'         => get_option('wlw_button_position', 'right'),
        'recaptchaSiteKey' => $rc_site_key,
    ]);
}

// ─── Inject HTML ──────────────────────────────────────────────────────────────

add_action('wp_footer', 'wlw_render_widget');
function wlw_render_widget()
{
    echo '<div id="wlw-root"></div>';
}
