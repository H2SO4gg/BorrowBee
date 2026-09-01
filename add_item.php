<?php
include("auth_owner.php");
include("db.php");
include("functions.php");

$error = '';
$success = '';
$values = [
    'item_name'=>'', 'description'=>'', 'rental_price'=>'',
    'security_deposit'=>'', 'location'=>'', 'rental_rules'=>''
];

$uploadDir = __DIR__ . '/uploads/items/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $old) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if ($values['item_name'] === '' || !is_numeric($values['rental_price']) || !is_numeric($values['security_deposit']) || $values['location'] === '' || $values['rental_rules'] === '') {
        $error = "Complete all required fields with valid prices.";
    } elseif (!isset($_FILES['item_image']) || $_FILES['item_image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Choose an item image.";
    } elseif (!is_writable($uploadDir)) {
        $error = "Upload folder is not writable. On Mac/XAMPP run: chmod -R 775 uploads";
    } else {
        $file = $_FILES['item_image'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = [
            'image/jpeg'=>'jpg',
            'image/png'=>'png',
            'image/webp'=>'webp',
            'image/gif'=>'gif'
        ];

        if (!isset($allowed[$mime])) {
            $error = "Only JPG, PNG, WEBP or GIF images are allowed.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = "Image must be smaller than 5 MB.";
        } else {
            $fileName = 'item_' . time() . '_' . mt_rand(1000, 9999) . '.' . $allowed[$mime];
            $destination = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $error = "Could not save the uploaded image.";
            } else {
                $imagePath = 'uploads/items/' . $fileName;
                $category_id = 1;
                $status = 'available';

                mysqli_begin_transaction($conn);

                try {
                    // Original SQL keeps item_id as a normal PRIMARY KEY, not AUTO_INCREMENT.
                    // Generate the next ID in PHP instead of modifying the database schema.
                    $idResult = mysqli_query($conn, "SELECT MAX(item_id) AS max_id FROM items FOR UPDATE");
                    $idRow = mysqli_fetch_assoc($idResult);
                    $item_id = ($idRow['max_id'] === null) ? 0 : ((int)$idRow['max_id'] + 1);

                    $stmt = mysqli_prepare($conn,
                        "INSERT INTO items
                        (item_id, owner_id, category_id, item_name, description, rental_price, security_deposit, location, rental_rules, status, created_at, Image)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iiissddssss",
                        $item_id,
                        $owner_id,
                        $category_id,
                        $values['item_name'],
                        $values['description'],
                        $values['rental_price'],
                        $values['security_deposit'],
                        $values['location'],
                        $values['rental_rules'],
                        $status,
                        $imagePath
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    mysqli_commit($conn);
                    $success = "Item added successfully. Item ID: #" . $item_id;
                    foreach ($values as $key => $old) { $values[$key] = ''; }
                } catch (Throwable $ex) {
                    mysqli_rollback($conn);
                    @unlink($destination);
                    $error = "Could not add item: " . $ex->getMessage();
                }
            }
        }
    }
}

$current = 'add';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Item | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
<?php include("owner_sidebar.php"); ?>
<main class="main-content">
<header class="topbar"><div><h1>Add New Item</h1><p class="topbar-sub">Upload a photo and enter rental information.</p></div></header>
<section class="panel panel-narrow">
<?php if($error):?><div class="error-msg"><?php echo e($error); ?></div><?php endif;?>
<?php if($success):?><div class="success-msg"><?php echo e($success); ?></div><?php endif;?>
<form method="post" enctype="multipart/form-data" class="stacked-form">
<label>Item Picture</label>
<div class="image-upload-row"><div class="image-preview-box"><span id="placeholder">🖼️</span><img id="preview" alt="preview" hidden></div><input type="file" name="item_image" id="imageInput" accept="image/*" required></div>
<label>Item Name</label><input name="item_name" value="<?php echo e($values['item_name']); ?>" required>
<label>Description</label><textarea name="description"><?php echo e($values['description']); ?></textarea>
<label>Rental Price (per day)</label><input type="number" step="0.01" min="0" name="rental_price" value="<?php echo e($values['rental_price']); ?>" required>
<label>Security Deposit</label><input type="number" step="0.01" min="0" name="security_deposit" value="<?php echo e($values['security_deposit']); ?>" required>
<label>Location</label><input name="location" value="<?php echo e($values['location']); ?>" required>
<label>Rental Rules</label><textarea name="rental_rules" required><?php echo e($values['rental_rules']); ?></textarea>
<button class="btn-login">ADD ITEM</button>
</form>
</section>
</main>
</div>
<script>
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(event) {
        const image = document.getElementById('preview');
        image.src = event.target.result;
        image.hidden = false;
        document.getElementById('placeholder').hidden = true;
    };
    reader.readAsDataURL(file);
});
</script>
</body>
</html>
