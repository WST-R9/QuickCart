<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// ------------------------
// ADD CATEGORY
// ------------------------
if (isset($_POST['addCategory'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $slug = mysqli_real_escape_string($conn, $_POST['slug']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $parentId = !empty($_POST['parentId']) ? intval($_POST['parentId']) : "NULL";

    $insertQuery = "INSERT INTO categories (name, slug, description, parentId)
                    VALUES ('$name', '$slug', '$description', $parentId)";

    if (mysqli_query($conn, $insertQuery)) {
        echo "<script>alert('Category added successfully!'); window.location.href='categories.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error adding category!');</script>";
    }
}

// ------------------------
// DELETE CATEGORY
// ------------------------
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    $deleteQuery = "DELETE FROM categories WHERE categoryId = $deleteId";
    mysqli_query($conn, $deleteQuery);

    echo "<script>alert('Category deleted!'); window.location.href='categories.php';</script>";
    exit;
}

// ------------------------
// UPDATE CATEGORY
// ------------------------
if (isset($_POST['updateCategory'])) {
    $categoryId = intval($_POST['categoryId']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $slug = mysqli_real_escape_string($conn, $_POST['slug']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $parentId = !empty($_POST['parentId']) ? intval($_POST['parentId']) : "NULL";

    $updateQuery = "UPDATE categories 
                    SET name='$name', slug='$slug', description='$description', parentId=$parentId
                    WHERE categoryId=$categoryId";

    mysqli_query($conn, $updateQuery);

    echo "<script>alert('Category updated successfully!'); window.location.href='categories.php';</script>";
    exit;
}

// ------------------------
// FETCH CATEGORIES
// ------------------------
$categoriesQuery = "SELECT c1.*, c2.name AS parentName
                    FROM categories c1
                    LEFT JOIN categories c2 ON c1.parentId = c2.categoryId
                    ORDER BY c1.createdAt DESC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

// For dropdown parent list
$parentListQuery = "SELECT categoryId, name FROM categories WHERE parentId IS NULL";
$parentListResult = mysqli_query($conn, $parentListQuery);
?>

<div class="pagetitle">
  <h1>Categories</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Categories</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">

    <!-- ADD CATEGORY FORM -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Add Category</h5>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Category Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" class="form-control" required>
              <small class="text-muted">Example: snacks, beverages</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Parent Category (Optional)</label>
              <select name="parentId" class="form-select">
                <option value="">None</option>
                <?php while ($p = mysqli_fetch_assoc($parentListResult)): ?>
                  <option value="<?= $p['categoryId'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <button type="submit" name="addCategory" class="btn btn-success w-100">
              <i class="bi bi-plus-circle"></i> Add Category
            </button>
          </form>

        </div>
      </div>
    </div>

    <!-- CATEGORY TABLE -->
    <div class="col-lg-8">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Category List</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Parent</th>
                <th>Date Created</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($categoriesResult)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= htmlspecialchars($row['slug']) ?></td>
                  <td><?= $row['parentName'] ? htmlspecialchars($row['parentName']) : "None" ?></td>
                  <td><?= date("M d, Y", strtotime($row['createdAt'])) ?></td>
                  <td>
                    <a href="categories-edit.php?id=<?= $row['categoryId'] ?>" class="btn btn-sm btn-primary">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="categories.php?delete=<?= $row['categoryId'] ?>" 
                       onclick="return confirm('Are you sure you want to delete this category?');"
                       class="btn btn-sm btn-danger">
                      <i class="bi bi-trash"></i>
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