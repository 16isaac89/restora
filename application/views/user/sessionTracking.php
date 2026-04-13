<section class="main-content-wrapper">
    <section class="content-header">
        <h2 class="top-left-header">User Session Tracking</h2>
        <input type="hidden" class="datatable_name" data-title="User Session Tracking" data-id_name="datatable">
    </section>

    <div class="box-wrapper">
        <div class="table-box">
            <div class="row mb-3">
                <div class="col-sm-12 col-md-2">
                    <?php echo form_open(base_url() . 'User/sessionTracking') ?>
                    <div class="form-group">
                        <input type="text" readonly class="form-control customDatepicker" name="start_date" placeholder="Start Date" value="<?php echo escape_output(isset($start_date)?$start_date:''); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-2">
                    <div class="form-group">
                        <input type="text" readonly class="form-control customDatepicker" name="end_date" placeholder="End Date" value="<?php echo escape_output(isset($end_date)?$end_date:''); ?>">
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <select class="form-control select2" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo escape_output($user->id); ?>" <?php echo (isset($selected_user_id) && $selected_user_id==$user->id)?'selected':''; ?>><?php echo escape_output($user->full_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-2">
                    <button type="submit" class="btn bg-blue-btn w-100">Submit</button>
                </div>
                <div class="col-sm-12 col-md-2">
                    <a class="btn bg-blue-btn w-100" href="<?php echo base_url(); ?>User/users">Back</a>
                </div>
                <?php echo form_close(); ?>
            </div>

            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                    <tr>
                        <th><?php echo lang('sn'); ?></th>
                        <th><?php echo lang('name'); ?></th>
                        <th><?php echo lang('designation'); ?></th>
                        <th><?php echo lang('outlet'); ?></th>
                        <th>Login At</th>
                        <th>Logout At</th>
                        <th>Duration (Min)</th>
                        <th><?php echo lang('status'); ?></th>
                        <th>IP</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(isset($sessions) && $sessions): ?>
                        <?php foreach ($sessions as $k=>$session): ?>
                            <tr>
                                <td><?php echo escape_output($k+1); ?></td>
                                <td><?php echo escape_output($session->full_name); ?></td>
                                <td><?php echo escape_output($session->designation); ?></td>
                                <td><?php echo escape_output($session->outlet_name); ?></td>
                                <td><?php echo escape_output($session->login_at); ?></td>
                                <td><?php echo escape_output($session->logout_at); ?></td>
                                <td><?php echo escape_output(isset($session->duration_minutes)?$session->duration_minutes:0); ?></td>
                                <td><?php echo escape_output($session->status); ?></td>
                                <td><?php echo escape_output($session->ip_address); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php $this->view('common/footer_js')?>
