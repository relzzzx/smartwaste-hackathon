<?php
// Konfigurasi database
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "smartwaste_db";

// Koneksi
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB connection failed"]));
}

// Baca input JSON dari ESP32
$data = json_decode(file_get_contents("php://input"), true);

// Validasi data
if (!isset($data['weight'], $data['distance'], $data['latitude'], $data['longitude'], $data['is_full'])) {
    echo json_encode(["status" => "error", "message" => "Incomplete data"]);
    exit;
}

// Ambil nilai dari JSON
$weight = (float) $data['weight'];
$distance = (float) $data['distance'];
$latitude = (float) $data['latitude'];
$longitude = (float) $data['longitude'];
$is_full = (bool) $data['is_full'];

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO smartwaste (weight, distance, latitude, longitude, is_full) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ddddi", $weight, $distance, $latitude, $longitude, $is_full);
$stmt->execute();

// Kirim notifikasi Telegram kalau penuh
if ($is_full) {
    $bot_token = "YOUR_BOT_TOKEN";
    $chat_id = "YOUR_CHAT_ID";
    $message = "🚨 Tempat sampah penuh!\nBerat: {$weight} kg\nVolume: {$distance} cm\nLokasi: https://maps.google.com/?q={$latitude},{$longitude}";
    file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($message));
}

// Response ke ESP32
echo json_encode(["status" => "ok"]);
