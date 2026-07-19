# 📚 PCCM - Phân công Chuyên môn

Ứng dụng web hỗ trợ **phân công chuyên môn** cho giáo viên năm học 2026-2027.

### Tính năng chính
- ✅ Thêm / Sửa / Xóa phân công cực nhanh (chọn Giáo viên + Môn + Lớp → số tiết **tự động**)
- ✅ Thêm nhiều lớp cùng lúc cho 1 giáo viên
- ✅ Quản lý danh sách Giáo viên, Môn học, Lớp
- ✅ Chỉnh số tiết chuẩn theo từng khối
- ✅ Báo cáo tổng hợp đẹp (dạng văn bản giống file Excel cũ)
- ✅ Xuất Excel / Sao lưu JSON
- ✅ Giao diện tiếng Việt, dễ dùng trên máy tính & điện thoại

---

## 🚀 Cách Deploy miễn phí (Streamlit Community Cloud)

### Bước 1: Repo
https://github.com/nguyenhongdanxm/pccm

### Bước 2: Deploy lên Streamlit Cloud

1. Vào https://share.streamlit.io
2. Đăng nhập bằng tài khoản GitHub (`nguyenhongdanxm`)
3. Bấm **New app**
4. Chọn repository: `pccm`
5. Main file path: `app.py`
6. Bấm **Deploy**

Sau 1-2 phút bạn sẽ có link dạng:
```
https://pccm-xxxx.streamlit.app
```

---

## 💻 Chạy local

```bash
pip install -r requirements.txt
streamlit run app.py
```

Mở trình duyệt: http://localhost:8501

---

Made with ❤️ for nhà trường
