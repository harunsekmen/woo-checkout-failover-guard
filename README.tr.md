# 🛡️ WooCommerce Ödeme Failover & Güvenlik Kalkanı

[🇬🇧 English](README.md) · [🇹🇷 Türkçe](README.tr.md)

> Ödeme ağ geçidi gecikmelerinde ve sunucu zaman aşımlarında (504 Gateway Timeout) donan ödeme ekranlarını çözen, mükerrer çekimleri engelleyen ve siparişi kurtaran istemci taraflı devre kesici katmanı.

Geliştirici ve Bakım Sorumlusu: **[Harun Sekmen](https://github.com/harunsekmen)** — E-Ticaret Sistem Mühendisi & WooCommerce Uzmanı.

[✉️ İletişime Geç](mailto:sekmenwp@gmail.com) · [💼 Upwork Profili](https://www.upwork.com/freelancers/~01a7a8b58043e6e6d9) · [🔗 GitHub Ana Profil](https://github.com/harunsekmen)

---

## 📸 Görsel Önizlemeler ve Yönetim Paneli

### 1. Mağaza İçi İstemci Devre Kesici (Circuit Breaker)
Ödeme ağ geçidi kilitlendiğinde sonsuz dönen yükleme simgesi kaldırılır ve müşteriye net kurtarma seçenekleri sunulur:

![Ödeme Devre Kesici Önizlemesi](docs/assets/checkout-failover-preview.png)

### 2. WordPress Yönetici Telemetri ve Sağlık Paneli
Yöneticiler ağ geçidi yanıt sürelerini izleyebilir, kurtarılan siparişleri görebilir ve zaman aşımı limitlerini ayarlayabilir:

![Ödeme Güvenlik Paneli](docs/assets/admin-telemetry-preview.png)

---

## ⚡ Çözdüğü Kritik Sorun

Trafik patlamalarında, indirim dönemlerinde veya ödeme altyapılarındaki (Stripe, PayPal, sanal POS) anlık gecikmelerde:
- **Donan Ödeme Ekranları:** Ödeme sayfası sonsuz dönen gri bir yükleme simgesinde asılı kalır. Müşteri siparişin geçip geçmediğini anlayamaz.
- **Mükerrer Kart Çekimleri:** Panikleyen müşteri sayfayı yeniler veya 'Siparişi Onayla' butonuna tekrar tekrar tıklar; kartından iki kez para çekilme riski doğar.
- **Askıda Kalan Siparişler:** Sipariş veritabanında 'Ödeme Bekliyor' (Pending Payment) olarak asılı kalır ve müşteri hizmetlerine iş çıkarır.

**WooCommerce Checkout Failover Guard**, ödeme akışına asenkron bir devre kesici ekler. Ağ geçidi makul sürede yanıt vermezse formu açar, net bir bilgilendirme yapar ve müşterinin bilgilerini koruyarak tekrar denemesine imkan tanır.

---

## 🛠️ Öne Çıkan Özellikler

- **Asenkron Zaman Aşımı Kesici:** Yanıt vermeyen ağ geçitlerinde donan ekranı çözer ve formun kontrolünü müşteriye geri verir.
- **Müşteri Kendi Kendini Kurtarır:** Adres bilgilerini baştan yazmak zorunda kalmadan ödemeyi tekrar deneyebilir veya alternatif yönteme geçebilir.
- **Mükerrer Çekim Koruması:** Butona art arda basılsa bile sipariş durumunu kilitleyerek birden fazla tahsilatı engeller.
- **Yönetici Telemetrisi:** Ağ geçitlerinin yanıt sürelerini ve hata sıklıklarını WooCommerce yönetim panelinde canlı gösterir.

---

## 🏗️ Teknik Mimari ve Standartlar

`PHP 8.1+` • `WooCommerce HPOS` • `Action Scheduler` • `Vanilla ES6+` • `Circuit Breaker Pattern`

---

## 🚀 Kurulum ve Test

1. Depoyu eklentiler dizinine klonlayın:
   ```bash
   git clone https://github.com/harunsekmen/woo-checkout-failover-guard.git wp-content/plugins/woo-checkout-failover-guard
   ```
2. Test paketini çalıştırın:
   ```bash
   php tests/test-guard.php
   ```

---

## 📄 Lisans

Bu proje **GPL-3.0 Lisansı** ile açık kaynak olarak sunulmaktadır.
