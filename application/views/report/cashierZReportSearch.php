<link rel="stylesheet" href="<?php echo base_url(); ?>assets/dist/css/custom/report.css">

<section class="main-content-wrapper">
    <section class="content-header px-0">
        <div class="d-flex align-items-center">
            <h3 class="top-left-header text-left">
                <?php echo lang('z_report'); ?> - Cashier Sessions
                <input type="hidden" class="datatable_name" data-id_name="datatable">
            </h3>
            <?php if(isLMni() && isset($outlet_id)):?>
                <p class="mx-2 txt-color-grey my-0"><?php echo lang('outlet'); ?>: <?php echo escape_output(getOutletNameById($outlet_id))?></p>
            <?php endif;?>
        </div>
    </section>

    <div class="box-wrapper">
        <div class="test-filter-modals mb-2">
            <div class="row">
                <?php echo form_open(base_url() . 'Report/cashierZReport') ?>
                <div class="col-sm-12 mb-2 col-md-4 col-lg-2">
                    <div class="form-group">
                        <input type="text" name="startDate" readonly class="form-control customDatepicker"
                               placeholder="<?php echo lang('start_date'); ?>" value="<?php echo isset($start_date)?escape_output($start_date):''; ?>">
                    </div>
                </div>
                <div class="col-sm-12 mb-2 col-md-4 col-lg-2">
                    <div class="form-group">
                        <input type="text" name="endDate" readonly class="form-control customDatepicker"
                               placeholder="<?php echo lang('end_date'); ?>" value="<?php echo isset($end_date)?escape_output($end_date):''; ?>">
                    </div>
                </div>
                <div class="col-sm-12 mb-2 col-md-4 col-lg-2">
                    <div class="form-group">
                        <select class="form-control select2 ir_w_100" name="user_id">
                            <option value=""><?php echo lang('user'); ?></option>
                            <?php foreach ($users as $value): ?>
                                <option <?php echo (isset($user_id) && $user_id==$value->id)?'selected':''; ?> value="<?php echo escape_output($value->id); ?>">
                                    <?php echo escape_output($value->full_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php if(isLMni()): ?>
                    <div class="col-sm-12 mb-2 col-md-4 col-lg-2">
                        <div class="form-group">
                            <select class="form-control select2 ir_w_100" id="outlet_id" name="outlet_id">
                                <?php foreach (getAllOutlestByAssign() as $value): ?>
                                    <option <?php echo (isset($outlet_id) && $outlet_id==$value->id)?'selected':''; ?> value="<?php echo escape_output($value->id); ?>">
                                        <?php echo escape_output($value->outlet_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="col-sm-12 mb-2 col-md-4 col-lg-2">
                    <div class="form-group">
                        <button type="submit" name="submit" value="submit" class="btn bg-blue-btn w-100"><?php echo lang('submit'); ?></button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>

        <div class="table-box">
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                    <tr>
                        <th><?php echo lang('sn'); ?></th>
                        <th>Cashier</th>
                        <th><?php echo lang('counter'); ?></th>
                        <th><?php echo lang('opening_date_time'); ?></th>
                        <th><?php echo lang('closing_date_time'); ?></th>
                        <th><?php echo lang('closing_balance'); ?></th>
                        <th><?php echo lang('action'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(isset($register_info) && $register_info): ?>
                        <?php $i=1; foreach($register_info as $row): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo escape_output(userName($row->user_id)); ?></td>
                                <td><?php echo escape_output($row->counter_name); ?></td>
                                <td><?php echo escape_output($row->opening_balance_date_time); ?></td>
                                <td><?php echo escape_output($row->closing_balance_date_time); ?></td>
                                <td><?php echo escape_output(getAmtPCustom($row->closing_balance)); ?></td>
                                <td>
                                    <a class="btn btn-xs bg-blue-btn" href="<?php echo base_url().'Report/cashierZReport/'.$this->custom->encrypt_decrypt($row->id, 'encrypt'); ?>">
                                        <?php echo lang('z_report'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script src="<?php echo base_url(); ?>assets/datatable_custom/jquery-3.3.1.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.html5.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.print.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jszip.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/pdfmake.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/vfs_fonts.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/newDesign/js/forTable.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/custom_report_no_sorting.js"></script>
