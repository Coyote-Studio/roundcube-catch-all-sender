(function() {
    function getLabel(key) {
        if (window.rcmail && rcmail.gettext) {
            return rcmail.gettext(key, 'catch_all_sender');
        }
        return key;
    }

    function toggleIdentityFields(enabled) {
        var $inputs = $('#rule-name, #rule-org, #rule-reply, #rule-bcc, #rule-sig, #rule-html');
        $inputs.prop('disabled', !enabled);
        $inputs.closest('fieldset').css({
            'opacity': enabled ? '1' : '0.45',
            'pointer-events': enabled ? 'auto' : 'none'
        });
    }

    function initCatchAllSender() {
        if (!window.rcmail || rcmail.env.action !== 'plugin.catch_all_sender') {
            return;
        }
        if (window._catch_all_sender_initialized) {
            return;
        }
        window._catch_all_sender_initialized = true;

        var rules = rcmail.env.catch_all_sender_rules || [];
        var primary = rcmail.env.catch_all_primary_identity || {};

        function ensureDefaultRule() {
            var hasDefault = false;
            for (var i = 0; i < rules.length; i++) {
                if (rules[i].is_default || rules[i].mask === '*@*.*') {
                    rules[i].is_default = true;
                    rules[i].mask = '*@*.*';
                    hasDefault = true;
                    if (i !== rules.length - 1) {
                        var def = rules.splice(i, 1)[0];
                        rules.push(def);
                    }
                    break;
                }
            }
            if (!hasDefault) {
                rules.push({
                    mask: '*@*.*',
                    allow_creation: false,
                    name: '',
                    organization: '',
                    reply_to: '',
                    bcc: '',
                    signature: '',
                    html_signature: 0,
                    is_default: true
                });
            }
        }

        function renderTable() {
            ensureDefaultRule();
            var tbody = $('#catchall-rules-list');
            tbody.empty();

            $.each(rules, function(idx, rule) {
                var isFirst = (idx === 0);

                var statusBadge = rule.allow_creation
                    ? '<span class="badge" style="background:#28a745;color:#fff;padding:4px 8px;border-radius:3px;">' + getLabel('status_allowed') + '</span>'
                    : '<span class="badge" style="background:#dc3545;color:#fff;padding:4px 8px;border-radius:3px;">' + getLabel('status_blocked') + '</span>';

                var orderBtns = '';
                if (!rule.is_default) {
                    orderBtns += '<button type="button" class="btn btn-sm btn-outline-secondary btn-up" data-idx="' + idx + '" ' + (isFirst ? 'disabled' : '') + '>▲</button> ';
                    orderBtns += '<button type="button" class="btn btn-sm btn-outline-secondary btn-down" data-idx="' + idx + '" ' + (idx === rules.length - 2 ? 'disabled' : '') + '>▼</button>';
                } else {
                    orderBtns = '<span class="text-muted" style="font-size:11px;">' + getLabel('locked') + '</span>';
                }

                var actions = '<button type="button" class="btn btn-sm btn-primary btn-edit" data-idx="' + idx + '">' + getLabel('edit') + '</button> ';
                if (!rule.is_default) {
                    actions += '<button type="button" class="btn btn-sm btn-danger btn-delete" data-idx="' + idx + '">' + getLabel('delete') + '</button>';
                }

                var tr = $('<tr>')
                    .append($('<td>').html(orderBtns))
                    .append($('<td>').html('<code>' + $('<div>').text(rule.mask).html() + '</code>' + (rule.is_default ? ' <em>(' + getLabel('default_rule') + ')</em>' : '')))
                    .append($('<td>').html(statusBadge))
                    .append($('<td>').text(rule.name || '-'))
                    .append($('<td style="text-align: right;">').html(actions));

                tbody.append(tr);
            });
        }

        renderTable();

        $('#rule-allow').off('change').on('change', function() {
            toggleIdentityFields($(this).is(':checked'));
        });

        $('#btn-add-rule').off('click').on('click', function(e) {
            e.preventDefault();
            $('#rule-index').val('-1');
            $('#rule-is-default').val('0');
            $('#rule-mask').val('').prop('disabled', false);
            $('#rule-allow').prop('checked', true);

            $('#rule-name').val(primary.name || '');
            $('#rule-org').val(primary.organization || '');
            $('#rule-reply').val(primary['reply-to'] || '');
            $('#rule-bcc').val(primary.bcc || '');
            $('#rule-sig').val(primary.signature || '');
            $('#rule-html').prop('checked', primary.html_signature == 1);

            toggleIdentityFields(true);

            $('#modal-title').text(getLabel('add_rule'));
            $('#catchall-modal-backdrop').css('display', 'flex');
        });

        $(document).off('click', '.btn-edit').on('click', '.btn-edit', function() {
            var idx = parseInt($(this).data('idx'), 10);
            var rule = rules[idx];
            var allow = !!rule.allow_creation;

            $('#rule-index').val(idx);
            $('#rule-is-default').val(rule.is_default ? '1' : '0');
            $('#rule-mask').val(rule.mask).prop('disabled', !!rule.is_default);
            $('#rule-allow').prop('checked', allow);

            $('#rule-name').val(rule.name || '');
            $('#rule-org').val(rule.organization || '');
            $('#rule-reply').val(rule.reply_to || '');
            $('#rule-bcc').val(rule.bcc || '');
            $('#rule-sig').val(rule.signature || '');
            $('#rule-html').prop('checked', !!rule.html_signature);

            toggleIdentityFields(allow);

            $('#modal-title').text(getLabel('edit_rule'));
            $('#catchall-modal-backdrop').css('display', 'flex');
        });

        $('#btn-modal-close, #btn-modal-cancel').off('click').on('click', function() {
            $('#catchall-modal-backdrop').hide();
        });

        $('#catchall-modal-backdrop').off('click').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });

        $('#btn-modal-apply').off('click').on('click', function() {
            var idx = parseInt($('#rule-index').val(), 10);
            var isDef = $('#rule-is-default').val() === '1';
            var mask = $.trim($('#rule-mask').val());

            if (!isDef && !mask) {
                alert(getLabel('error_empty_mask'));
                return;
            }

            var updated = {
                mask: isDef ? '*@*.*' : mask,
                allow_creation: $('#rule-allow').is(':checked'),
                name: $('#rule-name').val(),
                organization: $('#rule-org').val(),
                reply_to: $('#rule-reply').val(),
                bcc: $('#rule-bcc').val(),
                signature: $('#rule-sig').val(),
                html_signature: $('#rule-html').is(':checked') ? 1 : 0,
                is_default: isDef
            };

            if (idx === -1) {
                rules.unshift(updated);
            } else {
                rules[idx] = updated;
            }

            $('#catchall-modal-backdrop').hide();
            renderTable();
        });

        $(document).off('click', '.btn-delete').on('click', '.btn-delete', function() {
            var idx = parseInt($(this).data('idx'), 10);
            if (confirm(getLabel('confirm_delete'))) {
                rules.splice(idx, 1);
                renderTable();
            }
        });

        $(document).off('click', '.btn-up').on('click', '.btn-up', function() {
            var idx = parseInt($(this).data('idx'), 10);
            if (idx > 0) {
                var temp = rules[idx];
                rules[idx] = rules[idx - 1];
                rules[idx - 1] = temp;
                renderTable();
            }
        });

        $(document).off('click', '.btn-down').on('click', '.btn-down', function() {
            var idx = parseInt($(this).data('idx'), 10);
            if (idx < rules.length - 2) {
                var temp = rules[idx];
                rules[idx] = rules[idx + 1];
                rules[idx + 1] = temp;
                renderTable();
            }
        });

        $('#btn-save-rules').off('click').on('click', function() {
            ensureDefaultRule();
            var lock = rcmail.set_busy(true, 'saving');
            rcmail.http_post('plugin.catch_all_sender_save', { _rules: JSON.stringify(rules) }, lock);
        });
    }

    $(document).ready(initCatchAllSender);
    if (window.rcmail) {
        rcmail.addEventListener('init', initCatchAllSender);
    }
})();