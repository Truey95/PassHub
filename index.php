<?php

$baseurl = '../';

/*
  Determine what version of PassHub is installed, and whether it is eligible for an upgrade.
  The upgrade process will only run if the installed version is older than the upgrader version.
*/

// Read PassHub config file and convert to array
$config_path = '../app/config/config.ini';

if(!$config = parse_ini_file($config_path)) {
  die($failed_label . ' Could not parse config.ini');
};

// Detect current PassHub version
function getCurrentPassHubVersion($install_dir, $config) {
  $version = '';
  $config_version = '';
  // 1.0 unique feature: does not have Groups.php file
  // 1.1 unique feature: has app/models/PassHub/Groups.php file
  // 1.2.0+: config file has PASSHUB_VERSION constant
  $settings_file_path = $install_dir . 'app/models/PassHub/Settings.php';
  $groups_file_path = $install_dir . 'app/models/PassHub/Groups.php';
  if(file_exists($groups_file_path) === true) {
      $version = '1.1.0';
  } else {
      $version = '1.0.X';
  }
  if(isset($config['PASSHUB_VERSION'])) {
      $version = $config['PASSHUB_VERSION'];
  }
  return $version;
}

$eligible_for_upgrade = false;
$passhub_version = getCurrentPassHubVersion($baseurl, $config);

if(version_compare($passhub_version, '1.2.5', '<')) {
  $eligible_for_upgrade = true;
}

 ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
  <title>PassHub</title>

  <!-- CSS -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="<?php echo $baseurl; ?>assets/css/materialize.css" type="text/css" rel="stylesheet">
  <link href="<?php echo $baseurl; ?>assets/css/style.css" type="text/css" rel="stylesheet">

  <!-- Favicon -->
  <link rel="apple-touch-icon" href="<?php echo $baseurl; ?>assets/images/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" href="<?php echo $baseurl; ?>assets/images/favicon/android-chrome-192x192.png">
  <link rel="manifest" href="<?php echo $baseurl; ?>assets/images/favicon/manifest.json">
  <link rel="mask-icon" href="<?php echo $baseurl; ?>assets/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
  <link rel="shortcut icon" href="<?php echo $baseurl; ?>assets/images/favicon/favicon.ico">

  <!-- Styles for this page only -->
  <style>
    .bullet-list {
      list-style-type: disc;
      padding-left: 2em;
    }
    .bullet-list li {
      list-style-type: disc;
    }
    .thin-text {
      font-weight: 300;
    }
    nav .brand-logo {
      position: static;
    }
    .result {
      display: inline-block;
      padding: 0 5px;
      border-radius: 3px;
      font-weight: bold;
    }
    .btn .preloader-wrapper {
      top:16px;
    }
    #statusLog {
      max-height: 10em;
      overflow-y: scroll;
    }
  </style>
</head>
<body class="groups" data-mode="groups">
  <nav class="light-blue lighten-1">
    <div class="nav-wrapper container center"><a id="logo-container" href="#" class="brand-logo light-blue-text text-lighten-5"><span class="white-text">Pass</span>Hub <span class="thin-text">Updater</span></a>
    </div>
  </nav>

  <div class="container" id="title">
    <div class="row">
      <div class="col s12 m10 l8 offset-m1 offset-l2 center">
        <h2 class="grey-text text-darken-3">Welcome</h2>
        <?php if($eligible_for_upgrade): ?>
          <p class="intro">To upgrade your copy of PassHub to <strong>1.2.5</strong>, <br>use the update button below.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php if($eligible_for_upgrade): ?>
      <div class="row">
        <div class="col s12 m10 l8 offset-m1 offset-l2">
            <div class="card yellow lighten-4">
              <div class="card-content">
                <span class="card-title grey-text text-darken-4"><i class="material-icons">info_outline</i> Before You Start</span>
                <ul class="bullet-list">
                  <li>Note that existing data will be transfered over.</li>
                  <li>Back up your existing database and files as a safety precaution.</li>
                  <li>Note that if you have made any edits to the code or database structure, they will be lost in the upgrade process.</li>
                </ul>
              </div>
            </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="container">
    <div class="row">
      <div class="col s12 m10 l8 offset-m1 offset-l2">
        <div class="card">
          <div class="card-content">
            <span class="card-title grey-text text-darken-4">Version Check</span>
            <table>
              <thead>
                <tr>
                  <th>Updater Version</th>
                  <th>Your Version</th>
                  <th>Eligible for Upgrade</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>1.2.5</td>
                  <td><?php echo $passhub_version; ?></td>
                  <td>
                    <?php if($eligible_for_upgrade): ?>
                      <span class="result ok green white-text">YES</span>
                    <?php else: ?>
                      <span class="result ok blue white-text">not necessary</span>
                    <?php endif; ?>
                  </td>
                </tr>
              </tbody>
            </table>
            <?php if($eligible_for_upgrade === false): ?>
              <div class="notification blue lighten-5 left-align"><i class="material-icons left">info_outline</i><p>You already have version 1.2.5 of PassHub, which is what this updater upgrades to.</p></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php if($eligible_for_upgrade): ?>
      <div class="row">
        <div class="col s12 m10 l8 offset-m1 offset-l2">
          <div class="card">
            <div class="card-content">
              <span class="card-title grey-text text-darken-4">Status Log</span>
              <pre id="statusLog" class="card-panel grey lighten-4 z-depth-0"></pre>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col s12 m10 l8 offset-m1 offset-l2">
            <div id="successMessage" style="display:none" class="card-panel green lighten-4">
              <i class="material-icons left">done</i>
              <p style="overflow: hidden; margin: 0; padding: 0;">The update is complete! If no errors were reported in the Status Log, please verify that <a class="green-text text-darken-4" target="_blank" href="../">your installation</a> is working then delete the "updater" folder. If there were errors reported, feel free to <a class="green-text text-darken-4" target="_blank" href="https://codecanyon.net/user/loewenweb#contact">contact me</a> for support.</p>
            </div>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="row">
        <div class="col s12 m10 l8 offset-m1 offset-l2 center">
          <button class="waves-effect waves-light btn btn-large" id="updateButton">Update</button>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <!-- Templates -->
  <!-- White Loading Spinner -->
  <script type="text/html" id="loadingTemplateWhite">
    <div class="preloader-wrapper small active">
      <div class="spinner-layer spinner-blue-only">
        <div class="circle-clipper left">
          <div class="circle"></div>
        </div><div class="gap-patch">
          <div class="circle"></div>
        </div><div class="circle-clipper right">
          <div class="circle"></div>
        </div>
      </div>
    </div>
  </script>

  <!-- Scripts -->
  <!-- Include jQuery with local fallback -->
  <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
  <script>window.jQuery || document.write('<script src="../assets/js/jquery-2.1.4.min.js"><\/script>')</script>
  <script>
    $(function() {
      var updateInProgress = false;
      var statusLogEl = $('#statusLog');
      var updateButtonEl = $('#updateButton');
      var successMessageEl = $('#successMessage');
      var newLine = "\r\n";

      function padTwo(number) {
        return ("00" + number).substr(-2,2);
      }

      /**
       * Gets the current timestamp in H:M:S format
       */
      function getTimestamp() {
        var timestamp = '';

        var date    = new Date();
        var seconds = padTwo(date.getSeconds());
        var minutes = padTwo(date.getMinutes());
        var hours   = padTwo(date.getHours());
        timestamp   = hours + ':' + minutes + ':' + seconds;
        return timestamp;
      }

      /**
       * Adds a line to the status log with timestamp
       * @param string text
       */
      function addLogMessage(text) {
        var timestamp = getTimestamp();
        statusLogEl.append('<span class="teal-text">' + timestamp + '</span> ' + text + newLine);
      }

      /**
       * Add or remove the loading state on a button
       *
       * @param boolean show set to true to show, false to hide
       * @param object element
       */
       function setButtonLoadingState(show, el) {
        if( show === true ) {
          // Disable button
          el.addClass('disabled');
          // Add loading indicator
          var loadingIndicator = $('#loadingTemplateWhite').html();
          el.append( loadingIndicator ).addClass('expanded');
        } else {
          // Enable button
          el.removeClass('disabled');
          // Remove loading indicator
          $('.preloader-wrapper', el).remove();
          el.removeClass('expanded');
        }
       }

      // Add initial log message
      addLogMessage('Awaiting User Action...');

      // Handle update button click
      updateButtonEl.on('click', function(e) {
        e.preventDefault();
        // If the update process hasn't started, run it
        if(updateInProgress === false) {
          updateInProgress = true;
          setButtonLoadingState(true, updateButtonEl);
          var last_response_len = false;
          $.ajax('includes/run-update.php', {
            xhrFields: {
                onprogress: function(e)
                {
                    var this_response, response = e.currentTarget.response;
                    if(last_response_len === false)
                    {
                        this_response = response;
                        last_response_len = response.length;
                    }
                    else
                    {
                        this_response = response.substring(last_response_len);
                        last_response_len = response.length;
                    }
                    addLogMessage(this_response);
                }
            }
          })
            .success(function() {
              updateButtonEl.fadeOut();
              successMessageEl.slideDown();
              updateInProgress = false;
              setButtonLoadingState(false, updateButtonEl);
            });
        }
      })
    });
  </script>
</body>
</html>
