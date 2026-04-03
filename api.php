<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// ตั้งค่าการเชื่อมต่อฐานข้อมูล (XAMPP ปกติรหัสผ่านจะว่างเปล่า)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'consign_db';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

// ดึงข้อมูลทั้งหมดไปแสดงผลที่หน้าเว็บ
if ($action === 'get_state') {
    $users = $pdo->query("SELECT id, username, name, role FROM users")->fetchAll(PDO::FETCH_ASSOC);

    $products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as &$p) {
        $p['price'] = (float)$p['price'];
        $p['sellerId'] = (int)$p['seller_id'];
    }

    $orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orders as &$o) {
        $o['total'] = (float)$o['total'];
        $o['customerId'] = (int)$o['customer_id'];
        $o['products'] = json_decode($o['product_ids'], true);
        $o['slipImg'] = $o['slip_image'];
    }

    $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'users' => $users,
        'products' => $products,
        'orders' => $orders,
        'settings' => ['commissionRate' => (float)$settings['commission_rate']]
    ]);
    exit;
}

if ($action === 'login') {
    $stmt = $pdo->prepare("SELECT id, username, name, role FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$data['username'], $data['password']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user['id'] = (int)$user['id'];
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($action === 'register') {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
    try {
        $success = $stmt->execute([$data['username'], $data['password'], $data['name'], $data['role']]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($action === 'save_product') {
    if (!empty($data['id'])) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, category=?, condition_text=?, description=?, image=? WHERE id=?");
        $stmt->execute([$data['name'], $data['price'], $data['cat'], $data['condition'], $data['desc'], $data['img'], $data['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, category, condition_text, description, image, seller_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['price'], $data['cat'], $data['condition'], $data['desc'], $data['img'], $data['sellerId'], 'pending']);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_product') {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update_product_status') {
    $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'create_order') {
    $stmt = $pdo->prepare("INSERT INTO orders (id, customer_id, product_ids, total, slip_image, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data['id'], $data['customerId'], json_encode($data['products']), $data['total'], $data['slipImg'], 'verifying']);

    $inQuery = implode(',', array_fill(0, count($data['products']), '?'));
    $stmt2 = $pdo->prepare("UPDATE products SET status = 'reserved' WHERE id IN ($inQuery)");
    $stmt2->execute($data['products']);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update_order_status') {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $data['id']]);

    if ($data['status'] === 'completed') {
        $inQuery = implode(',', array_fill(0, count($data['products']), '?'));
        $stmt2 = $pdo->prepare("UPDATE products SET status = 'sold' WHERE id IN ($inQuery)");
        $stmt2->execute($data['products']);
    } else if ($data['status'] === 'rejected') {
        $inQuery = implode(',', array_fill(0, count($data['products']), '?'));
        $stmt2 = $pdo->prepare("UPDATE products SET status = 'active' WHERE id IN ($inQuery)");
        $stmt2->execute($data['products']);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update_commission') {
    $stmt = $pdo->prepare("UPDATE settings SET commission_rate = ?");
    $stmt->execute([$data['rate']]);
    echo json_encode(['success' => true]);
    exit;
}
