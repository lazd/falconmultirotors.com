<?php
  $orderfound = false;
  $sendanalytics = false;
  $items = array();
  $tx_token = null;
  $amount_without_shipping = null;
  $amount = null;
  $shipping_amount = null;

  $pp_hostname = 'www.paypal.com'; // Change to www.sandbox.paypal.com to test against sandbox
  $auth_token = "wNHBPwbBihVpBZSokURLPzYv2Q4-ufVRw5Y7W_m9fkB0tTHM3QawT8WNuYW";

  // Check if a PayPal transaction occurred
  if (isset($_GET['tx'])) {
    $tx_token = $_GET['tx'];

    // read the post from PayPal system and add 'cmd'
    $req = "cmd=_notify-synch&tx=$tx_token&at=$auth_token";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://$pp_hostname/cgi-bin/webscr");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);

    //set cacert.pem verisign certificate path in curl using 'CURLOPT_CAINFO' field here,
    //if your server does not bundled with default verisign certificates.
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: $pp_hostname"));

    $res = curl_exec($ch);

    curl_close($ch);

    if (!$res){
      //HTTP ERROR
    }
    else {
      // parse the data
      $lines = explode("\n", $res);
      $keyarray = array();
      if (strcmp($lines[0], "SUCCESS") == 0) {
        $orderfound = true;

        for ($i = 1; $i < count($lines); $i++){
          list($key,$val) = explode("=", $lines[$i]);
          $keyarray[urldecode($key)] = urldecode($val);
        }

        // check the payment_status is Completed
        // check that txn_id has not been previously processed
        // check that receiver_email is your Primary PayPal email
        // check that payment_amount/payment_currency are correct
        // process payment
        $itemcount = $keyarray['num_cart_items'];
        $firstname = $keyarray['first_name'];
        $lastname = $keyarray['last_name'];
        $amount = $keyarray['payment_gross'];
        $shipping_amount = $keyarray['mc_shipping'];
        $amount_without_shipping = $amount - $shipping_amount;

        // Gather array of items
        for ($i = 1; $i <= $itemcount; $i++) {
          array_push($items, array(
            "name" => $keyarray["item_name".$i],
            "quantity" => $keyarray["quantity".$i],
            "price" => $keyarray["mc_gross_".$i]
          ));
        }

        if(!isset($_COOKIE['tx_'.$tx_token])) {
          setcookie('tx_'.$tx_token, 'true', time() + (10 * 365 * 24 * 60 * 60));
          $sendanalytics = true;
        }
      }
      else if (strcmp ($lines[0], "FAIL") == 0) {
        // log for manual investigation
      }
    }
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Order Success | Falcon Multirotors</title>
  <meta charset="utf-8">

  <!-- Ensure correct presentation on iOS -->
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">

  <!-- Core -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:300,400,700|Quicksand:300|family=Play:400,700">
  <link rel="stylesheet" href="external/photoswipe/photoswipe.css">
  <link rel="stylesheet" href="external/photoswipe/default-skin/default-skin.css">
  <link rel="stylesheet" href="external/slick/slick.css">
  <link rel="stylesheet" href="external/slick/slick-theme.css">
  <link rel="stylesheet" href="index.css">

  <!-- Analytics -->
  <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-74430530-1', 'auto');
    ga('send', 'pageview');
    ga('require', 'ecommerce');
  </script>

  <!-- jQuery -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
  <?php if ($sendanalytics) { ?>
  <script>
    // Track as transaction for ecommerce
    ga('ecommerce:addTransaction', {
      id: "<?= $tx_token ?>", // Transaction ID. Required.
      revenue: <?= $amount_without_shipping ?> // Grand Total.
    });

    <?php foreach ($items as $item) { ?>
    ga('ecommerce:addItem', {
      id: "<?= $tx_token ?>", // Transaction ID. Required.
      name: "<?= $item['name'] ?>", // Product name. Required.
      price: <?= $item['price'] ?>, // Unit price.
      quantity: <?= $item['quantity'] ?> // Quantity.
    });
    <?php } ?>

    ga('ecommerce:send');
  </script>
  <?php } ?>

  <!-- App -->
  <script src="index.js"></script>
</head>
<body>
  <div class="header">
    <section class="header-section header-section--left">
      <nav class="tablist frametabs">
        <a class="tablist-tab" href="rasvelg.html">Räsvelg</a>
        <a class="tablist-tab" href="garudastretch.html">Garuda Stretch</a>
        <a class="tablist-tab" href="garuda.html">Garuda 200</a>
        <a class="tablist-tab" href="falcon.html">Falcon X</a>
      </nav>
    </section>
    <img class="header-logo" src="images/logo-trace-small.png" alt="Falcon Multirotors">
  </div>

  <div class="hero">
    <?php if ($orderfound) { ?>
    <h1 class="hero-text">Thank you.</h1>
    <h2 class="hero-subtext">Your order has been placed.</h2>
    <p>Your transaction is complete, and a receipt for your purchase has been e-mailed to you.</p>
    <p>You'll recieve an e-mail with tracking information once your order has been shipped.</p>

    <div class="table-container">
      <table class="table table--alernate">
        <thead>
          <th>Product</th>
          <th>Quantity</th>
          <th>Price</th>
        </thead>
        <tbody>
          <?php foreach ($items as $item) { ?>
            <tr>
              <td><?= $item['name'] ?></td>
              <td><?= $item['quantity'] ?></td>
              <td class="table-price">$<?= $item['price'] ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>

      <table class="table table--right">
        <tbody>
          <tr>
            <td>Shipping</td>
            <td class="table-price">$<?= $shipping_amount ?></td>
          </tr>
          <tr>
            <td>Total</td>
            <td class="table-price">$<?= $amount ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <?php } else { ?>
    <h1 class="hero-text">Error.</h1>
    <h2 class="hero-subtext">No order details were found.</h2>
    <p>&nbsp;</p>
    <?php } ?>

    <h2 class="hero-subtext">What to do next?</h2>
    <p>Start planning your build on <a trackclick="RotorBuilds" href="http://rotorbuilds.com/post">RotorBuilds</a> and get the rest of the parts you need coming from fine retailers such as <a href="http://www.fpvheadquarters.com">FPVHQ</a>, <a href="http://www.droneeclipse.com">Drone Eclipse</a>, and <a href="http://www.multirotormania.com">MRM</a>.</p>
  </div>

  <footer class="footer">
    &copy; <script>document.write((new Date()).getFullYear())</script> Falcon Multirotors | <a href="mailto:lazdnet@gmail.com?subject=Falcon">Contact us</a>
  </footer>
</body>
</html>
