<?php
include(_PS_ADMIN_DIR_.'/../config/config.inc.php');

if (isset($_GET['secure_key'])) {
    $secureKey = md5(_COOKIE_KEY_.Configuration::get('PS_SHOP_NAME'));
    if (!empty($secureKey) && $secureKey === $_GET['secure_key']) {
      $conn = mysqli_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_, _DB_NAME_);
      mysqli_set_charset($conn, "utf8");
      getIdPack($conn);
      showlog();   
    }
}


function getIdPack($conn)
{

  $zapytanie1 = 'SELECT p.id_product,
    p.reference,
    p.cache_is_pack,
    pac.id_product_item,
    pac.id_product_attribute_item
    FROM ' ._DB_PREFIX_ . 'product p 
    LEFT JOIN ' . _DB_PREFIX_ . 'pack pac ON (p.id_product = pac.id_product_pack)
    LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product and pl.id_lang=3)
    WHERE p.cache_is_pack =1 AND  pac.id_product_attribute_item > 0';

  $sqlProductId = mysqli_query($conn, $zapytanie1) or die("Problemy z odczytem danych!" . logs('<p class="err">ERROR Zapytanie 1!</p><br>', null));

  if (mysqli_num_rows($sqlProductId) > 0) {
    logs("<p class='red'>Uruchomienie</p><br>", null);
    displayTable("<table><tr><td>ID-Zestaw</td><td>SKU</td><td>Paczka-produktu</td><td>ID-Produktu w paczce</td><td>ID-Kombinacji</td><td>MIN Prop</td><td>Ilość Kompletow</td></tr><br>");
    while ($row = mysqli_fetch_array($sqlProductId)) {
      displayTable("<tr>" . "<td>" . $row[0] . "</td>" . "<td>" . $row[1] . "</td>" . "<td>" . $row[2] . "</td>" . "<td>" . $row[3] . " </td>" . "<td>" . $row[4] . "</td>");
      getMinPackValue($conn, $row[0]);
    }
  } else {
    logs("<p class='red'>0 Wyników Zapytanie 1</p><br>", null);
  }
}


function getMinPackValue($conn, $id)
{

  $quantityPack = 'SELECT sav.quantity
    FROM ' . _DB_PREFIX_ . 'product a  
    LEFT JOIN ' . _DB_PREFIX_ . 'stock_available sav ON (sav.id_product = a.id_product AND sav.id_product_attribute = 0 AND sav.id_shop = 1 AND sav.id_shop_group = 0 ) 
    WHERE a.cache_is_pack = 1 AND a.id_product =' . $id;

  $sqlMin = 'SELECT MIN(sn.quantity)
    FROM  ' . _DB_PREFIX_ . 'product p 
    LEFT JOIN ' . _DB_PREFIX_ . 'pack pac ON (p.id_product = pac.id_product_pack)
    LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON (pac.id_product_attribute_item = pa.id_product_attribute)
    LEFT JOIN ' . _DB_PREFIX_ . 'stock_available sn ON ( pa.id_product_attribute = sn.id_product_attribute)
    WHERE p.cache_is_pack = 1 AND pac.id_product_attribute_item > 0 AND p.id_product=' . $id;

  $getMin = mysqli_query($conn, $sqlMin) or die("Problemy z odczytem danych!");
  $getquantityPack = mysqli_query($conn, $quantityPack) or die("Problemy z odczytem danych!");

  if (mysqli_num_rows($getMin) > 0) {
    while ($rowMin = mysqli_fetch_array($getMin)) {
      while ($qrow = mysqli_fetch_row($getquantityPack)) {
        if ($rowMin[0] != $qrow[0]) {
          update($conn, $id, $rowMin[0], $qrow[0]);
        }
        displayTable("<td>" . $rowMin[0] . "</td>" . "<td>" . $qrow[0] . "</td>");
     
      }
    }
  } else {
    logs("0 Wynikow :(", null);
    mysqli_close($conn);
  }
}


function update($conn, $id, $min, $qpack)
{
  // $update = 'UPDATE ' . _DB_PREFIX_ . 'stock_available SET quantity = '. $min .' WHERE id_product = ' .$id;
  // $result = mysqli_query($conn, $update) or die("Problem z Update" . mysqli_error($conn));
  // logs("<p style='color:green; display:inline;'>ZMIANA</p>", $id . " " . $qpack . " -> " . $min . "</br>");

  $update = 'UPDATE ' . _DB_PREFIX_ . 'stock_available SET quantity = '. $min .' WHERE id_product = ' .$id;
    // $result = mysqli_query($conn, $update) or die("Problem z Update" . mysqli_error($conn));

   if (mysqli_query($conn, $update)) {
    logs("<p style='color:green; display:inline;'>ZMIANA</p>", $id . " " . $qpack . " -> " . $min . "</br>");
  } else {
    // echo "Error updating record: " . mysqli_error($conn);
    logs("<p style='color:green; display:inline;'>ERROR</p>", $id . " ". mysqli_error($conn) . "</br>");
  }

}


function dateTime()
{
  $date = new DateTime();
  $date = $date->format("y:m:d h:i:s");
  return $date;
}

function logs($type, $dane)
{
  $dane =   datetime() . " " . $type . " " . $dane ;
  savelog($dane);
}


function savelog($dane)
{
  $logfile = fopen("updateLog.txt", "a") or die("Unable to open file!");
  $txt = $dane;
  $dane += "<br>";
  fwrite($logfile, $txt);
  fclose($logfile);
}


function displayTable($dane)
{
  $debugMode = true;
  if ($debugMode === true) {
    echo $dane;
  }
}

function showlog()
{
  $debugMode = true;
  if ($debugMode === true) {
    $logfile = fopen("updateLog.txt", "r") or die("Unable to open file!");
    $stareDane = fread($logfile, filesize("updateLog.txt"));
    echo "History! <br>" . $stareDane;
    fclose($logfile);
  }
}

?>

<style>
  table tr td {
    border: 2px solid black;
    text-align: center;
  }

  .err {
    color: red;
    display: inline-block;
  }

  .red {
    color: red;
    display: inline;
  }

  .green {
    color: green;
    display: inline-block;
  }
</style>