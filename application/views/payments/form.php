<?php $this->load->view("partial/header"); ?>

<link href="css/plugins/datapicker/datepicker3.css" rel="stylesheet">

<?php echo form_open('payments/save/' . $payment_info->loan_payment_id, array('id' => 'payment_form', 'class' => 'form-horizontal')); ?>

<input type="hidden" id="loan_payment_id" name="loan_payment_id" value="<?= $payment_info->loan_payment_id; ?>" />

<div class="title-block">
    <h3 class="title"> 

        <?php if ($payment_info->loan_payment_id > 0): ?>
            Update payment
        <?php else: ?>
            New payment
        <?php endif; ?>

    </h3>
    <p class="title-description">
        Payment basic information
    </p>
</div>

<div class="section">
    <div class="row sameheight-container">
        <div class="col-lg-12">

            <div class="card">

                <div class="card-block">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="inqbox float-e-margins">
                                
                                <div class="inqbox-content">

                                    <div style="text-align: center">
                                        <div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>
                                        <ul id="error_message_box"></ul>
                                    </div>

                                    <div class="form-group row"><label class="col-sm-2 control-label"><?php echo form_label($this->lang->line('loans_customer') . ':', 'inp-customer', array('class' => 'wide required')); ?></label>
                                        <div class="col-sm-10">
                                            <?php
                                            echo form_input(
                                                    array(
                                                        'name' => 'inp-customer',
                                                        'id' => 'inp-customer',
                                                        'value' => $payment_info->customer_name,
                                                        'class' => 'form-control',
                                                        'placeholder' => $this->lang->line("common_start_typing"),
                                                        'style' => 'display:' . ($payment_info->customer_id <= 0 ? "" : "none")
                                                    )
                                            );
                                            ?>

                                            <span id="sp-customer" style="display: <?= ($payment_info->customer_id > 0 ? "" : "none") ?>">
                                                <?= $payment_info->customer_name; ?>
                                            </span>
                                            <input type="hidden" id="customer" name="customer" value="<?= $payment_info->customer_id; ?>" />
                                        </div>
                                    </div>
                                    <div class="hr-line-dashed"></div>

                                    <div class="form-group row"><label class="col-sm-2 control-label"><?php echo form_label($this->lang->line('payments_account') . ':', 'inp-customer-id', array('class' => 'wide')); ?></label>
                                        <div class="col-sm-10">
                                            <?php
                                            echo form_input(
                                                    array(
                                                        'name' => 'account',
                                                        'id' => 'account',
                                                        'value' => $payment_info->account,
                                                        'class' => 'form-control',
                                                    )
                                            );
                                            ?>
                                        </div>
                                    </div>
                                    <div class="hr-line-dashed"></div>

                                    <div class="form-group row"><label class="col-sm-2 control-label"><?php echo form_label($this->lang->line('payments_loan') . ':', 'loan', array('class' => 'wide required')); ?></label>
                                        <div class="col-sm-10">
                                            <select id="loan_id" name="loan_id" class="form-control">
                                                <?= $balance_amount = ''; ?>
                                                <option value="">Choose</option>
                                                <?php foreach ($loans as $loan): ?>
                                                    <?php $selected = ''; ?>
                                                    <?php if ($loan['loan_id'] === $payment_info->loan_id) : ?>
                                                        <?php $selected = 'selected="selected"'; ?>                    
                                                        <?php $balance_amount = $loan['balance']; ?>                    
                                                    <?php endif; ?>
                                                    <option value="<?= $loan['loan_id'] ?>" <?= $selected; ?> data-balance="<?= $loan['balance'] ?>"><?= $loan['text']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="balance_amount" id="balance_amount" value="<?= $balance_amount; ?>" />
                                        </div>
                                    </div>
                                    <div class="hr-line-dashed"></div>

                                    <div class="form-group row" style="display:none;">
                                        <label class="col-sm-2 control-label">
                                            For payment due on:
                                        </label>
                                        <div class="col-sm-10">
                                            <select class="form-control" id="sel_payment_due" name="payment_due">
                                                <option>Choose</option>
                                                <?php if ($payment_info->payment_due > 0): ?>
                                                    <option selected="selected"><?= date($this->config->item('date_format'), $payment_info->payment_due) ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="hr-line-dashed"></div>

                                    <div class="form-group row">
                                        <label class="col-sm-2 control-label">
                                            Amount to Pay:
                                        </label>
                                        <div class="col-sm-10">
                                            <?php
                                            echo form_input(
                                                    array(
                                                        'name' => 'paid_amount',
                                                        'id' => 'paid_amount',
                                                        'value' => $payment_info->paid_amount,
                                                        'class' => 'form-control',
                                                        'type' => 'number',
                                                        'step' => 'any',
                                                    )
                                            );
                                            ?>
                                        </div>
                                    </div>
                                    <div class="hr-line-dashed"></div>

                                    <script>
                                        $(document).ready(function () {
                                            $("#loan_id").change(function () {
                                                var url = '<?= site_url('payments/ajax') ?>';
                                                var params = {
                                                    softtoken: $("input[name='softtoken']").val(),
                                                    loan_id: $("#loan_id").val(),
                                                    type: 1
                                                };
                                                $("#sel_payment_due").prop('disabled', true);
                                                $.post(url, params, function (data) {
                                                    if (data.status == "OK")
                                                    {
                                                        $("#sel_payment_due").html(data.options);
                                                        $("#sel_payment_due").prop('disabled', false);
                                                        $("#balance_amount").val(data.balance);
                                                    }
                                                }, 'json');
                                            });

                                            $("#sel_payment_due").change(function () {
                                                var url = '<?= site_url('payments/ajax') ?>';
                                                var params = {
                                                    softtoken: $("input[name='softtoken']").val(),
                                                    due_date: $("#sel_payment_due").val(),
                                                    amount_to_pay: $("#sel_payment_due option:selected").attr('data-amount-to-pay'),
                                                    penalty_value: $("#sel_payment_due option:selected").attr('data-penalty-value'),
                                                    penalty_type: $("#sel_payment_due option:selected").attr('data-penalty-type'),
                                                    type: 2
                                                };
                                                $.post(url, params, function (data) {
                                                    if (data.status == "OK")
                                                    {
                                                        $("#hid-penalty-amount-total").val(data.penalty_amount);
                                                        $("#paid_amount").val(data.amount_to_pay);
                                                    }
                                                }, 'json');
                                            });
                                        });
                                    </script>

                                    <div class="form-group row"><label class="col-sm-2 control-label"><?php echo form_label($this->lang->line('payments_teller') . ':', 'teller', array('class' => 'wide')); ?></label>
                                        <div class="col-sm-10">
                                            <?php echo isset($payment_info->teller_name) ? ucwords($payment_info->teller_name) : ucwords($user_info->first_name . " " . $user_info->last_name); ?>
                                            <input type="hidden" id="teller" name="teller" value="<?= $payment_info->teller_id; ?>" />
                                        </div>
                                    </div>
                                    <div class="hr-line-dashed"></div>

                                    <div class="form-group row" id="data_1">
                                        <label class="col-sm-2 control-label">
                                            <?php echo form_label($this->lang->line('payments_date') . ':', 'payment_date', array('class' => 'wide required')); ?>
                                        </label>
                                        <div class="col-sm-10">
                                            <div class="input-group date">
                                                <span class="input-group-addon input-group-prepend"><span class="input-group-text"><i class="fa fa-calendar"></i></span></span>
                                                <?php
                                                echo form_input(
                                                        array(
                                                            'name' => 'date_paid',
                                                            'id' => 'date_paid',
                                                            'value' => (isset($payment_info->date_paid) && $payment_info->date_paid > 0) ? date($this->config->item('date_format'), $payment_info->date_paid) : date($this->config->item('date_format')),
                                                            'class' => 'form-control',
                                                        )
                                                );
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hr-line-dashed"></div>

                                    <div class="form-group row"><label class="col-sm-2 control-label"><?php echo form_label($this->lang->line('common_remarks') . ':', 'remarks', array('class' => 'wide')); ?></label>
                                        <div class="col-sm-10">
                                            <?php
                                            echo form_textarea(
                                                    array(
                                                        'name' => 'remarks',
                                                        'id' => 'remarks',
                                                        'value' => $payment_info->remarks,
                                                        'rows' => '5',
                                                        'cols' => '17',
                                                        'class' => 'form-control'
                                                    )
                                            );
                                            ?>
                                        </div>
                                    </div>
                                    <div class="hr-line-dashed"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-12">
                    <div class="form-group">

                        <a href="<?=site_url('payments'); ?>" type="button" class="btn btn-default" ><?= $this->lang->line("common_close"); ?></a>
                        <?php if ( check_access($user_info->role_id, "payments", 'add') ): ?>
                            <?php if ($payment_info->loan_payment_id === -1) : ?>
                                <?php
                                echo form_submit(
                                        array(
                                            'name' => 'submit',
                                            'id' => 'btn-save',
                                            'value' => $this->lang->line('common_save'),
                                            'class' => 'btn btn-primary'
                                        )
                                );
                                ?>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>    
</div>

<input type="hidden" id="modified_by" name="modified_by" value="<?= $payment_info->modified_by; ?>" />
<input type="hidden" id="user_info" value="<?= $user_info->person_id; ?>" />

<?php
echo form_close();
?>


<?php $this->load->view("partial/footer"); ?>


<script type='text/javascript'>

                    //validation and submit handling
                    $(document).ready(function ()
                    {
                        $('#data_1 .input-group.date').datepicker({
                            format: '<?= calendar_date_format(); ?>',
                            todayBtn: "linked",
                            keyboardNavigation: false,
                            forceParse: false,
                            calendarWeeks: true,
                            autoclose: true
                        });

                        $("#inp-customer-id").change(function () {
                            get_customer_by_id($(this).val());
                        });

                        if ($("#teller").val() <= 0)
                        {
                            $("#teller").val($("#user_info").val());
                        }

                        if ($("#loan_payment_id").val() > -1)
                        {
                            $("#modified_by").val($("#user_info").val());
                            $("#payment_form input, textarea").prop("readonly", true);
                            $("#payment_form select").prop("disabled", true);
                            $("#btn-save").hide();
                        }

                        $(document).on("click", ".btn-remove-row", function () {
                            clear_customer();
                        });

                        $('#inp-customer').autocomplete({
                            serviceUrl: '<?php echo site_url("loans/customer_search"); ?>',
                            onSelect: function (suggestion) {
                                $("#inp-customer-id").val(suggestion.data);
                                $("#customer").val(suggestion.data);
                                $("#sp-customer").html(suggestion.value + ' <span><a href="javascript:void(0)" title="Remove Customer" class="btn-remove-row"><i class="fa fa-times"></i></a></span>');
                                $("#sp-customer").show();
                                $("#inp-customer").hide();

                                populate_loans(suggestion.data);
                            }
                        });

                        var settings = {
                            submitHandler: function (form) {
                                $(form).ajaxSubmit({
                                    success: function (response) {
                                        post_payment_form_submit(response);
                                    },
                                    dataType: 'json',
                                    type: 'post'
                                });
                            },
                            rules: {
                                customer: "required",
                                loan_id: "required"
                            },
                            messages: {
                                customer: "<?php echo $this->lang->line('payment_customer_required'); ?>",
                                loan_id: "<?php echo $this->lang->line('payment_loan_required'); ?>",
                            }
                        };

                        $('#payment_form').validate(settings);

                        $.validator.addMethod("greaterThanZero", function (value, element) {
                            if ((parseFloat(value) > 0) && parseFloat(value) <= parseFloat($("#balance_amount").val()))
                            {
                                return true;
                            }
                            
                            return false;
                            
                        }, "<?php echo $this->lang->line('payment_paid_amount_required') ?>");
                    });

                    function populate_loans(customer_id)
                    {
                        $.ajax({
                            url: "<?= site_url("payments/get_loans") ?>/" + customer_id,
                            type: "get",
                            dataType: 'json',
                            success: function (data) {
                                var options = $("#loan_id");
                                options.empty();
                                options.append('<option value="">Choose</option>');
                                $.each(data, function () {
                                    options.append($("<option />").val(this.loan_id).attr("data-balance", this.loan_balance).text(this.loan_type + " (" + this.loan_amount + ") - " + this.loan_balance));
                                });

                                var balance = $('#loan_id option:selected').data('balance');
                                $("#balance_amount").val(balance.replace(/[^\d.]/g, ''));
                            },
                            error: function () {
                                ;
                            }
                        });
                    }

                    function get_customer_by_id(customer_id)
                    {
                        $.ajax({
                            url: "<?= site_url("payments/get_customer") ?>/" + customer_id,
                            type: "get",
                            dataType: 'json',
                            success: function (suggestion) {
                                if ($.trim(suggestion.value) !== "")
                                {
                                    $("#customer").val(suggestion.data);
                                    $("#sp-customer").html(suggestion.value + ' <span><a href="javascript:void(0)" title="Remove Customer" class="btn-remove-row"><i class="fa fa-times"></i></a></span>');
                                    $("#sp-customer").show();
                                    $("#inp-customer").hide();
                                    populate_loans(suggestion.data);
                                } else
                                {
                                    clear_customer();
                                }
                            },
                            error: function () {
                                ;
                            }
                        });
                    }

                    function clear_customer()
                    {
                        $("#sp-customer").hide();
                        $("#sp-customer").html("");
                        $("#inp-customer").val("");
                        $("#inp-customer").show();
                        $("#customer").val("");
                        var options = $("#loan_id");
                        options.empty();
                        $("#inp-customer-id").val("");
                    }

                    function post_payment_form_submit(response)
                    {
                        if (!response.success)
                        {
                            set_feedback(response.message, 'error_message', true);
                        } else
                        {
                            set_feedback(response.message, 'success_message', false);
                        }

                        $("#payment_form").attr("action", "<?= site_url(); ?>payments/save/" + response.loan_payment_id);
                    }
</script>