<?php

/**
 * @deprecated
 * Need a complete rewrite
 * https://dev.quiqqer.com/quiqqer/quiqqer/issues/754
 */

/**
 * @author PCSG - Moritz
 * @package com.pcsg.extern.omnigrid
 * @copyright  2011 PCSG
 * @version    $Revision: 3315 $
 */

define(
    'TCPDF_FILE',
    \dirname(\dirname(\dirname(\dirname(\dirname(\dirname(\dirname(__FILE__))))))).'/tecnickcom/tcpdf/tcpdf.php'
);

/**
 * Class OmnigridExport
 */
class OmnigridExport
{
    private $_data = [];
    private $_header = [];
    private $_type = '';

    /**
     * Konstruktor
     *
     * @param unknown_type $settings
     */
    public function __construct($type, $data)
    {
        $this->_data   = $data['data'];
        $this->_header = $data['header'];
        $this->_type   = $type;

        switch ($type) {
            case 'print':
                $this->_print();
                break;

            case 'csv':
                $this->_csv();
                break;

            case 'pdf':
                $this->_pdf();
                break;

            case 'json':
                $this->_json();
                break;

            default:
                $this->_json();
        }

    }

    private function _print()
    {
        $data = $this->_data;

    }

    private function _pdf()
    {
        if (!\file_exists(TCPDF_FILE)) {
            echo "Package \"tecnickcom/tcpdf\" not found. Please install to use PDF export.";
            exit;
        }

        $data   = $this->_data;
        $header = $this->_header;
        $html   = '<table cellpadding="2" style="margin-top: 5mm;"> <tr style="background-color: #F2F4F7;">';

        foreach ($header as $field) {
            $html .= '<th style="font-weight: bold;  font-size: 10;">'.\htmlspecialchars($field['header']).'</th>';
        }

        $html .= '</tr>';
        $bg   = '#FFFFFF';

        foreach ($data as $line) {
            $html .= '<tr style="background-color:'.$bg.';">';

            foreach ($header as $field => $val) {
                $html .= '<td  style="font-weight: normal; font-size: 9;" valign="top">'.$line[$field].'</td>';
            }

            $bg   = ($bg == '#FFFFFF') ? '#F2F4F7' : '#FFFFFF';
            $html .= "</tr>";
        }

        $html .= '</table>';


        $Pdf = new OmnigridPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true);

        $Pdf->SetCreator(PDF_CREATOR);
        $Pdf->SetAuthor("PCSG WWS");
        $Pdf->SetTitle("Grid Export");
        $Pdf->SetSubject(\date('dd.mm.yy'));
        $Pdf->SetKeywords('');

        //$Pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

        // set header and footer fonts
        //$Pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        //$Pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        //set margins
        $Pdf->SetMargins(10, PDF_MARGIN_TOP, 15);
        $Pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $Pdf->SetFooterMargin(0);

        //set auto page breaks
        $Pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $Pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        //set some language-dependent strings
        //$pdf->setLanguageArray($l);

        //initialize document
//		$Pdf->AliasNbPages();


        // add a page
        $Pdf->AddPage();

        $Pdf->writeHTML($html);

        $Pdf->lastPage();
        $Pdf->Output('GRID_Export_'.\date('d.m.Y').'.pdf', 'D');
    }

    private function _csv()
    {
        $data    = $this->_data;
        $header  = $this->_header;
        $content = '';

        foreach ($header as $field) {
            $content .= '"'.\nl2br(\preg_replace('/\r/', "\n",
                    \preg_replace('/[\t]/', '', \htmlspecialchars($field['header'])))).'"';
            $content .= ',';
        }

        $content .= "\n";

        foreach ($data as $line) {
            foreach ($header as $field => $val) {
                $content .= '"'.\nl2br(\preg_replace('/\r/', "\n",
                        \preg_replace('/[\t]/', '', \htmlspecialchars($line[$field])))).'"';
                $content .= ',';
            }

            $content .= "\n";
        }

        \header("Content-Type: application/csv");
        \header("Content-Disposition: attachment; filename=\"CSV Export ".\date("d.m.Y - H:i").".csv\"");
        \header("Content-Description: csv Export File");
        \header("Pragma: no-cache");
        \header("Expires: ".\gmdate("D, d M Y H:i:s")." GMT");

        echo $content;

    }

    private function _json()
    {
        $content = [
            'header' => $this->_header,
            'data'   => $this->_data
        ];

        \header("Content-Type: application/json");
        \header("Content-Disposition: attachment; filename=\"JSON Export ".\date("d.m.Y - H:i").".json\"");
        \header("Content-Description: Json Export File");
        \header("Pragma: no-cache");
        \header("Expires: ".\gmdate("D, d M Y H:i:s")." GMT");

        echo \json_encode($content);
    }

    static function genRandomString()
    {
        $length     = 20;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $string     = '';
        $strl       = \strlen($characters) - 1;

        for ($p = 0; $p < $length; $p++) {

            $string .= $characters[\mt_rand(0, $strl)];

        }

        return $string;
    }

}

if (\file_exists(TCPDF_FILE)) {
    require_once TCPDF_FILE;

    class OmnigridPDF extends TCPDF
    {

        var $_header = [];

        // Page header
        public function Header()
        {
            $this->SetFont('dejavusansbi', 'B', 7);
            $this->Ln();
            $this->Cell(145, 0, \date('d.m.Y'), 0, 0, 'L');
            $this->Ln(5);
        }

        // Page footer
        public function Footer()
        {
            //Position at 1.5 cm from bottom
            $this->SetY(-10);
            //Arial italic 8
            $this->SetFont('dejavusansbi', 'I', 8);
            //Page number
            $this->Cell(0, 10, 'Seite '.$this->PageNo(), 0, 0, 'C');
        }
    }
}


\session_start();
//formular erstellen
if (!isset($_REQUEST['DataField'])) {
    $doctype = DOMImplementation::createDocumentType(
        'html',
        '-//W3C//DTD XHTML 1.1//EN',
        'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'
    );

    $doc = DOMImplementation::createDocument(
        'http://www.w3.org/1999/xhtml',
        'html',
        $doctype
    );

    $head   = $doc->createElement('head');
    $script = $doc->createElement('script');
    $rand   = OmnigridExport::genRandomString();

    $js = "
    		function loadExportData()
    		{
    			var data =parent.document.getElementById('exportDataField').value;
    			var form = document.getElementById('DataFieldForm');
    			document.getElementById('DataField').value = data;

				form.submit();

    		}
    ";

    $js     = $doc->createTextNode($js);
    $js     = $script->appendChild($js);
    $script = $head->appendChild($script);
    $head   = $doc->appendChild($head);
    $body   = $doc->createElement('body');
    $form   = $doc->createElement('form');
    $input  = $doc->createElement('input');
    $input2 = $doc->createElement('input');

    $body->setAttribute('onload', 'loadExportData();');
    $input->setAttribute('id', 'DataField');
    $input->setAttribute('name', 'DataField');
    $input->setAttribute('value', '');
    $input->setAttribute('style', 'display:none;');
    $input2->setAttribute('id', 'RandomField');
    $input2->setAttribute('name', 'RandomField');
    $input2->setAttribute('value', $rand);
    $input2->setAttribute('type', 'hidden');
    $form->setAttribute('id', 'DataFieldForm');
    $form->setAttribute('method', 'POST');
    $form->setAttribute('action', '');
    $form->appendChild($input);
    $form->appendChild($input2);
    $body->appendChild($form);
    $html = $doc->getElementsByTagName('html')->item(0);
    $html->appendChild($body);

    $_SESSION['omnigrid_export'] = $rand;

    echo $doc->saveHTML();
    exit;
}

if (!isset($_REQUEST['RandomField'])
    || !isset($_SESSION['omnigrid_export'])
    || $_REQUEST['RandomField'] != $_SESSION['omnigrid_export']) {
    exit;
}

$dataField = $_REQUEST['DataField'];

if (\get_magic_quotes_gpc()) {
    $dataField = stripslashes($dataField);
}

$data = \json_decode($dataField, true);

if (!isset($data['type']) || !isset($data['data'])) {
    exit;
}

$Export = new OmnigridExport($data['type'], $data['data']);

//var_dump($data);
