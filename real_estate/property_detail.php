<?php
include 'config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$property_id = $_GET['id'];
$stmt = $conn->prepare("SELECT p.*, u.username as seller_name 
                        FROM properties p
                        JOIN users u ON p.seller_id = u.id
                        WHERE p.id = ? AND p.status = 'approved'");
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header("Location: index.php");
    exit;
}

$deposit_error = '';
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'buyer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $buyer_id = $_SESSION['user_id'];

    if ($amount <= 0 || $amount > $property['price'] * 0.3) {
        $deposit_error = "Invalid deposit amount. Maximum is 30% of the property value.";
    } else {
        $stmt = $conn->prepare("INSERT INTO transactions (buyer_id, property_id, amount, transaction_date) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$buyer_id, $property_id, $amount]);
        header("Location: profile.php?deposit_success=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details - <?= htmlspecialchars($property['title']) ?></title>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
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
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="row g-0">
                <div class="col-md-6">
                    <img src="<?= $property['image'] ? 'uploads/' . $property['image'] : 'https://via.placeholder.com/600x400' ?>" class="img-fluid rounded-start" alt="<?= htmlspecialchars($property['title']) ?>">
                </div>
                <div class="col-md-6">
                    <div class="card-body">
                        <h2 class="card-title"><?= htmlspecialchars($property['title']) ?></h2>
                        <p class="card-text text-danger fw-bold fs-4"><?= number_format($property['price']) ?> VND</p>
                        <p class="card-text"><i class="fas fa-map-marker-alt text-danger"></i> <strong>Location:</strong> <?= htmlspecialchars($property['location']) ?></p>
                        <p class="card-text"><i class="fas fa-user text-danger"></i> <strong>Admin:</strong> <?= htmlspecialchars($property['seller_name']) ?></p>
                        <hr>
                        <h5 class="text-danger">Detailed Description</h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($property['description'])) ?></p>
                        <?php if ($deposit_error): ?>
                            <div class="alert alert-danger"><?= $deposit_error ?></div>
                        <?php endif; ?>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-secondary">Back</a>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'buyer'): ?>
                                <button class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#depositModal">Deposit</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'buyer'): ?>
        <div class="modal fade" id="depositModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Deposit for Property</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Deposit Amount (VND)</label>
                                <input type="number" name="amount" class="form-control" required min="1000000" max="<?= $property['price'] * 0.3 ?>">
                                <small class="text-muted">Maximum 30% of property value (<?= number_format($property['price'] * 0.3) ?> VND)</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

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