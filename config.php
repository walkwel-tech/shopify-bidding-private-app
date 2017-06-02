<?php
 /* Define your APP`s key and secret*/
define('SHOPIFY_API_KEY','52f88dbc7e564e28cf96a30aa4917c90');
define('SHOPIFY_SECRET','5a591c165bf5ae7f02d671c33202aa7f');
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