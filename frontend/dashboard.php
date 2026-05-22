<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../backend/db_connect.php';

// ==================== INDEX OPTIMIZATION DEMO ====================
// Ensure index on products.product_name exists for benchmarking
$index_check = $pdo->query("SHOW INDEX FROM products WHERE Key_name = 'idx_product_name'");
if ($index_check->rowCount() == 0) {
    $pdo->exec("ALTER TABLE products ADD INDEX idx_product_name (product_name)");
}

if (isset($_GET['benchmark']) && isset($_GET['search_name'])) {
    $search_term = trim($_GET['search_name']);
    if ($search_term === '') {
        header("Location: dashboard.php?msg=" . urlencode("Please enter a product name to search."));
        exit();
    }
    $like = "$search_term%";
    $use_index = $_GET['benchmark'] === 'with_index';
    
    $start = microtime(true);
    if ($use_index) {
        $sql = "SELECT * FROM products FORCE INDEX (idx_product_name) WHERE product_name LIKE :like";
    } else {
        $sql = "SELECT * FROM products IGNORE INDEX (idx_product_name) WHERE product_name LIKE :like";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['like' => $like]);
    $count = $stmt->rowCount();
    $end = microtime(true);
    $time_ms = round(($end - $start) * 1000, 2);
    
    // Get EXPLAIN to see which index is used
    $explain_stmt = $pdo->prepare("EXPLAIN " . str_replace(':like', "'$like'", $sql));
    $explain_stmt->execute();
    $explain_row = $explain_stmt->fetch(PDO::FETCH_ASSOC);
    $key_used = $explain_row['key'] ?? 'NONE';
    
    $msg = "🔍 Search for product name starting with '{$search_term}' | Records: {$count} | Time: {$time_ms} ms | Index used: " . ($key_used ?: 'NO INDEX');
    header("Location: dashboard.php?msg=" . urlencode($msg));
    exit();
}

// Regular product search logic
$search = trim($_GET['search'] ?? '');

if (isset($_GET['search']) && $search !== '') {
    $stmt = $pdo->prepare("
        INSERT INTO audit_log
        (action_type, table_name, row_id, old_value, new_value)
        VALUES
        ('SEARCH', 'products', NULL, NULL, :new_value)
    ");
    $stmt->execute([
        'new_value' => json_encode([
            'query' => $search
        ])
    ]);
}

$sql = "
    SELECT
        p.*,
        c.category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.category_id
    WHERE
        (
            p.product_name LIKE :likeSearch
            OR c.category_name LIKE :likeSearch
        )
        OR (p.sku = :exactSku)
        OR (p.product_id = :productId)
        OR (p.unit_price = :unitPrice)
    ORDER BY p.product_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'likeSearch' => "%$search%",
    'exactSku' => $search,
    'productId' => is_numeric($search) ? (int)$search : -1,
    'unitPrice' => is_numeric($search) ? $search : -1,
]);

$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories')->fetchAll();
$customers = $pdo->query('SELECT customer_id, customer_name FROM customers')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .alert { padding: 12px; border-radius: 4px; margin: 10px 0; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .demo-card { background: #f8f9fc; border: 1px solid #cce5ff; border-radius: 8px; padding: 15px 20px; margin: 20px 0; display: flex; flex-wrap: wrap; gap: 20px; align-items: center; }
        .demo-card h4 { margin: 0 0 8px 0; color: #0056b3; }
        .demo-btn { background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .demo-btn:hover { background: #0056b3; }
        .demo-btn.without { background: #6c757d; }
        .demo-btn.without:hover { background: #5a6268; }
        small { font-size: 12px; color: #555; }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <h1>Inventory Dashboard</h1>
        <div class="user">
            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="audit.php">Audit Log</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): 
        $msg_class = (strpos($_GET['msg'], '🔍') !== false) ? 'alert-info' : 'alert-success'; ?>
        <div class="alert <?= $msg_class ?>"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <!-- Index Optimization Demo Card -->
    <div class="demo-card">
        <div style="flex: 2; min-width: 200px;">
            <h4>📊 Index Optimization Demo (product_name)</h4>
            <form method="get" action="dashboard.php" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <input type="text" name="search_name" placeholder="Product name starts with (e.g., Laptop)" required style="padding: 8px; border:1px solid #ccc; border-radius:4px; flex:2;">
                <button type="submit" name="benchmark" value="without_index" class="demo-btn without">Without Index</button>
                <button type="submit" name="benchmark" value="with_index" class="demo-btn">With Index</button>
            </form>
            <small>🔍 Compares search speed with/without index on `product_name`. Shows execution time & EXPLAIN key used.</small>
        </div>
    </div>

    <!-- Existing Search Form -->
    <div class="card">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="card">
        <h2>Products</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['product_id'] ?></td>
                        <td><?= htmlspecialchars($product['sku']) ?></td>
                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                        <td><?= htmlspecialchars($product['category_name']) ?></td>
                        <td>$<?= number_format($product['unit_price'], 2) ?></td>
                        <td><?= $product['current_stock'] ?></td>
                        <td class="actions">
                            <button type="button" onclick='editProduct(<?= json_encode($product) ?>)'>Edit</button>
                            <form method="POST" action="../backend/delete.php" class="inline-form" onsubmit="return confirm('Delete product?')">
                                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Add Product</h3>
            <form action="../backend/insert.php" method="POST">
                <input type="text" name="sku" placeholder="SKU" required>
                <input type="text" name="product_name" placeholder="Product Name" required>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="supplier_id" placeholder="Supplier ID" required>
                <input type="number" step="0.01" name="unit_price" placeholder="Unit Price" required>
                <input type="number" name="current_stock" placeholder="Stock" required>
                <button type="submit">Add Product</button>
            </form>
        </div>

        <div class="card">
            <h3>Create Sale</h3>
            <form action="../backend/transaction.php" method="POST" onsubmit="return confirm('Create sale?')">
                <select name="customer_id" required>
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $cust): ?>
                        <option value="<?= $cust['customer_id'] ?>"><?= htmlspecialchars($cust['customer_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="product_id" required>
                    <option value="">Select Product</option>
                    <?php foreach ($products as $prod): ?>
                        <option value="<?= $prod['product_id'] ?>">
                            <?= htmlspecialchars($prod['product_name']) ?> (Stock: <?= $prod['current_stock'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="quantity" placeholder="Quantity" required>
                <button type="submit">Create Sale</button>
            </form>
        </div>
    </div>

    <div class="card" id="updateForm" style="display:none;">
        <h3>Update Product</h3>
        <form action="../backend/update.php" method="POST">
            <input type="hidden" name="product_id" id="update_id">
            <input type="text" name="sku" id="update_sku" placeholder="SKU" required>
            <input type="text" name="product_name" id="update_name" placeholder="Product Name" required>
            <select name="category_id" id="update_category" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="supplier_id" id="update_supplier" placeholder="Supplier ID" required>
            <input type="number" step="0.01" name="unit_price" id="update_price" placeholder="Unit Price" required>
            <input type="number" name="current_stock" id="update_stock" placeholder="Stock" required>
            <button type="submit">Update Product</button>
            <button type="button" onclick="document.getElementById('updateForm').style.display='none'">Cancel</button>
        </form>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('update_id').value = product.product_id;
    document.getElementById('update_sku').value = product.sku;
    document.getElementById('update_name').value = product.product_name;
    document.getElementById('update_category').value = product.category_id;
    document.getElementById('update_supplier').value = product.supplier_id;
    document.getElementById('update_price').value = product.unit_price;
    document.getElementById('update_stock').value = product.current_stock;
    document.getElementById('updateForm').style.display = 'block';
    window.scrollTo({ top: document.getElementById('updateForm').offsetTop - 20, behavior: 'smooth' });
}

const rowsPerPage = 5;
const rows = document.querySelectorAll('tbody tr');
const pagination = document.getElementById('pagination');
const totalPages = Math.ceil(rows.length / rowsPerPage);
let currentPage = 1;

function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    rows.forEach((row, index) => {
        row.style.display = index >= start && index < end ? '' : 'none';
    });
    renderPagination();
}

function renderPagination() {
    if (!pagination) return;
    pagination.innerHTML = '';
    const prevBtn = document.createElement('button');
    prevBtn.innerText = 'Previous';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => changePage(currentPage - 1);
    pagination.appendChild(prevBtn);
    let startPage = Math.max(1, currentPage - 1);
    let endPage = Math.min(totalPages, currentPage + 1);
    if (currentPage === 1) endPage = Math.min(3, totalPages);
    if (currentPage === totalPages) startPage = Math.max(1, totalPages - 2);
    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.innerText = i;
        if (i === currentPage) btn.classList.add('active');
        btn.onclick = () => changePage(i);
        pagination.appendChild(btn);
    }
    const nextBtn = document.createElement('button');
    nextBtn.innerText = 'Next';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.onclick = () => changePage(currentPage + 1);
    pagination.appendChild(nextBtn);
}

changePage(1);
</script>
</body>
</html>