var app_url = 'https://pink-flamingo.herokuapp.com';

function placemybid(cus_id, cus_name, prod_id, prod_name, min_price, increement, ending_date){
      debugger;
      
      var bid_price = $('#bid_price').val();
      if(bid_price == "" || bid_price == 0) {
        $('.error').html('Bid value should not be empty or zero');
        return;
      }
      var last_bid_price = $('#last_bid_price').val();
      var shop = $('#shop_name').val();
      var data = 'product_id='+prod_id+'&product_name='+prod_name+'&customer_id='+cus_id+'&customer_name='+cus_name+'&bid_price='+bid_price+'&ending_date='+ending_date+'&shop='+shop;
      if(parseInt(bid_price) < parseInt(min_price)) {
        $('.error').html('Your bid price should be greater than minimum bid price');
        return;
      }

      if(last_bid_price > 0) {
        var tprice = parseInt(last_bid_price) + parseInt(increement);

        if(parseInt(last_bid_price) > parseInt(bid_price)) {
          $('.error').html('Your bid price should be greater than last bid price');
          return;
        }
        else if( parseInt(bid_price) < tprice ) {
          $('.error').html('Minimum bidding price allowed $' +tprice );
          
        }
        else {
          placenow(data);
          $('.last_bid').html('Current Bid : <sup class="dollar-n">$</sup>'+ bid_price + '.00');
        }
      }
      else {
        placenow(data);
        $('.last_bid').html('Current Bid : <sup class="dollar-n">$</sup>'+ bid_price + '.00');
      }
      
    }

function placenow(data) {
        $.ajax({
            type: 'POST',
            data: data,
            url: app_url+"/bid_handle.php?mode=1",
            success:function(response) {
                if(response == 1) {
                  //$('.bidnow1').fadeOut(1000);
                  $('.error').html('');
                  $('#bid_price').val('');

                  $('.success').html('Bid Placed Success!!');
                  $('.success').fadeIn(1000);
                }
                else {
                  //alert('Something went wrong!!');
                }
            },
            error:function(response) {
                console.log(response);
            }
        });
}

function checkbid(cust_id, prod_id) {

        var data = 'prod_id='+prod_id+'&cust_id='+cust_id;
        $.ajax({
            type: 'POST',
            data: data,
            url: app_url+"/bid_handle.php?mode=2",
            success:function(response) {
                if(response == 1) {
                  $('.bidnow1').fadeOut(1000);
                  $('.bidnow3').fadeIn(1000);
                }
                else {
                  $('.bidnow1').fadeIn(1000);
                  $('.bidnow3').fadeOut(1000);
                  //alert('Something went wrong!!');
                }
            },
            error:function(response) {
                console.log(response);
            }
        });

}

function getproductbids(prod_id) {

          var data = 'product_id='+prod_id;
          $.ajax({
            type: 'POST',
            data: data,
            url: app_url+"/bid_handle.php?mode=4",
            success:function(response) {
                $('#show_bids_by_prod').html(response);
            },
            error:function(response) {
                console.log(response);
            }
          });
}

function getwinner(prod_id, shop) {
  debugger;
          var data = 'prod_id='+prod_id+'&shop='+shop;
          $.ajax({
            type: 'POST',
            data: data,
            url: app_url+"/bid_handle.php?mode=6",
            success:function(response) {
              if(response == 1){
                console.log('email sent!!');
              }
            },
            error:function(response) {
                console.log(response);
            }
          });
}

function getlastbid(prod_id, increement) {
  debugger;
        var data = 'prod_id='+prod_id;
          $.ajax({
            type: 'POST',
            data: data,
            url: app_url+"/bid_handle.php?mode=7",
            success:function(response) {
              if(response != 0 || response != ""){
                var nextprice = parseInt(response) + parseInt(increement);
                $('.last_bid').html('Current Bid : <sup class="dollar-n">$</sup>'+ response + '.00');
                $('#lastbidd').html('$'+ nextprice + '.00');
                $('#last_bid_price').val(response);
              }
            },
            error:function(response) {
              console.log(response);
            }
          });
}

function accountbids(cust_id) {
  debugger;
  var data = 'cust_id='+cust_id;
          $.ajax({
            type: 'POST',
            data: data,
            url: app_url+"/bid_handle.php?mode=12",
            success:function(response) {
              if(response != 0 || response != ""){
                $('#userbids').html(response);
                //$('#last_bid_price').val(response);
              }
            },
            error:function(response) {
              console.log(response);
            }
          });
}


