<?php
 /* Define your APP`s key and secret*/
define('SHOPIFY_API_KEY','0e9003149bbbfa3a8f683732c89cc8ca');
define('SHOPIFY_SECRET','3e4a7cfe4dd7a71c188f75733e863f16');
define('SITE_URL', 'https://pink-flamingo.herokuapp.com');

/* Define requested scope (access rights) - checkout https://docs.shopify.com/api/authentication/oauth#scopes   */
define('SHOPIFY_SCOPE','read_content,write_content, read_themes, write_themes, read_products, write_products, read_customers, write_customers, read_orders, write_orders, read_script_tags, write_script_tags, read_fulfillments, write_fulfillments, read_shipping, write_shipping, read_checkouts, write_checkouts'); //eg: define('SHOPIFY_SCOPE','read_orders,write_orders');

//$servername = "localhost";
//$username = "cdemo_demo";
//$password = "x0f(XhXmsoGm";
//$dbname = "cdemo_shopify";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "flamingo_bids";

global $conn;   
$conn = new mysqli($servername, $username, $password, $dbname);