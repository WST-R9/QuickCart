<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// Fetch tickets
$ticketsQuery = "SELECT 
    t.ticketId,
    t.ticketNumber,
    CONCAT(u.firstName,' ',u.lastName) AS customerName,
    t.subject,
    t.category,
    t.priority,
    t.status,
    t.createdAt
FROM support_tickets t
JOIN users u ON t.userId = u.userId
ORDER BY t.createdAt DESC";

$ticketsResult = mysqli_query($conn, $ticketsQuery);
?>

<div class="pagetitle">
  <h1>Customer Support</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Customer Support</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">

      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Support Tickets</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Ticket #</th>
                <th>Customer</th>
                <th>Subject</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <?php while ($row = mysqli_fetch_assoc($ticketsResult)): ?>
                <?php
                $statusBadge = "bg-secondary";
                if ($row['status'] == "open") $statusBadge = "bg-danger";
                elseif ($row['status'] == "in_progress") $statusBadge = "bg-warning text-dark";
                elseif ($row['status'] == "resolved") $statusBadge = "bg-success";
                elseif ($row['status'] == "closed") $statusBadge = "bg-dark";

                $priorityBadge = "bg-secondary";
                if ($row['priority'] == "low") $priorityBadge = "bg-info";
                elseif ($row['priority'] == "medium") $priorityBadge = "bg-primary";
                elseif ($row['priority'] == "high") $priorityBadge = "bg-danger";
                ?>

                <tr>
                  <td><?= htmlspecialchars($row['ticketNumber']) ?></td>
                  <td><?= htmlspecialchars($row['customerName']) ?></td>
                  <td><?= htmlspecialchars($row['subject']) ?></td>
                  <td><?= ucfirst($row['category']) ?></td>

                  <td>
                    <span class="badge <?= $priorityBadge ?>">
                      <?= ucfirst($row['priority']) ?>
                    </span>
                  </td>

                  <td>
                    <span class="badge <?= $statusBadge ?>">
                      <?= ucfirst(str_replace("_"," ",$row['status'])) ?>
                    </span>
                  </td>

                  <td><?= date("M d, Y", strtotime($row['createdAt'])) ?></td>

                  <td>
                    <a href="ticket-view.php?id=<?= $row['ticketId'] ?>" class="btn btn-sm btn-primary">
                      <i class="bi bi-eye"></i> View
                    </a>
                  </td>
                </tr>

              <?php endwhile; ?>
            </tbody>

          </table>

        </div>
      </div>

    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>