<?php
require_once "../db.php";
$notifs = $conn->query("
    SELECT n.*, b.plate_number 
    FROM notifications n JOIN buses b ON n.bus_id = b.bus_id 
    ORDER BY n.sent_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<html>
<head><link rel="stylesheet" href="../assets/style.css"></head>
<body>
<h2>Notifications</h2>
<table>
<tr><th>Plate</th><th>Message</th><th>Status</th><th>Time</th></tr>
<?php foreach($notifs as $n): ?>
<tr>
<td><?=$n['plate_number']?></td>
<td><?=$n['message']?></td>
<td><?=$n['status']?></td>
<td><?=$n['sent_at']?></td>
</tr>
<?php endforeach; ?>
</table>
<a href="index.php">← Back</a>
</body></html>
