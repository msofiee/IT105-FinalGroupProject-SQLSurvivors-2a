<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../backend/db_connect.php';

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

    <div class="card">
        <form method="GET" class="search-form">
            <input
                type="text"
                name="search"
                placeholder="Search products..."
                value="<?= htmlspecialchars($search) ?>"
            >
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

    <div class="card" id="updateForm">
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

    <?php if (isset($_GET['msg'])): ?>
        <p class="success">Operation successful!</p>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <p class="error"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>
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

    window.scrollTo({
        top: document.getElementById('updateForm').offsetTop - 20,
        behavior: 'smooth',
    });
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

