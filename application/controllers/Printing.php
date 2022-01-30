<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once ("Secure_area.php");
require_once ("interfaces/idata_controller.php");

class Printing extends Secure_area implements iData_controller {

    function __construct()
    {
        parent::__construct('overdues');
    }

    public function print_list($filename = '')
    {
        ini_set('memory_limit', '-1');

        //$html = $this->load->view("loans/pdf/pdf_header", null, true);
        $html = $this->input->post("html");

        $pdfFilePath = FCPATH . "/downloads/reports/" . $filename;

        if (file_exists($pdfFilePath))
        {
            @unlink($pdfFilePath);
        }

        $this->load->library('pdf');

        $pdf = $this->pdf->load('"en-GB-x","A4-L","","",10,10,10,10,6,3');
        $pdf->SetFooter($_SERVER['HTTP_HOST'] . '|{PAGENO}|' . date(DATE_RFC822));
        $pdf->WriteHTML($html); // write the HTML into the PDF
        $pdf->Output($pdfFilePath, 'F'); // save to file because we can
        
        $return["status"] = "OK";
        $return["url"] = base_url("downloads/reports/" . $filename);

        send($return);
    }

    public function delete()
    {
        
    }

    public function get_form_width()
    {
        
    }

    public function get_row()
    {
        
    }

    public function index()
    {
        
    }

    public function save($data_item_id = -1)
    {
        
    }

    public function search()
    {
        
    }

    public function suggest()
    {
        
    }

    public function view($data_item_id = -1)
    {
        
    }

}
