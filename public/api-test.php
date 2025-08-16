<?php
require_once __DIR__ . '/../vendor/autoload.php';

use NES\Config\Config;
use NES\Service\CustomerService;
use NES\Service\InvoiceService;
use NES\Exception\NESException;

// Hata raporlamayı etkinleştir
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = Config::getInstance();
$customerService = new CustomerService();
$invoiceService = new InvoiceService();

$results = [];
$errors = [];

// Test fonksiyonları
function runTest($name, $callback) {
    global $results, $errors;
    
    try {
        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);
        
        $results[] = [
            'name' => $name,
            'status' => 'success',
            'result' => $result,
            'duration' => round(($end - $start) * 1000, 2)
        ];
        
        return $result;
    } catch (Exception $e) {
        $errors[] = [
            'name' => $name,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
        
        $results[] = [
            'name' => $name,
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        
        return null;
    }
}

// Testleri çalıştır
if (isset($_POST['run_tests'])) {
    // Test 1: Konfigürasyon kontrolü
    runTest('Konfigürasyon Kontrolü', function() use ($config) {
        $configData = $config->getAll();
        return [
            'test_mode' => $config->get('test_mode'),
            'api_url' => $config->get('api.base_url'),
            'has_credentials' => !empty($config->get('api.username'))
        ];
    });
    
    // Test 2: Müşteri servisi test
    runTest('Müşteri Servisi Test', function() use ($customerService) {
        $testIdentifier = '12345678901'; // Test TCSI
        return [
            'identifier_validation' => $customerService->validateIdentifier($testIdentifier),
            'identifier_type' => $customerService->validateIdentifier($testIdentifier) ? 'TCSI' : 'Invalid'
        ];
    });
    
    // Test 3: Test müşteri verisi
    runTest('Test Müşteri Verisi', function() use ($customerService) {
        $testIdentifier = '12345678901';
        return $customerService->getTestCustomerData($testIdentifier);
    });
    
    // Test 4: Test fatura verisi
    runTest('Test Fatura Verisi', function() use ($invoiceService) {
        $testIdentifier = '12345678901';
        return $invoiceService->getTestInvoiceData($testIdentifier);
    });
    
    // Test 5: API bağlantı testi (sadece test modunda)
    if ($config->get('test_mode')) {
        runTest('API Bağlantı Testi', function() use ($customerService) {
            $testIdentifier = '12345678901';
            return $customerService->checkCustomerStatus($testIdentifier);
        });
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NES API Test Sayfası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-result {
            border-left: 4px solid #dee2e6;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .test-result.success {
            border-left-color: #28a745;
        }
        .test-result.error {
            border-left-color: #dc3545;
        }
        .test-duration {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .error-details {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        pre {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            font-size: 0.875rem;
            overflow-x: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-4">
                    <h1 class="display-5 text-primary">
                        <i class="fas fa-vial me-2"></i>
                        NES API Test Sayfası
                    </h1>
                    <p class="lead text-muted">Entegrasyon testleri ve API kontrolü</p>
                </div>

                <!-- Test Kontrolü -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-play-circle me-2"></i>
                            Test Kontrolü
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <button type="submit" name="run_tests" class="btn btn-primary btn-lg">
                                <i class="fas fa-play me-2"></i>
                                Testleri Çalıştır
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Test Sonuçları -->
                <?php if (!empty($results)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clipboard-check me-2"></i>
                                Test Sonuçları
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($results as $result): ?>
                                <div class="test-result <?= $result['status'] ?>">
                                    <h6 class="mb-2">
                                        <i class="fas fa-<?= $result['status'] === 'success' ? 'check-circle text-success' : 'times-circle text-danger' ?> me-2"></i>
                                        <?= htmlspecialchars($result['name']) ?>
                                        <?php if (isset($result['duration'])): ?>
                                            <span class="test-duration">(<?= $result['duration'] ?>ms)</span>
                                        <?php endif; ?>
                                    </h6>
                                    
                                    <?php if ($result['status'] === 'success'): ?>
                                        <div class="alert alert-success">
                                            <strong>Başarılı!</strong>
                                            <button class="btn btn-sm btn-outline-success ms-2" type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#result-<?= md5($result['name']) ?>">
                                                Detayları Göster
                                            </button>
                                        </div>
                                        <div class="collapse" id="result-<?= md5($result['name']) ?>">
                                            <pre><?= htmlspecialchars(json_encode($result['result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <strong>Hata:</strong> <?= htmlspecialchars($result['error']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Hata Detayları -->
                <?php if (!empty($errors)): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Hata Detayları
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($errors as $error): ?>
                                <div class="error-details mb-3">
                                    <h6 class="text-danger"><?= htmlspecialchars($error['name']) ?></h6>
                                    <p class="mb-2"><strong>Hata:</strong> <?= htmlspecialchars($error['error']) ?></p>
                                    <button class="btn btn-sm btn-outline-danger" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#trace-<?= md5($error['name']) ?>">
                                        Stack Trace Göster
                                    </button>
                                    <div class="collapse mt-2" id="trace-<?= md5($error['name']) ?>">
                                        <pre><?= htmlspecialchars($error['trace']) ?></pre>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Konfigürasyon Bilgileri -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Mevcut Konfigürasyon
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>API Ayarları</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Base URL:</strong> <?= htmlspecialchars($config->get('api.base_url')) ?></li>
                                    <li><strong>Username:</strong> <?= !empty($config->get('api.username')) ? '***' : 'Ayarlanmamış' ?></li>
                                    <li><strong>Client ID:</strong> <?= !empty($config->get('api.client_id')) ? '***' : 'Ayarlanmamış' ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Sistem Ayarları</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Test Modu:</strong> <?= $config->get('test_mode') ? 'Aktif' : 'Pasif' ?></li>
                                    <li><strong>Log Seviyesi:</strong> <?= htmlspecialchars($config->get('logging.level')) ?></li>
                                    <li><strong>Log Dosyası:</strong> <?= htmlspecialchars($config->get('logging.file')) ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kullanım Talimatları -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Kullanım Talimatları
                        </h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li><strong>Konfigürasyon:</strong> Önce <code>.env</code> dosyasında API bilgilerinizi ayarlayın</li>
                            <li><strong>Test Çalıştırma:</strong> "Testleri Çalıştır" butonuna tıklayarak entegrasyonu test edin</li>
                            <li><strong>Sonuçları İnceleme:</strong> Test sonuçlarını ve olası hataları kontrol edin</li>
                            <li><strong>Log Kontrolü:</strong> Hata durumunda log dosyasını inceleyin</li>
                        </ol>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Önemli:</strong> Bu test sayfası sadece geliştirme ve test amaçlıdır. 
                            Gerçek ortamda kullanmadan önce güvenlik önlemlerini alın.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
