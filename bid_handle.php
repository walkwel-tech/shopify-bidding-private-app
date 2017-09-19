<?php
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");
error_reporting(E_ALL);
require_once("config.php");
require_once('shopify.php');
//print_r($_POST);
//die;

function getToken($shop, $conn) {

	$query = mysqli_query($conn, "SELECT access_token FROM tbl_usersettings WHERE store_name = '".$shop."'") or die(mysqli_error($query));
    $row  = mysqli_fetch_array($query);

    $token = $row['access_token'];
    return $token;
}

// place bid by customer
if($_GET['mode'] == 1){
	if(isset($_POST['product_id']) && isset($_POST['customer_id']) && isset($_POST['bid_price'])) {

	$query = mysqli_query($conn, "SELECT * FROM auctions where product_id = '".$_POST['product_id']."' AND auc_exp_date = '".$_POST['ending_date']."'"); 
	$row  = mysqli_fetch_array($query);
	$auc_id = $row['id'];
	$query = mysqli_query($conn, "INSERT INTO customer_bids(id, auc_id, product_id, product_name, user_id, user_name, bid_price ) VALUES('', '".$auc_id."', '".$_POST['product_id']."', '".$_POST['product_name']."', '".$_POST['customer_id']."', '".$_POST['customer_name']."', '".$_POST['bid_price']."')") or die(mysqli_error($query));

	if($query) {
		$customer_bid_id = mysqli_insert_id($conn);
		$option = md5(uniqid($_POST['product_id'], true));
		$option .= $_POST['product_name'];
		$shop = $_POST['shop'];
		$shop = preg_replace('#^https?://#', '', $shop);
		$prod_id = $_POST['product_id']; 

		$token = getToken($shop, $conn);
		$sc = new ShopifyClient($shop, $token, SHOPIFY_API_KEY, SHOPIFY_SECRET);
		$variant_data = array("variant" => array("option1" => $option, "price" => $_POST['bid_price'], "inventory_policy" => "continue") );
    	$variant = $sc->call('POST', '/admin/products/'.$prod_id.'/variants.json', $variant_data);
    	
    	if($variant['id']) {
    		$query_ad = mysqli_query($conn, "INSERT INTO bid_variants(id, customer_bid_id, variant_id ) VALUES('', '".$customer_bid_id."', '".$variant['id']."')") or die(mysqli_error($query_ad));
    		echo 1;
    	}
		
	}
	else {
		echo 0;
	}

}

}

// check if customer have already bid for product(not usable)
elseif($_GET['mode'] == 2) {

	$query = mysqli_query($conn, "SELECT * FROM customer_bids WHERE user_id = '".$_POST['cust_id']."' AND product_id = '".$_POST['prod_id']."' AND delete_status = 0") or die(mysqli_error($query));

	if(mysqli_num_rows($query) > 0) {
		echo 1;
	}
	else {
		echo 0;
	}
}

// get bids by customer for admin panel
elseif($_GET['mode'] == 3) {
	if($_POST['expired'] == 1) {
		$query = mysqli_query($conn, "SELECT cb.id as cbid, cb.*, ea.* FROM customer_bids cb INNER JOIN auctions ea ON cb.auc_id = ea.id WHERE cb.auc_id = '".$_POST['auc_id']."' AND cb.delete_status = 0 AND cb.expired = '".$_POST['expired']."'") or die(mysqli_error($query));
	}
	else {
		$query = mysqli_query($conn, "SELECT cb.id as cbid, cb.*, ea.* FROM customer_bids cb INNER JOIN auctions ea ON cb.auc_id = ea.id WHERE cb.auc_id = '".$_POST['auc_id']."' AND cb.delete_status = 0 AND cb.expired = '".$_POST['expired']."' AND ea.winner_bid_id = ''") or die(mysqli_error($query));
	}

	$count = 1;
	$html = "";
	while($row = mysqli_fetch_array($query)) {
		if($row['cbid'] == $row['winner_bid_id']) {
			$winner = "<span class='text-success'>(winner!!)</span>";
		}
		else {
			$winner = "";
		}
		$html .= "<tr><td>".$count."</td><td><a target='_blank' href='https://test-storewalkwel.myshopify.com/admin/customers/".$row['user_id']."'>".$row['user_name']."</a> $winner</td><td>$ ".$row['bid_price'].".00</td></tr>";
		$count++;
	}
	echo $html;
}

// get bids by customer for frontend
elseif($_GET['mode'] == 4) {

	$query = mysqli_query($conn, "SELECT * FROM customer_bids WHERE product_id = '".$_POST['product_id']."' AND delete_status = 0 AND expired = 0") or die(mysqli_error($query));

	$count = 1;
	$html = "";
	while($row = mysqli_fetch_array($query)) {
		$html .= "<tr><td>".$count."</td><td>".$row['user_name']."</td><td>$ ".$row['bid_price'].".00</td></tr>";
		$count++;
	}
	echo $html;
}

// delete customer bid(not usable)
elseif($_GET['mode'] == 5) {

	$prod_id = $_POST['prod_id'];
    $query = mysqli_query($conn, "UPDATE customer_bids SET delete_status = 1 WHERE product_id = '".$prod_id."'");
    if($query) {
    	echo 1;
    }
    else {
    	echo 0;
    }
}

// send winner customer email notification from frontend
elseif($_GET['mode'] == 6) {
	$shop = $_POST['shop'];
	$shop = preg_replace('#^https?://#', '', $shop);
	$prod_id = $_POST['prod_id'];
	sentwinner($shop, $prod_id, $conn);
}

// get last bid on product
elseif($_GET['mode'] == 7){
	$prod_id = $_POST['prod_id'];

	$query = mysqli_query($conn, "SELECT * FROM customer_bids WHERE product_id = '".$prod_id."' AND delete_status = 0 AND expired = 0 ORDER BY added_at DESC LIMIT 1") or die(mysqli_error($query));
	$bid_price = "";
	while($row = mysqli_fetch_array($query)) {
		$bid_price = $row['bid_price'];
	}
	if($query){
		echo $bid_price;
	}
	else{
		echo 0;
	}
}

// add product auction
elseif($_GET['mode'] == 8){
	$prod_id = $_POST['product_id'];
	$end_date = $_POST['end_date'];
	$start_price = $_POST['start_price'];
	$res_price = $_POST['res_price'];
	$bid_increement = $_POST['bid_increement'];
	$shop = $_POST['shop'];
	$check_auc_date = $_POST['check_auc_date'];
	

    $endate = date('d M, Y', strtotime($end_date));
    //echo $endate;
    //die;
    $token = getToken($shop, $conn);

    $data = array("token" => $token, "shop" => $shop, "prod_id" => $prod_id);

    $metafield1 = array("metafield" => array("namespace" => "auction", "key" => "end", "value" => $endate, "value_type" => "string") );
    $metafield2 = array("metafield" => array("namespace" => "auction", "key" => "minprice", "value" => $start_price, "value_type" => "string") );
    $metafield3 = array("metafield" => array("namespace" => "auction", "key" => "bid_increement", "value" => $bid_increement, "value_type" => "string") );
    $metafield4 = array("metafield" => array("namespace" => "auction", "key" => "resprice", "value" => $res_price, "value_type" => "string") );

    
    
    $data['minprice'] = $start_price;
    $data['resprice'] = $res_price;
    $data['bid_increement'] = $bid_increement;
    $data['enddate'] = $endate;
    $query = mysqli_query($conn, "SELECT * FROM auctions WHERE product_id = '".$data['prod_id']."' AND status = 1");
    $cou = mysqli_num_rows($query);

    if(empty($check_auc_date) || $cou == 0) {
    	$query2 = mysqli_query($conn, "INSERT INTO auctions(id, product_id, winner_bid_id, auc_start_price, auc_res_price, auc_exp_date, auc_bid_increement, winner_claimed_prod, status) VALUES('', '".$data['prod_id']."', '', '".$data['minprice']."', '".$data['resprice']."', '".$data['enddate']."', '".$data['bid_increement']."', 0, 1)") or die(mysqli_error($query2));
    	echo "New bid added success!!";
    }
    else {
    	$query2 = mysqli_query($conn, "UPDATE auctions SET auc_start_price = '".$data['minprice']."', auc_res_price = '".$data['resprice']."', auc_exp_date = '".$data['enddate']."', auc_bid_increement = '".$data['bid_increement']."' WHERE product_id = '".$data['prod_id']."' AND status = 1") or die(mysqli_error($query2));
    	echo "bid updated success!!";
    }

    if(date('Y-m-d', strtotime($check_auc_date)) != date('Y-m-d', strtotime($end_date))) {
    	$metafield5 = array("metafield" => array("namespace" => "auction", "key" => "expiredflag", "value" => false, "value_type" => "string") );
    	addmeta($metafield5, $data, $conn);
    }	

    addmeta($metafield1, $data, $conn);
    addmeta($metafield2, $data, $conn);
    addmeta($metafield3, $data, $conn);
    addmeta($metafield4, $data, $conn);

    
    
}
// get metafields according live auction shopify products
elseif($_GET['mode'] == 9) {
	$prod_id = $_POST['product_id'];
	$shop = $_POST['shop'];
	$token = getToken($shop, $conn);

	$sc = new ShopifyClient($shop, $token, SHOPIFY_API_KEY, SHOPIFY_SECRET);
    $getMeta = $sc->call('GET', '/admin/products/'.$prod_id.'/metafields.json', array());
    echo json_encode($getMeta);
}

// delete product bid metafields
elseif($_GET['mode'] == 10) {

	$prod_id = $_POST['product_id'];
	$shop = $_POST['shop'];
	$token = getToken($shop, $conn);
	$sc = new ShopifyClient($shop, $token, SHOPIFY_API_KEY, SHOPIFY_SECRET);
	$check_q = mysqli_query($conn, "SELECT * FROM customer_bids WHERE product_id = '".$prod_id."'");
	$count_bids = mysqli_num_rows($check_q);
	if($count_bids >= 1) {
		echo "You can't delete as bids already placed on this product. ";
	}
	else {
		$query = mysqli_query($conn, "SELECT * FROM product_meta WHERE product_id = '".$prod_id."'");
		while($row = mysqli_fetch_array($query)) {

			$deleteMeta = $sc->call("DELETE", '/admin/products/'.$prod_id.'/metafields/'.$row['meta_id'].'.json', array());

			$query_d = mysqli_query($conn, "DELETE FROM product_meta WHERE meta_id = '".$row['meta_id']."'");
		}

		$query_d2 = mysqli_query($conn, "DELETE FROM auctions WHERE product_id = '".$prod_id."' AND status = 1");

		echo "Delete Success!!";
	}
	
    
    
}

//
elseif($_GET['mode'] == 11) {
	$today = strtotime(date('M d,Y'));
	$shop = $_POST['shop'];
	$token = getToken($shop, $conn);
	$sc = new ShopifyClient($shop, $token, SHOPIFY_API_KEY, SHOPIFY_SECRET);
    $data = array("token" => $token, "shop" => $shop);
	$query = mysqli_query($conn, "SELECT * FROM auctions WHERE status = 1");
	
	$count_update = 0;
	while($row = mysqli_fetch_array($query)) {
		$exp_date = strtotime($row['auc_exp_date']);
		
		if($exp_date < $today) {

			$data['prod_id'] = $row['product_id'];
			$prod_id = $row['product_id'];
			sentwinner($shop, $prod_id, $conn);
			$count_update++;
		}
	}
	echo "Total records expired are: " . $count_update;
}

//customer bids
elseif($_GET['mode'] == 12) {
	$cust_id = $_POST['cust_id'];
	$query = mysqli_query($conn, "SELECT cb.id as cbid, cb.*, ea.* FROM customer_bids cb INNER JOIN auctions ea ON cb.auc_id = ea.id WHERE cb.user_id = '".$cust_id."' AND cb.delete_status = 0 AND cb.expired = 1 ") or die(mysqli_error($query));
	
	$count = 1;
	$html = "";
	$shop = $_POST['shop'];
	$shop = preg_replace('#^https?://#', '', $shop);
	$token = getToken($shop, $conn);
	$sc = new ShopifyClient($shop, $token, SHOPIFY_API_KEY, SHOPIFY_SECRET);
	

	while($row = mysqli_fetch_array($query)) {
		if($row['cbid'] == $row['winner_bid_id']) {
			$product = $sc->call("GET", '/admin/products/'.$row['product_id'].'.json', array());
			$winner = "<span class='text-success'>(winner!!)</span>";
			$html .= "<tr><td>".$count."</td><td><a target='_blank' href='https://test-storewalkwel.myshopify.com/products/live-auctions/products/".$product['handle']."'>".$row['product_id']."</a> </td><td><a target='_blank' href='https://test-storewalkwel.myshopify.com/collections/live-auctions/products/".$product['handle']."'>".$row['product_name']."</a> </td><td>$ ".$row['bid_price'].".00</td><td> ".$winner."</td></tr>";
			$count++;
		}
		else {
			$winner = "<span class='text-danger'>No Luck!!</span>";
		}
		// $prod_handle = ucwords(str_replace(" ","-", $row['product_name']));
		// $prod_handle = ucwords(str_replace("/","-", $prod_handle));
		// $html .= "<tr><td>".$count."</td><td><a target='_blank' href='https://test-storewalkwel.myshopify.com/products/".$prod_handle."'>".$row['product_id']."</a> </td><td><a target='_blank' href='https://test-storewalkwel.myshopify.com/products/".$prod_handle."'>".$row['product_name']."</a> </td><td>$ ".$row['bid_price'].".00</td><td> ".$winner."</td></tr>";
		// $count++;
	}
	echo $html;
}

//current auction bids of user in order page
elseif($_GET['mode'] == 13) {
	$cust_id = $_POST['cust_id'];
	$query = mysqli_query($conn, "SELECT cb.id as cbid, cb.*, ea.* FROM customer_bids cb INNER JOIN auctions ea ON cb.auc_id = ea.id WHERE cb.user_id = '".$cust_id."' AND cb.delete_status = 0 AND cb.expired = 0 ") or die(mysqli_error($query));
	
	$count = 1;
	$html = "";
	$shop = $_POST['shop'];
	$shop = preg_replace('#^https?://#', '', $shop);
	$token = getToken($shop, $conn);
	$sc = new ShopifyClient($shop, $token, SHOPIFY_API_KEY, SHOPIFY_SECRET);

	while($row = mysqli_fetch_array($query)) {
		$product = $sc->call("GET", '/admin/products/'.$row['product_id'].'.json', array());
		
		$html .= "<tr><td>".$count."</td><td><a target='_blank' href='https://test-storewalkwel.myshopify.com/collections/live-auctions/products/".$product['handle']."'>".$row['product_id']."</a> </td><td><a target='_blank' href='https://test-storewalkwel.myshopify.com/collections/live-auctions/products/".$product['handle']."'>".$row['product_name']."</a> </td><td>$ ".$row['bid_price'].".00</td></tr>";
		$count++;
	}
	echo $html;
}

function addmeta($metafields, $data, $conn) {

	$sc = new ShopifyClient($data['shop'], $data['token'], SHOPIFY_API_KEY, SHOPIFY_SECRET);
    $addmeta = $sc->call('POST', '/admin/products/'.$data['prod_id'].'/metafields.json', $metafields);
    if($addmeta) {
    	$query = mysqli_query($conn, "INSERT INTO product_meta(id, product_id, meta_id ) VALUES('', '".$addmeta['owner_id']."', '".$addmeta['id']."')") or die(mysqli_error($query));
    	return true;
    }

}

function setwinner($conn, $data) {
	//$query = mysqli_query($conn, "INSERT INTO auctions(id, product_id, winner_user_id, auc_start_price, auc_res_price, auc_exp_date, auc_bid_increement, winner_claimed_prod) VALUES('', '".$data['prod_id']."', '".$data['user_id']."', '".$data['minprice']."', '".$data['resprice']."', '".$data['enddate']."', '".$data['bid_increement']."', 0)") or die(mysqli_error($query));

	$query = mysqli_query($conn, "UPDATE auctions SET winner_bid_id = '".$data['bid_id']."', status = 0 WHERE product_id = '".$data['prod_id']."' AND winner_bid_id IS NULl OR winner_bid_id = '' ");
	if($query){
		return true;
	}
    else {
    	return false;
    }
}

function sentwinner($shop, $prod_id, $conn) {
	
	//$cust_email = $_POST['email_id'];
	$token = getToken($shop, $conn);
	
	$data = array("token" => $token, "shop" => $shop, "prod_id" => $prod_id);

	$sc = new ShopifyClient($shop, $token, SHOPIFY_API_KEY, SHOPIFY_SECRET);
	$prodMetas = $sc->call('GET', '/admin/products/'.$prod_id.'/metafields.json', array());
	//print_r($prodMetas);
	//die;
	$resprice = 0;
	$sentemail = false;
	foreach($prodMetas as $prometa) {
		if($prometa['key'] == 'expiredflag') {
			if($prometa['value'] == "true") {
				$sentemail = true;
			}
		}
		if($prometa['key'] == 'resprice') {
			$resprice = $prometa['value'];
			$data['resprice'] = $resprice; 
		}
		if($prometa['key'] == 'minprice') {
			$minprice = $prometa['value'];
			$data['minprice'] = $minprice; 
		}
		if($prometa['key'] == 'end') {
			$endDate = $prometa['value'];
			$data['enddate'] = $endDate; 
		}
		if($prometa['key'] == 'bid_increement') {
			$increement = $prometa['value'];
			$data['bid_increement'] = $increement; 
		}
	}
	
	if(empty($sentemail) || $sentmail == false) {

		$query = mysqli_query($conn, "SELECT cb.*, cv.* FROM customer_bids cb INNER JOIN bid_variants cv ON cb.id = cv.customer_bid_id WHERE cb.product_id = '".$prod_id."' AND cb.bid_price >= '".$resprice."' AND cb.delete_status = 0 AND cb.expired = 0 ORDER BY cb.added_at ASC") or die(mysqli_error($query));
		$setFlag = 0;
		$cou = mysqli_num_rows($query);
		echo $cou;
		while($row = mysqli_fetch_array($query)) { 
			if($setFlag == 0) {
				echo "in here";
				//echo json_encode($checkout_data);
				//die;
				
				$userdata = $sc->call('GET', '/admin/customers/'.$row['user_id'].'.json', array());
				
				$to = $userdata['email'];

				$checkout_data = array("checkout" => array("email" => $to, "line_items" => array(array("variant_id" => $row['variant_id'], "quantity" => 1))));

				$checkout = $sc->call('POST', '/admin/checkouts.json', $checkout_data);
				// echo "<pre>";
				// echo $to. "<br>";
				// print_r($checkout);
				// echo $token ."<br>". $resprice;
				// print_r($row);

				if(isset($checkout['message'])) {
					die('Error of capabilities!!');
				}
				elseif(!isset($checkout['web_url'])) {
					die('Error in checkout');
				}

				$checkout_url = $checkout['web_url'];
				$subject = "Congratulations!! You have won the bid!!";
				$prod_handle = str_replace(' ', '-', strtolower($row['product_name']));
				

				$message = '<table cellpadding="0" width="100%" cellspacing="0" border="0" id="backgroundTable" class="bgBody">
	<tr>
		<td>
	<table cellpadding="0" width="620" class="container" align="center" cellspacing="0" border="0">
	<tr>
		<td>
		

		<table cellpadding="0" cellspacing="0" border="0" align="center" width="600" class="container">
			<tr>
				<td class="movableContentContainer bgItem">

					<div class="movableContent">
						<table cellpadding="0" cellspacing="0" border="0" align="center" width="600" class="container">
							<tr height="40">
								<td width="200">&nbsp;</td>
								<td width="200">&nbsp;</td>
								<td width="200">&nbsp;</td>
							</tr>
							<tr>
								<td width="200" valign="top">&nbsp;</td>
								<td width="200" valign="top" align="center">
									<div class="contentEditableContainer contentImageEditable">
					                	<div class="contentEditable" align="center" >
					                  		<img src="images/logo.png" width="155" height="155"  alt="Logo"  data-default="placeholder" />
					                	</div>
					              	</div>
								</td>
								<td width="200" valign="top">&nbsp;</td>
							</tr>
							<tr height="25">
								<td width="200">&nbsp;</td>
								<td width="200">&nbsp;</td>
								<td width="200">&nbsp;</td>
							</tr>
						</table>
					</div>

					<div class="movableContent">
						<table cellpadding="0" cellspacing="0" border="0" align="center" width="600" class="container">
							<tr>
								<td width="100%" colspan="3" align="center" style="padding-bottom:10px;padding-top:0;">
									<div class="contentEditableContainer contentTextEditable">
					                	<div class="contentEditable" align="center" >
					                  		<h2 style="font-family: Helvetica, Arial, sans-serif;font-size: 22px;line-height: 22px;color: black;"><strong>Auction Winner User</strong></h2>
					                	</div>
					              	</div>
								</td>
							</tr>
							<tr>
							
								<td width="400" align="center">
									<div class="contentEditableContainer contentTextEditable">
					                	<div class="contentEditable" align="left" >
					                  		<p style="color: #555;font-family: Helvetica, Arial, sans-serif;font-size: 16px;line-height: 160%;">Dear,
					                  			
					                  			<br/>'.$row["user_name"].' You have won the bid on product <a target="_blank" href="https://test-storewalkwel.myshopify.com/products/'.$prod_handle.'">'.$row["product_name"].'</a>. Please Checkout using below url. Thankyou!!<br><br><br></p>
					                	</div>
					              	</div>
								</td>
							
							</tr>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" align="center" width="600" class="container">
							<tr>
							
								<td width="400" align="center" style="padding-top:25px;">
									<table cellpadding="0" cellspacing="0" border="0" align="center" width="200" height="50">
										<tr>
											<td bgcolor="#2496DC" align="center" style="border-radius:4px;" width="200" height="50">
												<div class="contentEditableContainer contentTextEditable">
								                	<div class="contentEditable" align="center" >
								                  		<a style="color: #fff;text-decoration: none;font-family: Helvetica, Arial, sans-serif;font-size: 16px;border-radius: 4px;" target="_blank" href="'.$checkout_url.'" class="link2">Click here to Checkout</a> 
								                  		
								                	</div>

								              	</div>

											</td>
																			                  		
										</tr>
										<tr align="center"><td><h3 style="font-family: sans-serif;">or</h3></td></tr>
										<tr style="margin-top:0px; display: inline-block;"><td width="400" align="center"><a style="font-size: 14px;color: #3691D7;text-transform: uppercase;text-decoration: none;font-family: sans-serif;" href="#" class="visit_store"><strong>visit your store</strong></a></td></tr>
									</table>
								</td>
							
							</tr>
						</table>
					</div>


					<div class="movableContent">
						<table cellpadding="0" cellspacing="0" border="0" align="center" width="600" class="container">
							<tr>
								<td width="100%" colspan="2" style="padding-top:65px;">
									<hr style="height:1px;border:none;color:#333;background-color:#ddd;" />
								</td>
							</tr>
							<tr>
								<td width="60%" height="70" valign="middle" style="padding-bottom:20px;">
									<div class="contentEditableContainer contentTextEditable">
					                	<div class="contentEditable" align="left" >
					                  		<span style="font-size:13px;color:#181818;font-family:Helvetica, Arial, sans-serif;line-height:200%;">If you have any questions, reply to this email or contact us at</span>
											<br/>
											<span style="font-size:11px;color:#555;font-family:Helvetica, Arial, sans-serif;line-height:200%;"><a href="mailto:someone@example.com" target="_top">someone@example.com</a></span>
											<br/>
											
					                	</div>
					              	</div>
								</td>
								<td width="40%" height="70" align="right" valign="top" align="right" style="padding-bottom:20px;">
									<table width="100%" border="0" cellspacing="0" cellpadding="0" align="right">
										<tr>
											<td width="57%"></td>
											<td valign="top" width="34">
												<div class="contentEditableContainer contentFacebookEditable" style="display:inline;">
							                        <div class="contentEditable" >
							                            <a target="_blank" href="#" data-default="placeholder"  style="text-decoration:none;">
							                            <img src="images/facebook.png" data-default="placeholder" data-max-width="30" data-customIcon="true" width="30" height="30" alt="facebook" style="margin-right:40x;">
							                            </a>
							                        </div>
							                    </div>
											</td>
											<td valign="top" width="34">
												<div class="contentEditableContainer contentTwitterEditable" style="display:inline;">
							                      <div class="contentEditable" >
							                        <a target="_blank" href="#" data-default="placeholder"  style="text-decoration:none;">
							                        <img src="images/twitter.png" data-default="placeholder" data-max-width="30" data-customIcon="true" width="30" height="30" alt="twitter" style="margin-right:40x;">
							                        </a>
							                      </div>
							                    </div>
											</td>
											<td valign="top" width="34">
												<div class="contentEditableContainer contentImageEditable" style="display:inline;">
							                      <div class="contentEditable" >
							                        <a target="_blank" href="#" data-default="placeholder"  style="text-decoration:none;">
														<img src="images/pinterest.png" width="30" height="30" data-max-width="30" alt="pinterest" style="margin-right:40x;" />
													</a>
							                      </div>
							                    </div>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>


				</td>
			</tr>
		</table>

		
		

	</td></tr></table>
	
		</td>
	</tr>
	</table>';

				 // $message = "Dear ".$row['user_name'].", You have won the bid on product <a target='_blank' href='https://test-storewalkwel.myshopify.com/products/".$prod_handle."' >".$row['product_name']."</a>. Please Checkout using below url. Thankyou!!<br><br><br> <a href='".$checkout_url"'>'".$checkout_url."'</a>";

				// Always set content-type when sending HTML email
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

				echo  "More headers";
				$headers .= 'From: <developer.walkwel@gmail.com>' . "\r\n";
				

				$mail = mail($to,$subject,$message,$headers);
				$data['bid_id'] = $row['customer_bid_id'];
				$setme = setwinner($conn, $data);
				echo "<br>". $setme;
				if($setme == true) {
					$setFlag = 1;
				}
    			
			}

		}
		if($setFlag == 1){
			echo "email sent success!!";
			$queryupdate = mysqli_query($conn, "UPDATE customer_bids SET expired = 1 WHERE product_id = '".$data['prod_id']."'");

				
			$metafield = array("metafield" => array("namespace" => "auction", "key" => "expiredflag", "value" => true, "value_type" => "string") );

		    addmeta($metafield, $data, $conn);
		}
		else{
			echo 0;
		}	
		
	}
	else {
		echo "email already sent!!";
	}


	
}


?>