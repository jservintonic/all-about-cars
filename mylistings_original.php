<?php include_once("header.php")?>
<?php require("utilities.php")?>
<?php include("database.php")?>


<?php

 // Retrieve these from the URL

if (!isset($_GET['cat'])) {
  $category = NULL;
} else {
  $category = $_GET['cat'];
}

if (!isset($_GET['order_by'])) {
  $ordering = NULL;
} else {
  $ordering = $_GET['order_by'];
}
if (!isset($_GET['page'])) {
  $curr_page = 1;
} else {
  $curr_page = $_GET['page'];
}

?>

<div class="container">

<h2 class="my-3">My listings</h2>

<div id="searchSpecs">
<!-- When this form is submitted, this PHP page is what processes it.
     Search/sort specs are passed to this page through parameters in the URL
     (GET method of passing data to a page). -->
<form method="get" action="mylistings.php">
  <div class="row">
    <div class="col-md-0 pr-0">
      <div class="form-group">
	    <div class="input-group">
          <div class="input-group-prepend">
            </span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-group">
        <label for="cat" class="sr-only">Search within:</label>
        <select class="form-control" name="cat" id="cat">
          <option value="all">All categories</option>
          <?php
            $sql_query_category = "SELECT categoryID, categoryName FROM category;";
            $result = mysqli_query($conn, $sql_query_category) or die('Error making select login query' . mysql_error());
            // Query data
            if (mysqli_num_rows($result) > 0) {
                // output data of each row
                while ($row = mysqli_fetch_assoc($result)) {
                    $selected = $category == $row['categoryID']? "selected" : "";
                    echo <<< EOM
                      <option value="${row['categoryID']}" $selected >${row['categoryName']}</option>
                   EOM;
                }
            }
          ?>
        </select>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-inline">
        <label class="mx-2" for="order_by">Sort by:</label>
        <select class="form-control" name="order_by" id="order_by">
          <?php
            echo "<option value='pricelow' " . ($ordering == 'pricelow'? 'selected' : '') . '>Price (low to high)</option>';
            echo "<option value='pricehigh' ". ($ordering == 'pricehigh'? 'selected' : '') . '>Price (high to low)</option>';
            echo "<option value='date' " . ($ordering == 'date'? 'selected' : '') . '>Expiring first</option>';
          ?>
        </select>
      </div>
    </div>
    <div class="col-md-1 px-0">
    <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </div>
</form>
</div> <!-- end search specs bar -->

<?php
    

   $query_condition = "";

   if ($category != "" and $category != "all") {
       $query_condition .= " AND categoryID = " . $category . " ";
   }
   // default order by price(low to high)
   $query_ordering = "";
   if ($ordering == "" or $ordering == "pricelow") {
       $query_ordering .= " ORDER BY currentPrice ";
   } elseif ($ordering == "pricehigh") {
       $query_ordering .= " ORDER BY currentPrice DESC ";
   } elseif ($ordering == "date") {
       $query_ordering .= " ORDER BY enddate ";
   }
   /* For the purposes of pagination, it would also be helpful to know the
      total number of results that satisfy the above query */
  // Get the total result in order to set up the pagination
  $results_per_page = 10;
  $start_record = ($curr_page - 1) * $results_per_page;
  $sql = "SELECT count(itemID) as count FROM items" . $query_condition;
  $result = mysqli_query($conn, $sql) or die('Error making select login query' . mysql_error());
  $num_results = mysqli_fetch_assoc($result);
  $max_page = ceil($num_results['count'] / $results_per_page);
?>

<div class="container mt-5">

<ul class="list-group">

<?php
  // This page is for showing a user the auction listings they've made.
  // It will be pretty similar to browse.php, except there is no search bar.
  // This can be started after browse.php is working with a database.
  // Feel free to extract out useful functions from browse.php and put them in
  // the shared "utilities.php" where they can be shared by multiple files.
  
  
  // TODO: Check user's credentials (cookie/session).
  // TODO: Perform a query to pull up their auctions.
  // TODO: Loop through results and print them out as list items.
  
  $id = $_SESSION['userID'];
      
  $query = "";
  if (isset($_SESSION['account_type']) && $_SESSION['account_type'] == 'seller') {
    $query = "SELECT i.itemID, itemName, description, categoryID, IFNULL(b.numBid, 0) as numBid, IFNULL(b.currentPrice, i.startPrice) as currentPrice, DATE_FORMAT(endDate, '%Y-%m-%dT%H:%i:%s') as endDate
                      FROM items as i
                      LEFT JOIN (SELECT itemID ,count(*) as numBid, Max(bidPrice) as currentPrice FROM bids GROUP BY itemID) as b
                      On i.itemID = b.itemID
                      WHERE sellerID = '$id' " . $query_condition . $query_ordering .
                      "LIMIT $start_record, $results_per_page";

    $result = mysqli_query($conn, $query) or die($conn->error);
    while($row = $result->fetch_assoc()) {
  
      print_listing_li($row["itemID"], $row["itemName"], $row["description"], $row["currentPrice"], $row["numBid"], $row["endDate"]);
  
     }

     if (mysqli_num_rows($result)==0) {
      echo <<<EOM
      <div class="container">
        <h1 style="font-size:170%"> You have no listings currently. </h1>
        <p> To add a new listing, click Create Auction. </p>
      </div>
      EOM;

     }
  
    }
  

?>

</ul>

<!-- Pagination for results listings -->
<nav aria-label="Search results pages" class="mt-5">
  <ul class="pagination justify-content-center">

<?php
  // Copy any currently-set GET variables to the URL.
  $querystring = "";
  foreach ($_GET as $key => $value) {
      if ($key != "page") {
          $querystring .= "$key=$value&amp;";
      }
  }

  $high_page_boost = max(3 - $curr_page, 0);
  $low_page_boost = max(2 - ($max_page - $curr_page), 0);
  $low_page = max(1, $curr_page - 2 - $low_page_boost);
  $high_page = min($max_page, $curr_page + 2 + $high_page_boost);

  if ($curr_page != 1) {
      echo('
    <li class="page-item">
      <a class="page-link" href="mylistings.php?' . $querystring . 'page=' . ($curr_page - 1) . '" aria-label="Previous">
        <span aria-hidden="true"><i class="fa fa-arrow-left"></i></span>
        <span class="sr-only">Previous</span>
      </a>
    </li>');
  }

  for ($i = $low_page; $i <= $high_page; $i++) {
      if ($i == $curr_page) {
          // Highlight the link
          echo('
    <li class="page-item active">');
      } else {
          // Non-highlighted link
          echo('
    <li class="page-item">');
      }

      // Do this in any case
      echo('
      <a class="page-link" href="mylistings.php?' . $querystring . 'page=' . $i . '">' . $i . '</a>
    </li>');
  }

  if ($curr_page != $max_page and $max_page != 0) {
      echo('
    <li class="page-item">
      <a class="page-link" href="mylistings.php?' . $querystring . 'page=' . ($curr_page + 1) . '" aria-label="Next">
        <span aria-hidden="true"><i class="fa fa-arrow-right"></i></span>
        <span class="sr-only">Next</span>
      </a>
    </li>');
  }
?>

  </ul>
</nav>

</div>


<?php include_once("footer.php")?>