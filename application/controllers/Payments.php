<?php

require_once ("Secure_area.php");
require_once ("interfaces/idata_controller.php");

class Payments extends Secure_area implements iData_controller {

    function __construct()
    {
        parent::__construct('payments');
        
        $this->load->library('DataTableLib');
    }

    function index()
    {
        $data['controller_name'] = strtolower(get_class());
        $data['form_width'] = $this->get_form_width();
        
        $res = $this->Employee->getLowerLevels();
        $data['staffs'] = $res;
        
        $this->set_dt_payments($this->datatablelib->datatable());
        $data["tbl_payments"] = $this->datatablelib->render();   
        
        $this->load->view('payments/manage', $data);
    }
    
    function ajax()
    {
        $type = $this->input->post("type");
        switch( $type )
        {
            case 1: // get loan schedules
                $this->_get_loan_schedules();
                break;
            case 2: // check for penalties
                $this->_check_loan_penalties();
                break;
            case 3: // get payments table
                $this->_dt_payments();
                break;
            case 4:
                $this->_get_total_balance();
                break;
        }
    }
    
    private function _get_total_balance()
    {
        $total_balance = $this->session->userdata("tbl_balance");
        
        $return["total_balance"] = to_currency($total_balance, 1);
        $return["status"] = "OK";
        
        send($return);
    }
    
    function set_dt_payments($datatable)
    {
        $datatable->add_server_params('', '', [$this->security->get_csrf_token_name() => $this->security->get_csrf_hash(), "type" => 3]);
        $datatable->ajax_url = site_url('payments/ajax');

        $datatable->add_column('actions', false);
        $datatable->add_column('trans_id', false);
        $datatable->add_column('customer', false);
        $datatable->add_column('loan_amount', false);
        $datatable->add_column('loan_balance', false);
        $datatable->add_column('payable_amount', false);
        $datatable->add_column('trans_date', false);
        $datatable->add_column('teller', false);
        

        $datatable->add_table_definition(["orderable" => false, "targets" => 0]);
        $datatable->order = [[1, 'desc']];

        $datatable->allow_search = true;
        $datatable->no_expand_height = true;
        $datatable->callbacks["footerCallback"] = "paymentsFooter";
        
        $datatable->table_id = "#tbl_payments";
        $datatable->add_titles('Payments');
        $datatable->has_edit_dblclick = 0;
    }

    function _dt_payments()
    {
        $selected_user = $this->input->post("employee_id");
        $status = $this->input->post("status");
        $from_date = $this->input->post("from_date");
        $to_date = $this->input->post("to_date");

        $offset = $this->input->post("start");
        $limit = $this->input->post("length");

        $index = $this->input->post("order")[0]["column"];
        $dir = $this->input->post("order")[0]["dir"];
        $keywords = $this->input->post("search")["value"];

        $order = array("index" => $index, "direction" => $dir);
        
        $filters = [];
        $filters["from_date"] = $this->config->item('date_format') == 'd/m/Y' ? strtotime(uk_to_isodate($from_date)) : strtotime($from_date);
        $filters["to_date"] = $this->config->item('date_format') == 'd/m/Y' ? strtotime(uk_to_isodate($to_date)) : strtotime($to_date);
        $payments = $this->Payment->get_all($limit, $offset, $keywords, $order, $selected_user, $filters);  
        
        $user_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $user_info = $this->Employee->get_info($user_id);

        $tmp = array();

        $count_all = 0;
        $tbl_balance = 0;
        foreach ($payments->result() as $payment)
        {
            $actions = "<a href='" . site_url('payments/view/' . $payment->loan_payment_id) . "' class='btn btn-xs btn-default btn-secondary' title='View'><span class='fa fa-eye'></span></a> ";
            
            if ( check_access($user_info->role_id, "payments", 'delete') )
            {
                $actions .= "<a href='javascript:void(0)' class='btn-xs btn-danger btn-delete btn' data-payment-id='" . $payment->loan_payment_id . "' title='Delete'><span class='fa fa-trash'></span></a> ";
            }
            $actions .= "<a href='javascript:void(0)' data-url='".  site_url('payments/printIt/' . $payment->loan_payment_id) ."' class='btn-print-receipt btn btn-default'>Print</a>";

            $data_row = [];
            $data_row["DT_RowId"] = $payment->loan_payment_id;
            $data_row["actions"] = $actions;
            
            $data_row["trans_id"] = $payment->loan_payment_id;
            $data_row["customer"] = ucwords($payment->customer_name);
            $data_row["loan_amount"] = (trim($payment->loan_type) !== "" ? $payment->loan_type : "Flexible") . " (" . to_currency($payment->loan_amount) . ")";
            $data_row["loan_balance"] = to_currency($payment->balance_amount - $payment->paid_amount);
            $data_row["payable_amount"] = to_currency($payment->paid_amount);
            $data_row["trans_date"] = date($this->config->item('date_format'), $payment->date_paid);
            $data_row["teller"] = ucwords($payment->teller_name);
            
            $tbl_balance += $payment->paid_amount;

            $tmp[] = $data_row;
            $count_all++;
        }

        $this->session->set_userdata("tbl_balance", $tbl_balance);
        
        $data["data"] = $tmp;
        $data["recordsTotal"] = $count_all;
        $data["recordsFiltered"] = $count_all;

        send($data);
    }

    function search()
    {
        
    }

    /*
      Gives search suggestions based on what is being searched for
     */

    function suggest()
    {
        
    }

    function get_row()
    {
        
    }

    function view($payment_id = -1)
    {
        $data['payment_info'] = $this->Payment->get_info($payment_id);
        $res = $this->Payment->get_loans($data['payment_info']->customer_id);

        $loans = array();
        foreach ($res as $loan)
        {
            $tmp['loan_id'] = $loan->loan_id;
            $tmp['balance'] = $loan->loan_balance;
            $tmp['text'] = $loan->loan_type . " (" . to_currency($loan->loan_amount) . ") - bal: " . to_currency($loan->loan_balance);
            $loans[] = $tmp;
        }

        $data['loans'] = $loans;
        $this->load->view("payments/form", $data);
    }

    function printIt($payment_id = -1)
    {
        $payment = $this->Payment->get_info($payment_id);
        $loan = $this->Loan->get_info($payment->loan_id);
        $loan_type = $this->Loan_type->get_info($loan->loan_type_id);
        $person = $this->Person->get_info($payment->teller_id);
        $customer = $this->Person->get_info($payment->customer_id);
        $collateral = $this->Guarantee->get_info($payment->loan_id);

        // pdf viewer 
        $data['collateral'] = $collateral;
        $data['count'] = $payment->loan_payment_id;
        $data['client'] = ucwords($customer->first_name." ".$customer->last_name);
        $data['account'] = $loan->account;
        $data['loan'] = to_currency($loan->loan_amount);
        $data['balance'] = to_currency($loan->loan_balance);
        $data['paid'] = to_currency($payment->paid_amount);
        $data['trans_date'] = date($this->config->item('date_format'), $payment->date_paid);
        $data['teller'] = $person->first_name . " " . $person->last_name;

        $filename = "payments_".date("ymdhis");
        // As PDF creation takes a bit of memory, we're saving the created file in /downloads/reports/
        $pdfFilePath = FCPATH . "/downloads/reports/$filename.pdf";

        ini_set('memory_limit', '-1');
        $html = $this->load->view('payments/pdf_report', $data, true); // render the view into HTML

        $this->load->library('pdf');
        $pdf = $this->pdf->load();
        $pdf->SetFooter($_SERVER['HTTP_HOST'] . '|{PAGENO}|' . date(DATE_RFC822)); 
        $pdf->WriteHTML($html); // write the HTML into the PDF
        $pdf->Output($pdfFilePath, 'F'); // save to file because we can
        // end of pdf viewer
        //$data['pdf_file'] = FCPATH ."/downloads/reports/$filename.pdf";
        $data['pdf_file'] = base_url("downloads/reports/$filename.pdf");


        $this->load->view("payments/print", $data);
    }

    function save($payment_id = -1)
    {
        $payment_data = array(
            'account' => $this->input->post('account'),
            'loan_id' => $this->input->post('loan_id'),
            'customer_id' => $this->input->post('customer'),
            'paid_amount' => $this->input->post('paid_amount'),
            'balance_amount' => $this->input->post('balance_amount'),
            'date_paid' => $this->config->item('date_format') == 'd/m/Y' ? strtotime(uk_to_isodate($this->input->post('date_paid'))) : strtotime($this->input->post('date_paid')),
            'remarks' => $this->input->post('remarks'),
            'teller_id' => $this->input->post('teller'),
            'modified_by' => $this->input->post('modified_by') > 0 ? $this->input->post('modified_by') : 0,
            'payment_due' => $this->config->item('date_format') == 'd/m/Y' ? strtotime(uk_to_isodate($this->input->post('payment_due'))) : strtotime($this->input->post('payment_due')),
            'lpp_amount' => $this->input->post('lpp_amount')
        );

        if ($this->input->post("loan_payment_id") > 0)
        {
            $payment_data['loan_payment_id'] = $this->input->post('loan_payment_id');
        }

        // transactional to make sure that everything is working well
        $this->db->trans_start();
        if ($this->Payment->save($payment_data, $payment_id))
        {
            $wallet_data["amount"] = $payment_data["paid_amount"];
            $wallet_data["wallet_type"] = "debit";
            $wallet_data["trans_date"] = strtotime(date("Y-m-d H:i:s"));
            $wallet_data["added_by"] = $this->Employee->get_logged_in_employee_info()->person_id;
            $wallet_data["descriptions"] = "In payments for <a href='" . site_url("payments/view/" . $payment_data['loan_payment_id']) . "' target='_blank'>" . site_url("payments/view/" . $payment_data['loan_payment_id']) . "</a>";
            
            $this->My_wallet->save($wallet_data);
            
            //New Payment
            if ($payment_id == -1)
            {
                // deduct the loan amount            
                $this->Loan->update_balance($payment_data['paid_amount'], $payment_data['loan_id'], $payment_data["payment_due"]);
                echo json_encode(array('success' => true, 'message' => $this->lang->line('loans_successful_adding') . ' ' .
                    $payment_data['loan_payment_id'], 'loan_payment_id' => $payment_data['loan_payment_id']));
                $payment_id = $payment_data['loan_payment_id'];
            }
            else //previous loan
            {
                $update_amount = $payment_data['paid_amount'] - $this->input->post("original_pay_amount");
                // deduct the loan amount            
                $this->Loan->update_balance($update_amount, $payment_data['loan_id']);
                echo json_encode(array('success' => true, 'message' => $this->lang->line('loans_successful_updating') . ' ' .
                    $payment_data['loan_payment_id'], 'loan_payment_id' => $payment_id));
            }
        }
        else//failure
        {
            echo json_encode(array('success' => false, 'message' => $this->lang->line('loans_error_adding_updating') . ' ' .
                $payment_data['loan_payment_id'], 'loan_payment_id' => -1));
        }
        $this->db->trans_complete();
    }

    function delete()
    {
        $payments_to_delete = $this->input->post('ids');

        if ($this->Payment->delete_list($payments_to_delete))
        {
            echo json_encode(array('success' => true, 'message' => $this->lang->line('loans_successful_deleted') . ' ' .
                count($payments_to_delete) . ' ' . $this->lang->line('payments_one_or_multiple')));
        }
        else
        {
            echo json_encode(array('success' => false, 'message' => $this->lang->line('payments_cannot_be_deleted')));
        }
    }

    /*
      get the width for the add/edit form
     */

    function get_form_width()
    {
        return 360;
    }

    function data()
    {
        $sel_user = $this->input->get("employee_id");
        $index = isset($_GET['order'][0]['column']) ? $_GET['order'][0]['column'] : 1;
        $dir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : "asc";
        $order = array("index" => $index, "direction" => $dir);
        $length = isset($_GET['length'])?$_GET['length']:50;
        $start = isset($_GET['start'])?$_GET['start']:0;
        $key = isset($_GET['search']['value'])?$_GET['search']['value']:"";

        $payments = $this->Payment->get_all($length, $start, $key, $order, $sel_user);

        $format_result = array();

        foreach ($payments->result() as $payment)
        {
            $actions = anchor('payments/view/' . $payment->loan_payment_id, $this->lang->line('common_view'), array('class' => 'btn btn-success', "title" => $this->lang->line('payments_update'))) . " ";
            $actions .= "<a href='javascript:void(0)' data-url='".  site_url('payments/printIt/' . $payment->loan_payment_id) ."' class='btn-print-receipt btn btn-default'>Print</a>";
            //$actions .= anchor('payments/printIt/' . $payment->loan_payment_id, $this->lang->line('common_print'), array('class' => 'modal_link btn btn-default', 'data-toggle' => 'modal', 'data-target' => '#print_modal', "title" => $this->lang->line('payments_print')));
            
            $format_result[] = array(
                "<input type='checkbox' name='chk[]' id='payment_$payment->loan_payment_id' value='" . $payment->loan_payment_id . "'/>",
                $payment->loan_payment_id,
                ucwords($payment->customer_name),
                (trim($payment->loan_type) !== "" ? $payment->loan_type : "Flexible") . " (" . to_currency($payment->loan_amount) . ")",
                to_currency($payment->balance_amount),
                to_currency($payment->paid_amount),
                date($this->config->item('date_format'), $payment->date_paid),
                ucwords($payment->teller_name),
                $actions
            );
        }

        $data = array(
            "recordsTotal" => $this->Payment->count_all($sel_user),
            "recordsFiltered" => $this->Payment->count_all($sel_user),
            "data" => $format_result
        );

        echo json_encode($data);
        exit;
    }

    function get_loans($customer_id)
    {
        $loans = $this->Payment->get_loans($customer_id);

        foreach ($loans as $loan)
        {
            $loan->loan_amount = to_currency($loan->loan_amount);
            $loan->loan_balance = "bal: " . to_currency($loan->loan_balance);
            $loan->loan_type = (trim($loan->loan_type) !== "")?$loan->loan_type:"Flexible";
        }

        echo json_encode($loans);
        exit;
    }

    function get_customer($customer_id)
    {
        $customer = $this->Customer->get_info($customer_id);
        $suggestion['data'] = $customer->person_id;
        $suggestion['value'] = $customer->first_name . " " . $customer->last_name;

        echo json_encode($suggestion);
        exit;
    }
    
    private function _check_loan_penalties()
    {
        $due_date = $this->input->post("due_date");
        $amount_to_pay = $this->input->post("amount_to_pay");
        $penalty_value = $this->input->post("penalty_value");
        $penalty_type = $this->input->post("penalty_type");
        
        $penalty_amount = 0;
        if ( time() > strtotime($due_date) )
        {
            // penalize
            $penalty = $penalty_value;
            if ( $penalty_type == 'percentage' && $penalty_value > 0 )
            {
                $penalty = $amount_to_pay * ($penalty_value / 100);
            }
            
            if ( $penalty > 0 )
            {
                $penalty_amount = $amount_to_pay + $penalty;
            }
        }
        
        $return['status'] = "OK";
        $return['penalty_amount'] = $penalty_amount;
        $return['amount_to_pay'] = number_format($amount_to_pay, 2, '.', '');
        
        send($return);
    }
    
    private function _get_loan_schedules()
    {
        $loan_id = $this->input->post("loan_id");
        
        $this->db->where("loan_id", $loan_id);
        $query = $this->db->get("loans");
        
        $loan_balance = 0;
        $options = '<option value="">Choose</option>';
        if ( $query && $query->num_rows() > 0 )
        {
            $row = $query->row();
            $penalty_value = $row->penalty_value;
            $penalty_type = $row->penalty_type;
            $next_payment_date = $row->loan_payment_date;
            
            // Match the payment date where it belongs
            $scheds = json_decode($row->periodic_loan_table);
            if ( count($scheds) > 0 )
            {
                $i = 0;
                foreach ( $scheds as $sched )
                {
                    $payment_date = strtotime($this->config->item('date_format') == 'd/m/Y' ? uk_to_isodate($sched->payment_date) : $sched->payment_date);
                    
                    if ( $payment_date < $next_payment_date )
                    {
                        $options .= '<option value="'.$sched->payment_date.'" data-amount-to-pay="'. $sched->payment_amount .'" data-penalty-value="' . $penalty_value . '" data-penalty-type="' . $penalty_type . '" disabled="disabled">' . $sched->payment_date . ' (paid)</option>';
                    }
                    else
                    {
                        $selected = $i == 0 ? 'selected="selected"' : '';
                        $options .= '<option ' . $selected . ' value="'.$sched->payment_date.'" data-amount-to-pay="'. $sched->payment_amount .'" data-penalty-value="' . $penalty_value . '" data-penalty-type="' . $penalty_type . '">' . $sched->payment_date . '</option>';
                        $i++;
                    }
                }
            }
            
            $loan_balance = $row->loan_balance;
        }
        
        $return["status"] = "OK";
        $return["options"] = $options;
        $return["balance"] = $loan_balance;
        send($return);
    }

}

?>