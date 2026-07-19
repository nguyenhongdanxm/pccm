#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
PCCM - Phân công Chuyên môn
Ứng dụng quản lý phân công giảng dạy cho giáo viên
"""

from flask import Flask, render_template, request, redirect, url_for, flash, jsonify, send_file
from pathlib import Path
import json
from datetime import datetime
from io import BytesIO
import csv

app = Flask(__name__)
app.secret_key = "pccm-secret-key-2026-change-me"

# ==================== ĐƯỜNG DẪN ====================
BASE_DIR = Path(__file__).parent
DATA_DIR = BASE_DIR / "data"
DATA_DIR.mkdir(exist_ok=True)

TEACHERS_FILE = DATA_DIR / "teachers.json"
SUBJECTS_FILE = DATA_DIR / "subjects.json"
CLASSES_FILE = DATA_DIR / "classes.json"
ASSIGNMENTS_FILE = DATA_DIR / "assignments.json"

# ==================== DỮ LIỆU MẶC ĐỊNH ====================
DEFAULT_TEACHERS = [
    "Nguyễn Thị Ngân", "Nguyễn Hồng Dân", "Lục Thị Kim Liên", "Nguyễn Thị Hoa T",
    "Hoàng Tú Phượng", "Hoàng Thị Thanh Huệ", "Hoàng Trọng Đại", "Ma Ngọc Doanh",
    "Nguyễn Thị Hoa V", "Nguyễn Thị Ninh", "Phú", "Lê Thị Hiền",
    "Nguyễn Thị Thu Hường", "Vũ Thị Linh", "Hoàng Minh Hải", "Đinh Thị Phượng",
    "Vương Hữu Sơn", "Vũ Thị Thanh Hường", "Vàng Thị Thêm", "Lương Thị Bích Tuệ",
    "Ma Thị Hà", "Vũ Tiến Sĩ", "Sùng Đức Kinh", "Nguyễn Thị Thu Huyền",
    "Nguyễn Đức Hội", "Đinh Xuân Nghĩa", "Bùi Thị Xuân", "Nguyễn Trọng Dũng",
    "Nguyễn Thị Kim Dung", "Hoàng Thị Hiền", "Nguyễn Khắc Kiên", "Vương Văn Quân"
]

DEFAULT_CLASSES = [
    "6A", "6B", "7A", "7B", "7C", "8A", "8B", "8C",
    "9A", "9B", "10A", "10B", "11A", "11B", "12A", "12B"
]

DEFAULT_SUBJECTS = {
    "Toán học": {"6": 4, "7": 4, "8": 4, "9": 4, "10": 4, "11": 4, "12": 3},
    "Ngữ văn": {"6": 4, "7": 4, "8": 4, "9": 4, "10": 4, "11": 3, "12": 3},
    "Ngoại ngữ": {"6": 3, "7": 3, "8": 3, "9": 3, "10": 3, "11": 3, "12": 3},
    "Vật lí": {"10": 2, "11": 2, "12": 2},
    "Hóa học": {"10": 2, "11": 2, "12": 2},
    "Sinh học": {"10": 2, "11": 2, "12": 2},
    "Lịch sử": {"10": 1.5, "11": 1.5, "12": 1.5},
    "Địa lí": {"10": 2, "11": 2, "12": 2},
    "GDCD": {"6": 1, "7": 1, "8": 1, "9": 1},
    "GD&KTPL": {"10": 2, "11": 2, "12": 2},
    "GDTC": {"6": 2, "7": 2, "8": 2, "9": 2, "10": 2, "11": 2, "12": 2},
    "Tin học": {"6": 1, "7": 1, "8": 1, "9": 1, "10": 2, "11": 2, "12": 2},
    "Công nghệ": {"6": 1, "7": 1, "8": 1, "9": 1},
    "Âm nhạc": {"10": 2, "11": 2, "12": 2},
    "Nghệ thuật": {"6": 1, "7": 1, "8": 1, "9": 1},
    "KHTN (Lí)": {"6": 1.9, "7": 1.2, "8": 1.5, "9": 1.6},
    "KHTN (Hoá)": {"6": 0.6, "7": 0.7, "8": 1.2, "9": 1.4},
    "KHTN (Sinh)": {"6": 1.5, "7": 2.1, "8": 1.25, "9": 1.0},
    "LS&ĐL": {"6": 1.5, "7": 1.5, "8": 1.5, "9": 1.5},
    "CĐ Toán": {"10": 1, "11": 1, "12": 1},
    "CĐ Văn": {"10": 1, "11": 1, "12": 1},
    "CĐ Hoá": {"10": 1, "11": 1, "12": 1},
    "CĐ Sinh": {"10": 1, "11": 1, "12": 1},
    "GDQP": {"10": 1, "11": 1, "12": 1},
    "GDĐP": {"6": 1, "7": 1, "8": 1, "9": 1, "10": 1, "11": 1, "12": 1},
    "HĐTN": {"6": 2, "7": 3, "8": 3, "9": 3, "10": 3, "11": 2, "12": 2},
}

# ==================== HELPER ====================
def load_json(path, default):
    if path.exists():
        try:
            with open(path, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            return default
    return default

def save_json(path, data):
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

def init_data():
    if not TEACHERS_FILE.exists():
        save_json(TEACHERS_FILE, DEFAULT_TEACHERS)
    if not SUBJECTS_FILE.exists():
        save_json(SUBJECTS_FILE, DEFAULT_SUBJECTS)
    if not CLASSES_FILE.exists():
        save_json(CLASSES_FILE, DEFAULT_CLASSES)
    if not ASSIGNMENTS_FILE.exists():
        save_json(ASSIGNMENTS_FILE, [])

def get_grade(class_name: str) -> str:
    return "".join(c for c in class_name if c.isdigit())

def get_periods(subject: str, class_name: str, subjects: dict):
    grade = get_grade(class_name)
    return subjects.get(subject, {}).get(grade)

def get_all_data():
    return {
        "teachers": load_json(TEACHERS_FILE, DEFAULT_TEACHERS),
        "subjects": load_json(SUBJECTS_FILE, DEFAULT_SUBJECTS),
        "classes": load_json(CLASSES_FILE, DEFAULT_CLASSES),
        "assignments": load_json(ASSIGNMENTS_FILE, []),
    }

# ==================== INIT ====================
init_data()

# ==================== ROUTES ====================
@app.route("/")
def index():
    data = get_all_data()
    assignments = data["assignments"]
    teacher_load = {}
    for a in assignments:
        t = a["teacher"]
        teacher_load[t] = teacher_load.get(t, 0) + float(a.get("periods", 0))
    sorted_load = sorted(teacher_load.items(), key=lambda x: x[1], reverse=True)
    return render_template(
        "index.html",
        teachers=data["teachers"],
        subjects=data["subjects"],
        classes=data["classes"],
        assignments=assignments,
        teacher_load=sorted_load,
        total_assignments=len(assignments),
    )

@app.route("/them", methods=["GET", "POST"])
def them():
    data = get_all_data()
    if request.method == "POST":
        teacher = request.form.get("teacher", "").strip()
        subject = request.form.get("subject", "").strip()
        class_name = request.form.get("class_name", "").strip()
        note = request.form.get("note", "").strip()
        periods_manual = request.form.get("periods_manual", "").strip()
        if not teacher or not subject or not class_name:
            flash("Vui lòng chọn đầy đủ Giáo viên, Môn học và Lớp.", "danger")
            return redirect(url_for("them"))
        for a in data["assignments"]:
            if a["teacher"] == teacher and a["subject"] == subject and a["class"] == class_name:
                flash(f"Đã tồn tại: {teacher} – {subject} – {class_name}", "warning")
                return redirect(url_for("them"))
        periods = get_periods(subject, class_name, data["subjects"])
        if periods is None:
            try:
                periods = float(periods_manual) if periods_manual else 0
            except ValueError:
                periods = 0
        new_item = {
            "id": datetime.now().strftime("%Y%m%d%H%M%S%f"),
            "teacher": teacher,
            "subject": subject,
            "class": class_name,
            "periods": periods,
            "note": note,
            "created_at": datetime.now().isoformat(),
        }
        data["assignments"].append(new_item)
        save_json(ASSIGNMENTS_FILE, data["assignments"])
        flash(f"Đã thêm: {teacher} dạy {subject} lớp {class_name} ({periods} tiết)", "success")
        return redirect(url_for("them"))
    return render_template(
        "them.html",
        teachers=sorted(data["teachers"]),
        subjects=sorted(data["subjects"].keys()),
        classes=data["classes"],
        subjects_data=data["subjects"],
    )

@app.route("/them-nhieu", methods=["POST"])
def them_nhieu():
    data = get_all_data()
    teacher = request.form.get("teacher", "").strip()
    subject = request.form.get("subject", "").strip()
    classes = request.form.getlist("classes")
    if not teacher or not subject or not classes:
        flash("Vui lòng chọn đầy đủ.", "danger")
        return redirect(url_for("them"))
    added = 0
    for cls in classes:
        exists = any(
            a["teacher"] == teacher and a["subject"] == subject and a["class"] == cls
            for a in data["assignments"]
        )
        if not exists:
            p = get_periods(subject, cls, data["subjects"]) or 0
            data["assignments"].append({
                "id": datetime.now().strftime("%Y%m%d%H%M%S%f") + cls,
                "teacher": teacher,
                "subject": subject,
                "class": cls,
                "periods": p,
                "note": "",
                "created_at": datetime.now().isoformat(),
            })
            added += 1
    save_json(ASSIGNMENTS_FILE, data["assignments"])
    flash(f"Đã thêm {added} phân công mới.", "success")
    return redirect(url_for("danhsach"))

@app.route("/danhsach")
def danhsach():
    data = get_all_data()
    assignments = data["assignments"]
    f_teacher = request.args.get("teacher", "")
    f_subject = request.args.get("subject", "")
    f_class = request.args.get("class", "")
    filtered = assignments
    if f_teacher:
        filtered = [a for a in filtered if a["teacher"] == f_teacher]
    if f_subject:
        filtered = [a for a in filtered if a["subject"] == f_subject]
    if f_class:
        filtered = [a for a in filtered if a["class"] == f_class]
    return render_template(
        "danhsach.html",
        assignments=filtered,
        all_assignments=assignments,
        teachers=sorted(set(a["teacher"] for a in assignments)),
        subjects=sorted(set(a["subject"] for a in assignments)),
        classes=sorted(set(a["class"] for a in assignments)),
        f_teacher=f_teacher,
        f_subject=f_subject,
        f_class=f_class,
    )

@app.route("/xoa/<aid>", methods=["POST"])
def xoa(aid):
    data = get_all_data()
    data["assignments"] = [a for a in data["assignments"] if a["id"] != aid]
    save_json(ASSIGNMENTS_FILE, data["assignments"])
    flash("Đã xóa phân công.", "success")
    return redirect(url_for("danhsach"))

@app.route("/xoa-nhieu", methods=["POST"])
def xoa_nhieu():
    ids = request.form.getlist("ids")
    data = get_all_data()
    data["assignments"] = [a for a in data["assignments"] if a["id"] not in ids]
    save_json(ASSIGNMENTS_FILE, data["assignments"])
    flash(f"Đã xóa {len(ids)} phân công.", "success")
    return redirect(url_for("danhsach"))

@app.route("/baocao")
def baocao():
    data = get_all_data()
    assignments = data["assignments"]
    by_teacher = {}
    for a in assignments:
        t = a["teacher"]
        if t not in by_teacher:
            by_teacher[t] = []
        by_teacher[t].append(a)
    summaries = {}
    for teacher, items in by_teacher.items():
        by_subject = {}
        total = 0
        for a in items:
            s = a["subject"]
            if s not in by_subject:
                by_subject[s] = []
            by_subject[s].append(f"{a['class']}({a['periods']})")
            total += float(a["periods"])
        lines = [f"{s}: {', '.join(parts)}" for s, parts in sorted(by_subject.items())]
        summaries[teacher] = {"text": "\n".join(lines), "total": total, "items": items}
    return render_template("baocao.html", summaries=summaries)

@app.route("/giaovien", methods=["GET", "POST"])
def giaovien():
    data = get_all_data()
    if request.method == "POST":
        action = request.form.get("action")
        if action == "add":
            name = request.form.get("name", "").strip()
            if name and name not in data["teachers"]:
                data["teachers"].append(name)
                data["teachers"].sort()
                save_json(TEACHERS_FILE, data["teachers"])
                flash(f"Đã thêm giáo viên: {name}", "success")
            elif name in data["teachers"]:
                flash("Giáo viên đã tồn tại.", "warning")
        elif action == "delete":
            name = request.form.get("name", "").strip()
            if name in data["teachers"]:
                data["teachers"].remove(name)
                save_json(TEACHERS_FILE, data["teachers"])
                flash(f"Đã xóa: {name}", "success")
        return redirect(url_for("giaovien"))
    return render_template("giaovien.html", teachers=sorted(data["teachers"]))

@app.route("/monhoc", methods=["GET", "POST"])
def monhoc():
    data = get_all_data()
    if request.method == "POST":
        action = request.form.get("action")
        if action == "update":
            subject = request.form.get("subject")
            if subject and subject in data["subjects"]:
                new_vals = {}
                for g in ["6", "7", "8", "9", "10", "11", "12"]:
                    val = request.form.get(f"grade_{g}", "").strip()
                    if val:
                        try:
                            new_vals[g] = float(val)
                        except ValueError:
                            pass
                data["subjects"][subject] = new_vals
                save_json(SUBJECTS_FILE, data["subjects"])
                flash(f"Đã cập nhật số tiết môn {subject}", "success")
        elif action == "add":
            name = request.form.get("name", "").strip()
            if name and name not in data["subjects"]:
                data["subjects"][name] = {}
                save_json(SUBJECTS_FILE, data["subjects"])
                flash(f"Đã thêm môn: {name}", "success")
        return redirect(url_for("monhoc"))
    return render_template(
        "monhoc.html",
        subjects=data["subjects"],
        grades=["6", "7", "8", "9", "10", "11", "12"],
    )

@app.route("/lop", methods=["GET", "POST"])
def lop():
    data = get_all_data()
    if request.method == "POST":
        action = request.form.get("action")
        if action == "add":
            name = request.form.get("name", "").strip().upper()
            if name and name not in data["classes"]:
                data["classes"].append(name)
                data["classes"].sort(key=lambda x: (int("".join(c for c in x if c.isdigit()) or 0), x))
                save_json(CLASSES_FILE, data["classes"])
                flash(f"Đã thêm lớp: {name}", "success")
        elif action == "delete":
            name = request.form.get("name", "").strip()
            if name in data["classes"]:
                data["classes"].remove(name)
                save_json(CLASSES_FILE, data["classes"])
                flash(f"Đã xóa lớp: {name}", "success")
        return redirect(url_for("lop"))
    return render_template("lop.html", classes=data["classes"])

@app.route("/api/periods")
def api_periods():
    subject = request.args.get("subject", "")
    class_name = request.args.get("class", "")
    subjects = load_json(SUBJECTS_FILE, DEFAULT_SUBJECTS)
    periods = get_periods(subject, class_name, subjects)
    return jsonify({"periods": periods})

@app.route("/xuat")
def xuat():
    data = get_all_data()
    assignments = data["assignments"]
    output = BytesIO()
    import io
    text_buffer = io.StringIO()
    writer = csv.writer(text_buffer)
    writer.writerow(["Giáo viên", "Môn học", "Lớp", "Số tiết", "Ghi chú"])
    for a in assignments:
        writer.writerow([a["teacher"], a["subject"], a["class"], a["periods"], a.get("note", "")])
    output.write(text_buffer.getvalue().encode("utf-8-sig"))
    output.seek(0)
    return send_file(
        output,
        mimetype="text/csv",
        as_attachment=True,
        download_name=f"phan_cong_{datetime.now().strftime('%Y%m%d')}.csv",
    )

# ==================== PASSENGER ====================
application = app

if __name__ == "__main__":
    app.run(debug=True, host="0.0.0.0", port=5000)
