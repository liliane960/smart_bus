<?php
require_once "../db.php";

// Handle form
if ($_SERVER['REQUEST_METHOD']=='POST'){
    if(isset($_POST['add'])){
        $conn->prepare("INSERT INTO buses(plate_number, capacity, driver_id) VALUES(?,?,?)")
             ->execute([$_POST['plate'], $_POST['capacity'], $_POST['driver_id']]);
    }
    if(isset($_POST['delete'])){
        $conn->prepare("DELETE FROM buses WHERE bus_id=?")->execute([$_POST['bus_id']]);
    }
}

// Fetch buses & drivers
$buses = $conn->query("SELECT * FROM buses")->fetchAll(PDO::FETCH_ASSOC);
$drivers = $conn->query("SELECT * FROM drivers")->fetchAll(PDO::FETCH_ASSOC);
?>
<html>
<head><link rel="stylesheet" href="../assets/style.css"></head>
<body>
<h2>Manage Buses</h2>
<form method="POST">
    Plate: <input name="plate">
    Capacity: <input name="capacity" type="number">
    Driver:
    <select name="driver_id"><?php foreach($drivers as $d){ echo "<option value='{$d['driver_id']}'>{$d['name']}</option>"; }?></select>
    <button name="add">Add Bus</button>
</form>
<table>
<tr><th>ID</th><th>Plate</th><th>Capacity</th><th>Action</th></tr>
<?php foreach($buses as $b): ?>
<tr>
<td><?=$b['bus_id']?></td><td><?=$b['plate_number']?></td><td><?=$b['capacity']?></td>
<td>
<form method="POST">
    <input type="hidden" name="bus_id" value="<?=$b['bus_id']?>">
    <button name="delete">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
</body></html>
