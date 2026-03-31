# Contact URL Help

Bu alanda, seçtiğiniz iletişim aracı için geçerli bir bağlantı (URL) girmeniz gerekir.

Kullanıcı frontend’de ilgili contact tool’a tıkladığında, bu bağlantı açılır.

Sistem gerekirse bağlantı içindeki bazı özel alanları da otomatik olarak değiştirir. Böylece linke o an görüntülenen yatın bilgisi eklenebilir.

---

## Genel Kurallar

- Web tabanlı bağlantılarda tam adres kullanın:
  - `https://...`
- Telefon için:
  - `tel:`
- E-posta için:
  - `mailto:`
- FaceTime için:
  - `facetime://`
  - veya `facetime-audio://`

Eksik ya da yarım link kullanmayın.

### Yanlış örnekler
- `wa.me/905551112233`
- `hello@example.com`
- `meet.google.com/abc-defg-hij`

### Doğru örnekler
- `https://wa.me/905551112233`
- `mailto:hello@example.com`
- `https://meet.google.com/abc-defg-hij`

---

## Dinamik Placeholder Desteği

İsterseniz URL içinde aşağıdaki placeholder’ları kullanabilirsiniz:

- `{post_id}` → o anki yatın WordPress post ID değeri
- `{yacht_id}` → yat ID değeri
- `{permalink}` → yatın detay sayfası linki

Sistem kullanıcı tıklamadan hemen önce bu alanları gerçek değerlerle değiştirir.

### Örnek
https://wa.me/905551112233?text=Hello%2C%20I%20am%20interested%20in%20{permalink}

Tıklama anında bu yapı şu hale gelir:
https://wa.me/905551112233?text=Hello%2C%20I%20am%20interested%20in%20https://example.com/yacht/lucky-you

### Önerilen minimum placeholder seti
- `{post_id}`
- `{yacht_id}`
- `{permalink}`

---

## 1. WhatsApp

Temel format:
https://wa.me/905551112233

İlk mesaj ile:
https://wa.me/905551112233?text=Hello%20I%20want%20to%20connect

Yat linki ile örnek:
https://wa.me/905551112233?text=Hello%2C%20I%20am%20interested%20in%20{permalink}

Notlar:
- Numara uluslararası formatta olmalıdır
- `+`, boşluk, parantez ve tire kullanmayın
- `text=` parametresindeki mesaj URL encoded olmalıdır

---

## 2. Phone Call

Temel format:
tel:+905551112233

Notlar:
- Telefon numarasını uluslararası formatta girin
- Genelde mesaj desteklemez
- Uyumlu cihazlarda doğrudan arama ekranı açılır

---

## 3. Email

Temel format:
mailto:hello@example.com

Konu ile:
mailto:hello@example.com?subject=Yacht%20Inquiry

Konu ve mesaj ile:
mailto:hello@example.com?subject=Yacht%20Inquiry&body=Hello%20I%20want%20more%20information

Dinamik örnek:
mailto:hello@example.com?subject=Inquiry%20for%20{yacht_id}&body=Hello%2C%20I%20am%20interested%20in%20{permalink}

Notlar:
- `subject` ve `body` alanlarını encode edin
- Sadece e-posta adresi yazmayın, mutlaka `mailto:` ile başlayın

---

## 4. Google Meet

Temel format:
https://meet.google.com/abc-defg-hij

Notlar:
- Toplantının tam linkini yapıştırın
- Kısmi kod yerine tam URL kullanın

---

## 5. Zoom

Temel format:
https://zoom.us/j/1234567890

Şifre parametresi varsa:
https://zoom.us/j/1234567890?pwd=abc123

Notlar:
- En güvenlisi tam davet linkini kullanmaktır
- Linki elle yeniden kurmaya çalışmayın

---

## 6. Microsoft Teams

Temel format:
https://teams.microsoft.com/l/meetup-join/...

Notlar:
- Tam davet linkini kullanın
- Kısaltılmış ya da yarım URL kullanmayın

---

## 7. Calendly / Booking Link

Temel format:
https://calendly.com/crew-member/15min

Parametreli örnek:
https://calendly.com/crew-member/15min?utm_source=yacht-selector&utm_content={post_id}

Notlar:
- Genel rezervasyon veya görüşme planlama linkleri için uygundur
- Placeholder kullanımı burada özellikle faydalıdır

---

## 8. Telegram

Temel format:
https://t.me/username

Mesaj taslağı ile:
https://t.me/username?text=Hello%20I%20am%20interested%20in%20this%20yacht

Dinamik örnek:
https://t.me/username?text=Hello%2C%20I%20am%20interested%20in%20{permalink}

Notlar:
- Kullanıcı adı varsa `t.me/username` formatı kullanılabilir
- `text=` parametresi kullanılacaksa düzgün encode edilmelidir

---

## 9. FaceTime

Video görüşme:
facetime://hello@example.com
veya
facetime://+905551112233

Sesli görüşme:
facetime-audio://hello@example.com
veya
facetime-audio://+905551112233

Notlar:
- Özellikle Apple ekosisteminde kullanışlıdır
- Cihaz desteğine bağlı olarak davranış değişebilir

---

## 10. Generic HTTPS Tool

Temel format:
https://example.com/contact-path

Dinamik örnek:
https://example.com/inquiry?yacht={post_id}&url={permalink}

Uygun kullanım alanları:
- özel iletişim sayfası
- booking formu
- CRM formu
- canlı demo odası
- özel portal

---

## Kısa Öneri

- tam URL kullanın
- uygun şemayı kullanın (`https`, `mailto`, `tel`, `facetime`)
- gerekiyorsa sadece temel parametreleri ekleyin
- yat bilgisini eklemek istiyorsanız placeholder kullanın:
  - `{post_id}`
  - `{yacht_id}`
  - `{permalink}`
