# NES Fatura Entegrasyonu

Bu proje, NES Fatura sistemi ile PHP kullanarak entegrasyon sağlayan kapsamlı bir kütüphanedir. E-Fatura ve E-Arşiv faturaları oluşturma, müşteri sorgulama ve fatura yönetimi işlemlerini destekler.

## 🚀 Özellikler

- **E-Fatura Oluşturma**: Müşteri e-fatura kullanıcısı ise e-fatura oluşturma
- **E-Arşiv Faturası**: Müşteri e-arşiv kullanıcısı ise e-arşiv faturası oluşturma
- **Müşteri Sorgulama**: TCSI (11 hane) veya VKN (10 hane) ile müşteri durumu kontrolü
- **Detaylı Hata Yönetimi**: Kapsamlı hata yakalama ve loglama sistemi
- **Test Sayfaları**: Entegrasyonu test etmek için hazır web arayüzleri
- **Güvenli API İletişimi**: OAuth2 token tabanlı kimlik doğrulama
- **Loglama**: Monolog ile detaylı log yönetimi

## 📋 Gereksinimler

- PHP 7.4 veya üzeri
- Composer
- Web sunucusu (Apache/Nginx)
- NES API erişim bilgileri

## 🛠️ Kurulum

### 1. Projeyi Klonlayın

```bash
git clone <repository-url>
cd nes-fatura-entegrasyon
```

### 2. Bağımlılıkları Yükleyin

```bash
composer install
```

### 3. Environment Dosyasını Oluşturun

```bash
cp env.example .env
```

`.env` dosyasını düzenleyerek NES API bilgilerinizi girin:

```env
# NES API Konfigürasyonu
NES_API_BASE_URL=https://developertest.nes.com.tr/api
NES_API_USERNAME=your_username
NES_API_PASSWORD=your_password
NES_API_CLIENT_ID=your_client_id
NES_API_CLIENT_SECRET=your_client_secret

# Loglama
LOG_LEVEL=INFO
LOG_FILE=logs/nes_fatura.log

# Test Modu
TEST_MODE=true
```

### 4. Log Dizinini Oluşturun

```bash
mkdir -p logs
chmod 755 logs
```

## 🔧 Kullanım

### Temel Kullanım

```php
<?php
require_once 'vendor/autoload.php';

use NES\Service\CustomerService;
use NES\Service\InvoiceService;

// Müşteri servisi
$customerService = new CustomerService();

// Müşteri durumunu kontrol et
$status = $customerService->checkCustomerStatus('12345678901'); // TCSI
$status = $customerService->checkCustomerStatus('1234567890');  // VKN

// Fatura servisi
$invoiceService = new InvoiceService();

// E-Fatura oluştur
$invoiceData = [
    'customer_identifier' => '12345678901',
    'invoice_date' => '2024-01-15',
    'due_date' => '2024-02-15',
    'currency' => 'TRY',
    'items' => [
        [
            'name' => 'Ürün Adı',
            'quantity' => 2,
            'unit_price' => 100.00,
            'tax_rate' => 18.0
        ]
    ]
];

$result = $invoiceService->createEInvoice($invoiceData);
```

### Müşteri Sorgulama

```php
// Müşteri arama
$customer = $customerService->searchCustomer('12345678901');

// Müşteri durumu kontrolü
$status = $customerService->checkCustomerStatus('12345678901');

// TCSI/VKN format doğrulama
$isValid = $customerService->validateIdentifier('12345678901');
```

### Fatura İşlemleri

```php
// Fatura durumu sorgulama
$status = $invoiceService->getInvoiceStatus('invoice_id');

// Fatura listesi
$invoices = $invoiceService->getInvoices(['status' => 'active']);

// Fatura iptal etme
$result = $invoiceService->cancelInvoice('invoice_id', 'İptal nedeni');

// PDF indirme
$pdfContent = $invoiceService->downloadInvoicePDF('invoice_id');
```

## 🌐 Test Sayfaları

Proje iki test sayfası içerir:

### 1. Ana Test Sayfası (`public/index.php`)
- Müşteri sorgulama
- Test fatura oluşturma
- Konfigürasyon bilgileri
- Log kayıtları

### 2. API Test Sayfası (`public/api-test.php`)
- Kapsamlı API testleri
- Hata detayları
- Performans ölçümü
- Konfigürasyon kontrolü

## 📁 Proje Yapısı

```
nes-fatura-entegrasyon/
├── src/
│   ├── Config/           # Konfigürasyon yönetimi
│   ├── Client/           # API client sınıfları
│   ├── Service/          # İş mantığı servisleri
│   ├── Exception/        # Özel exception sınıfları
│   └── Logger/           # Loglama sistemi
├── public/               # Web erişilebilir dosyalar
├── logs/                 # Log dosyaları
├── vendor/               # Composer bağımlılıkları
├── composer.json         # Composer konfigürasyonu
├── env.example           # Environment örnek dosyası
└── README.md            # Bu dosya
```

## 🔒 Güvenlik

- API anahtarları `.env` dosyasında saklanır
- `.env` dosyası `.gitignore`'a eklenmiştir
- Tüm kullanıcı girdileri doğrulanır
- HTTPS kullanımı önerilir

## 📝 Loglama

Sistem Monolog kullanarak detaylı loglama yapar:

- **Log Seviyeleri**: DEBUG, INFO, WARNING, ERROR, CRITICAL
- **Log Rotasyonu**: 30 günlük otomatik rotasyon
- **Log Formatı**: Tarih, seviye, mesaj ve bağlam bilgileri

## 🚨 Hata Yönetimi

Özel exception sınıfları ile hata yönetimi:

- `NESException`: Ana exception sınıfı
- API yanıt hataları
- Kimlik doğrulama hataları
- Geçersiz istek hataları
- Sunucu hataları

## 🧪 Test Etme

### 1. Web Arayüzü ile Test
```bash
# Web sunucusunu başlatın
php -S localhost:8000 -t public

# Tarayıcıda açın
http://localhost:8000
```

### 2. API Test Sayfası
```bash
http://localhost:8000/api-test.php
```

### 3. Manuel Test
```php
// Test müşteri verisi
$testData = $customerService->getTestCustomerData('12345678901');

// Test fatura verisi
$testInvoice = $invoiceService->getTestInvoiceData('12345678901');
```

## 🔧 Konfigürasyon

### Environment Değişkenleri

| Değişken | Açıklama | Varsayılan |
|-----------|----------|------------|
| `NES_API_BASE_URL` | NES API base URL | `https://developertest.nes.com.tr/api` |
| `NES_API_USERNAME` | API kullanıcı adı | - |
| `NES_API_PASSWORD` | API şifresi | - |
| `NES_API_CLIENT_ID` | Client ID | - |
| `NES_API_CLIENT_SECRET` | Client Secret | - |
| `LOG_LEVEL` | Log seviyesi | `INFO` |
| `LOG_FILE` | Log dosya yolu | `logs/nes_fatura.log` |
| `TEST_MODE` | Test modu | `true` |

## 📞 Destek

- **Dokümantasyon**: [NES Developer Portal](https://developertest.nes.com.tr/docs/)
- **E-posta**: entegrasyon@nesbilgi.com.tr
- **GitHub Issues**: Proje repository'sinde issue açın

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit yapın (`git commit -m 'Add amazing feature'`)
4. Push yapın (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## 📋 Changelog

### v1.0.0
- İlk sürüm
- E-Fatura ve E-Arşiv desteği
- Müşteri sorgulama
- Detaylı hata yönetimi
- Test sayfaları
- Loglama sistemi

## ⚠️ Uyarılar

- Bu proje sadece test amaçlıdır
- Gerçek ortamda kullanmadan önce güvenlik testleri yapın
- API anahtarlarınızı güvenli tutun
- Log dosyalarını düzenli olarak temizleyin
