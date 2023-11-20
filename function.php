<?php
session_start(); // Start or resume the session
require 'connection.php'; // Include your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signup'])) {
        $name = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['pass'];
        $confirm = $_POST['confirmPass'];
        $role = "user";

        if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
            echo "";
            exit;
        }

        if ($password != $confirm) {
            echo "";
            exit;
        }

        $password_hash = hash('sha256', $password);

        $query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password_hash, $role);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: login.php", true, 303);
                exit();
            } else {
                echo "" . mysqli_error($conn);
            }

            mysqli_stmt_close($stmt);
        } else {
            echo "" . mysqli_error($conn);
        }

        mysqli_close($conn);
    } elseif (isset($_POST['signin'])) {
        $email = $_POST['email'];
        $password = $_POST['pass'];

        $query = "SELECT user_id, password FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            $stored_password = $row['password'];

            $entered_password_hash = hash('sha256', $password);

            if ($entered_password_hash === $stored_password) {
                session_start(); // Mulai sesi
                $_SESSION['user_id'] = $row['user_id'];
                header("Location: index.php");
                exit();
            } else {
                echo "";
            }
        } else {
            echo "";
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    }
}

if (isset($_POST['addCart'])) {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['cart_quantity']);
    $status = "ordered";


    // Mendapatkan user_id dari sesi (misalnya, setelah pengguna login)
    $user_id = $_SESSION['user_id'];

    if ($quantity <= 0) {
        $_SESSION['alert_min'] = "You cannot fill in below the number 0";
    } else {
        // Memeriksa apakah product_id sudah ada dalam cart untuk user_id tertentu
        $sql = "SELECT cart_product FROM cart WHERE user_id = ? AND cart_product = ? AND cart_status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $user_id, $product_id, $status);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Jika product_id sudah ada dalam cart, lakukan update quantity
            $sql = "UPDATE cart SET cart_quantity = cart_quantity + ? WHERE user_id = ? AND cart_product = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
            $stmt->execute();
        } else {
            // Jika product_id belum ada dalam cart, lakukan insert data baru
            $sql = "INSERT INTO cart (user_id, cart_product, cart_quantity, cart_status) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $user_id, $product_id, $quantity, $status);
            $stmt->execute();
        }

        header('Location: shop-grid.php');
        exit;
    }
}

if (isset($_POST['updateCart'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = $_POST['quantity'];

    var_dump($cart_id, $quantity);
    die;

    $query = "UPDATE cart SET cart_quantity = cart_quanityt + ? WHERE cart_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $quantity, $cart_id);
    $stmt->execute();
    $stmt->close();

    header("shoping-cart.php");
    exit;
}

if (isset($_POST['delete_order'])) {
    $id_to_delete = intval($_POST['id_to_delete']);

    $query = "DELETE FROM cart WHERE cart_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();

    header('Location: shoping-cart.php');
    exit;
}

if (isset($_POST["pay"])) {
    $firstName = htmlspecialchars($_POST["firstName"]);
    $lastName = htmlspecialchars($_POST["lastName"]);
    $country = htmlspecialchars($_POST["country"]);
    $address = htmlspecialchars($_POST["address"]);
    $apartment = htmlspecialchars($_POST["apartment"]);
    $townCity = htmlspecialchars($_POST["townCity"]);
    $postCode = intval($_POST["postCode"]);
    $phoneNumber = intval($_POST["phoneNumber"]);
    $email = htmlspecialchars($_POST["email"]);
    $notes = htmlspecialchars($_POST["notes"]);

    $query = "INSERT INTO delivery (delivery_firstName, delivery_lastName, delivery_country, delivery_address, delivery_apartment, delivery_townCity, delivery_postCode, delivery_phoneNumber, delivery_email, delivery_notes) VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssiiss", $firstName, $lastName, $country, $address, $apartment, $townCity, $postCode, $phoneNumber, $email, $notes);

    if ($stmt->execute()) {
        $cart_id = intval($_POST["cart_id"]);
        $text = "waiting";

        $query = "UPDATE cart SET cart_status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $text, $cart_id);
        $stmt->execute();
        $stmt->close();
    }

    header("location: checkout-completed.php");
    exit;
}

$show = function ($tabel) use ($conn) {
    $query = $conn->query("SELECT * FROM $tabel");
    return $query;
};

$showFeatured = function ($tabel, $amount) use ($conn) {
    $query = $conn->query("SELECT * FROM $tabel WHERE product_rating >= $amount");
    return $query;
};



?>