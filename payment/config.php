<?php
require_once __DIR__ . '/../vendor/autoload.php'; // sesuaikan path

// Load .env from root
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

\Midtrans\Config::$serverKey = $_ENV['API_KEY']; // Ganti dengan server key dari Midtrans
\Midtrans\Config::$isProduction = false; // true = live, false = sandbox
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;
