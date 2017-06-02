<?php
session_start();

require_once("config.php");
require 'shopify.php';
?>
<script src="https://cdn.shopify.com/s/assets/external/app.js"></script>
<script type="text/javascript">
  ShopifyApp.init({
    apiKey: '<?php echo SHOPIFY_API_KEY;  ?>',
    shopOrigin: 'https://<?php echo $_GET["shop"]; ?>',
    debug: true,
    forceRedirect: true
  });        
  ShopifyApp.ready(function(){
           /* ShopifyApp.Bar.initialize({
                icon: '',
                title: 'PixiFlex Dashboard',
                buttons: {
                   primary: {
                        label: 'Save',
                        message: 'save',
                        callback: function(){
               
                    	}
                	}
            	}
            });*/
            ShopifyApp.Bar.loadingOff();
          });

        </script>
        <?php
	//if(!isset($_SESSION['shop']) || empty($_SESSION['shop']) || !isset($_SESSION['token']) || empty($_SESSION['token']) ){
		//header("Location:install.php?shopname=".urlencode($_GET['shop'])."");
		//die;
	//}

        ?>
<!doctype html>
        <html lang="en">
        <head>
          <meta charset="utf-8">
          <title>FLAMINGO BIDDING APP</title>
          <!-- Latest compiled and minified CSS -->
          <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

          <link rel="stylesheet" href="css/style.css">
<!-- Optional theme 
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css"> -->

  <!-- Latest compiled and minified Jquery Library -->
  <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
  <!-- Latest compiled and minified JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" ></script>
  <script src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js" ></script>
  <script src="https://cdn.datatables.net/1.10.15/js/dataTables.bootstrap.min.js" ></script>

</head>

<body>
<div class="loading">Loading&#8230;</div>
  <div class="container auctionproduct_container">
    <h2 class="product_bid_header">Auction Product Bids</h2>  
    <div id="exTab1" class="container"> 
      <ul  class="nav nav-pills">
        <li class="active">
          <a  href="#1a" data-toggle="tab">Bids</a>
        </li>
        <li>
          <a href="#2a" data-toggle="tab">Add Product Auction</a>
        </li>
      </ul>

      <div class="tab-content clearfix">
        <div class="tab-pane active" id="1a">




          <table class="table table-responsive table-bordered table-striped" style="margin-top: 5px;">
            <thead>
              <tr class="th_row_heading">

                <th>ID</th>
                <th>Product Name</th>
                <th>Bids Count</th>
                <th>View Bids</th>
                <!--<th>Action</th>-->
              </tr>
            </thead>
            <tbody>
              <?php
              $query = mysqli_query($conn, "SELECT *, COUNT('auc_id') as tprods FROM customer_bids WHERE delete_status = 0 GROUP BY auc_id, expired");
              $count = 1;
              while($row = mysqli_fetch_array($query)) {
                ?>
                <tr>
                  <td><span class="serial_number"><?php echo $count; ?></span></td>

                  <td><span class="product_name"><a target="_blank" href="https://test-storewalkwel.myshopify.com/admin/products/<?php echo $row['product_id'];  ?>"><?php echo $row['product_name'];?></a><?php  if($row['expired'] == 1) { echo "<span class='text-danger'> (expired)</span>"; }  ?></span></td>

                  <td><span class="bids_count"><?php echo $row['tprods']; ?></span></td>

                  <td><button type="button" class="btn btn-info btn-bids" data-auc-id="<?php echo $row['auc_id']; ?>" data-expired="<?php echo $row['expired']; ?>" data-toggle="modal" data-target="#myModal">View Bids</button></td>

                  <!--<td><button type="button" class="btn btn-danger btn-del-bid" data-prod-id="<?php echo $row['product_id']; ?>" data-toggle="modal" data-target="#delModal">Delete</button></td>-->
                </tr>
                <?php
                $count++;
              }
              if(mysqli_num_rows($query) == 0){
                //echo "<tr><td align='center' colspan='5'>No Bids Found!!</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
        <div class="tab-pane" id="2a">
          <table class="table table-responsive table-bordered table-striped" style="margin-top: 5px;">
            <thead>
              <tr class="th_row_heading">
                <th>ID</th>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Product Price</th>
                <th>Auction</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              
                <?php 
                  $shop =  $_GET['shop'];
                  $query = mysqli_query($conn, "SELECT access_token FROM tbl_usersettings WHERE store_name = '".$shop."'") or die(mysqli_error($query));
                  $row  = mysqli_fetch_array($query);

                  $sc = new ShopifyClient($shop, $row['access_token'], SHOPIFY_API_KEY, SHOPIFY_SECRET);
                  $allProducts = $sc->call('GET', '/admin/products.json?collection_id=419688084', array());
                  
                  $procount = 1;
                  foreach($allProducts as $product){
                    $query2 = mysqli_query($conn, "SELECT * from auctions WHERE product_id = '".$product['id']."'");
                    $count_auc = mysqli_num_rows($query2);
                    //$auc_data = mysqli_fetch_array($query2);
                    $allMetas = $sc->call('GET', '/admin/products/'.$product['id'].'/metafields.json', array());
                    // echo "<pre>";
                    // print_r($allMetas);
                    // echo "</pre>";
                    $expired = 0;
                    foreach ($allMetas as  $meta) {
                      if($meta['key'] == 'expiredflag') {
                        if($meta['value'] == "true") {
                          $expired = 1;
                        }
                      }
                    }
                    // echo $expired;
                  ?>
                    <tr>
                      <td><?php echo $procount; ?></td>
                      <td><?php echo $product['id']; ?></td>
                      <td><?php echo $product['title']; ?></td>
                      <td><?php echo $product['variants'][0]['price']; ?></td>
                      <td><?php 
                              
                              if($expired == 1) { 
                                echo "<span class='text-danger'>Expired!!</span>"; 
                              } 
                              else if($count_auc > 0) { 
                                echo "<span class='text-success'>Added</span>"; 
                              } 
                              else { 
                                echo "<span class='text-danger' >Not Added</span>"; 
                              } 
                          ?></td>
                      <td><button type="button" onclick="getbid(<?php echo $product['id']; ?>);" class="btn btn-info btn-add-bid" data-prod-id="<?php echo $product['id']; ?>" >Add/Edit Bid</button> <button type="button" onclick="delete_bid(<?php echo $product['id']; ?>);" class="btn btn-danger btn-del-bid" data-prod-id="<?php echo $product['id']; ?>" >Delete</button></td>
                    </tr>
                  <?php
                    $expired = 0;
                    $procount++;
                  }
                ?>
               
            </tbody>
          </table>
        </div>
        
      </div>
    </div>



  </div>

  <!-- Add Bid Modal -->

    <div id="addBidModal" class="modal fade" role="dialog">
      <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Add Auction</h4>
        </div>
        <div class="modal-body">
          <form action="" method="POST">
            <div class="form-group">
              <label for="date">Auction End Date:</label>
              <input type="date" class="form-control" id="auc_end_date" readonly />
              <input type="hidden" id="check_auc_date" />
            </div>
            <div class="form-group">
              <label for="start_price">Auction Start Price:</label>
              <input type="number" class="form-control" id="start_price">
            </div>

            <div class="form-group">
              <label for="res_price">Auction Reserved Price:</label>
              <input type="number" class="form-control" id="res_price">
            </div>

            <div class="form-group">
              <label for="bid_increement">Increement Rate:</label>
              <input type="number" class="form-control" id="bid_increement">
            </div>

            <div class="form-group">
              <input type="hidden" id="product_id">
              <input type="hidden" id="shop_name" value="<?php echo $_GET['shop']; ?>">
              <input type="button" class="btn btn-primary" id="add_bid" value="Submit" />
            </div>
            <div class="alert alert-success success" style="display: none;"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>

    </div>
  </div>

  <!-- Add Bid Modal-->

  <!-- Modal -->
  <div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">All Bids</h4>
        </div>
        <div class="modal-body">
          <table class="table table-responsive table-bordered table-striped" style="margin-top: 5px;">
            <thead>
              <tr class="th_row_heading">
                <th>ID</th>
                <th>Customer Name</th>
                <th>Bid Placed</th>
              </tr>
            </thead>
            <tbody id="show_bids">

            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>

    </div>
  </div>

  <div id="delModal" class="modal fade" role="dialog">
    <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h1 class="modal-title" align="center">Are you sure?</h1>
        </div>
        <div class="modal-body" style="text-align: center;">
          <form method="post" action="">
            <input type="hidden" id="prod_id" name="prod_id" />
            <button type="button" name="delete" class="btn btn-success deleteme">Yes</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>

    </div>
  </div>

</body>
<script>
  $('.loading').hide();
  $('.table').DataTable();
  var shop_name = '<?php echo $_GET['shop']; ?>';
  checkexpired();
  var app_url = 'https://pink-flamingo.herokuapp.com';
  function checkexpired() {
    debugger;
    var data = 'shop='+shop_name;
    $.ajax({
      type: 'POST',
      data: data,
      url: "/bid_handle.php?mode=11",
      success:function(response) {
        debugger;
        $('.loading').hide();
        console.log(response);
      },
      error:function(response) {
        console.log(response);
      }
    });
  }
 
  
  $('.btn-bids').click(function(){
    debugger;
    var auc_id = $(this).attr('data-auc-id');
    var expired = $(this).attr('data-expired');
    var data = 'auc_id='+auc_id+'&expired='+expired;
    $.ajax({
      type: 'POST',
      data: data,
      url: "/bid_handle.php?mode=3",
      success:function(response) {
        $('#show_bids').html(response);
        
      },
      error:function(response) {
        console.log(response);
      }
    });
  });

  function getbid(prod_id) {
    
    $('.loading').fadeIn(1000);
    $('#product_id').val(prod_id);
    $('#auc_end_date').attr('readonly', true);
    var shop_name = $('#shop_name').val();

    var data = 'product_id='+prod_id+'&shop='+shop_name;
    $.ajax({
      type: 'POST',
      data: data,
      url: "/bid_handle.php?mode=9",
      success:function(response) {
       debugger;
        if(response != "" && response != '[]') {
          
          var resdata = JSON.parse(response);
          $.each(resdata, function(index, val) {
            if(val.key == 'end') {
              var dateval = formatDate(val.value);
              $('#auc_end_date').val(dateval);
              $('#check_auc_date').val(dateval);
            }
            else if(val.key == 'bid_increement') {
              $('#bid_increement').val(val.value);
            }
            else if(val.key == 'resprice') {
              $('#res_price').val(val.value); 
            }
            else if(val.key == 'minprice') {
              $('#start_price').val(val.value);
            }
            else if(val.key == 'expiredflag') {
              $('#auc_end_date').attr('readonly', false);
            }
          });
          $('#addBidModal').modal('show');
        }
        else {
          $('#addBidModal').modal('show');
          $('#auc_end_date').attr('readonly', false);
        }
        $('.loading').fadeOut(1000);
      },
      error:function(response) {
        console.log(response);
      }
    });

  }

  function delete_bid(prod_id) {
    $('.loading').fadeIn(1000);
      var data = 'product_id='+prod_id+'&shop='+shop_name;
      $.ajax({
      type: 'POST',
      data: data,
      url: "/bid_handle.php?mode=10",
      success:function(response) {
       debugger;
        if(response != "" && response != '[]') {
          
        }
        else {
          
        }
        alert(response);
        $('.loading').fadeOut(1000);
      },
      error:function(response) {
        console.log(response);
      }
    });
  }

  $('#addBidModal').on('hidden.bs.modal', function () {
    console.log('hidden ');
    $('#auc_end_date').val('');
    $('#check_auc_date').val('');
    $('#bid_increement').val('');
    $('#res_price').val(''); 
    $('#start_price').val('');
    $('.success').html("");
    $('.success').hide();
  })

  $('#add_bid').click(function(){
    $('.loading').fadeIn(1000);
   debugger;
    var prod_id = $('#product_id').val();
    var auc_end_date = $('#auc_end_date').val();
    var start_price = $('#start_price').val();
    var res_price = $('#res_price').val(); 
    var bid_increement = $('#bid_increement').val(); 
    var shop_name = $('#shop_name').val();
    var check_auc_date = $('#check_auc_date').val();

    var data = 'product_id='+prod_id+'&end_date='+auc_end_date+'&start_price='+start_price+'&res_price='+res_price+'&bid_increement='+bid_increement+'&check_auc_date='+check_auc_date+'&shop='+shop_name;

    $.ajax({
      type: 'POST',
      data: data,
      url: "/bid_handle.php?mode=8",
      success:function(response) {
        console.log(response);
        if(response) {
          $('.success').html("Bid Updated Success!!");
          $('.success').show();
          location.reload();
        }
        $('.loading').fadeOut(1000);
      },
      error:function(response) {
        console.log(response);
      }
    });
  });




  $('.btn-del-bid').click(function(){
    $('.loading').fadeIn(1000);
    var prod_id = $(this).attr('data-prod-id');
    $('#prod_id').val(prod_id);
  });

  $('.deleteme').click(function(){
  
     var prod_id = $('#prod_id').val();
     var data = 'prod_id='+prod_id;
     $.ajax({
      type: 'POST',
      data: data,
      url: "/bid_handle.php?mode=5",
      success:function(response) {
        location.reload();
      },
      error:function(response) {
        console.log(response);
      }
    });
 })

  function formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
  }
</script>

</html>