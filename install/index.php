<?php
session_start();
$root = dirname(__DIR__);
if (file_exists($root . '/core/config.php') && strpos(file_get_contents($root . '/core/config.php'), '{{') === false) {
    header('Location: ../admin/'); exit;
}
$step = (int)($_GET['step'] ?? 1); $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $d = ['host'=>trim($_POST['db_host']??'localhost'),'name'=>trim($_POST['db_name']??''),'user'=>trim($_POST['db_user']??''),'pass'=>$_POST['db_pass']??'','prefix'=>trim($_POST['db_prefix']??'c3_')];
    try {
        $pdo = new PDO("mysql:host={$d['host']}",$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$d['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $_SESSION['c3_db'] = $d;
        // also encode into a token so step 3 works even if sessions fail
        $dbToken = base64_encode(json_encode($d));
        header('Location:?step=3&dbt=' . urlencode($dbToken));
        exit;
    }
    catch(PDOException $e) { $error = $e->getMessage(); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {
    // try session first, fall back to hidden field token
    if (isset($_SESSION['c3_db'])) {
        $d = $_SESSION['c3_db'];
    } elseif (!empty($_POST['dbt'])) {
        $d = json_decode(base64_decode($_POST['dbt']), true);
    } else {
        header('Location:?step=2');
        exit;
    }
    if (!$d || empty($d['name'])) { header('Location:?step=2'); exit; } $sn=trim($_POST['site_name']??''); $su=rtrim(trim($_POST['site_url']??''),'/');
    $au=trim($_POST['admin_user']??''); $ae=trim($_POST['admin_email']??''); $ap=$_POST['admin_pass']??''; $ap2=$_POST['admin_pass2']??'';
    if(!$au||!$ae||!$ap) $error='All fields are required.';
    elseif($ap!==$ap2) $error="Passwords don't match.";
    elseif(strlen($ap)<6) $error='Password must be at least 6 characters.';
    else {
        try {
            $pdo=new PDO("mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4",$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); $p=$d['prefix'];
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}users(id INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50) NOT NULL UNIQUE,email VARCHAR(100) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,display_name VARCHAR(100),role ENUM('admin','editor','author','subscriber') DEFAULT 'subscriber',status ENUM('active','inactive','banned') DEFAULT 'active',bio TEXT,reset_token VARCHAR(64),reset_expires DATETIME,last_login DATETIME,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}posts(id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(255) NOT NULL,slug VARCHAR(255) NOT NULL UNIQUE,content LONGTEXT,excerpt TEXT,content_format ENUM('html','markdown') DEFAULT 'html',featured_image VARCHAR(255),status ENUM('published','draft','trash') DEFAULT 'draft',allow_comments TINYINT(1) DEFAULT 1,author_id INT NOT NULL,category_id INT,views INT DEFAULT 0,published_at DATETIME,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(author_id) REFERENCES {$p}users(id) ON DELETE CASCADE)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}categories(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,slug VARCHAR(100) NOT NULL UNIQUE,description TEXT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}comments(id INT AUTO_INCREMENT PRIMARY KEY,post_id INT NOT NULL,user_id INT,author_name VARCHAR(100) NOT NULL,author_email VARCHAR(100) NOT NULL,content TEXT NOT NULL,status ENUM('approved','pending','spam') DEFAULT 'pending',ip_address VARCHAR(45),created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(post_id) REFERENCES {$p}posts(id) ON DELETE CASCADE)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}pages(id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(255) NOT NULL,slug VARCHAR(255) NOT NULL UNIQUE,content LONGTEXT,content_format ENUM('html','markdown') DEFAULT 'html',status ENUM('published','draft') DEFAULT 'published',show_in_nav TINYINT(1) DEFAULT 1,sort_order INT DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}settings(id INT AUTO_INCREMENT PRIMARY KEY,`key` VARCHAR(100) NOT NULL UNIQUE,`value` TEXT,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$p}widgets(id INT AUTO_INCREMENT PRIMARY KEY,zone VARCHAR(30) NOT NULL DEFAULT 'sidebar',type VARCHAR(50) NOT NULL,title VARCHAR(100),config TEXT,active TINYINT(1) DEFAULT 1,sort_order INT DEFAULT 0)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $h=password_hash($ap,PASSWORD_BCRYPT,['cost'=>12]); $pdo->prepare("INSERT INTO {$p}users(username,email,password,display_name,role,status) VALUES(?,?,?,?,'admin','active')")->execute([$au,$ae,$h,$au]);
            $ins=$pdo->prepare("INSERT INTO {$p}settings(`key`,`value`) VALUES(?,?)");
            foreach(['site_name'=>$sn,'site_description'=>'A blog powered by Core 3 CMS','site_tagline'=>'','posts_per_page'=>'10','theme'=>'default','timezone'=>'UTC','date_format'=>'M d, Y','comments_enabled'=>'1','comments_moderation'=>'1','registration_enabled'=>'0','default_role'=>'subscriber','mail_method'=>'phpmail','mail_from'=>$ae,'smtp_host'=>'','smtp_port'=>'587','smtp_user'=>'','smtp_pass'=>'','smtp_encryption'=>'tls','meta_description'=>'','meta_keywords'=>'','module_sitemap'=>'1','module_analytics'=>'1'] as $k=>$v) $ins->execute([$k,$v]);
            $pdo->exec("INSERT INTO {$p}categories(name,slug,description) VALUES('Uncategorized','uncategorized','Default category')");
            $pdo->prepare("INSERT INTO {$p}posts(title,slug,content,excerpt,status,allow_comments,author_id,category_id,published_at) VALUES(?,?,?,?,'published',1,1,1,NOW())")->execute(['Welcome to Core 3 CMS','welcome-to-core-3-cms','<p>Welcome! This is your first post. Edit or delete it from the admin panel.</p>','Welcome to your new blog powered by Core 3 CMS.']);
            $pdo->prepare("INSERT INTO {$p}pages(title,slug,content,status,show_in_nav,sort_order) VALUES(?,?,?,?,?,?)")->execute(['About','about','<p>This is your about page. Edit it from the admin panel.</p>','published',1,0]);
            $wi=$pdo->prepare("INSERT INTO {$p}widgets(zone,type,title,config,active,sort_order) VALUES(?,?,?,?,1,?)"); $wi->execute(['sidebar','search','','{}',0]); $wi->execute(['sidebar','recent_posts','Recent Posts','{"limit":5}',1]); $wi->execute(['sidebar','categories','Categories','{}',2]);
            $salt=bin2hex(random_bytes(32)); $cfg=file_get_contents($root.'/core/config.sample.php');
            $cfg=str_replace(['{{DB_HOST}}','{{DB_NAME}}','{{DB_USER}}','{{DB_PASS}}','{{DB_PREFIX}}','{{SITE_URL}}','{{AUTH_SALT}}'],[$d['host'],$d['name'],$d['user'],$d['pass'],$d['prefix'],$su,$salt],$cfg);
            file_put_contents($root.'/core/config.php',$cfg);

            // Generate .htaccess with correct RewriteBase
            $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
            $rewriteBase = $base ? $base . '/' : '/';
            $htaccess = "# Core 3 CMS\n"
                . "<IfModule mod_rewrite.c>\n"
                . "RewriteEngine On\n"
                . "RewriteBase {$rewriteBase}\n\n"
                . "RewriteRule ^core/ - [F,L]\n"
                . "RewriteRule ^content/uploads/.*\\.ph(p[345s]?|tml)$ - [F,L]\n\n"
                . "RewriteRule ^index\\.php$ - [L]\n"
                . "RewriteRule ^admin/index\\.php$ - [L]\n"
                . "RewriteRule ^install/ - [L]\n\n"
                . "RewriteCond %{REQUEST_FILENAME} !-f\n"
                . "RewriteRule ^admin(/.*)?$ admin/index.php [L,QSA]\n\n"
                . "RewriteCond %{REQUEST_FILENAME} !-f\n"
                . "RewriteRule . index.php [L]\n"
                . "</IfModule>\n";
            file_put_contents($root.'/.htaccess', $htaccess);

            unset($_SESSION['c3_db']); header('Location:?step=4'); exit;
        } catch(PDOException $e) { $error=$e->getMessage(); }
    }
}
$proto=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
$detected=$proto.'://'.$_SERVER['HTTP_HOST'].rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])),'/');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installation ‹ Core 3 CMS</title>
<link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg"><link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;background:#f0f0f1;color:#1e1e1e;font-size:14px;line-height:1.6;-webkit-font-smoothing:subpixel-antialiased}
.wrap{max-width:520px;margin:0 auto;padding:40px 20px}
.logo{text-align:center;margin-bottom:28px}.logo img{height:56px}
.steps{display:flex;gap:2px;margin-bottom:24px}
.steps div{flex:1;text-align:center;padding:8px 0;font-size:12px;font-weight:600;color:#787c82;background:#dcdcde;border-radius:3px}
.steps .done{background:#00a32a;color:#fff}.steps .active{background:#2271b1;color:#fff}
.card{background:#fff;border:1px solid #c3c4c7;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04);margin-bottom:16px}
.card-bd{padding:24px}
.card-bd p.intro{color:#646970;margin-bottom:20px}
label{display:block;font-size:14px;font-weight:600;margin-bottom:6px}
input{width:100%;padding:0 10px;height:40px;border:1px solid #8c8f94;border-radius:4px;font-size:16px;font-family:inherit;margin-bottom:16px;color:#1e1e1e}
input:focus{outline:none;border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}
.hint{font-size:13px;color:#787c82;margin-top:-12px;margin-bottom:16px;font-style:italic}
.btn{display:block;width:100%;padding:12px;background:#2271b1;color:#fff;border:none;border-radius:4px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;text-align:center;text-decoration:none;transition:.1s}
.btn:hover{background:#135e96}
.btn-green{background:#00a32a}.btn-green:hover{background:#008a20}
.alert{padding:12px 16px;margin-bottom:16px;border-left:4px solid;background:#fff;box-shadow:0 1px 1px rgba(0,0,0,.04);font-size:13px;border-radius:0 4px 4px 0}
.alert-error{border-color:#d63638}.alert-warning{border-color:#dba617;background:#fcf9e8}.alert-success{border-color:#00a32a}
hr{border:none;border-top:1px solid #dcdcde;margin:24px 0}
.req{list-style:none}.req li{padding:10px 0;border-bottom:1px solid #f0f0f1;display:flex;justify-content:space-between;font-size:14px}.req li:last-child{border:0}
.pass{color:#00a32a;font-weight:600}.fail{color:#d63638;font-weight:600}
.success-box{text-align:center;padding:40px 24px}
.success-box h2{font-size:24px;font-weight:400;margin-bottom:8px}
.success-box p{color:#646970;margin-bottom:24px}
.links{display:flex;gap:12px}.links a{flex:1;text-align:center;padding:12px;border-radius:4px;font-weight:600;text-decoration:none;font-size:14px}
.links .pri{background:#2271b1;color:#fff}.links .pri:hover{background:#135e96}
.links .sec{background:#f6f7f7;border:1px solid #c3c4c7;color:#1e1e1e}.links .sec:hover{background:#f0f0f1}
</style></head><body>
<div class="wrap">
<div class="logo"><img src="../assets/images/logo.svg" alt="Core 3 CMS" onerror="this.outerHTML='<h1 style=font-size:24px;font-weight:400>Core 3 CMS</h1>'"></div>
<div class="steps"><?php for($i=1;$i<=4;$i++):?><div class="<?=$i<$step?'done':($i===$step?'active':'')?>"><?= ['','Check','Database','Setup','Done'][$i] ?></div><?php endfor;?></div>
<?php if($error):?><div class="alert alert-error"><?=htmlspecialchars($error)?></div><?php endif;?>

<?php if($step===1): $checks=['PHP ≥ 7.4'=>version_compare(PHP_VERSION,'7.4.0','>='),'PDO MySQL'=>extension_loaded('pdo_mysql'),'mbstring'=>extension_loaded('mbstring'),'JSON'=>extension_loaded('json'),'core/ writable'=>is_writable($root.'/core'),'uploads/ writable'=>is_writable($root.'/content/uploads')]; $ok=!in_array(false,$checks); ?>
<div class="card"><div class="card-bd">
<p class="intro">Before we begin, we need to check your server meets the minimum requirements.</p>
<ul class="req"><?php foreach($checks as $l=>$v):?><li><?=$l?><span class="<?=$v?'pass':'fail'?>"><?=$v?'✓ Pass':'✗ Failed'?></span></li><?php endforeach;?></ul>
<?php if($ok):?><a href="?step=2" class="btn">Let's Go!</a><?php else:?><div class="alert alert-error">Please fix the failed requirements.</div><?php endif;?>
</div></div>

<?php elseif($step===2):?>
<div class="card"><div class="card-bd">
<p class="intro">Enter your database connection details below. If you're not sure about these, contact your web host.</p>
<form method="post" action="?step=2">
<label>Database Host</label><input type="text" name="db_host" value="localhost" required>
<label>Database Name</label><input type="text" name="db_name" placeholder="core3cms" required><p class="hint">The database will be created if it doesn't exist.</p>
<label>Database Username</label><input type="text" name="db_user" required>
<label>Database Password</label><input type="password" name="db_pass">
<label>Table Prefix</label><input type="text" name="db_prefix" value="c3_"><p class="hint">If you want to run multiple Core 3 installations in a single database, change this.</p>
<button type="submit" class="btn">Submit</button>
</form></div></div>

<?php elseif($step===3):
    // grab the token from the URL to embed in the form
    $dbToken = isset($_GET['dbt']) ? $_GET['dbt'] : '';
?>
<div class="card"><div class="card-bd">
<p class="intro">Welcome! Just fill in the information below and you'll be on your way.</p>
<form method="post" action="?step=3">
<input type="hidden" name="dbt" value="<?= htmlspecialchars($dbToken) ?>">
<label>Site Title</label><input type="text" name="site_name" value="My Blog" required>
<label>Site URL</label><input type="text" name="site_url" value="<?=htmlspecialchars($detected)?>" required><p class="hint">No trailing slash.</p>
<hr>
<label>Username</label><input type="text" name="admin_user" required>
<label>Email</label><input type="email" name="admin_email" required><p class="hint">Double-check your email address before continuing.</p>
<label>Password</label><input type="password" name="admin_pass" required>
<label>Confirm Password</label><input type="password" name="admin_pass2" required>
<button type="submit" class="btn btn-green">Install Core 3 CMS</button>
</form></div></div>

<?php elseif($step===4):?>
<div class="card"><div class="success-box">
<h2>Success!</h2>
<p>Core 3 CMS has been installed. You're ready to start publishing.</p>
<div class="links"><a href="../admin/login" class="pri">Log In</a><a href="../" class="sec">View Your Site</a></div>
</div></div>
<div class="alert alert-warning"><strong>Important:</strong> You should delete the <code>/install</code> directory now. Leaving it accessible is a security risk.</div>
<?php endif;?>
</div></body></html>
