# Contact URL Help (Advanced)

Bu alan, crew member için seçilen iletişim araçlarına özel bağlantıların (URL) nasıl oluşturulacağını açıklar.

Frontend’de kullanıcı bir contact tool’a tıkladığında:
→ Bu URL direkt olarak açılır  
→ Bu yüzden URL’nin doğru ve çalışır olması kritik öneme sahiptir

---

# Genel Mantık

Her contact tool için:

- 1 tool = 1 URL
- URL → doğrudan iletişim başlatır
- Eksik / yanlış URL → kullanıcı deneyimini bozar

---

# Temel URL Kuralları

## Doğru formatlar

| Kullanım        | Format        |
|----------------|--------------|
| Web link       | https://...  |
| Email          | mailto:...   |
| Telefon        | tel:...      |
| FaceTime       | facetime:... |

---

## En sık yapılan hata

❌ Yanlış:
wa.me/90555...
hello@example.com
meet.google.com/abc

✅ Doğru:
https://wa.me/905551112233
mailto:hello@example.com
https://meet.google.com/abc-defg-hij

---

# Contact Tools + Advanced Usage

## 1. Phone Call

Basic:
tel:+905551112233

Notlar:
- Uluslararası format kullan (+90)
- Boşluk kullanma
- tel: zorunlu

---

## 2. Email

Basic:
mailto:hello@example.com

Subject ile:
mailto:hello@example.com?subject=Yacht Inquiry

Subject + Body:
mailto:hello@example.com?subject=Yacht Inquiry&body=Hello I want more info

Encoded:
mailto:hello@example.com?subject=Yacht%20Inquiry&body=Hello%20I%20want%20more%20info

---

## 3. WhatsApp

Basic:
https://wa.me/905551112233

Mesaj ile:
https://wa.me/905551112233?text=Hello I want to book this yacht

Encoded:
https://wa.me/905551112233?text=Hello%20I%20want%20to%20book%20this%20yacht

Dynamic mesaj önerisi:
Hello, I'm interested in {{yacht_name}}

Encoded:
Hello%2C%20I%27m%20interested%20in%20Lucky%20You

---

## 4. Google Meet

https://meet.google.com/abc-defg-hij

Not:
- Tam link olmalı
- Eksik link kullanma

---

## 5. Zoom

https://zoom.us/j/1234567890

Şifreli:
https://zoom.us/j/1234567890?pwd=abc123

---

## 6. FaceTime

Email:
facetime:hello@example.com

Telefon:
facetime:+905551112233

---

## 7. Telegram

https://t.me/username

---

## 8. Microsoft Teams

https://teams.microsoft.com/l/meetup-join/...

---

## 9. Booking / Calendly

https://calendly.com/crew/15min

---

## 10. Custom Tool

https://example.com/custom-contact

---

# Parametre Kullanımı (Advanced)

Genel yapı:
https://example.com?param=value

---

## Email parametreleri

subject → konu  
body → mesaj  

---

## WhatsApp parametreleri

text → mesaj  

---

## Örnek (dynamic mesaj)

https://wa.me/905551112233?text=Hello%20I%20want%20to%20connect%20about%20{{yacht_name}}

---

# Dynamic Data Kullanımı (Öneri)

Aşağıdaki placeholder’lar kullanılabilir:

{{yacht_name}}  
{{port}}  
{{model}}  

Örnek:
Hello, I'm interested in {{yacht_name}} in {{port}}

---

# Güvenli URL Scheme Listesi

https://  
http://  
mailto:  
tel:  
facetime:  

---

# Validation Kuralları

- Boş URL → disable
- Invalid URL → save etme
- Seçili tool yoksa → input gösterme

---

# Sık Yapılan Hatalar

Eksik URL:
❌ meet.google.com/xxx  
✅ https://meet.google.com/xxx  

Email yanlış:
❌ hello@example.com  
✅ mailto:hello@example.com  

Telefon yanlış:
❌ 0555 111 22 33  
✅ tel:+905551112233  

WhatsApp yanlış:
❌ https://wa.me/+90 555...  
✅ https://wa.me/90555...  

---

# Best Practice

✔ Her zaman tam URL kullan  
✔ Test edilmiş link gir  
✔ Mümkünse otomatik mesaj ekle  
✔ Crew özel link kullan  
✔ Tool bazlı URL map mantığını koru  

---

# Final Not

Girilen URL:
→ direkt kullanıcıya açılır  
→ crew ile iletişimin ana entry point’idir  

Bu yüzden:
Doğru, temiz ve test edilmiş URL girilmesi kritik öneme sahiptir