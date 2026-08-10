<div class="card mb-3">
    <div class="card-body py-3">
        <h5 class="card-title mb-3">Payment Edit</h5>
        <form id="payment-edit-search-form" method="post">
            <?= csrf_field() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label for="payment-edit-search-id" class="form-label">Payment ID</label>
                    <input class="form-control" type="number" min="1" step="1" id="payment-edit-search-id" name="txtsearch" placeholder="Enter Payment ID" required>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="payment-edit-result"></div>

<script>
(function () {
    if (!window.jQuery) {
        return;
    }

    const $doc = window.jQuery(document);
    let actionInFlight = false;
    let csrfHash = '<?= esc(csrf_hash()) ?>';
    const csrfToken = '<?= esc(csrf_token()) ?>';

    function showResult(html) {
        window.jQuery('#payment-edit-result').html(html);
    }

    function showError(xhr) {
        const message = xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message
            : 'Unable to update payment record.';
        window.jQuery('#payment-edit-action-message').html('<div class="alert alert-danger mb-3">' + window.jQuery('<div>').text(message).html() + '</div>');
        if (xhr.responseJSON && xhr.responseJSON.csrf_hash) {
            csrfHash = xhr.responseJSON.csrf_hash;
        }
    }

    function captureSearchToken(xhr) {
        const nextToken = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (nextToken) {
            csrfHash = nextToken;
            window.jQuery('#payment-edit-search-form input[name="' + csrfToken + '"]').val(nextToken);
        }
    }

    function correctionPayload(extra) {
        return Object.assign({
            pay_id: window.jQuery('#payment-edit-id').val(),
            correction_reason: window.jQuery('#payment-edit-reason').val(),
            [csrfToken]: csrfHash
        }, extra || {});
    }

    function updatePayment(url, payload, confirmation) {
        if (actionInFlight || !confirm(confirmation)) {
            return;
        }

        actionInFlight = true;
        window.jQuery('#payment-edit-action-message').html('<div class="alert alert-info mb-3">Updating payment...</div>');
        window.jQuery.post(url, payload)
            .done(function (response) {
                if (response.csrf_hash) {
                    csrfHash = response.csrf_hash;
                }
                showResult('<div class="alert alert-success">' + window.jQuery('<div>').text(response.message).html() + '</div>' + response.html);
            })
            .fail(showError)
            .always(function () {
                actionInFlight = false;
            });
    }

    $doc.off('submit.paymentGeneralEdit', '#payment-edit-search-form')
        .on('submit.paymentGeneralEdit', '#payment-edit-search-form', function (event) {
            event.preventDefault();
            showResult('<div class="alert alert-info">Loading payment...</div>');
            window.jQuery.post('<?= base_url('Payment/payment_record') ?>', window.jQuery(this).serialize())
                .done(function (html, textStatus, xhr) {
                    captureSearchToken(xhr);
                    showResult(html);
                })
                .fail(function (xhr) {
                    captureSearchToken(xhr);
                    showResult(xhr.responseText || '<div class="alert alert-danger">Unable to find payment.</div>');
                });
        });

    $doc.off('click.paymentGeneralEdit', '#payment-edit-to-bank')
        .on('click.paymentGeneralEdit', '#payment-edit-to-bank', function () {
            updatePayment('<?= base_url('Payment/change_to_bank') ?>', correctionPayload({
                cbo_pay_type: window.jQuery('#payment-edit-bank-source').val(),
                input_card_tran: window.jQuery('#payment-edit-transaction').val()
            }), 'Change this payment to Bank/Online?');
        });

    $doc.off('click.paymentGeneralEdit', '#payment-edit-to-cash')
        .on('click.paymentGeneralEdit', '#payment-edit-to-cash', function () {
            updatePayment('<?= base_url('Payment/change_to_cash') ?>', correctionPayload(), 'Change this payment to Cash?');
        });

    $doc.off('click.paymentGeneralEdit', '#payment-edit-change-user')
        .on('click.paymentGeneralEdit', '#payment-edit-change-user', function () {
            updatePayment('<?= base_url('Payment/change_user') ?>', correctionPayload({
                user_list: window.jQuery('#payment-edit-user').val()
            }), 'Change the user recorded for this payment?');
        });

    $doc.off('click.paymentGeneralEdit', '#payment-edit-change-amount')
        .on('click.paymentGeneralEdit', '#payment-edit-change-amount', function () {
            updatePayment('<?= base_url('Payment/change_amount') ?>', correctionPayload({
                change_value: window.jQuery('#payment-edit-amount').val()
            }), 'Change the amount recorded for this payment?');
        });
})();
</script>