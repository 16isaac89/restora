<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/custom/report.css">

<section class="main-content-wrapper">
    <section class="content-header">
        <h3 class="top-left-header"><?php echo lang('z_report'); ?> - Cashier Session</h3>
    </section>

    <div class="box-wrapper">
        <div class="table-box p-3">
            <?php if(isset($register_info) && $register_info): ?>
                <?php
                $payment_methods_sale = json_decode($register_info->payment_methods_sale);
                $others_currency = json_decode($register_info->others_currency);
                ?>
                <div class="mb-3">
                    <a class="btn bg-blue-btn me-2" href="javascript:window.print()"><?php echo lang('print'); ?></a>
                    <a class="btn bg-blue-btn" href="<?php echo base_url(); ?>Report/registerReport"><?php echo lang('back'); ?></a>
                </div>

                <table class="table table-bordered">
                    <tbody>
                    <tr>
                        <th><?php echo lang('outlet'); ?></th>
                        <td><?php echo escape_output($register_info->outlet_name); ?></td>
                        <th><?php echo lang('counter'); ?></th>
                        <td><?php echo escape_output($register_info->counter_name); ?></td>
                    </tr>
                    <tr>
                        <th>Cashier</th>
                        <td><?php echo escape_output($register_info->cashier_name); ?></td>
                        <th><?php echo lang('sn'); ?></th>
                        <td>#<?php echo escape_output($register_info->id); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo lang('opening_date_time'); ?></th>
                        <td><?php echo escape_output($register_info->opening_balance_date_time); ?></td>
                        <th><?php echo lang('closing_date_time'); ?></th>
                        <td><?php echo escape_output($register_info->closing_balance_date_time); ?></td>
                    </tr>
                    </tbody>
                </table>

                <table class="table table-bordered mt-3">
                    <thead>
                    <tr>
                        <th>Summary</th>
                        <th class="text-end"><?php echo lang('amount'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr><td><?php echo lang('opening_balance'); ?></td><td class="text-end"><?php echo escape_output(getAmtPCustom($register_info->opening_balance)); ?></td></tr>
                    <tr><td><?php echo lang('sale'); ?> (<?php echo lang('paid_amount'); ?>)</td><td class="text-end"><?php echo escape_output(getAmtPCustom($register_info->sale_paid_amount)); ?></td></tr>
                    <tr><td><?php echo lang('refund_amount'); ?></td><td class="text-end"><?php echo escape_output(getAmtPCustom($register_info->refund_amount)); ?></td></tr>
                    <tr><td><?php echo lang('customer_due_receive'); ?></td><td class="text-end"><?php echo escape_output(getAmtPCustom($register_info->customer_due_receive)); ?></td></tr>
                    <tr><td><?php echo lang('purchase'); ?></td><td class="text-end"><?php echo escape_output(getAmtPCustom($register_info->total_purchase)); ?></td></tr>
                    <tr><td><?php echo lang('expense'); ?></td><td class="text-end"><?php echo escape_output(getAmtPCustom($register_info->total_expense)); ?></td></tr>
                    <tr><td><?php echo lang('due_payment'); ?></td><td class="text-end"><?php echo escape_output(getAmtPCustom($register_info->total_due_payment)); ?></td></tr>
                    <tr><th><?php echo lang('closing_balance'); ?></th><th class="text-end"><?php echo escape_output(getAmtPCustom($register_info->closing_balance)); ?></th></tr>
                    </tbody>
                </table>

                <table class="table table-bordered mt-3">
                    <thead>
                    <tr>
                        <th><?php echo lang('sale_in_payment_method'); ?></th>
                        <th class="text-end"><?php echo lang('amount'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(isset($payment_methods_sale) && $payment_methods_sale): ?>
                        <?php foreach ($payment_methods_sale as $payment_name=>$payment_amount): ?>
                            <tr>
                                <td><?php echo escape_output($payment_name); ?></td>
                                <td class="text-end"><?php echo escape_output(getAmtPCustom($payment_amount)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center">No payment data found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <table class="table table-bordered mt-3">
                    <thead>
                    <tr>
                        <th><?php echo lang('others_currency'); ?></th>
                        <th class="text-end"><?php echo lang('amount'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(isset($others_currency) && $others_currency): ?>
                        <?php foreach ($others_currency as $currency_row): ?>
                            <tr>
                                <td><?php echo escape_output($currency_row->payment_name); ?></td>
                                <td class="text-end"><?php echo escape_output($currency_row->amount); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center">N/A</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-danger">Register session not found.</div>
                <a class="btn bg-blue-btn" href="<?php echo base_url(); ?>Report/registerReport"><?php echo lang('back'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
