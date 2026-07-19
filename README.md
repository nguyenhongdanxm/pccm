# PCCM – Phân công Chuyên môn

Ứng dụng web quản lý phân công giảng dạy giáo viên năm học 2026-2027.

### Tính năng
- Thêm phân công (chọn Giáo viên + Môn + Lớp → **số tiết tự động**)
- Thêm nhanh nhiều lớp cùng lúc
- Danh sách + lọc + xóa
- Báo cáo tổng hợp dạng văn bản đẹp
- Quản lý Giáo viên / Môn học / Số tiết chuẩn / Lớp
- Xuất CSV

---

## Deploy lên Vietnix (cPanel)

### Bước 1: Clone từ GitHub

Trong cPanel → **Git Version Control** → Create Repository:

- Clone URL: `https://github.com/nguyenhongdanxm/pccm`
- Repository Path: `repositories/pccm`
- Repository Name: `pccm`

Bấm **Create**.

### Bước 2: Tạo Python App

1. Vào cPanel → **Setup Python App**
2. Bấm **Create Application**
3. Điền:
   - **Python version**: 3.10 hoặc 3.11
   - **Application root**: `repositories/pccm`
   - **Application URL**: chọn domain/subdomain
   - **Application startup file**: `passenger_wsgi.py`
   - **Application Entry point**: `application`
4. Bấm **Create**

### Bước 3: Cài thư viện

Trong Setup Python App → Run Pip Install:
```
flask
```

Hoặc Terminal:
```bash
source ~/virtualenv/repositories/pccm/<version>/bin/activate
cd ~/repositories/pccm
pip install -r requirements.txt
```

### Bước 4: Restart App

Trong **Setup Python App** → bấm **Restart**.

---

## Chạy local

```bash
pip install -r requirements.txt
python app.py
```

Mở http://localhost:5000

---

Made with ❤️ for nhà trường
