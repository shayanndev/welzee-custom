<?php
// api/submit_order.php
session_start();
header('Content-Type: application/json');

// Include Database connection
require_once '../config/db.php';

// Native PHPMailer includes
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get POST data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit;
}

$product = $input['product'] ?? 'N/A';
$color = $input['color'] ?? 'N/A';
$designMethod = $input['designMethod'] ?? 'embroidery';
$totalQuantity = $input['total'] ?? 0;
$quantities = $input['quantities'] ?? [];
$contact = $input['contact'] ?? [];
$designBase64 = $input['designBase64'] ?? '';
$logos = $input['logos'] ?? [];

$name = trim($contact['name'] ?? '');
$email = trim($contact['email'] ?? '');
$phone = trim($contact['phone'] ?? '');
$notes = trim($contact['notes'] ?? '');

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Validations
if (empty($name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Name and Email are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

try {
    // 1. Process Main Design Image
    $designPath = '';
    if (!empty($designBase64)) {
        $imgParts = explode(';base64,', $designBase64);
        if (count($imgParts) == 2) {
            $imgTypeAux = explode('image/', $imgParts[0]);
            $imageType = $imgTypeAux[1];
            $imageBase64 = base64_decode($imgParts[1]);

            $fileName = uniqid('design_') . '.' . $imageType;
            $designPath = '../uploads/designs/' . $fileName;

            file_put_contents($designPath, $imageBase64);
            $designPath = 'uploads/designs/' . $fileName; // DB path
        }
    }

    // 2. Process Logos (Taking the first one for the main DB column for simplicity, saving others if needed)
    $logoPath = '';
    if (!empty($logos)) {
        $firstLogo = $logos[0];
        $logoData = $firstLogo['data'];
        $lParts = explode(';base64,', $logoData);
        if (count($lParts) == 2) {
            // Very basic mime check mapping
            $lTypeAux = explode('image/', $lParts[0]);
            // handle svg+xml format
            $logoType = str_replace('+xml', '', $lTypeAux[1]);
            // fallback generic
            if (empty($logoType))
                $logoType = 'png';

            $lBase64 = base64_decode($lParts[1]);

            $lFileName = uniqid('logo_') . '.' . $logoType;
            $logoPathFull = '../uploads/logos/' . $lFileName;

            file_put_contents($logoPathFull, $lBase64);
            $logoPath = 'uploads/logos/' . $lFileName; // DB path
        }
    }

    // 3. Insert into Database using Prepared Statements
    $stmt = $conn->prepare("INSERT INTO enquiries (user_id, name, email, phone, message, product_type, base_color, design_method, total_quantity, design_image_path, logo_image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssiss", $user_id, $name, $email, $phone, $notes, $product, $color, $designMethod, $totalQuantity, $designPath, $logoPath);

    $stmt->execute();
    $enquiry_id = $stmt->insert_id;
    $stmt->close();

    // 4. Insert Quantities
    if ($enquiry_id > 0 && !empty($quantities)) {
        $stmt_items = $conn->prepare("INSERT INTO enquiry_items (enquiry_id, size, quantity) VALUES (?, ?, ?)");
        foreach ($quantities as $size => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $stmt_items->bind_param("isi", $enquiry_id, $size, $qty);
                $stmt_items->execute();
            }
        }
        $stmt_items->close();
    }

    // 5. Send Notification Email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'digitizingmillemb@gmail.com';     // SMTP username
        $mail->Password = 'udah citu ozoi vfxr';             // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('digitizingmillemb@gmail.com', 'Welzee Custom Orders');
        $mail->addAddress('digitizingmillemb@gmail.com');      // Send to yourself/admin
        $mail->addReplyTo($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Custom Order Request: " . ucfirst($product) . " from " . $name;

        $emailBody = "<h2>New Custom Order Details</h2>";
        $emailBody .= "<p><strong>Name:</strong> {$name}</p>";
        $emailBody .= "<p><strong>Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>";
        $emailBody .= "<p><strong>Phone:</strong> {$phone}</p>";
        $emailBody .= "<hr>";
        $emailBody .= "<p><strong>Product:</strong> " . ucfirst($product) . "</p>";
        $emailBody .= "<p><strong>Color Hex:</strong> {$color}</p>";
        $emailBody .= "<p><strong>Design Method:</strong> " . ucfirst($designMethod) . "</p>";
        $emailBody .= "<p><strong>Total Quantity:</strong> {$totalQuantity}</p>";
        $emailBody .= "<h3>Size Breakdown:</h3><ul>";
        foreach ($quantities as $size => $qty) {
            $emailBody .= "<li>{$size}: {$qty}</li>";
        }
        $emailBody .= "</ul>";
        $emailBody .= "<h3>Notes:</h3><p>" . nl2br(htmlspecialchars($notes)) . "</p>";
        $emailBody .= "<hr>";
        $emailBody .= "<p><strong>Design Mockup Link:</strong> <a href='http://" . $_SERVER['HTTP_HOST'] . "/" . $designPath . "'>View Mockup</a></p>";

        if ($logoPath) {
            $emailBody .= "<p><strong>Uploaded Logo Link:</strong> <a href='http://" . $_SERVER['HTTP_HOST'] . "/" . $logoPath . "'>Download Logo</a></p>";
        }

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</li>', '</p>', '<hr>'], ["\n", "\n", "\n\n", "\n---\n"], $emailBody));

        // Add attachments if you want them in the email directly:
        // $mail->addAttachment('../' . $designPath);
        // if ($logoPath) { $mail->addAttachment('../' . $logoPath); }

        $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        // We log the error but still return success to the frontend because the order was saved in DB
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Order submitted successfully',
        'id' => $enquiry_id
    ]);

} catch (Exception $e) {
    // Log error securely
    error_log("Order Submission Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A server error occurred while saving the order.']);
}
?>