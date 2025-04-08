<?php
include 'config.php';

$search = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$location = $_GET['location'] ?? '';

$sql = "SELECT p.*, u.username as seller_name 
        FROM properties p
        JOIN users u ON p.seller_id = u.id
        WHERE p.status = 'approved'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.title LIKE :search OR p.description LIKE :search)";
    $params['search'] = "%$search%";
}
if (!empty($min_price)) {
    $sql .= " AND p.price >= :min_price";
    $params['min_price'] = $min_price;
}
if (!empty($max_price)) {
    $sql .= " AND p.price <= :max_price";
    $params['max_price'] = $max_price;
}
if (!empty($location)) {
    $sql .= " AND p.location LIKE :location";
    $params['location'] = "%$location%";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real Estate Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php"><span class="text-danger fw-bold">RealEstate</span>Online</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a></li>
                </ul>
                <form class="d-flex me-2" method="GET" action="index.php">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-danger" type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
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

    <div class="container mt-4">
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <button class="btn btn-link text-white" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="fas fa-filter"></i> Advanced Filter
                </button>
            </div>
            <div class="collapse" id="filterCollapse">
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Minimum Price (VND)</label>
                                <select name="min_price" id="min_price" class="form-control">
                                    <option value="">Select Minimum Price</option>
                                    <option value="100000000" <?= $min_price == '100000000' ? 'selected' : '' ?>>100 million</option>
                                    <option value="200000000" <?= $min_price == '200000000' ? 'selected' : '' ?>>200 million</option>
                                    <option value="500000000" <?= $min_price == '500000000' ? 'selected' : '' ?>>500 million</option>
                                    <option value="1000000000" <?= $min_price == '1000000000' ? 'selected' : '' ?>>1 billion</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Maximum Price (VND)</label>
                                <select name="max_price" id="max_price" class="form-control">
                                    <option value="">Select Maximum Price</option>
                                    <option value="100000000" <?= $max_price == '100000000' ? 'selected' : '' ?>>100 million</option>
                                    <option value="200000000" <?= $max_price == '200000000' ? 'selected' : '' ?>>200 million</option>
                                    <option value="500000000" <?= $max_price == '500000000' ? 'selected' : '' ?>>500 million</option>
                                    <option value="1000000000" <?= $max_price == '1000000000' ? 'selected' : '' ?>>1 billion</option>
                                    <option value="5000000000" <?= $max_price == '5000000000' ? 'selected' : '' ?>>5 billion</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($location) ?>" placeholder="Enter location">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-search"></i> Search</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <h3 class="mb-4 text-danger">Featured Properties</h3>
        <div class="row">
            <?php if (count($properties) > 0): ?>
                <?php foreach ($properties as $property): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card property-card h-100">
                            <img src="<?= $property['image'] ? 'uploads/' . $property['image'] : 'https://via.placeholder.com/300x200' ?>" class="card-img-top" alt="<?= htmlspecialchars($property['title']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>
                                <p class="card-text text-danger fw-bold"><?= number_format($property['price']) ?> VND</p>
                                <p class="card-text"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?></p>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="property_detail.php?id=<?= $property['id'] ?>" class="btn btn-outline-danger btn-sm">View Details</a>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'buyer'): ?>
                                    <a href="property_detail.php?id=<?= $property['id'] ?>" class="btn btn-danger btn-sm float-end">Deposit</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h4 class="text-muted">No properties found</h4>
                </div>
            <?php endif; ?>
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
    <script>
        // Price milestones
        const priceOptions = [
            { value: "", label: "Select price" },
            { value: "100000000", label: "100 million" },
            { value: "200000000", label: "200 million" },
            { value: "500000000", label: "500 million" },
            { value: "1000000000", label: "1 billion" },
            { value: "5000000000", label: "5 billion" }
        ];

        // Get select elements
        const minPriceSelect = document.getElementById('min_price');
        const maxPriceSelect = document.getElementById('max_price');

        // Function to update max price options based on min price
        function updateMaxPriceOptions() {
            const minPrice = minPriceSelect.value ? parseInt(minPriceSelect.value) : 0;
            maxPriceSelect.innerHTML = ''; // Clear current options

            priceOptions.forEach(option => {
                if (!minPrice || parseInt(option.value) > minPrice || option.value === "") {
                    const opt = document.createElement('option');
                    opt.value = option.value;
                    opt.text = option.label;
                    if (option.value === "<?= $max_price ?>") opt.selected = true;
                    maxPriceSelect.appendChild(opt);
                }
            });
        }

        // Function to update min price options based on max price
        function updateMinPriceOptions() {
            const maxPrice = maxPriceSelect.value ? parseInt(maxPriceSelect.value) : Infinity;
            minPriceSelect.innerHTML = ''; // Clear current options

            priceOptions.forEach(option => {
                if (parseInt(option.value) < maxPrice || option.value === "") {
                    const opt = document.createElement('option');
                    opt.value = option.value;
                    opt.text = option.label;
                    if (option.value === "<?= $min_price ?>") opt.selected = true;
                    minPriceSelect.appendChild(opt);
                }
            });
        }

        // Trigger functions on value change
        minPriceSelect.addEventListener('change', updateMaxPriceOptions);
        maxPriceSelect.addEventListener('change', updateMinPriceOptions);

        // Initial setup
        updateMaxPriceOptions();
        updateMinPriceOptions();
    </script>
</body>
</html>