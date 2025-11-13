# เว็บข่าวประชาสัมพันธ์ + ศูนย์ดาวน์โหลด (PHP + Tailwind)

ไฟล์นี้อธิบายการตั้งค่าโครงการ, ทางเลือกฐานข้อมูล, วิธีลิงก์ไฟล์จาก Google Drive และตัวอย่างการเพิ่ม/ลบ ข่าวกับเอกสาร

---

## สถานะปัจจุบัน
- โค้ดตัวอย่างใช้ PHP + Tailwind (CDN)
- รองรับฐานข้อมูลแบบ MySQL (ค่าเริ่มต้นในโปรเจ็กต์นี้) หรือ SQLite (มีสคริปต์เดิมสำหรับทดสอบแบบ local)
- ไฟล์สำคัญ:
	- `index.php`, `news.php`, `news-detail.php`, `downloads.php`, `contact.php`
	- `includes/config.php` — ตั้งค่า DB และ `BASE_URL`
	- `includes/header.php`, `includes/footer.php`
	- `mysql_schema.sql` — ใช้นำเข้าใน phpMyAdmin (สร้างตาราง `news` และ `documents`)

> หมายเหตุ: สคริปต์ `setup_db.php` สำหรับ SQLite ถูกลบทิ้งจาก repo นี้ เพราะคุณใช้ XAMPP/phpMyAdmin (MySQL)

---

## การตั้งค่าเบื้องต้น (XAMPP + phpMyAdmin)
1. วางโฟลเดอร์โปรเจ็กต์ไว้ที่ `C:\xampp\htdocs\your-folder` (หรือวางไฟล์ตรง `htdocs`)
2. เปิด XAMPP Control Panel → Start `Apache` และ `MySQL`
3. เปิด http://localhost/phpmyadmin/ → สร้างฐานข้อมูลชื่อตัวอย่าง `webtest` (หรือชื่อที่ต้องการ)
4. นำเข้าไฟล์ `mysql_schema.sql` (Import → เลือกไฟล์ → Go)
5. ตรวจว่าตาราง `news` และ `documents` ถูกสร้างและมีข้อมูลตัวอย่าง
6. ใน `includes/config.php` ตรวจค่าว่า:
	 - `DB_TYPE` ตั้งเป็น `'mysql'`
	 - `MYSQL_HOST` ปกติเป็น `127.0.0.1` หรือ `localhost`
	 - `MYSQL_DB` = `webtest` (ชื่อฐานข้อมูลที่สร้าง)
	 - `MYSQL_USER` = `root` (ค่าเริ่มต้น XAMPP)
	 - `MYSQL_PASS` = '' (ค่าว่าง ถ้าไม่มีรหัส)
7. เปิดเว็บที่ `http://localhost/your-folder/` (หรือ `http://localhost/` ถาวางไฟล์ไว้ที่ root)

---

## การลิงก์ไฟล์กับ Google Drive (วิธีง่าย — แบบแมนนวล)
1. อัปโหลดไฟล์ (PDF/DOCX/...) ไปที่ Google Drive
2. คลิกขวาที่ไฟล์ → Share → Change to "Anyone with the link" (หรือตั้งเป็น Viewer)
3. คัดลอกลิงก์ที่ได้ ตัวอย่างลิงก์แบบปกติ:
	 - `https://drive.google.com/file/d/DRIVE_FILE_ID/view?usp=sharing`
4. เก็บ `DRIVE_FILE_ID` และลิงก์นี้ในฐานข้อมูลช่อง `drive_id` และ `drive_url` ของตาราง `documents`

Direct download (ทางเลือก)
	`https://drive.google.com/uc?export=download&id=DRIVE_FILE_ID`

ตัวอย่าง SQL เพิ่มเอกสาร (phpMyAdmin → SQL):

```sql
INSERT INTO documents (title, description, category, drive_id, drive_url, uploaded_at, tags, views)
VALUES ('แบบฟอร์มตัวอย่าง', 'คำอธิบาย', 'แบบฟอร์ม', 'DRIVE_FILE_ID', 'https://drive.google.com/file/d/DRIVE_FILE_ID/view?usp=sharing', NOW(), 'form', 0);
```

---

## การสร้างบัญชีผู้ดูแล (admins table)
สำหรับระบบ admin panel ปัจจุบัน เราเก็บบัญชีผู้ดูแลไว้ในตาราง `admins` ของฐานข้อมูล

1. สร้างตาราง `admins` โดยใช้ `mysql_schema.sql` (จะมีอยู่แล้วใน repo)
2. สร้าง password hash ด้วยคำสั่ง PHP (บนเครื่องที่มี PHP CLI):

```powershell
php -r "echo password_hash('รหัสผ่านที่ต้องการ', PASSWORD_DEFAULT).PHP_EOL;"
```

3. นำค่าที่ได้ไป INSERT ใน phpMyAdmin ตัวอย่าง:

```sql
INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$...yourhash...');
```

4. ตอนนี้คุณสามารถเข้าสู่ระบบที่ `http://localhost/login.php` ด้วยบัญชีที่สร้าง

หมายเหตุความปลอดภัย: เก็บรหัสผ่านเป็น hash เสมอ และอย่าเก็บไฟล์คีย์ service-account.json ใน repo สาธารณะ

ตัวอย่างการเพิ่มเอกสารจาก PHP (pseudo-code):

```php
$pdo = get_db();
$stmt = $pdo->prepare("INSERT INTO documents (title, description, category, drive_id, drive_url, uploaded_at) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$title, $description, $category, $drive_id, $drive_url, date('Y-m-d H:i:s')]);
```

การดาวน์โหลดและนับสถิติ
- เมื่อผู้ใช้คลิกปุ่มดาวน์โหลด ให้ลิงก์ไปที่ `drive_url` (เปิดใหม่ `target="_blank"`) หรือสร้างสคริปต์ PHP กลางเพื่อนับ `views` แล้ว redirect ไปที่ `drive_url`:

```php
// download.php?id=123
$id = (int)$_GET['id'];
$stmt = $pdo->prepare('SELECT drive_url, views FROM documents WHERE id=?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if ($row) {
	$pdo->prepare('UPDATE documents SET views = views + 1 WHERE id = ?')->execute([$id]);
	header('Location: ' . $row['drive_url']);
	exit;
}
```

---

## การเพิ่ม/แก้ไข/ลบ ข่าวประชาสัมพันธ์
วิธีง่าย ๆ (phpMyAdmin):
- เข้า phpMyAdmin → เลือกฐานข้อมูล `webtest` → เลือกตาราง `news` → Insert

ตัวอย่าง SQL เพิ่มข่าว

```sql
INSERT INTO news (title, content, image, date_posted, category)
VALUES ('หัวข้อข่าว', 'เนื้อหาเต็มของข่าว...', '', NOW(), 'ประกาศ');
```

แก้ไขข่าว
- ใช้คำสั่ง UPDATE ใน phpMyAdmin หรือหน้า admin panel:

```sql
UPDATE news SET title='แก้หัวข้อ', content='แก้เนื้อหา' WHERE id = 5;
```

ลบข่าว

```sql
DELETE FROM news WHERE id = 5;
```

แนะนำ: สร้างหน้า admin (อนาคต) ที่มีฟอร์มสำหรับ เพิ่ม/แก้ไข/ลบ เพื่อความปลอดภัยและใช้ง่าย

---

## ถ้าต้องการเชื่อม Google Drive อัตโนมัติ (อัปโหลดจากเว็บ → Drive)
1. สร้าง Project ใน Google Cloud Console และเปิด Google Drive API
2. สร้าง Service Account และดาวน์โหลดไฟล์ JSON ของคีย์
3. ติดตั้งไลบรารี PHP: `composer require google/apiclient:^2.0`
4. ตัวอย่าง flow (โดยสรุป):
	 - อัปโหลดไฟล์จากฟอร์ม (multipart/form-data)
	 - ใช้ google/apiclient เพื่ออัปโหลดไฟล์ไปโฟลเดอร์ที่กำหนดบน Drive
	 - เปลี่ยนสิทธิ์ไฟล์เป็น 'anyoneWithLink' (permissions)
	 - เก็บ `fileId` และ `webViewLink`/`webContentLink` ลงในฐานข้อมูล

ตัวอย่าง (แนวคิด) ด้วย google/apiclient

```php
// โหลด service account credentials และตั้งค่า client
$client->setAuthConfig('/path/to/service-account.json');
$service = new Google_Service_Drive($client);
$fileMetadata = new Google_Service_Drive_DriveFile(['name' => $filename, 'parents' => [$folderId]]);
$content = file_get_contents($tmpPath);
$file = $service->files->create($fileMetadata, ['data' => $content, 'uploadType' => 'multipart']);
// ตั้ง permission
$permission = new Google_Service_Drive_Permission(['type'=>'anyone','role'=>'reader']);
$service->permissions->create($file->id, $permission);
// เก็บ $file->id และ $file->webViewLink ลง DB
```

รายละเอียดขั้นตอนและ security
- เก็บไฟล์คีย์ (service-account.json) ให้ปลอดภัย นอก repo
- จำกัดสิทธิ์ของ Service Account ตามความจำเป็น

---

## คำแนะนำเพิ่มเติม
- ถ้าใช้งานจริง ควรสร้างหน้าจัดการ (admin) ที่ล็อกอินได้ เพื่อเพิ่ม/แก้ไข/ลบข่าวและเอกสาร แทนการใช้ phpMyAdmin โดยตรง
- ใช้ prepared statements (PDO) เสมอเพื่อป้องกัน SQL injection
- ถ้าต้องการผมสามารถสร้างหน้า admin เบื้องต้นให้ (รวม login และฟอร์มอัปโหลด)

---

ถ้าต้องการให้ผมลบ `setup_db.php` ต่อไปหรือคืนไว้เพื่อการทดสอบแบบ SQLite แจ้งมาได้ครับ — ตอนนี้ผมได้ลบไฟล์ให้แล้วตามคำขอ

อยากให้ผมสร้างตัวอย่างหน้า admin ที่สามารถเพิ่ม/แก้/ลบข่าวและเอกสารเลยหรือไม่? (ผมจะทำฟอร์ม + การเชื่อม DB ให้พร้อม)
