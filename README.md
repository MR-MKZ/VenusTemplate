## مراحل نصب قالب ونوس

### مرزبان

1. **قالب رو با دستور زیر دانلود کنید**
   ```sh
   sudo wget -N -P /var/lib/marzban/templates/subscription/ https://github.com/MR-MKZ/VenusTemplate/releases/download/v1.2.0/index.html
   ```

2. **دستورات زیر رو تو ترمینال سرورتون بزنید**
   ```sh
   echo 'CUSTOM_TEMPLATES_DIRECTORY="/var/lib/marzban/templates/"' | sudo tee -a /opt/marzban/.env
   echo 'SUBSCRIPTION_PAGE_TEMPLATE="subscription/index.html"' | sudo tee -a /opt/marzban/.env
   ```
   یا مقادیر زیر رو در فایل `.env` در پوشه `/opt/marzban` با پاک کردن `#` اول آنها از حالت کامنت در بیارید.
   ```
   CUSTOM_TEMPLATES_DIRECTORY="/var/lib/marzban/templates/"
   SUBSCRIPTION_PAGE_TEMPLATE="subscription/index.html"
   ```

3. **توکن لایسنس خود را در فایل قرار دهید**
   ```sh
   sudo nano /var/lib/marzban/templates/subscription/index.html
   ```

4. **ریستارت کردن مرزبان**
   ```sh
   marzban restart
   ```

## آموزش شخصی سازی لیست سیستم عامل ها و نرم افزار ها

1.**این ریپو رو فورک کنید و در فایل های apps.json و os.json تغییرات مد نظر خود را وارد کنید.**

2.**لینک raw فایل ها رو کپی کنید و در بخش مربوط در فایل `index.html` یا `index.php` قرار دهید.تب**
