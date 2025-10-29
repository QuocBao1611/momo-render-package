# HƯỚNG DẪN CÀI ĐẶT MOMO TEST (SANDBOX)

1️⃣ Trên InfinityFree:
- Upload file `ipn_bridge.php` vào thư mục `/Web/api/order/`
- Đảm bảo `create_momo.php` có dòng:
  $ipnUrl = "https://techstore-momo.onrender.com/ipn_bridge.php";

2️⃣ Trên Render:
- Tạo service mới (Web Service)
- Upload toàn bộ thư mục `MoMo_Render_Package/`
- Cấu hình:
  Runtime: PHP 8.2
  Start command: `php -S 0.0.0.0:10000 -t .`

3️⃣ Khi thanh toán MoMo thành công:
- MoMo → Render (webhook_momo.php)
- Render → InfinityFree (ipn_bridge.php)
- InfinityFree → cập nhật orders.status = 'paid'

4️⃣ Kiểm tra log:
- Trong Render: `momo_ipn_log.txt`
- Trong InfinityFree: `orders` table (status = paid)
