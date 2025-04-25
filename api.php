<?php
// Konfigurasi database
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "smartwaste";

// Koneksi database
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB connection failed"]));
}

// Ambil JSON dari ESP32
$data = json_decode(file_get_contents("php://input"), true);

// Validasi: semua field harus ada
$required = ['weight', 'distance', 'latitude', 'longitude', 'is_full'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        echo json_encode(["status" => "error", "message" => "Field '$field' is missing"]);
        exit;
    }
}

// Ambil nilai & cast
$weight = (float) $data['weight'];
$distance = (float) $data['distance'];
$latitude = (float) $data['latitude'];
$longitude = (float) $data['longitude'];
$is_full = filter_var($data['is_full'], FILTER_VALIDATE_BOOLEAN);

// Validasi rentang nilai
if ($weight < 0 || $weight > 100) {
    echo json_encode(["status" => "error", "message" => "Berat tidak valid"]);
    exit;
}
if ($distance < 0 || $distance > 100) {
    echo json_encode(["status" => "error", "message" => "Jarak tidak valid"]);
    exit;
}
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    echo json_encode(["status" => "error", "message" => "Koordinat GPS tidak valid"]);
    exit;
}

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO smartwaste (weight, distance, latitude, longitude, is_full) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ddddi", $weight, $distance, $latitude, $longitude, $is_full);
$stmt->execute();

// Kirim notifikasi jika penuh
if ($is_full) {
    $bot_token = "YOUR_BOT_TOKEN";
    $chat_id = "YOUR_CHAT_ID";
    $message = "🚨 Tempat sampah penuh!\nBerat: {$weight} kg\nVolume: {$distance} cm\nLokasi: https://maps.google.com/?q={$latitude},{$longitude}";
    file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($message));
}

// Sukses
echo json_encode(["status" => "ok", "message" => "Data tersimpan"]);
