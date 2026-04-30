<?php
require_once "functions/connect_db.php";
$pdo = connect_db();
if($pdo) {
    create_tables($pdo);
    seed_data($pdo);
}
$page = $_GET['page'] ?? 'home';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <?php @include "components/menu.php"; ?>
    <?php
    if(file_exists("pages/{$page}.php")) {
        include "pages/{$page}.php";
    } else {
        echo "<h1 class='text-center mt-5'>Page not found</h1>";
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>