# JSON Schema Help

Bu alan, Yacht Selector plugin içindeki **JSON Data Import** bölümü için kullanılacak örnek veri yapısını açıklar.

Bu JSON yapısı, yat verilerini toplu olarak sisteme aktarmak için kullanılır.

---

## Genel Mantık

- JSON kök yapısı bir **array** olmalıdır
- Her item bir yatı temsil eder
- Sistem bu veriyi okuyup kendi iç veri yapısına map eder
- Eksik taxonomy değerleri gerekiyorsa otomatik oluşturulabilir
- Bazı alanlar opsiyoneldir, bazıları ise güçlü şekilde önerilir

---

## Temel JSON Örneği

```json
[
  {
    "title": "Lucky You",
    "description": "Modern motor yacht for private charters.",
    "url": "https://example.com/yachts/lucky-you",
    "image": "https://example.com/uploads/lucky-you.jpg",

    "country": {
      "name": "Turkey",
      "slug": "turkey"
    },

    "port": {
      "name": "Bodrum",
      "slug": "bodrum"
    },

    "availability": ["2026-06", "2026-07", "2026-08"],

    "model": "Motor Yacht",
    "length": 28.5,
    "beam": 6.2,
    "engine": "2 x 1800 HP",
    "build_year": 2018,
    "refit_year": 2023,
    "cabins": 4,
    "guests": 8,
    "crew": 4,
    "priority": 90,

    "features": ["WiFi", "Generator", "Water Toys"],

    "cta": {
      "watch_video": "https://example.com/yachts/lucky-you",
      "video_call": "https://meet.google.com/abc-defg-hij",
      "schedule": "https://calendly.com/example/lucky-you"
    }
  }
]
```

---

## Alan Açıklamaları

### `title`
Yat adı.

**Tip:** string  
**Örnek:**
```json
"title": "Lucky You"
```

---

### `description`
Kısa açıklama veya tanıtım metni.

**Tip:** string  
**Örnek:**
```json
"description": "Modern motor yacht for private charters."
```

---

### `url`
Yatın dış linki veya detay bağlantısı.

**Tip:** string (URL)  
**Örnek:**
```json
"url": "https://example.com/yachts/lucky-you"
```

---

### `image`
Kartta kullanılacak ana görsel URL’si.

**Tip:** string (URL)  
**Örnek:**
```json
"image": "https://example.com/uploads/lucky-you.jpg"
```

---

## Lokasyon Yapısı

### `country`
Ülke bilgisidir. Parent taxonomy gibi düşünülür.

**Tip:** object

```json
"country": {
  "name": "Turkey",
  "slug": "turkey"
}
```

### `port`
Liman bilgisidir. Country altında child term olarak düşünülebilir.

**Tip:** object

```json
"port": {
  "name": "Bodrum",
  "slug": "bodrum"
}
```

### Önemli Not
- `country` yoksa sistem fallback olarak `unknown` kullanabilir
- `port` varsa ama `country` eksikse, port `unknown` ülkesinin altına bağlanabilir
- `slug` alanı stabil ve temiz olmalıdır

---

## Availability

### `availability`
Bu alan uygun ayları temsil eder.

**Tip:** array  
**Format:** `YYYY-MM`

```json
"availability": ["2026-06", "2026-07", "2026-08"]
```

### Not
Bu alan frontend’de ay seçimi ve `available` durumu için kullanılır.

---

## Teknik / Meta Alanları

### `model`
Yat tipi veya model sınıfı.

```json
"model": "Motor Yacht"
```

### `length`
Yat uzunluğu.

**Tip:** number  
**Decimal destekler**

```json
"length": 28.5
```

### `beam`
Yat genişliği.

**Tip:** number  
**Decimal destekler**

```json
"beam": 6.2
```

### `engine`
Motor açıklaması.

```json
"engine": "2 x 1800 HP"
```

### `build_year`
Üretim yılı.

```json
"build_year": 2018
```

### `refit_year`
Refit / yenileme yılı.

```json
"refit_year": 2023
```

### `cabins`
Kabin sayısı.

```json
"cabins": 4
```

### `guests`
Misafir kapasitesi.

```json
"guests": 8
```

### `crew`
Mürettebat sayısı.

```json
"crew": 4
```

### `priority`
Frontend sıralama önceliği.

```json
"priority": 90
```

### Not
Priority değeri ne kadar yüksekse, sıralamada o kadar yukarı çıkabilir.

---

## Extra Features

### `features`
Yata ait ek özelliklerin listesi.

**Tip:** array of strings

```json
"features": ["WiFi", "Generator", "Water Toys"]
```

### Not
- Ayarlarda yoksa import sırasında otomatik eklenebilir
- Duplicated / tekrar eden feature’lardan kaçının

---

## CTA Yapısı

### `cta`
İsteğe bağlı aksiyon linkleri.

**Tip:** object

```json
"cta": {
  "watch_video": "https://example.com/yachts/lucky-you",
  "video_call": "https://meet.google.com/abc-defg-hij",
  "schedule": "https://calendly.com/example/lucky-you"
}
```

### Açıklama
- `watch_video` daha sonra detay sayfası davranışına map edilebilir
- `video_call` harici görüşme linki olabilir
- `schedule` harici rezervasyon / görüşme planlama linki olabilir

---

## Minimal Çalışan JSON Örneği

Aşağıdaki örnek minimum seviyede anlamlı bir item üretir:

```json
[
  {
    "title": "Lucky You",
    "country": {
      "name": "Turkey",
      "slug": "turkey"
    },
    "port": {
      "name": "Bodrum",
      "slug": "bodrum"
    },
    "availability": ["2026-06"],
    "model": "Motor Yacht",
    "length": 28.5,
    "beam": 6.2,
    "priority": 90
  }
]
```

---

## Önerilen Alanlar

Mümkün olduğunca aşağıdaki alanları eklemeniz önerilir:

- `title`
- `country`
- `port`
- `availability`
- `model`
- `length`
- `beam`
- `priority`
- `features`

---

## Validation Kuralları

JSON import sırasında genel beklentiler:

- JSON geçerli olmalı
- Root bir array olmalı
- Her item object olmalı
- `length` ve `beam` decimal olabilir
- `availability` ay formatı `YYYY-MM` olmalı
- URL alanları tam ve geçerli URL olmalı

---

## Sık Yapılan Hatalar

### 1. Root array yerine object kullanmak
Yanlış:
```json
{
  "title": "Lucky You"
}
```

Doğru:
```json
[
  {
    "title": "Lucky You"
  }
]
```

### 2. Availability formatını yanlış girmek
Yanlış:
```json
"availability": ["June", "July"]
```

Doğru:
```json
"availability": ["2026-06", "2026-07"]
```

### 3. Decimal alanları string karışık yazmak
Tercihen:
```json
"length": 28.5,
"beam": 6.2
```

### 4. Country / port object yerine düzensiz string yazmak
Yanlış:
```json
"country": "Turkey",
"port": "Bodrum"
```

Doğru:
```json
"country": {
  "name": "Turkey",
  "slug": "turkey"
},
"port": {
  "name": "Bodrum",
  "slug": "bodrum"
}
```

---

## Kısa Öneri

En güvenli yaklaşım:

- root array kullanın
- her yat için tek bir object yazın
- `country` ve `port` alanlarını object formatında verin
- `availability` için `YYYY-MM` kullanın
- `length` ve `beam` için decimal sayı kullanabilirsiniz
- URL alanlarında tam link kullanın
- `slug` değerlerini temiz ve tutarlı tutun

Bu sayede import işlemi daha temiz, daha stabil ve daha ölçeklenebilir olur.
