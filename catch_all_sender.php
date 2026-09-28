<?php
/**
 * Catch-All Sender
 *
 * @version 1.0
 * @author Coyote Studio <https://coyote.studio>
 * @license GNU GPLv3+
 */

class catch_all_sender extends rcube_plugin
{
    public $task = 'mail|settings';

    function init()
    {
        $rcmail = rcmail::get_instance();
        $this->add_texts('localization/', true);

        // Exporte les clés nécessaires au moteur JavaScript de Roundcube
        $this->add_label(
            'status_allowed',
            'status_blocked',
            'locked',
            'edit',
            'delete',
            'default_rule',
            'add_rule',
            'edit_rule',
            'error_empty_mask',
            'confirm_delete'
        );

        if ($rcmail->task == 'settings') {
            $this->add_hook('settings_actions', array($this, 'settings_actions'));
            $this->register_action('plugin.catch_all_sender', array($this, 'settings_view'));
            $this->register_action('plugin.catch_all_sender_save', array($this, 'settings_save'));
        }

        if ($rcmail->task == 'mail') {
            $this->add_hook('message_load', array($this, 'analyze_and_create_identity'));
        }
    }

    private function get_rules()
    {
        $rcmail = rcmail::get_instance();
        $default_rules = [
            [
                'mask'           => '*@*.*',
                'allow_creation' => false,
                'name'           => '',
                'organization'   => '',
                'reply_to'       => '',
                'bcc'            => '',
                'signature'      => '',
                'html_signature' => 0,
                'is_default'     => true
            ]
        ];

        $rules = null;

        if ($rcmail->user) {
            $prefs = $rcmail->user->get_prefs();
            if (!empty($prefs['catch_all_sender_rules']) && is_array($prefs['catch_all_sender_rules'])) {
                $rules = $prefs['catch_all_sender_rules'];
            }
        }

        if (!$rules) {
            $rules = $rcmail->config->get('catch_all_sender_rules', null);
        }

        if (!is_array($rules) || empty($rules)) {
            $rules = $default_rules;
        }

        return array_values($rules);
    }

    function settings_actions($args)
    {
        $args['actions'][] = array(
            'action' => 'plugin.catch_all_sender',
            'class'  => 'catchall',
            'label'  => 'catch_all_sender.plugin_title',
            'domain' => 'catch_all_sender'
        );
        return $args;
    }

    function settings_view()
    {
        $rcmail = rcmail::get_instance();
        $this->include_stylesheet('catch_all_sender.css');
        $this->include_script('catch_all_sender.js');
        $rcmail->output->set_pagetitle($this->gettext('plugin_title'));

        $rules = $this->get_rules();
        $primary = $rcmail->user->get_identity();
        if (!$primary || !is_array($primary)) {
            $primary = [];
        }

        $rcmail->output->set_env('catch_all_sender_rules', $rules);
        $rcmail->output->set_env('catch_all_primary_identity', $primary);

        $this->register_handler('plugin.body', array($this, 'render_html'));
        $rcmail->output->add_handler('plugin.body', array($this, 'render_html'));
        $rcmail->output->add_handler('pluginbody', array($this, 'render_html'));

        $rcmail->output->send('plugin');
    }

    function render_html()
    {
        return '
        <div class="catchall-container scroller">
            <div class="catchall-header">
                <h2>' . htmlspecialchars($this->gettext('plugin_title')) . '</h2>
                <div class="catchall-actions">
                    <button type="button" id="btn-add-rule" class="btn btn-primary">' . htmlspecialchars($this->gettext('add_rule')) . '</button>
                    <button type="button" id="btn-save-rules" class="btn btn-success" style="margin-left: 10px;">' . htmlspecialchars($this->gettext('save')) . '</button>
                </div>
            </div>

            <p class="text-muted">' . htmlspecialchars($this->gettext('rules_description')) . '</p>

            <div class="table-responsive">
                <table class="table table-striped records-table catchall-table">
                    <thead>
                        <tr>
                            <th style="width: 90px;">' . htmlspecialchars($this->gettext('order')) . '</th>
                            <th>' . htmlspecialchars($this->gettext('rule_mask')) . '</th>
                            <th>' . htmlspecialchars($this->gettext('action')) . '</th>
                            <th>' . htmlspecialchars($this->gettext('display_name')) . '</th>
                            <th style="width: 160px; text-align: right;">' . htmlspecialchars($this->gettext('actions')) . '</th>
                        </tr>
                    </thead>
                    <tbody id="catchall-rules-list"></tbody>
                </table>
            </div>
        </div>

        <div id="catchall-modal-backdrop" class="catchall-modal-backdrop" style="display:none;">
            <div class="catchall-modal">
                <div class="catchall-modal-header">
                    <h3 id="modal-title">' . htmlspecialchars($this->gettext('edit_rule')) . '</h3>
                    <button type="button" class="btn-close" id="btn-modal-close">&times;</button>
                </div>
                <div class="catchall-modal-body">
                    <input type="hidden" id="rule-index" value="" />
                    <input type="hidden" id="rule-is-default" value="0" />

                    <div class="form-group mb-3">
                        <label for="rule-mask"><b>' . htmlspecialchars($this->gettext('rule_mask')) . '</b></label>
                        <input type="text" id="rule-mask" class="form-control" placeholder="' . htmlspecialchars($this->gettext('mask_placeholder')) . '" />
                        <small class="form-text text-muted">' . htmlspecialchars($this->gettext('mask_help')) . '</small>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="rule-allow" class="form-check-input" />
                        <label class="form-check-label" for="rule-allow"><b>' . htmlspecialchars($this->gettext('allow_creation')) . '</b></label>
                    </div>

                    <fieldset class="border p-3 rounded">
                        <legend class="w-auto px-2" style="font-size: 1rem;">' . htmlspecialchars($this->gettext('identity_parameters')) . '</legend>

                        <div class="form-group mb-2">
                            <label for="rule-name">' . htmlspecialchars($this->gettext('display_name')) . '</label>
                            <input type="text" id="rule-name" class="form-control" />
                        </div>

                        <div class="form-group mb-2">
                            <label for="rule-org">' . htmlspecialchars($this->gettext('organization')) . '</label>
                            <input type="text" id="rule-org" class="form-control" />
                        </div>

                        <div class="form-group mb-2">
                            <label for="rule-reply">' . htmlspecialchars($this->gettext('reply_to')) . '</label>
                            <input type="text" id="rule-reply" class="form-control" />
                        </div>

                        <div class="form-group mb-2">
                            <label for="rule-bcc">' . htmlspecialchars($this->gettext('bcc')) . '</label>
                            <input type="text" id="rule-bcc" class="form-control" />
                        </div>

                        <div class="form-group mb-2">
                            <label for="rule-sig">' . htmlspecialchars($this->gettext('signature')) . '</label>
                            <textarea id="rule-sig" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" id="rule-html" class="form-check-input" />
                            <label class="form-check-label" for="rule-html">' . htmlspecialchars($this->gettext('html_signature')) . '</label>
                        </div>
                    </fieldset>
                </div>
                <div class="catchall-modal-footer">
                    <button type="button" id="btn-modal-cancel" class="btn btn-secondary">' . htmlspecialchars($this->gettext('cancel')) . '</button>
                    <button type="button" id="btn-modal-apply" class="btn btn-primary">' . htmlspecialchars($this->gettext('apply')) . '</button>
                </div>
            </div>
        </div>';
    }

    function settings_save()
    {
        $rcmail = rcmail::get_instance();

        $raw_rules = rcube_utils::get_input_value('_rules', rcube_utils::INPUT_POST, true);
        if (empty($raw_rules) && isset($_POST['_rules'])) {
            $raw_rules = $_POST['_rules'];
        }

        $saved = false;

        if (!empty($raw_rules)) {
            $rules = json_decode($raw_rules, true);
            if (!$rules && is_string($raw_rules)) {
                $rules = json_decode(stripslashes($raw_rules), true);
            }

            if (is_array($rules)) {
                $clean_rules = [];
                foreach ($rules as $r) {
                    if (is_array($r) && !empty($r['mask'])) {
                        $clean_rules[] = [
                            'mask'           => trim($r['mask']),
                            'allow_creation' => !empty($r['allow_creation']),
                            'name'           => (string)($r['name'] ?? ''),
                            'organization'   => (string)($r['organization'] ?? ''),
                            'reply_to'       => (string)($r['reply_to'] ?? ''),
                            'bcc'            => (string)($r['bcc'] ?? ''),
                            'signature'      => (string)($r['signature'] ?? ''),
                            'html_signature' => !empty($r['html_signature']) ? 1 : 0,
                            'is_default'     => !empty($r['is_default'])
                        ];
                    }
                }

                $saved = $rcmail->user->save_prefs(['catch_all_sender_rules' => $clean_rules]);
            }
        }

        if ($saved) {
            $rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
        } else {
            $rcmail->output->command('display_message', $this->gettext('errorsaving'), 'error');
        }

        $rcmail->output->send();
    }

    function analyze_and_create_identity($args)
    {
        $rcmail = rcmail::get_instance();
        $message = $args['object'] ?? null;
        $user = $rcmail->user;

        if (!$message || !is_object($message) || empty($message->headers)) {
            return $args;
        }

        $rules = $this->get_rules();

        $raw_headers = [];
        if (!empty($message->headers->to)) {
            $raw_headers[] = $message->headers->to;
        }
        if (!empty($message->headers->cc)) {
            $raw_headers[] = $message->headers->cc;
        }

        if (method_exists($message, 'get_header')) {
            $del_to = $message->get_header('delivered-to');
            $orig_to = $message->get_header('x-original-to');
            if (!empty($del_to)) $raw_headers[] = $del_to;
            if (!empty($orig_to)) $raw_headers[] = $orig_to;
        }

        if (empty($raw_headers)) {
            return $args;
        }

        $decoded = rcube_mime::decode_address_list(implode(', ', $raw_headers), null, false);
        $candidates = [];

        foreach ($decoded as $item) {
            $mail = '';
            if (is_array($item) && !empty($item['mailto'])) {
                $mail = $item['mailto'];
            } elseif (is_string($item)) {
                $mail = $item;
            }
            $mail = strtolower(trim($mail));
            if (!empty($mail) && filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $candidates[$mail] = $mail;
            }
        }

        if (empty($candidates)) {
            return $args;
        }

        $existing = $user->list_identities();
        $existing_emails = [];
        if (is_array($existing)) {
            foreach ($existing as $id) {
                if (!empty($id['email'])) {
                    $existing_emails[] = strtolower(trim($id['email']));
                }
            }
        }

        foreach ($candidates as $recipient_email) {
            if (in_array($recipient_email, $existing_emails)) {
                continue;
            }

            foreach ($rules as $rule) {
                $mask = trim($rule['mask'] ?? '');
                if ($mask === '') {
                    continue;
                }

                $pattern = '/^' . str_replace('\*', '.*', preg_quote($mask, '/')) . '$/iu';

                if (preg_match($pattern, $recipient_email)) {
                    if (!empty($rule['allow_creation'])) {
                        $user->insert_identity([
                            'email'          => $recipient_email,
                            'name'           => $rule['name'] ?? '',
                            'organization'   => $rule['organization'] ?? '',
                            'reply-to'       => $rule['reply_to'] ?? '',
                            'bcc'            => $rule['bcc'] ?? '',
                            'signature'      => $rule['signature'] ?? '',
                            'html_signature' => !empty($rule['html_signature']) ? 1 : 0,
                            'standard'       => 0
                        ]);

                        rcube::write_log('catch_all_sender', "Identity created: " . $recipient_email);
                        $existing_emails[] = $recipient_email;
                    }
                    break;
                }
            }
        }

        return $args;
    }
}