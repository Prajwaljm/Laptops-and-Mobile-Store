<?php
  // Initialize the session
  session_start();
  include 'config.php';

  // Check if the user is logged in, if not then redirect to login page
  if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true)
  {
      header("location: login.php");
      exit;
  }

  $sql_for_json = "SELECT MODEL FROM products";
  $sql_json_result = $conn->query($sql_for_json);
  while($row = $sql_json_result->fetch_array())
  {
    $model_data[] = array("name" => $row["MODEL"]);
  }

  $file = "data.json";
  if(file_put_contents($file, json_encode($model_data)))
  {  }//echo("File created");}
  else
    echo("JSON file creation Failed");


  $sql_customer_id_extract = "SELECT ID FROM customer WHERE USER_ID = ?";
  $customer_extract_err = "";
  if($stmt =$conn->prepare($sql_customer_id_extract))
  {
    $stmt->bind_param("i",$_SESSION["id"]);
    if($stmt->execute())
    {
      $stmt->store_result();
      $stmt->bind_result( $param_customer_id);
      $stmt->fetch();
      $customer_id = $param_customer_id;
    }
    else
    {
      $customer_extract_err = "Execution failed.";
    }
    $stmt->close();
  }
  else
  {
    $customer_extract_err = "Preparation failed";
  }


  $sql_addresses_extract = "SELECT ID, ADDRESS_LINE1, ADDRESS_LINE2, DISTRICT, STATE, PINCODE, LANDMARK FROM address WHERE CUSTOMER_ID = ?";
  $address_extract_err = "";
  $address_count = 0;
  if($stmt_address_extract = $conn->prepare($sql_addresses_extract))
  {
      $stmt_address_extract->bind_param("i",$customer_id);
      if($stmt_address_extract->execute())
      {
        $stmt_address_extract->store_result();
        $address_count = $stmt_address_extract->num_rows;
        $stmt_address_extract->bind_result( $param_ID, $param_Address_Line1, $param_Address_Line2, $param_District, $param_State, $param_Pincode, $param_Landmark);
      }
      else
      {
        $address_extract_err = "Execution failed.";
      }
  }
  else
  {
    $address_extract_err = "Preparation failed";
  }



  $Address_Line1 = $Address_Line2 = $District = $State = $Landmark = "";
  $Address_Line1_err = $Address_Line2_err = $District_err = $State_err = $Pincode_err = $Landmark_err = "";
  $Pincode = NULL;

  $address_insert_err = "";
  $address_check_err = "";

  if($_SERVER["REQUEST_METHOD"] == "POST")
  {

    if($_POST["address_submit"] == "Submit" && $address_count<3)
    {

      if(empty(trim($_POST["Address_Line1"])))
        $Address_Line1_err = "Address Line 1 is empty.";
      else
        $Address_Line1 = trim($_POST["Address_Line1"]);

      if(empty(trim($_POST["Address_Line2"])))
        $Address_Line2_err = "Address Line 2 is empty.";
      else
        $Address_Line2 = trim($_POST["Address_Line2"]);

      if(empty(trim($_POST["District"])))
        $District_err = "District is empty.";
      else
        $District = trim($_POST["District"]);

      if(empty(trim($_POST["State"])))
        $State_err = "State is empty.";
      else
        $State = trim($_POST["State"]);

      $Pincode = $_POST["Pincode"];

      if(empty(trim($_POST["Landmark"])))
        $Landmark_err = "Landmark is empty.";
      else
        $Landmark = trim($_POST["Landmark"]);


      if(empty($Address_Line1_err) && empty($Address_Line2_err) && empty($District_err) && empty($State_err) && empty($Pincode_err) && empty($Landmark_err))
      {
        $sql_address_check = "SELECT COUNT(*) FROM address WHERE ADDRESS_LINE1 = ? AND ADDRESS_LINE2 = ? AND DISTRICT = ? AND STATE = ? AND PINCODE = ? AND LANDMARK = ? AND CUSTOMER_ID = ?";
        if($stmt_address_check = $conn->prepare($sql_address_check))
        {
          $stmt_address_check->bind_param("ssssisi", $Address_Line1, $Address_Line2, $District, $State, $Pincode, $Landmark, $customer_id);
          if($stmt_address_check->execute())
          {
            $stmt_address_check->store_result();
            $stmt_address_check->bind_result($param_count);
            $stmt_address_check->fetch();
            if($param_count>0)
              $address_check_err = "This Address already exists in your account.";
          }
          else
          {
            $address_check_err = "Execution failed";
          }
        }
        else
        {
            $address_check_err = "Preparation failed";
        }

        $sql_address_insert = "INSERT INTO address(ADDRESS_LINE1, ADDRESS_LINE2, DISTRICT, STATE, PINCODE, LANDMARK, CUSTOMER_ID) VALUES( ?, ?, ?, ?, ?, ?, ?)";
        if(empty($address_check_err) && $stmt_addresss_insert = $conn->prepare($sql_address_insert))
        {
          $stmt_addresss_insert->bind_param("ssssisi", $Address_Line1, $Address_Line2, $District, $State, $Pincode, $Landmark, $customer_id);
          if($stmt_addresss_insert->execute())
          {
            $address_insert_err = "New Address added into account.";
          }
          else
          {
            $address_insert_err = "Execution failed.";
          }
          $stmt_addresss_insert->close();
        }

      }
    }

    else if($_POST["search_submit"] === "search_it")
    {
      $_SESSION["search_sent"] = 1;
      $_SESSION["search_query"] = $_POST["search"];
      header("location: index.php");
    }


  }

?>


<!DOCTYPE html>
<html lang="en" >
<head>
      <meta charset="UTF-8">
      <title>Add Address</title>
      <link rel="stylesheet" href="index.css" />
      <!-- CSS only -->
      <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
      <!-- JavaScript Bundle with Popper -->
      <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
      <!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />-->
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
      <style type="text/css">
          body { font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif,Apple Color Emoji,Segoe UI Emoji; font-size: 14px; }
          .wrapper { margin-left: 500px; margin-top: 30px; width: 400px; padding: 20px; background-color:#e1e4e8; border-radius: 25px; }
          h2 { text-align: center; font-size:45px; }
          input[type="text"], input[type="password"], input[type="email"] { width: 300px; }
          #SB, #RB { opacity: 0.9; }
          .radio{top: 75px; left: 400px;}
          .address1{margin-left: 0; margin-top: -70px;}
          .address2{margin-left: 250px; margin-top: -145.5px;}
          .address3{margin-left: 500px; margin-top: -146.5px;}
          .add-button{margin-right: 500px;}
      </style>
  </head>

<body>

  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="top-navbar">
      <!-- insert a logo image -->
      <a href="https://imgbb.com/"><img src="https://i.ibb.co/j8GNM6K/Electronikart-dbms-project.png" alt="logo" class="logo" /></a>
      <div>
        <input type="text" name="search" id="search" placeholder="Search.." required>
        <!-- search icon -->
        <!--<span class="input-group-text">--><button class="input-group-text" type="submit" name="search_submit" value="search_it"><i class="fa fa-search"></i></button><!--</span>-->
        <ul class="list-group" id="result"></ul>
      </div>
      <div class="menu-bar">
        <ul>
          <li><a href="#"><i class="fa fa-shopping-basket"></i>Cart</a></li>
          <li><a href="reset-password.php">Reset Password</a></li>
          <li><a href="logout.php">Sign Out</a></li>
        </ul>
      </div>
    </div>
  </form>

  <!--second navbar-->
  <div class="navbar2">
    <a href="index.php">Home</a>
    <a href="add_address.php">Add Address</a>
    <!--<a href="#Laptops">Laptops</a>
    <a href="#Mobiles">Mobiles</a>-->
  </div>

  <div class="addresses-list">
    <?php
      while($stmt_address_extract->fetch())
      {
        echo "<form action=\"".htmlspecialchars($_SERVER['PHP_SELF'])."\" method=\"post\">";
        echo "<input typr=\"submit\" value=\"Delete\">";
        echo "</form>";
      }
    ?>
  </div>
  
  
  <div class='radio'>
        <div class='address1'>
        <input type="radio" id="address1" name="address" value="address1">
        <label for="address1">3-876,Teacher's colony,<br>Tirupati,<br>Chitt0or,<br>Andhra Pradesh,<br>517501,<br>Near Apollo Hospital</label><br>
        <button class="add-button" type="submit">Delete this address</button>
        </div>
        <div class='address2'>
         <input type="radio" id="address2" name="address" value="address2">
         <label for="address2">1-873,Raja Street,<br>Venkatagiri,<br>Nellore,<br>Andhra Pradesh,<br>524132,<br>Near Poleramma Temple</label><br>
         <button class="add-button" type="submit">Delete this address</button>
        </div>
        <div class='address3'>
         <input type="radio" id="address3" name="address" value="address3">
         <label for="address3">1-71,Pocharam Village,<br>Pocharam,<br>Hyderabad,<br>Telangana,<br>501506,<br>Near Infosys</label>
         <button class="add-button" type="submit">Delete this address</button>
        </div>
    </div>

  <div class="wrapper">
      <h4>Add Address</h4>
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="address">

          <div class="form-group">
              <label for="Address_Line1">Address_Line1</label>
              <input type="text" id="Address_Line1" placeholder="Enter your Address" name="Address_Line1" value="<?php echo $Address_Line1; ?>" required>
              <span class="help-block"><?php echo $Address_Line1_err; ?></span>
          </div>
          <!--<br><br>-->
          <div class="form-group">
              <label for="Address_Line2">Address_Line2</label>
              <input type="text" id="Address_Line2" placeholder="Enter your Address" name="Address_Line2" value="<?php echo $Address_Line2; ?>" required>
              <span class="help-block"><?php echo $Address_Line2_err; ?></span>
          </div>
          <!--<br><br>-->
          <div class="form-group">
              <label for="District">District</label>
              <input type="text" id="District" placeholder="District name" name="District" value="<?php echo $District; ?>" required>
              <span class="help-block"><?php echo $District_err; ?></span>
          </div>
          <!--<br><br>-->
          <div class="form-group">
              <label for="State">State</label>
              <input type="text" id="State" placeholder="State name" name="State" value="<?php echo $State; ?>" required>
                <span class="help-block"><?php echo $State_err; ?></span>
          </div>
          <!--<br><br>-->
          <div class="form-group">
              <label for="Pincode">Pincode</label>
              <input type="number" id="Pincode" placeholder="Pincode" name="Pincode" min="110000" max="999999" value="<?php echo $Pincode; ?>" required>
              <span class="help-block"><?php echo $Pincode_err; ?></span>
            </div>
          <!--<br><br>-->
          <div class="form-group">
              <label for="Landmark">Landmark</label>
              <input type="text" id="Landmark" placeholder="Any Landmark near your place" name="Landmark" value="<?php echo $Landmark; ?>" required>
              <span class="help-block"><?php echo $Landmark_err; ?></span>
          </div>
          <!--<br><br>-->
          <div class="form-group">
                <input id="SB" type="submit" class="btn btn-primary" name="address_submit" value="Submit">
                <input id="RB" type="reset" class="btn btn-default" value="Reset">
                <span class="help-block"><?php if($address_count>=3) echo "You cannot add anymomre addresses for this account. Max limit is 3."; ?></span>
                <span class="help-block"><?php  echo $address_check_err; ?></span>
                <span class="help-block"><?php echo $address_insert_err; ?></span>
            </div>
          <!--<br><br>-->
          </form>
      </div>
  </body>
</html>





<script>
  $(document).ready(function(){
    $.ajaxSetup({ cache: false });
    $('#search').keyup(function(){
      $('#result').html('');
      $('#state').val('');
      var searchField = $('#search').val();
      var expression = new RegExp(searchField, "i");
      $.getJSON('data.json', function(data) {
        $.each(data, function(key, value){
          if (value.name.search(expression) != -1 )
          {
            $('#result').append('<li class="list-group-item link-class">  '+value.name+'</li>');
          }
        });
      });
    });

  $('#result').on('click', 'li', function() {
  var click_text = $(this).text().split('|');
  $('#search').val($.trim(click_text[0]));
  $("#result").html('');
    });
  });

  window.onload = function(){
            var result = document.getElementById('result');
            document.onclick = function(e){
               if(e.target.id !== 'result' && e.target.id!== 'search'){
                  result.style.display = 'none';
               }
               else {
                 result.style.display = 'block';
               }
            };
         };
</script>