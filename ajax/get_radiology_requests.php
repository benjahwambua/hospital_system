<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_GET['patient_id']);

$rad = $conn->query("SELECT id, scan_type, price, status, created_at 
                     FROM radiology_requests 
                     WHERE patient_id=$patient_id
                     ORDER BY created_at DESC");

if($rad->num_rows > 0){
    echo "<table class='table table-bordered'>
    <tr><th>Scan</th><th>Price</th><th>Status</th><th>Action</th></tr>";
    while($r = $rad->fetch_assoc()){
        $btn = $r['status']=='pending' ? "<button class='btn btn-success btn-sm mark-paid' data-id='{$r['id']}'>Mark Paid</button>" : '';
        echo "<tr>
            <td>".htmlspecialchars($r['scan_type'])."</td>
            <td>".number_format($r['price'],2)."</td>
            <td>{$r['status']}</td>
            <td>$btn</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No radiology requests yet.</p>";
}
?>
<script>
document.querySelectorAll('.mark-paid').forEach(btn=>{
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        fetch('ajax/mark_radiology_paid.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id:id})
        }).then(res=>res.json())
          .then(r=>{
            alert(r.message);
            refreshRadiologyList();
            refreshBill();
          });
    });
});
</script>
