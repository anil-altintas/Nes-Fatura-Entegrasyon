<?php
require_once __DIR__ . '/../vendor/autoload.php';

use NES\Config\Config;
use NES\Service\CustomerService;
use NES\Service\InvoiceService;

// Hata raporlamayı etkinleştir
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = Config::getInstance();
$customerService = new CustomerService();
$invoiceService = new InvoiceService();

$message = '';
$messageType = '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'check_customer':
                    $identifier = trim($_POST['identifier']);
                    if (empty($identifier)) {
                        throw new Exception('TCSI/VKN giriniz');
                    }
                    
                    $status = $customerService->checkCustomerStatus($identifier);
                    $message = "Müşteri durumu başarıyla alındı!";
                    $messageType = 'success';
                    break;
                    
                case 'create_invoice':
                    $identifier = trim($_POST['customer_identifier']);
                    $invoiceType = $_POST['invoice_type'];
                    
                    if (empty($identifier)) {
                        throw new Exception('Müşteri TCSI/VKN giriniz');
                    }
                    
                    // Test verisi ile fatura oluştur
                    $invoiceData = $invoiceService->getTestInvoiceData($identifier);
                    
                    if ($invoiceType === 'e_fatura') {
                        $result = $invoiceService->createEInvoice($invoiceData);
                    } else {
                        $result = $invoiceService->createEArchiveInvoice($invoiceData);
                    }
                    
                    $message = "Fatura başarıyla oluşturuldu! Fatura ID: " . ($result['invoice_id'] ?? 'N/A');
                    $messageType = 'success';
                    break;
            }
        }
    } catch (Exception $e) {
        $message = "Hata: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NES Fatura Entegrasyonu - Test Sayfası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .alert {
            border-radius: 0.375rem;
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-4">
                    <h1 class="display-5 text-primary">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        NES Fatura Entegrasyonu
                    </h1>
                    <p class="lead text-muted">E-Fatura ve E-Arşiv Test Sayfası</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Müşteri Sorgulama -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-search me-2"></i>
                                    Müşteri Sorgulama
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="check_customer">
                                    <div class="mb-3">
                                        <label for="identifier" class="form-label">TCSI veya VKN</label>
                                        <input type="text" class="form-control" id="identifier" name="identifier" 
                                               placeholder="11 haneli TCSI veya 10 haneli VKN" 
                                               pattern="\d{10,11}" maxlength="11" required>
                                        <div class="form-text">
                                            TCSI: 11 hane, VKN: 10 hane sayısal değer
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>
                                        Müşteri Durumunu Kontrol Et
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Fatura Oluşturma -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    Test Faturası Oluştur
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_invoice">
                                    <div class="mb-3">
                                        <label for="customer_identifier" class="form-label">Müşteri TCSI/VKN</label>
                                        <input type="text" class="form-control" id="customer_identifier" name="customer_identifier" 
                                               placeholder="11 haneli TCSI veya 10 haneli VKN" 
                                               pattern="\d{10,11}" maxlength="11" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="invoice_type" class="form-label">Fatura Tipi</label>
                                        <select class="form-select" id="invoice_type" name="invoice_type" required>
                                            <option value="">Seçiniz</option>
                                            <option value="e_fatura">E-Fatura</option>
                                            <option value="e_arsiv">E-Arşiv</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        Test Faturası Oluştur
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bilgi Kartları -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-cog fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Konfigürasyon</h5>
                                <p class="card-text">
                                    <strong>Test Modu:</strong> <?= $config->get('test_mode') ? 'Aktif' : 'Pasif' ?><br>
                                    <strong>API URL:</strong> <?= htmlspecialchars($config->get('api.base_url')) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Kullanım</h5>
                                <p class="card-text">
                                    Önce müşteri durumunu kontrol edin, sonra uygun fatura tipini seçerek test faturası oluşturun.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <h5 class="card-title">Önemli</h5>
                                <p class="card-text">
                                    Bu sayfa sadece test amaçlıdır. Gerçek ortamda kullanmadan önce güvenlik önlemlerini alın.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log Dosyası -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-alt me-2"></i>
                            Son Log Kayıtları
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Seviye</th>
                                        <th>Mesaj</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $logFile = $config->get('logging.file');
                                    if (file_exists($logFile)) {
                                        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                        $lines = array_slice(array_reverse($lines), 0, 10);
                                        
                                        foreach ($lines as $line) {
                                            if (preg_match('/\[(.*?)\] .*?\.(.*?): (.*)/', $line, $matches)) {
                                                $date = $matches[1];
                                                $level = $matches[2];
                                                $message = $matches[3];
                                                
                                                $levelClass = match($level) {
                                                    'ERROR' => 'danger',
                                                    'WARNING' => 'warning',
                                                    'INFO' => 'info',
                                                    default => 'secondary'
                                                };
                                                
                                                echo "<tr>";
                                                echo "<td>{$date}</td>";
                                                echo "<td><span class='badge bg-{$levelClass}'>{$level}</span></td>";
                                                echo "<td>" . htmlspecialchars($message) . "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-muted'>Log dosyası bulunamadı</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validasyonu
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const inputs = form.querySelectorAll('input[required], select[required]');
                    let isValid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            isValid = false;
                            input.classList.add('is-invalid');
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Lütfen tüm gerekli alanları doldurun.');
                    }
                });
            });
            
            // TCSI/VKN format kontrolü
            const identifierInputs = document.querySelectorAll('input[pattern]');
            identifierInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const value = this.value.replace(/\D/g, '');
                    this.value = value;
                    
                    if (value.length === 11 || value.length === 10) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                });
            });
        });
    </script>
</body>
</html>
