<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý xóa tài khoản người dùng
if ($role === 'seller' && isset($_POST['delete_user'])) {
    $delete_user_id = $_POST['user_id'];
    if ($delete_user_id != $user_id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_user_id]);
        $stmt = $conn->prepare("DELETE FROM properties WHERE seller_id = ?");
        $stmt->execute([$delete_user_id]);
    }
    header("Location: profile.php");
    exit;
}

// Xử lý xóa sản phẩm (cho cả admin và seller bình thường)
if (isset($_POST['delete_property'])) {
    $property_id = $_POST['property_id'];
    $stmt = $conn->prepare("SELECT image FROM properties WHERE id = ?");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($property && $property['image']) {
        $image_path = 'uploads/' . $property['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Admin có thể xóa bất kỳ sản phẩm nào, buyer chỉ xóa sản phẩm của mình
    if ($role === 'seller') {
        $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);
    } else {
        $stmt = $conn->prepare("DELETE FROM properties WHERE id = ? AND seller_id = ?");
        $stmt->execute([$property_id, $user_id]);
    }
    header("Location: profile.php");
    exit;
}

// Xử lý phê duyệt/từ chối sản phẩm
if ($role === 'seller' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_property'])) {
        $property_id = $_POST['property_id'];
        $stmt = $conn->prepare("UPDATE properties SET status = 'approved' WHERE id = ?");
        $stmt->execute([$property_id]);
    } elseif (isset($_POST['reject_property'])) {
        $property_id = $_POST['property_id'];
        $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);
    }
    header("Location: profile.php");
    exit;
}

// Lấy danh sách bất động sản
if ($role === 'seller') {
    $stmt = $conn->prepare("SELECT p.*, u.username as seller_name FROM properties p JOIN users u ON p.seller_id = u.id");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT id, username, email, role FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($role === 'buyer') {
    $stmt = $conn->prepare("SELECT p.*, u.username as seller_name 
                            FROM properties p 
                            JOIN users u ON p.seller_id = u.id 
                            WHERE p.seller_id = ? 
                            UNION 
                            SELECT p.*, u.username as seller_name 
                            FROM properties p 
                            JOIN users u ON p.seller_id = u.id 
                            JOIN transactions t ON p.id = t.property_id 
                            WHERE t.buyer_id = ?");
    $stmt->execute([$user_id, $user_id]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php"><span class="text-danger fw-bold">RealEstate</span>Online</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="add_property.php"><i class="fas fa-plus"></i> Add Property</a></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-<?= $role === 'buyer' ? 'user' : 'user-tie' ?> fa-5x text-danger mb-3"></i>
                        <h4><?= htmlspecialchars($user['username']) ?></h4>
                        <p class="text-muted"><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                        <span class="badge bg-<?= $role === 'buyer' ? 'primary' : 'danger' ?>">
                            <?= $role === 'buyer' ? 'User' : 'Admin' ?>
                        </span>
                        <hr>
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-outline-primary">Search Properties</a>
                            <a href="add_property.php" class="btn btn-danger">Add New Property</a>
                            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-<?= $role === 'buyer' ? 'primary' : 'danger' ?> text-white">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?= $role === 'buyer' ? 'active' : '' ?>" data-bs-toggle="tab" href="#properties">
                                    <i class="fas fa-<?= $role === 'buyer' ? 'heart' : 'list' ?>"></i>
                                    <?= $role === 'buyer' ? 'My Properties' : 'Property Management' ?>
                                </a>
                            </li>
                            <?php if ($role === 'seller'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#users">
                                        <i class="fas fa-users"></i> User Management
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-body tab-content">
                        <!-- Tab Quản lý bất động sản -->
                        <div class="tab-pane fade <?= $role === 'buyer' ? 'show active' : '' ?>" id="properties">
                            <?php if (count($properties) > 0): ?>
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Information</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($properties as $property): ?>
                                            <tr>
                                                <td><img src="<?= $property['image'] ? 'uploads/' . $property['image'] : 'https://via.placeholder.com/50' ?>" width="50" class="rounded" alt=""></td>
                                                <td>
                                                    <h6><?= htmlspecialchars($property['title']) ?></h6>
                                                    <small><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?></small>
                                                </td>
                                                <td class="text-danger fw-bold"><?= number_format($property['price']) ?> VND</td>
                                                <td>
                                                    <span class="badge bg-<?= $property['status'] === 'approved' ? 'success' : 'warning' ?>">
                                                        <?= $property['status'] === 'approved' ? 'Approved' : 'Pending' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="property_detail.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                    <?php if ($role === 'seller' || $property['seller_id'] == $user_id): ?>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this property?');">
                                                            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                                                            <button type="submit" name="delete_property" class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($role === 'seller' && $property['status'] === 'pending'): ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                                                            <button type="submit" name="approve_property" class="btn btn-sm btn-success">Approve</button>
                                                            <button type="submit" name="reject_property" class="btn btn-sm btn-danger">Reject</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <h5 class="text-muted"><?= $role === 'buyer' ? 'You have not posted or deposited any properties' : 'No properties available' ?></h5>
                                    <a href="add_property.php" class="btn btn-<?= $role === 'buyer' ? 'primary' : 'danger' ?>">Add New Property</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tab Quản lý người dùng -->
                        <?php if ($role === 'seller'): ?>
                            <div class="tab-pane fade" id="users">
                                <?php if (count($users) > 0): ?>
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user_item): ?>
                                                <tr>
                                                    <td><?= $user_item['id'] ?></td>
                                                    <td><?= htmlspecialchars($user_item['username']) ?></td>
                                                    <td><?= htmlspecialchars($user_item['email']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $user_item['role'] === 'buyer' ? 'primary' : 'danger' ?>">
                                                            <?= $user_item['role'] === 'buyer' ? 'User' : 'Admin' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($user_item['id'] != $user_id): ?>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their properties.');">
                                                                <input type="hidden" name="user_id" value="<?= $user_item['id'] ?>">
                                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger">Delete</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted">Self</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <h5 class="text-muted">No users found</h5>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-danger">RealEstateOnline</h5>
                    <p>A trusted real estate trading platform</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>© 2025 RealEstateOnline. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>