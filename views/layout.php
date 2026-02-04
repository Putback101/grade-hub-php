<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Grade Hub'; ?></title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/tailwind.css">
</head>
<body>
    <div id="root">
        <?php echo $content ?? ''; ?>
    </div>
    <script src="./assets/js/main.js"></script>
</body>
</html>
