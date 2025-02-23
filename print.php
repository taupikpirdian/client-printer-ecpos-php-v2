<?php
/**
 * Created by PhpStorm.
 * User: yudadp
 * Date: 19/06/2023
 * Time: 08:47
 */

include_once 'config/app.php';
include_once 'config/api.php';
include_once 'config/func.php';

include_once 'lib/Request.php';
include_once 'lib/escpos/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

$response = METHOD == 'api' ? Request::get(PRINT_SALES) : file_get_contents('php://input');

$data = json_decode($response, true);

$status = $data['status'];
$errno  = $data['code'];

if (!$status && $errno != 404) {

  $printer_name = get_receipt_printer();
  $products     = $data['detail'];
  $payment_info = $data['detail_payment'];

  $trx_date  = $data['tgl_transaksi'];
  $inv_date  = $data['tgl_invoice'];
  $inv_no    = $data['no_transaksi'];
  $type      = $data['type'];
  $customer  = strlen($data['pelanggan']) <= 15 ? $data['pelanggan'] : substr($data['pelanggan'],0,15);
  $customer2 = strlen($data['pelanggan']) > 15 ? substr($data['pelanggan'], 15, 15) : "";
  $addrsChar = strlen($data['alamat']);
  $addrs     = $addrsChar <= 80 ? $data['alamat'] : substr($data['alamat'],0,80);
  $cif       = $data['no_cif'];
  $tlp       = $data['no_tlp'];
  $dealer_nm = $data['dealer_name'];
  $payment   = $data['payment'];
  $servedby  = $data['served_by'];
  $totalp    = $data['total'];

  $label_total = $type == 'INVOICE PEMBELIAN' ? 'Total Pembelian' : 'Total Penjualan';

  $lbl_servedBy = formatText('('.$servedby.')', 'center',15);

  $biayaKirim  = $data['biaya_pengiriman'];
  $notes       = $data['notes'];
  $logo        = $data['logo'];

  $line1 = '.................................';
  $line2 = '_________________________________';
  $line3 = '---------------------------------';

  $connector    = new WindowsPrintConnector($printer_name->title);

  $logo = EscposImage::load("media/images/logo50.png", false);
  $printer = new Printer($connector);
  $printer -> setJustification(Printer::JUSTIFY_CENTER);
  $printer -> bitImageForEpsonTMU220($logo, Printer::IMG_DOUBLE_WIDTH | Printer::IMG_DOUBLE_HEIGHT);
  $printer -> feed(2);
  $printer -> selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
  $printer->text(strtoupper("\n$type") . "\n");
  $printer -> selectPrintMode();
  $printer -> selectPrintMode(Printer::MODE_FONT_B);
  $printer->text("PT. Sari Arthagriha Valasindo Ekatama\n");
  $printer->text("Danau Sunter Utara F20 No. 24 Jakut\n");
  $printer->text("02129615678 - 087774568833 \n");
  $printer->text("www.savemoneychanger.com \n");
  $printer->text("$line1\n");
  $printer->feed();

  $printer->setJustification(Printer::JUSTIFY_LEFT);
  $printer -> selectPrintMode();
  $printer->text(sprintf('%-15.15s %1s %14s', "No. Transaksi", ":", formatText($inv_no,'left',14) ."\n"));
  $printer->text(sprintf('%-15.15s %1s %14s', "Tgl. Transaksi", ":", formatText($inv_date,'left',14) ."\n"));
  $printer->text(sprintf('%-15.15s %1s %14s', "Pelanggan", ":", formatText($customer,'left',15) ."\n"));
  if(!empty($customer2)) {
    $printer->text(sprintf('%-15.15s %1s %14s', "", " ", formatText($customer2,'left',15) ."\n"));
  }
  $printer -> setEmphasis(true);
  $printer->text(sprintf('%-15.15s %1s %14s', "No. Cif", ":", formatText($cif,'left',14) ."\n"));
  $printer -> setEmphasis(false);
  $printer->text(sprintf('%-15.15s %1s %14s', "No. Telepon", ":", formatText($tlp,'left',14) ."\n"));
  $printer->text(sprintf('%-15.15s %1s %14s', "Alamat", ":", formatText($addrs,'left', 50) ."\n"));
  
  $printer -> text("Detail Transaksi\n");
  $printer->text("$line3\n");

  $no = 1;
  foreach ($products as $item) {
    $amount   = is_int($item['amount']) ? number_format($item['amount']) : $item['amount'];
    $rate     = is_int($item['rate']) ? number_format($item['rate']) : $item['rate'];
    $subtotal = is_int($item['total']) ? number_format($item['total']) : $item['total'];

    $printer -> setEmphasis(true);
    $printer->text(sprintf('%-8.8s %1s %21s', "Currency", ":", formatText($item['currency'],'Right',21) ."\n"));
    $printer -> setEmphasis(false);
    $printer->text(sprintf('%-8.8s %1s %21s', "Amount", ":", $amount . "\n"));
    $printer->text(sprintf('%-8.8s %1s %21s', "Rate", ":", $rate . "\n"));
    $printer->text(sprintf('%-8.8s %1s %21s', "Total", ":", $subtotal . "\n"));

    $printer->text("$line3");
    $no++;
  }

  $printer->text("\n");
  $printer -> setEmphasis(true);
  $printer->text(sprintf('%-15.15s %2s %13s', formatText('Total Pembelian','center', 15), 'Rp', formatText(number_format($totalp),'center',13)));
  $printer -> setEmphasis(false);
  $printer->text("\n");
  $printer->text("$line3");
  $printer->text("\n\n");
  $printer->text("Dealer Name : $notes\n");

  foreach ($payment_info as $p) {
    $printer->text("Payment : $p[payment] ");
    $printer->text($p['bank'] ."\n");
    $printer->text(number_format($p['jumlah']) ."\n");
  }
  
  $printer->text("\n");
  $printer->setJustification(Printer::JUSTIFY_LEFT);
  $printer -> selectPrintMode(Printer::MODE_FONT_B);
  $printer->text("Saya menyatakan bahwa transaksi ini \n");
  $printer->text("belum melebihi threshold");
  $printer->text(" dalam bulan ini");
  $printer->text("dan akan menyertakan underlying jika \n");
  $printer->text("telah melebihi threshold");
  $printer->text("\n\n");
  $printer->setJustification(Printer::JUSTIFY_LEFT);
  $printer->text(sprintf('%-15.15s %15s', '    Served By:', 'Customer    '));
  $printer->text("\n\n\n\n\n");
  $printer->text(sprintf('%-15.15s %15s', $lbl_servedBy, formatText($data['pelanggan'],'center',15)));
  $printer->text("\n\n");
  $printer->text("Perhatian / Note \n");
  $printer -> selectPrintMode(Printer::MODE_FONT_B);
  $printer->text("Kekurangan penerimaan uang tidak ditanggung setelah meninggalkan loket/kasir.\n");
  $printer->text("Dokumen pendukung yang diberikan kepada Kami untuk kepentingan pembelian valas  adalah asli dan benar adanya, Serta tidak terkait dengan hasil dari tindak pidana\n");
  $printer -> setEmphasis(true);
  $printer->text("\nDeficiancies in cash receipts are not covered after leaving the counter/cashier.\n");
  $printer->text("Supporting documents provided to us for the purpose of purchasing foreign exchange are original and true.\n");
  $printer -> setEmphasis(false);
  $printer->feed();
  $printer->cut();
  $printer->pulse();
  $printer->close();

  if($printer) {
    echo json_encode(array('retval'=> '200', 'trxid' => $inv_no));
  }
} else {
  echo json_encode(array('retval'=> '400'));
}

if(METHOD == 'api') {
    echo '<script type="text/javascript">
    setTimeout(function(){
        location.reload();
    }, '. RELOAD_INTERVAL. ');
</script>';
}
?>

