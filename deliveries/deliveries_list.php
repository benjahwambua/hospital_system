<?php
include('../includes/header.php');
include('../includes/sidebar.php');
include('../config.php');

// --- SEARCH HANDLING ---
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// --- PAGINATION SETTINGS ---
$limit = 10; // records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// --- GET RECORDS ---
$query = "SELECT * FROM deliveries 
          WHERE mother_name LIKE '%$search%' 
          OR delivery_type LIKE '%$search%' 
          OR delivery_date LIKE '%$search%'
          ORDER BY id DESC 
          LIMIT $start, $limit";

$result = mysqli_query($conn, $query);

// --- COUNT TOTAL RECORDS ---
$countQuery = "SELECT COUNT(*) AS total FROM deliveries 
               WHERE mother_name LIKE '%$search%' 
               OR delivery_type LIKE '%$search%' 
               OR delivery_date LIKE '%$search%'";
$countResult = mysqli_query($conn, $countQuery);
$total = mysqli_fetch_assoc($countResult)['total'];

$pages = ceil($total / $limit);
?>

<div class="container-fluid">

    <h4 class="mt-4 mb-3">Deliveries</h4>

    <!-- SUCCESS ALERTS -->
    <?php if (isset($_GET['added'])) { ?>
        <div class="alert alert-success">Delivery record added successfully.</div>
    <?php } ?>
    <?php if (isset($_GET['updated'])) { ?>
        <div class="alert alert-success">Delivery record updated successfully.</div>
    <?php } ?>
    <?php if (isset($_GET['deleted'])) { ?>
        <div class="alert alert-danger">Delivery record deleted.</div>
    <?php } ?>

    <!-- TOP ACTIONS -->
    <div class="d-flex justify-content-between mb-3">
        <a href="deliveries_add.php" class="btn btn-primary">Add Delivery</a>

        <div class="d-flex">

            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= $search ?>">
                <button class="btn btn-secondary ms-2">Search</button>
            </form>

            <a href="#" onclick="window.print()" class="btn btn-dark ms-2">Print</a>
            <a href="export_deliveries_csv.php" class="btn btn-success ms-2">Export CSV</a>
        </div>
    </div>

    <!-- TABLE -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Mother</th>
                <th>Date</th>
                <th>Type</th>
                <th>Baby Weight</th>
                <th>Remarks</th>
                <th width="160">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['mother_name'] ?></td>
                <td><?= $row['delivery_date'] ?></td>
                <td><?= $row['delivery_type'] ?></td>
                <td><?= $row['baby_weight'] ?></td>
                <td><?= $row['remarks'] ?></td>
                <td>
                    <a href="deliveries_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="deliveries_delete.php?id=<?= $row['id'] ?>" 
                       onclick="return confirm('Delete this record?')" 
                       class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- PAGINATION -->
    <nav>
        <ul class="pagination">

            <!-- Previous Button -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= $search ?>">Previous</a>
            </li>

            <!-- Page Numbers -->
            <?php for ($i = 1; $i <= $pages; $i++) { ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= $search ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php } ?>

            <!-- Next Button -->
            <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= $search ?>">Next</a>
            </li>

        </ul>
    </nav>

</div>

<?php include('../includes/footer.php'); ?>
