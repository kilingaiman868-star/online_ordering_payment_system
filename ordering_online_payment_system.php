<?php
// DATABASE CONNECTION FOR XAMPP 
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_payment';

// Create connection to MySQL
$conn = new mysqli($host, $user, $password);

// Check connection
if ($conn->connect_error) {
    die(" Connection failed: " . $conn->connect_error);
}

// CREATE DATABASE IF NOT EXISTS
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql_create_db) === TRUE) {
    // Database created or already exists
} else {
    die(" Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($database);

// CREATE PRODUCTS TABLE 
$sql_products = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image_url VARCHAR(255)
)";
$conn->query($sql_products);

//  CREATE ORDERS TABLE 
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending'
)";
$conn->query($sql_orders);

// INSERT SAMPLE PRODUCTS IF TABLE IS EMPTY 
$check = $conn->query("SELECT COUNT(*) as total FROM products");
$data = $check->fetch_assoc();

if ($data['total'] == 0) {
    $insert = "INSERT INTO products (name, price, description, image_url) VALUES
        ('Smart Watch', 49.99, 'Fitness tracker with heart rate monitor', 'https://via.placeholder.com/150'),
        ('Wireless Headphones', 79.99, 'Noise cancelling bluetooth headphones', 'https://via.placeholder.com/150'),
        ('Phone Case', 12.99, 'Shockproof silicone case', 'https://via.placeholder.com/150')";
    $conn->query($insert);
}

// SAVE ORDER TO DATABASE WHEN FORM IS SUBMITTED 
$success = "";
$error = "";

if (isset($_POST['place_order'])) {
    // Get form data
    $customer_name = $conn->real_escape_string($_POST['customer_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    
    // Get product price
    $price_query = $conn->query("SELECT price, name FROM products WHERE id = $product_id");
    $product_data = $price_query->fetch_assoc();
    $total_price = $product_data['price'] * $quantity;
    $product_name = $product_data['name'];
    
    // Insert into orders table (data goes directly to database)
    $insert_order = "INSERT INTO orders (customer_name, phone, product_id, quantity, total_price, payment_method) 
                     VALUES ('$customer_name', '$phone', $product_id, $quantity, $total_price, '$payment_method')";
    
    if ($conn->query($insert_order) === TRUE) {
        $success = " Order saved to database! You ordered $product_name (x$quantity). Total: $$total_price via $payment_method";
    } else {
        $error = " Database Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Ordering & Payment System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .products {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
        }
        .product-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 250px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }
        .product-card h3 {
            margin: 10px 0;
        }
        .price {
            color: #e67e22;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .order-form {
            background: #eef;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            margin: 20px auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #27ae60;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            border: none;
        }
        button:hover {
            background: #2ecc71;
        }
        .message {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .db-status {
            margin-top: 20px;
            padding: 15px;
            background: #eef;
            border-radius: 8px;
            text-align: center;
        }
        .db-status h3 {
            margin-bottom: 10px;
        }
        .badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        .db-connected {
            color: green;
            font-weight: bold;
        }
    </style>
    <script>
        function validateOrder() {
            let name = document.querySelector("input[name='customer_name']").value;
            let phone = document.querySelector("input[name='phone']").value;
            let product = document.querySelector("select[name='product_id']").value;
            
            if(name == "" || phone == "" || product == "") {
                alert(" Please fill all required fields!");
                return false;
            }
            
            if(phone.length < 10) {
                alert(" Phone number must be at least 10 digits!");
                return false;
            }
            
            alert(" Order confirmed! Saving to database...");
            return true;
        }
    </script>
</head>
<body>
<div class="container">
    <h1> Online Store - Ordering and Payment System</h1>

    <?php if($success != ""): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php elseif($error != ""): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>

    <h2> Our Products</h2>
    <div class="products">
        <?php
        $result = $conn->query("SELECT * FROM products");
        while($row = $result->fetch_assoc()):
        ?>
        <div class="product-card">
            <img src="<?php echo $row['image_url']; ?>" alt="<?php echo $row['name']; ?>">
            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
            <p><?php echo htmlspecialchars($row['description']); ?></p>
            <div class="price">$<?php echo $row['price']; ?></div>
        </div>
        <?php endwhile; ?>
    </div>

    <h2>Place Your Order</h2>
    <div class="order-form">
        <form method="POST" action="" onsubmit="return validateOrder()">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="customer_name" required>
            </div>
            <div class="form-group">
                <label>Phone Number *</label>
                <input type="tel" name="phone" required>
            </div>
            <div class="form-group">
                <label>Select Product *</label>
                <select name="product_id" required>
                    <option value="">-- Choose product --</option>
                    <?php
                    $products = $conn->query("SELECT * FROM products");
                    while($row = $products->fetch_assoc()):
                    ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo $row['name']; ?> - $<?php echo $row['price']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" min="1" value="1" required>
            </div>
            <div class="form-group">
                <label>Payment Method *</label>
                <select name="payment_method" required>
                    <option value="Airtel Money"> Airtel Money</option>
                    <option value="M-Pesa"> M-Pesa</option>
                    <option value="tigo-pesa"> Tigo Pesa</option>
                    <option value="Credit Card"> Credit Card</option>
                    <option value="Cash on Delivery"> Cash on Delivery</option>
                </select>
            </div>
            <button type="submit" name="place_order"> PLACE ORDER AND PAY</button>
        </form>
    </div>

</div>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>