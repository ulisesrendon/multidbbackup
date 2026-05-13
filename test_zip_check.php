<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = app(App\Services\EncryptionService::class);
$sql = tempnam(sys_get_temp_dir(), "sql_");
file_put_contents($sql, str_repeat("SELECT 1;\n", 500));
$zipPath = tempnam(sys_get_temp_dir(), "zip_") . ".zip";
$svc->createPasswordProtectedZip($sql, $zipPath, "dump.sql");

$zip = new ZipArchive();
if ($zip->open($zipPath) === true) {
    $withoutPwd = $zip->getFromName("dump.sql") === false;
    $zip->setPassword($svc->zipPassword());
    $withPwd = $zip->getFromName("dump.sql") !== false;
    $stat = $zip->statName("dump.sql");
    $compressed = isset($stat["size"], $stat["comp_size"]) ? ($stat["comp_size"] < $stat["size"]) : null;
    $zip->close();
} else {
    $withoutPwd = null; $withPwd = null; $compressed = null;
}

@unlink($sql);
@unlink($zipPath);

echo json_encode(["without_password_blocked" => $withoutPwd, "with_password_opened" => $withPwd, "compressed" => $compressed]);