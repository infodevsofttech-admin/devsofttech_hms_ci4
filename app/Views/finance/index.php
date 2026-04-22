<section class="content finance-sop">
    <?php $user = function_exists('auth') ? auth()->user() : null; ?>
    <?php $canBillingSubmit = $user && method_exists($user, 'can') ? ($user->can('finance.cash.billing.submit') || $user->can('finance.*')) : false; ?>
    <?php $canAccountsVerify = $user && method_exists($user, 'can') ? ($user->can('finance.cash.accounts.accept') || $user->can('finance.cash.accounts.verify') || $user->can('finance.*')) : false; ?>
    <?php $canBankAudit = $user && method_exists($user, 'can') ? ($user->can('finance.bank.deposit.create') || $user->can('finance.bank.audit') || $user->can('finance.bank.statement.update') || $user->can('finance.*')) : false; ?>

    <div class="mb-3">
        <h2 class="mb-1">Accounts And Finance</h2>
        <p class="text-muted mb-0">Simple operational workflow for Billing cash statement submission, Accounts verification, and bank transaction audit.</p>
    </div>

    <div class="row g-3">
        <?php if ($canBillingSubmit): ?>
            <div class="col-md-4">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h5 class="card-title">1) Cash Collection Statement</h5>
                        <p class="small text-muted">Billing Department creates and submits daily cash statement to Accounts.</p>
                        <a class="btn btn-primary btn-sm" href="javascript:load_form('<?= base_url('billing/cash-submission/create') ?>','Billing Cash Statement Submission');">
                            Open Billing Statement
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($canAccountsVerify): ?>
            <div class="col-md-4">
                <div class="card h-100 border-info">
                    <div class="card-body">
                        <h5 class="card-title">2) Accounts Accept & Verify</h5>
                        <p class="small text-muted">Accounts Department accepts submitted statements and verifies payment records.</p>
                        <a class="btn btn-info btn-sm text-white" href="javascript:load_form('<?= base_url('Finance/cashbook/accounts') ?>','Accounts Accept and Verify');">
                            Open Verification Queue
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($canBankAudit): ?>
            <div class="col-md-4">
                <div class="card h-100 border-success">
                    <div class="card-body">
                        <h5 class="card-title">3) Bank Trans Audit</h5>
                        <p class="small text-muted">Audit bank transactions and mark updates posted in the bank statement register.</p>
                        <a class="btn btn-success btn-sm" href="javascript:load_form('<?= base_url('Finance/bank_deposits') ?>','Bank Transaction Audit');">
                            Open Bank Audit Register
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
