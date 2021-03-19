
<!-- A simple PHP file that queries tables of SQL database that contain numeric
and string values and distplays the results in HTML table format -->


<?php

// SQL login credentials.
// Confidential info which is different for different users.
const __lgin_creds__ =
["hostname" => "secret",       // name of the host on which SQL is running, best to run it on localhost
"username" => "top_secret",    // user name for the data base access
"password" => "top_secret"];   // password for the data base access
const __db_name__ = "secret";  // default data base name
const __nrowsmax__ = 5;        // default maximum number of queried rows
const __NROWSMAX__ = 100000;   // top limit on the number of rows a user can request

// Function to obtain table rows from the data base. Arguments:

// creds: an array of login credentials (default is set to an array of constants)
// dbname: data base name (default is set to a constant value)

// table: table within the data base to display; if (default) null is used then pick a random available table
// nrowsmax: maximum number of rows to display (default is set to a global constant defined above)

function queryDb($creds = __lgin_creds__, $dbname = __db_name__, $table = null, $nrowsmax = __nrowsmax__) {


  // Create a connection to the data base
  $conn = new mysqli($creds["hostname"],$creds["username"],$creds["password"],$dbname);


  // Check the connection
  if ($conn->connect_error) {
    return null; // return null result if failed, indicating a bad request
  }


  // pick a random table if none was provided as an argument
  if($table===null){
    $cmd="SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = '${dbname}' ORDER BY RAND() LIMIT 1;";
    $resp = $conn->query($cmd);
    if($resp->num_rows == 0) {
      return null; // if nothing came back, return a bad request
    }
    $table=$resp->fetch_assoc()["TABLE_NAME"];
  }

  // get column names for the table
  $cmd = "SHOW COLUMNS FROM ${table};";
  $columns=$conn->query($cmd);

  // get rows from the table
  $cmd = "SELECT * FROM ${table} LIMIT ${nrowsmax};";
  $data=$conn->query($cmd);

  // close the connection
  $conn->close();

  // return an array with table name, table column names as an array, and table row data
  return ["table" => $table, "columns" => $columns, "data" => $data];
}

// function that returns a list of tables in the given data base
// Arguments:
// creds: an array of login credentials (default is set to an array of constants)
// dbname: data base name (default is set to a constant value)
function get_tables($creds = __lgin_creds__, $dbname = __db_name__) {

  // Create connection
  $conn = new mysqli($creds["hostname"],$creds["username"],$creds["password"],$dbname);

  // Check connection
  if ($conn->connect_error) {
    return null; // return null (bad request) if the connection failed
  }


  $cmd="SELECT TABLE_NAME FROM information_schema.tables where
  TABLE_SCHEMA = '" . $dbname . "';";
  $resp = $conn->query($cmd);
  if($resp->num_rows == 0) {
    return null;
  }
  $tables = array();
  while($row=$resp->fetch_assoc()){
    array_push($tables,$row["TABLE_NAME"]);
  }
  $conn->close();

  // return the table names as an array
  return $tables;
}

?>

<!--- HTML starts,  set some CSS styles -->
<!DOCTYPE html>
<html>
<head>
  <style>
  body {background-color: powderblue;}

/* To split the deisplay area into two column pars */
  .grid-container {
    display: grid;
    grid-template: auto / 30% 70%;
    background-color: white;
    justify-items: center;
    padding: 25px;
  }
  .grid-left {
    text-align: center;
    justify-items: center;
    width: 100%;
  }
  .grid-right {
    width: auto;
  }
  table {
    width: 100%;
  }
  </style>
</head>

<body>

  <!-- grid container starts -->
  <div class="grid-container">

    <!-- left division of the grid container starts -->
    <div class="grid-left">


      <form id="table-frm" name="table-frm" accept-charset="utf-8" action="#" method="post">
        <label for="nrowsmax">Max number of rows:</label>
        <input type="number" id="nrowsmax" name="nrowsmax" value=<?='"' . __nrowsmax__ . '"'?> min="0"
        max=<?='"' . __NROWSMAX__ . '"'?> step="1">
        <br> <br><br>
        <label for="table">Select the table:</label>
        <br><br>

        <select name="table" id="table" onchange="this.form.submit();">
          <option value="Choose" selected="" disabled="">Choose reconstruction</option>
          <?php
          // insert a list of tables in the chosen default data base into HTML select option list
          $tables=get_tables();
          foreach($tables as $tbl) echo '<option value="' . $tbl . '">' . $tbl . '</option>';
          ?>
        </select>

        <?php
        // HTML tags and java script statements to add the submit button and to show previously submitted values in the form field
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['table']) {
          echo '<br><br><br>';
          echo '<input id = "table_frm_submit" type = "submit" value = "Submit">';
          echo '<script type="text/javascript">';
          echo 'document.getElementById(\'table\').value = ' . '\'' . $_POST['table'] . '\';';
          echo 'document.getElementById(\'nrowsmax\').value = ' . $_POST['nrowsmax'] . ';';
          echo '</script>';
        }
        ?>

      </form>

      <!-- left division of the grid container ends -->
    </div>

    <!-- right division of the grid container starts -->
    <div class="grid-right">

      <?php

      // if the request has been submitted via post method then display the SQL
      // query as a HTML table in the right division of the CSS grid container
      if ( $_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['table']) {


        $key = array_search($_POST['table'],$tables);
        if($key === false) {
          die("<br>Bad request made! <br>");
        }

        $nrowsmax = null;
        if(array_key_exists("nrowsmax",$_POST)) {
          $nrowsmax = filter_var($_POST["nrowsmax"], FILTER_SANITIZE_NUMBER_INT,
          array("options"=>array("min_range"=>0, "max_range"=>__NROWSMAX__)));
          if($nrowsmax === false) {
            $nrowsmax = null;
          }
        }
        // submit the query with default login credentials and data base,
        // and chosen table name and the maximum number of rows to show
        $result = queryDb(__lgin_creds__,__db_name__,$tables[$key],$nrowsmax);

        // if the query returned null result then stop the script imap_append
        // display an error message
        if(!$result) {
          die("<br>Bad request made! <br>");
        }

        echo "<br>";
        echo "<table id = dataset border='1' width='100%'>";

        // show table name as a caption of the table
        echo "<caption>".$result["table"]."</caption>";

        // proceed with construction of the table entries if the number of num_rows
        // received is greater than zero
        if ($result["data"]->num_rows > 0) {

          // use the SQL table field descriptors as HTML talble column names
          echo "<tr>";
          // iterating over each column name
          while($col = $result["columns"]->fetch_assoc()) {
            echo "<th>" . $col["Field"] . "</th>";
          }
          echo "</tr>";
          echo "<tr>";
          // output data of each row in HTML format
          $nrows=0; // count the number of rows received
          // iterating over each row
          while($row = $result["data"]->fetch_assoc()) {
            echo "<tr>";
            // iterate over each field of the row and insert its value into HTML
            // table
            foreach ($row as $field => $value) {
              echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
            $nrows ++;
          }

          // show the number of rows in the footer
          echo"<tfoot>
          <tr>
          <td>Nrows</td>
          <td>${nrows}</td>
          </tr>
          </tfoot>";

        } else {
          echo "0 results";
        }
      }

      ?>
      <!-- right division of the grid container ends -->
    </div>
    <!-- grid container ends -->
  </div>

</body>
</html>
