<?php $this->load->view("partial/header"); ?>

<style>
    #tbl_loans_transactions td:nth-child(5),
    #tbl_loans_transactions td:nth-child(6) {
        text-align: right;
    }
    .dataTables_info {
        float:left;
    }
</style>

<div class="title-block">
    <h3 class="title">List of Employees</h3>
    <p class="title-description">
        Add, update & delete employees
    </p>
</div>

<div class="section">
    <div class="row sameheight-container">

        <div class="col-lg-12">
            <div class="card" style="width:100%">

                <div class="card-block">

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="inqbox float-e-margins">

                                <div class="inqbox-content table-responsive">

                                    <table class="table table-hover table-bordered" id="tbl_employees">
                                        <thead>
                                            <tr>
                                                <th style="text-align: center; width: 1%"></th>
                                                <th style="text-align: center">Username</th>
                                                <th style="text-align: center">Last Name</th>
                                                <th style="text-align: center">First Name</th>
                                                <th style="text-align: center">User Type</th>
                                                <th style="text-align: center">Email</th>
                                                <th style="text-align: center">Phone Number</th>                            
                                            </tr>
                                        </thead>
                                    </table>

                                    <?= $tbl_employees; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="extra-filters" style="display: none;">
    &nbsp;<button class="btn btn-primary" id="btn-export-pdf"><span class="fa fa-print"></span> Print</button>
    <select class="form-control hidden-xs" id="sel-staff">
        <option value="0">Select employee</option>
        <?php foreach ($staffs as $staff): ?>
            <option value="<?= $staff->person_id; ?>" <?= ((isset($_GET['employee_id'])) && $_GET['employee_id'] === $staff->person_id) ? 'selected="selected"' : ""; ?>><?= $staff->first_name . " " . $staff->last_name; ?></option>
        <?php endforeach; ?>
    </select>&nbsp;
</div>

<div id="dt-extra-params">
    <input type="hidden" id="employee_id" name="employee_id" value="0" />
</div>

<?php echo form_open('employees/ajax', 'id="frmEmployeeDelete"', ["type" => 2]); ?>
<?php echo form_close(); ?>

<script>
    $(document).ready(function () {
        $("#tbl_employees_filter").prepend("<a href='<?= site_url('employees/view/-1') ?>' class='btn btn-primary pull-left'>New Employee</a>");
        $("#tbl_employees_filter input[type='search']").attr("placeholder", "Type your search here");
        $("#tbl_employees_filter input[type='search']").removeClass("input-sm");
        $("#tbl_employees_filter").append($(".extra-filters").html());
        
        $(document).on("click", "#btn-export-pdf", function(){
            var clone = $("#tbl_employees_wrapper .dataTables_scrollBody").clone();
            
            $(clone).find("table").attr("border", 1);
            $(clone).find("table").attr("cellpadding", 5);
            $(clone).find("table").attr("cellspacing", 1);
            $(clone).find("table").attr("width", "100%");
            $(clone).find("table th:nth-child(1)").remove();
            $(clone).find("table td:nth-child(1)").remove();
            
            var url = '<?=site_url('printing/print_list/employees.pdf');?>';
            var params = {
                softtoken:$("input[name='softtoken']").val(),
                html: clone.html()
            };
            blockElement("#btn-export-pdf");
            $.post(url, params, function(data){
                if ( data.status == "OK" )
                {
                    window.open(data.url,'_blank');
                }
                unblockElement("#btn-export-pdf");
            }, "json");
        });

        $(document).on("change", "#filter_by", function () {
            $("#status").val($(this).val());
            $("#tbl_employees").DataTable().ajax.reload();
        });

        $(document).on("change", "#sel-staff", function () {
            $("#employee_id").val($(this).val());
            $("#tbl_employees").DataTable().ajax.reload();
        });

        $(document).on("click", ".btn-delete", function () {
            var $this = $(this);
            alertify.confirm("Are you sure you wish to delete this employee?", function () {
                var url = $("#frmEmployeeDelete").attr("action");
                var params = $("#frmEmployeeDelete").serialize();
                params += '&ids=' + $this.attr("data-employee-id");
                $.post(url, params, function (data) {
                    if (data.success)
                    {
                        $("#tbl_employees").DataTable().ajax.reload();
                    }
                }, "json");
            });
        });
    });
</script>

<?php $this->load->view("partial/footer"); ?>