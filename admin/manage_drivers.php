<?php
require_once "../db.php";

if ($_SERVER['REQUEST_METHOD']=='POST'){
    if(isset($_POST['add'])){
        $conn->prepare("INSERT INTO drivers(name, phone) VALUES(?,?)")
             ->execute([$_POST['name'], $_POST['phone']]);
    }
    if(isset($_POST['delete'])){
        $conn->prepare("DELETE FROM drivers WHERE driver_id=?")->execute([$_POST['driver_id']]);
    }
}

$drivers = $conn->query("SELECT * FROM drivers")->fetchAll(PDO::FETCH_ASSOC);
?>
<html>
<head><link rel="stylesheet" href="../assets/style.css"></head>
<body>
<h2>Manage Drivers</h2>
<form method="POST">
    Name: <input name="name">
    Phone: <input name="phone">
    <button name="add">Add Driver</button>
</form>
<table>
<tr><th>ID</th><th>Name</th><th>Phone</th><th>Action</th></tr>
<?php foreach($drivers as $d): ?>
<tr>
<td><?=$d['driver_id']?></td><td><?=$d['name']?></td><td><?=$d['phone']?></td>
<td>
<form method="POST">
    <input type="hidden" name="driver_id" value="<?=$d['driver_id']?>">
    <button name="delete">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<a href="index.php">← Back</a>
</body></html>
