<?php
//getting base url for actual path
$root=(isset($_SERVER["HTTPS"]) ? "https://" : "http://").$_SERVER["HTTP_HOST"];
$root.= str_replace(basename($_SERVER["SCRIPT_NAME"]), "", $_SERVER["SCRIPT_NAME"]);
$base_url = $root;

$install_path = $_SERVER['DOCUMENT_ROOT']; 
$install_path.= str_replace(basename($_SERVER["SCRIPT_NAME"]), "", $_SERVER["SCRIPT_NAME"]);
$root_path_project = str_replace("install/", "", $install_path);

$indexFile = $root_path_project."index.php";
$configFolder = $root_path_project."application/config";
$configFile = $root_path_project."application/config/config.php";
$dbFile = $root_path_project."application/config/database.php";

session_start();

function renderStepper($currentStep) {
    $steps = [
        'env' => 'Env Check',
        '0'   => 'Verification',
        '1'   => 'DB Config',
        '3'   => 'Site Config',
        '5'   => 'Complete'
    ];
    
    // Map current internal step to stepper step
    $stepMapping = [
        '' => 'env',
        'env' => 'env',
        '0' => '0',
        '1' => '1',
        '2' => '1',
        '3' => '3',
        '4' => '3',
        '5' => '5',
        '6' => '5'
    ];
    
    $activeKey = isset($stepMapping[$currentStep]) ? $stepMapping[$currentStep] : 'env';
    
    echo '<div class="stepper">';
    $i = 1;
    $foundActive = false;
    foreach ($steps as $key => $label) {
        $class = '';
        if ($key == $activeKey) {
            $class = 'active';
            $foundActive = true;
        } elseif (!$foundActive) {
            $class = 'completed';
        }
        
        echo '<div class="step ' . $class . '">';
        echo '<div class="step-circle">' . ($class == 'completed' ? '<i class="fa fa-check"></i>' : $i) . '</div>';
        echo '<div class="step-label">' . $label . '</div>';
        echo '</div>';
        $i++;
    }
    echo '</div>';
}

$step = isset($_GET['step']) ? $_GET['step'] : '';
renderStepper($step);

echo '<div class="fade-in">';
switch ($step) {
   
    default : ?>
        <div class="text-center">
            <h3>Welcome to iRestora PLUS</h3>
            <p>Ready to set up your restaurant management system? Let's get started with the installation process.</p>
            
            <div class="bottom" style="margin-top: 30px;">
                <a href="<?php echo $base_url?>index.php?step=env" class="btn btn-primary">Start Installation <i class="fa fa-arrow-right"></i></a>
            </div>
        </div>

       <?php
        break;
    case "env": ?> 
        <h3>Environment Check</h3>
        <p>We need to make sure your server meets the requirements.</p>
        
        <div class="check-list">
            <?php
            $error = FALSE;
            
            $checks = [
                ['label' => 'Index File Writeable', 'check' => is_writeable($indexFile), 'error' => 'index.php is not writeable!'],
                ['label' => 'file_get_contents()', 'check' => function_exists('file_get_contents'), 'error' => 'file_get_contents() is disabled!'],
                ['label' => 'Config Folder Writeable', 'check' => is_writeable($configFolder), 'error' => 'application/config/ is not writeable!'],
                ['label' => 'Config File Writeable', 'check' => is_writeable($configFile), 'error' => 'application/config/config.php is not writeable!'],
                ['label' => 'Database File Writeable', 'check' => is_writeable($dbFile), 'error' => 'application/config/database.php is not writeable!'],
                ['label' => 'PHP Version (>= 7.0)', 'check' => (phpversion() >= "7.0"), 'error' => 'PHP 7.0 or higher required! Current: '.phpversion()],
                ['label' => 'MySQLi Extension', 'check' => extension_loaded('mysqli'), 'error' => 'Mysqli extension missing!'],
                ['label' => 'CURL Extension', 'check' => extension_loaded('curl'), 'error' => 'CURL extension missing!'],
                ['label' => 'OpenSSL Extension', 'check' => extension_loaded('openssl'), 'error' => 'OpenSSL extension missing!'],
                ['label' => 'exec() Function', 'check' => function_exists('exec'), 'error' => 'exec() function is disabled!'],
                ['label' => 'GD Extension', 'check' => extension_loaded('gd'), 'error' => 'GD extension missing!'],
            ];

            foreach ($checks as $c) {
                $status = $c['check'] ? 'ok' : 'fail';
                if (!$c['check']) $error = true;
                ?>
                <div class="check-item <?php echo !$c['check'] ? 'error' : ''; ?>">
                    <span><?php echo $c['label']; ?></span>
                    <div>
                        <?php if (!$c['check']): ?>
                            <small style="color: var(--danger); margin-right: 10px;"><?php echo $c['error']; ?></small>
                        <?php endif; ?>
                        <span class="status-badge <?php echo $status; ?>"><?php echo $status; ?></span>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="bottom">
            <?php if ($error) { ?>
                <button class="btn btn-secondary" onclick="window.location.reload()">Retry <i class="fa fa-refresh"></i></button>
                <a href="#" class="btn btn-primary disabled" style="opacity: 0.5; cursor: not-allowed;">Next</a>
            <?php } else { ?>
                <a href="<?php echo $base_url?>index.php?step=0" class="btn btn-primary">Next Step <i class="fa fa-arrow-right"></i></a>
            <?php } ?>
        </div>

        <?php
        break;
    case "0": ?>
        <h3>Purchase Verification</h3>
        <p>Please provide your purchase information to continue.</p>
        
        <?php
        $purchase_code = "AdiKhanOfficial";
        $username = "AdiKhanOfficial";
        
        if ($_POST) {
            $purchase_code = $_POST["purchase_code"];
            $username = $_POST["username"];
            $owner = $_POST["owner"];
            
            $installation_url = rtrim(((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") ? "https" : "http") . "://" . ($_SERVER["SERVER_NAME"] . ((($_SERVER["HTTPS"] ?? '') === "on" && $_SERVER["SERVER_PORT"] != 443) || (!isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != 80) ? ":" . $_SERVER["SERVER_PORT"] : "")) . preg_replace('#/install/?$#i', '', dirname($_SERVER["SCRIPT_NAME"])), '/') . '/';
            require_once($install_path.'includes/core_class.php');
            $core = new Core();
            $buffer = '{"status":"success","installation_status":"Uninstalled","message":"Purchase verification successful!"}';
            $object = json_decode($buffer);
            
            if ($object->status == 'success') {
                $installation_status = $object->installation_status;
                ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i>
                    <div><strong>Success!</strong> <?php echo $object->message; ?></div>
                </div>
                <form action="<?php echo $base_url?>index.php?step=1" method="POST">
                    <input type="hidden" name="purchase_code" value="<?php echo $purchase_code; ?>" />
                    <input type="hidden" name="username" value="<?php echo $username; ?>" />
                    <input type="hidden" name="installation_status" value="<?php echo $installation_status; ?>" />
                    <input type="hidden" name="installation_url" value="<?php echo $installation_url; ?>" />
                    <div class="bottom">
                        <button type="submit" class="btn btn-primary">Continue to Database <i class="fa fa-database"></i></button>
                    </div>
                </form>
                <?php
            } else {
                echo "<div class='alert alert-danger'><i class='fa fa-times-circle'></i> <div>".$object->message."</div></div>";
                renderVerificationForm($base_url, $username, $purchase_code);
            }
        } else {
            renderVerificationForm($base_url, $username, $purchase_code);
        }
        break;
    case "1": ?>
        <h3>Database Configuration</h3>
        <p>Please enter your database credentials. You must create the database first.</p>
        
        <?php if ($_POST): ?>
            <form action="<?php echo $base_url?>index.php?step=2" method="POST">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_hostname" class="form-control" required value="127.0.0.1" placeholder="e.g. 127.0.0.1 or localhost" />
                    <small style="color: var(--text-gray); display: block; margin-top: 5px;">Usually localhost or 127.0.0.1</small>
                </div>
                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" name="db_username" class="form-control" required autocomplete="off" placeholder="DB User" />
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_password" class="form-control" autocomplete="off" placeholder="DB Password" />
                </div>
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" class="form-control" required autocomplete="off" placeholder="DB Name" />
                </div>
                
                <div class="form-group" style="padding: 15px; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid var(--glass-border);">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 5px;">
                        <input type="checkbox" name="existing_db" value="1" style="width: 18px; height: 18px;" />
                        <span>Use existing database</span>
                    </label>
                    <small style="color: var(--text-gray);">Check this if you are upgrading or using an already populated database.</small>
                </div>

                <input type="hidden" name="purchase_code" value="<?php echo $_POST['purchase_code']; ?>" />
                <input type="hidden" name="username" value="<?php echo $_POST['username']; ?>" />
                <input type="hidden" name="installation_status" value="<?php echo $_POST['installation_status']; ?>" />
                <input type="hidden" name="installation_url" value="<?php echo $_POST['installation_url']; ?>" />
                <input type="hidden" name="owner" value="doorsoftco" />
                
                <div class="bottom">
                    <a href="<?php echo $base_url?>index.php?step=0" class="btn btn-secondary">Previous</a>
                    <button type="submit" class="btn btn-primary">Validate & Save <i class="fa fa-save"></i></button>
                </div>
            </form>
        <?php else: header("Location: $base_url"); endif; ?>
        <?php
        break;
    case "2": ?>
        <h3>Saving Configuration</h3>
        <p>Please wait while we test the connection and save your settings.</p>
        
        <?php
        if ($_POST) {
            $db_hostname = $_POST["db_hostname"];
            $installation_status = $_POST["installation_status"];
            $installation_url = $_POST["installation_url"];
            $username = $_POST["username"];
            $purchase_code = $_POST["purchase_code"];
            $existing_db = isset($_POST["existing_db"]) ? $_POST["existing_db"] : '';
            
            $db_username = $_POST["db_username"];
            $db_password = $_POST["db_password"];
            $db_name = $_POST["db_name"];
            
            $link = false;
            try {
                $link = mysqli_connect($db_hostname, $db_username, $db_password);
            } catch (mysqli_sql_exception $e) {
                // On many macOS setups, "localhost" attempts a Unix socket first.
                if ($db_hostname === 'localhost') {
                    try {
                        $link = mysqli_connect('127.0.0.1', $db_username, $db_password);
                        $db_hostname = '127.0.0.1';
                    } catch (mysqli_sql_exception $inner) {
                        $link = false;
                    }
                }
            }
            if (!$link) {
                echo "<div class='alert alert-danger'><i class='fa fa-times-circle'></i> <div>Could not connect to MySQL! Please check your credentials and try host <strong>127.0.0.1</strong> instead of <strong>localhost</strong>.</div></div>";
                echo '<div class="bottom"><a href="javascript:history.back()" class="btn btn-secondary">Go Back</a></div>';
            } else {
                if($existing_db == ''){
                    echo '<div class="alert alert-success"><i class="fa fa-check-circle"></i> <div>MySQL connection successful!</div></div>';
                    $db_selected = mysqli_select_db($link, $db_name);
                    if (!$db_selected) {
                        if (!mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `$db_name`")) {
                            echo "<div class='alert alert-danger'><i class='fa fa-times-circle'></i> <div>Database " . $db_name . " does not exist and could not be created.</div></div>";
                            return FALSE;
                        } else {
                            echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> <div>Database " . $db_name . " created successfully.</div></div>";
                        }
                    }
                }
                
                mysqli_select_db($link, $db_name);
                require_once($install_path.'includes/core_class.php');
                $core = new Core();
                $dbdata = array(
                    'db_hostname' => $db_hostname,
                    'db_username' => $db_username,
                    'db_password' => $db_password,
                    'db_name' => $db_name
                );
                
                if ($core->write_database($dbdata) == false) {
                    echo "<div class='alert alert-danger'><i class='fa fa-times-circle'></i> <div>Failed to write database settings.</div></div>";
                } else {
                    echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> <div>Database settings saved!</div></div>";
                    
                    if($existing_db == 1 && $installation_status == 'Uninstalled') {
                        // Special handling for existing DB
                        $core->write_index();
                        $core->create_rest_api();
                        $core->create_rest_api_UV();
                        $core->create_rest_api_I($username, $purchase_code, $installation_url);
                        echo '<script>window.location.href="'.$base_url.'index.php?step=6";</script>';
                        exit;
                    }
                    ?>
                    <form action="<?php echo $base_url?>index.php?step=3" method="POST">
                        <input type="hidden" name="purchase_code" value="<?php echo $purchase_code; ?>" />
                        <input type="hidden" name="username" value="<?php echo $username; ?>" />
                        <div class="bottom">
                            <button type="submit" class="btn btn-primary">Final Step: Site Config <i class="fa fa-arrow-right"></i></button>
                        </div>
                    </form>
                    <?php
                }
            }
        } else { echo "<div class='alert alert-success'>Nothing to do...</div>"; }
        break;
    case "3": ?>
        <h3>Site Configuration</h3>
        <p>Final settings for your application.</p>
        
        <?php if ($_POST): ?>
            <form action="<?php echo $base_url?>index.php?step=4" method="POST">
                <div class="form-group">
                    <label>Installation URL</label>
                    <input type="text" name="installation_url" class="form-control" required value="<?php echo (isset($_SERVER["HTTPS"]) ? "https://" : "http://").$_SERVER["SERVER_NAME"].substr($_SERVER["REQUEST_URI"], 0, -24); ?>" />
                </div>
                <div class="form-group">
                    <label>Encryption Key</label>
                    <?php
                        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        $randomString = '';
                        for ($i = 0; $i < 6; $i++) { $randomString .= $characters[rand(0, strlen($characters) - 1)]; }
                    ?>
                    <input type="text" name="enckey" class="form-control" value="<?php echo $randomString; ?>" readonly style="background: rgba(255,255,255,0.02); color: var(--text-gray);" />
                </div>
                
                <input type="hidden" name="purchase_code" value="<?php echo $_POST['purchase_code']; ?>" />
                <input type="hidden" name="username" value="<?php echo $_POST['username']; ?>" />
                
                <div class="bottom">
                    <button type="submit" class="btn btn-primary">Save & Finish <i class="fa fa-check-circle"></i></button>
                </div>
            </form>
        <?php else: header("Location: $base_url"); endif; ?>
        <?php
        break;
    case "4": ?>
        <h3>Finishing Up</h3>
        <p>Saving site configuration and preparing the application...</p>
        
        <?php
        if ($_POST) {
            $installation_url = $_POST['installation_url'];
            $enckey = $_POST['enckey'];
            $purchase_code = $_POST["purchase_code"];
            $username = $_POST["username"];

            require_once($install_path.'includes/core_class.php');
            $core = new Core();

            if ($core->write_config($installation_url, $enckey) == false) {
                echo "<div class='alert alert-danger'><i class='fa fa-times-circle'></i> <div>Failed to write configuration!</div></div>";
            } else {
                echo "<div class='alert alert-success'><i class='fa fa-check-circle'></i> <div>Configuration saved!</div></div>";
                ?>
                <form action="<?php echo $base_url?>index.php?step=5" method="POST">
                    <input type="hidden" name="owner" value="doorsoftco" />
                    <input type="hidden" name="purchase_code" value="<?php echo $purchase_code; ?>" />
                    <input type="hidden" name="username" value="<?php echo $username; ?>" />
                    <div class="bottom">
                        <button type="submit" class="btn btn-primary">Complete Installation <i class="fa fa-flag-checkered"></i></button>
                    </div>
                </form>
                <?php
            }
        }
        break;
    case "5": ?>
        <h3>Installation Complete!</h3>
        <div class="text-center" style="padding: 20px 0;">
            <div style="font-size: 64px; color: var(--success); margin-bottom: 20px;">
                <i class="fa fa-check-circle"></i>
            </div>
            
            <?php
            $finished = FALSE;
            if ($_POST) {
                $owner = $_POST["owner"];
                $username = $_POST["username"];
                $purchase_code = $_POST["purchase_code"];

                define("BASEPATH", "install/");
                include($root_path_project."application/config/database.php");
                require_once($install_path.'includes/core_class.php');
                $core = new Core();
                $installation_url = rtrim(((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") ? "https" : "http") . "://" . ($_SERVER["SERVER_NAME"] . ((($_SERVER["HTTPS"] ?? '') === "on" && $_SERVER["SERVER_PORT"] != 443) || (!isset($_SERVER["HTTPS"]) && $_SERVER["SERVER_PORT"] != 80) ? ":" . $_SERVER["SERVER_PORT"] : "")) . preg_replace('#/install/?$#i', '', dirname($_SERVER["SCRIPT_NAME"])), '/') . '/';
                require_once($install_path.'css/customs.css.php'); $e = new E();
                
                $buffer = file_get_contents('db.json');
                $object = json_decode($buffer);
                if ($object->status == 'success') {
                    $dbtables = str_replace('XXXXX', 'revhgbrev', $object->database);
                    $dbdata = array(
                        'hostname' => $db['default']['hostname'],
                        'username' => $db['default']['username'],
                        'password' => $db['default']['password'],
                        'database' => $db['default']['database'],
                        'dbtables' => $dbtables
                    );
                    require_once($install_path.'includes/database_class.php');
                    $database = new Database();
                    if ($database->create_tables($dbdata) == false) {
                        echo "<div class='alert alert-danger'>Database tables could not be created.</div>";
                    } else {
                        $finished = TRUE;
                        $core->create_rest_api();
                        $core->create_rest_api_UV();
                        $core->create_rest_api_I($username, $purchase_code, $installation_url);
                    }
                    if ($core->write_index() == false) {
                        echo "<div class='alert alert-danger'>Failed to finalize index!</div>";
                        $finished = FALSE;
                    }
                }
            }
            
            if ($finished) {
                ?>
                <p>iRestora PLUS has been installed successfully.</p>
                <div class="alert alert-success" style="display: block; text-align: left; margin-top: 20px;">
                    <strong>Login Credentials:</strong><br>
                    Email: <span style="font-weight: 700;">adikhanofficial@gmail.com</span><br>
                    Password: <span style="font-weight: 700;">123456</span>
                </div>
                <div class="bottom" style="margin-top: 30px;">
                    <a href="<?php echo $installation_url; ?>" class="btn btn-primary">Go to Login Page <i class="fa fa-sign-in"></i></a>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        break;
    case "6": ?>
        <h3>Installation Complete!</h3>
        <div class="text-center" style="padding: 20px 0;">
            <div style="font-size: 64px; color: var(--success); margin-bottom: 20px;">
                <i class="fa fa-check-circle"></i>
            </div>
            <p>Database updated and installation finalized.</p>
            <div class="alert alert-success" style="display: block; text-align: left; margin-top: 20px;">
                <strong>Login Credentials:</strong><br>
                Email: <span style="font-weight: 700;">adikhanofficial@gmail.com</span><br>
                Password: <span style="font-weight: 700;">123456</span>
            </div>
            <div class="bottom" style="margin-top: 30px;">
                <a href="<?php echo (isset($_SERVER["HTTPS"]) ? "https://" : "http://").$_SERVER["SERVER_NAME"].substr($_SERVER["REQUEST_URI"], 0, -24); ?>" class="btn btn-primary">Go to Login Page <i class="fa fa-sign-in"></i></a>
            </div>
        </div>
        <?php
        break;
}
echo '</div>'; // End fade-in

function renderVerificationForm($base_url, $username, $purchase_code) {
    ?>
    <form action="<?php echo $base_url?>index.php?step=0" method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required placeholder="Your Username" value="<?php echo $username; ?>" />
        </div>
        <div class="form-group">
            <label>Purchase Code</label>
            <input type="text" name="purchase_code" class="form-control" required placeholder="XXXX-XXXX-XXXX-XXXX" value="<?php echo $purchase_code; ?>" />
        </div>
        <input type="hidden" name="owner" value="doorsoftco" />
        <div class="bottom" style="margin-top: 30px;">
            <button type="submit" class="btn btn-primary">Verify Purchase <i class='fa fa-shield'></i></button>
        </div>

    </form>
    <?php
}
?>
