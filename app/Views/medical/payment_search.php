<div class="card mb-3">
    <div class="card-body py-3">
        <h5 class="card-title mb-3">Med. Payment Edit</h5>
        <form class="form_payment_search" method="post" action="javascript:void(0)">
            <div class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label for="txtsearch" class="form-label">Payment ID</label>
                    <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Enter Payment ID" required>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-info">Search Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="searchresult" id="searchresult"></div>

<script>
(function () {
    const form = document.querySelector('form.form_payment_search');
    const result = document.getElementById('searchresult');
    const resultSelector = '#searchresult';
    let actionInFlight = false;

    function renderResult(html) {
        if (window.jQuery) {
            window.jQuery(resultSelector).html(html);
            return;
        }

        result.innerHTML = html;
    }

    function reloadResult(url, payload, confirmMsg) {
        if (!window.jQuery) {
            return;
        }

        if (actionInFlight) {
            return;
        }

        if (!confirm(confirmMsg)) {
            return;
        }

        actionInFlight = true;

        window.jQuery(resultSelector).html('Updating...');

        window.jQuery.post(url, payload, function (html) {
            renderResult(html);
        }).fail(function () {
            renderResult('<div class="alert alert-danger">Unable to update payment record.</div>');
        }).always(function () {
            actionInFlight = false;
        });
    }

    if (!form || !result) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        renderResult('Loading...');

        $.post('<?= base_url('Payment_Medical/payment_record') ?>', $(form).serialize(), function (html) {
            renderResult(html);
        }).fail(function () {
            renderResult('<div class="alert alert-danger">Unable to fetch payment record.</div>');
        });
    });

    if (window.jQuery) {
        const $doc = window.jQuery(document);

        $doc.off('click.paymentEdit', '#btn_update_bank')
            .on('click.paymentEdit', '#btn_update_bank', function () {
                reloadResult('<?= base_url('Payment_Medical/change_to_bank') ?>', {
                    pay_id: window.jQuery('#p_id').val(),
                    cbo_pay_type: window.jQuery('#cbo_pay_type').val(),
                    input_card_tran: window.jQuery('#input_card_tran').val()
                }, 'Are you sure Edit this Payment?');
            });

        $doc.off('click.paymentEdit', '#btn_update_cash')
            .on('click.paymentEdit', '#btn_update_cash', function () {
                reloadResult('<?= base_url('Payment_Medical/change_to_cash') ?>', {
                    pay_id: window.jQuery('#p_id').val()
                }, 'Are you sure Edit this Payment?');
            });

        $doc.off('click.paymentEdit', '#btn_update_amount')
            .on('click.paymentEdit', '#btn_update_amount', function () {
                reloadResult('<?= base_url('Payment_Medical/update_amount') ?>', {
                    pay_id: window.jQuery('#p_id').val(),
                    change_value: window.jQuery('#input_change_value').val()
                }, 'Are you sure Change Amount?');
            });

        $doc.off('click.paymentEdit', '#btn_update_user')
            .on('click.paymentEdit', '#btn_update_user', function () {
                reloadResult('<?= base_url('Payment_Medical/change_user') ?>', {
                    pay_id: window.jQuery('#p_id').val(),
                    user_list: window.jQuery('#user_list').val()
                }, 'Are you sure Change User for this Payment?');
            });
    }
})();
</script>
