<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_login();

$patient_id = intval($_GET['patient_id']);

$lab = $conn->query("SELECT id, test_type, price, status, created_at 
                     FROM lab_requests 
                     WHERE patient_id=$patient_id
                     ORDER BY created_at DESC");

if($lab->num_rows > 0){
    echo "<table class='table table-bordered'>
    <tr><th>Test</th><th>Price</th><th>Status</th><th>Action</th></tr>";
    while($l = $lab->fetch_assoc()){
        $btn = $l['status']=='pending' ? "<button class='btn btn-success btn-sm mark-paid' data-id='{$l['id']}'>Mark Paid</button>" : '';
        echo "<tr>
            <td>".htmlspecialchars($l['test_type'])."</td>
            <td>".number_format($l['price'],2)."</td>
            <td>{$l['status']}</td>
            <td>$btn</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No lab requests yet.</p>";
}
?>
<script>
document.querySelectorAll('.mark-paid').forEach(btn=>{
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        fetch('ajax/mark_lab_paid.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id:id})
        }).then(res=>res.json())
          .then(r=>{
            alert(r.message);
            refreshLabList();
            refreshBill();
          });
    });
});
</script>
