<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

if (!isset($_GET['id'])) {
    header("Location: categories.php");
    exit;
}

$categoryId = intval($_GET['id']);

// Fetch category
$catQuery = "SELECT * FROM categories WHERE categoryId = $categoryId";
$catResult = mysqli_query($conn, $catQuery);

if (mysqli_num_rows($catResult) == 0) {
    echo "<script>alert('Category not found!'); window.location.href='categories.php';</script>";
    exit;
}

$category = mysqli_fetch_assoc($catResult);

// Parent dropdown
$parentQuery = "SELECT categoryId, name FROM categories WHERE categoryId != $categoryId";
$parentResult = mysqli_query($conn, $parentQuery);

// Update
if (isset($_POST['updateCategory'])) {
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
?>

<div class="pagetitle">
  <h1>Edit Category</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
      <li class="breadcrumb-item active">Edit</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-6">

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Update Category</h5>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Category Name</label>
              <input type="text" name="name" class="form-control"
                     value="<?= htmlspecialchars($category['name']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" class="form-control"
                     value="<?= htmlspecialchars($category['slug']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control"><?= htmlspecialchars($category['description']) ?></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Parent Category</label>
              <select name="parentId" class="form-select">
                <option value="">None</option>
                <?php while ($p = mysqli_fetch_assoc($parentResult)): ?>
                  <option value="<?= $p['categoryId'] ?>"
                    <?= ($category['parentId'] == $p['categoryId']) ? "selected" : "" ?>>
                    <?= htmlspecialchars($p['name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <button type="submit" name="updateCategory" class="btn btn-primary w-100">
              <i class="bi bi-save"></i> Save Changes
            </button>

            <a href="categories.php" class="btn btn-secondary w-100 mt-2">
              Cancel
            </a>
          </form>

        </div>
      </div>

    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>