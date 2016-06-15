<?php

require_once 'config.php';

require_once MAGE_PATH;
umask(0);
Mage::app('default');

if ($_GET['download-log'] == 'true') {
  $file = Mage::getBaseDir() . '/var/log/klarna-pushorder.log';
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename='.basename($file));
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  header('Content-Length: ' . filesize($file));
  ob_clean();
  flush();
  readfile($file);
  exit;
}

$methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

echo '<strong>Shipping methods:</strong> <br>';

foreach ($methods as $shippingCode => $shippingModel)
{
  $shippingTitle = Mage::getStoreConfig('carriers/'.$shippingCode.'/title');
  echo  'code: ' .$shippingCode . '<br>';
  echo 'title: ' . $shippingTitle . '<br><br>';
}

echo '<strong>Payment methods: </strong><br>';

$payments = Mage::getSingleton('payment/config')->getActiveMethods();

foreach ($payments as $paymentCode=>$paymentModel) {
  $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
  echo  'code: ' .$paymentCode . '<br>';
  echo 'title: ' . $paymentTitle . '<br><br>';
}

$sendConfirmationMail = Mage::getStoreConfig('sales_email')['order']['enabled'] == 1;
echo 'send confirmations: '. $sendConfirmationMail .'<br>';

echo '<hr>';

// get order info
$orderID = $_GET['klarna_order'];
if ($orderID) {
  echo '<strong>Klarna order:</strong><br>';
  require_once './Klarna/Checkout.php';

  $connector = Klarna_Checkout_Connector::create(
    KLARNA_SECRET,
    Klarna_Checkout_Connector::BASE_TEST_URL
  );

  $order = new Klarna_Checkout_Order($connector, $orderID);

  try {
    $order->fetch();
  } catch (Klarna_Checkout_ApiErrorException $e) {
    var_dump($e->getMessage());
    var_dump($e->getPayload());
    die;
  }

  echo "<pre>";
  echo htmlentities( print_r($order, true) );
  echo "</pre>";
}
