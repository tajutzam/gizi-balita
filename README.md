# 📘 **Sistem Monitoring Status Gizi Balita**

Aplikasi web untuk memantau status gizi balita menggunakan **PHP
Native**, **MySQL**, dan **Machine Learning** (Python/CSV/IPYNB).\
Memiliki dua role utama:

-   **👩‍⚕️ Tenaga Kesehatan**
-   **👨‍👩‍👧 Orang Tua**

Aplikasi ini membantu tenaga kesehatan dalam input pemeriksaan, prediksi
status gizi menggunakan model ML, dan memberi edukasi kepada orang tua.

## ✨ **Fitur Utama**

### 👩‍⚕️ **Tenaga Kesehatan**

-   Login (tanpa registrasi)
-   Input data pemeriksaan (BB, TB, Umur, JK, LILA)
-   Kirim data ke API Python untuk prediksi status gizi
-   Melihat daftar balita
-   Melihat riwayat pemeriksaan
-   Mengelola artikel edukasi (CRUD)
-   Melihat grafik perkembangan anak
-   Edit profil & ubah password

### 👨‍👩‍👧 **Orang Tua**

-   Registrasi & Login
-   Melihat hasil pemeriksaan dan prediksi gizi
-   Melihat grafik perkembangan BB/U & TB/U
-   Membaca artikel edukasi dari nakes
-   Edit profil & ubah password

## 🤖 **Integrasi Machine Learning**

Model dibuat menggunakan: - Dataset CSV - Notebook `ipynb` - Algoritma
klasifikasi status gizi\
API ML dijalankan menggunakan: - FastAPI atau Flask\
PHP mengirim data → API memproses → API mengirim prediksi kembali.

Output Model ML: - Gizi Baik\
- Gizi Buruk\
- Gizi Kurang\
- Gizi Lebih\
- Stunting

## 🗂 **Struktur Folder Proyek**

    project-root/
    │── public/
    │   ├── index.php
    │   ├── login_ortu.php
    │   ├── login_nakes.php
    │   ├── register_ortu.php
    │   ├── logout.php
    │   │
    │   ├── ortu/
    │   │   ├── dashboard.php
    │   │   ├── balita_detail.php
    │   │   ├── pemeriksaan_riwayat.php
    │   │   ├── grafik_perkembangan.php
    │   │   ├── artikel_list.php
    │   │   ├── artikel_baca.php
    │   │   ├── profile.php
    │   │   ├── edit_profile.php
    │   │   └── ubah_password.php
    │   │
    │   ├── nakes/
    │       ├── dashboard.php
    │       ├── balita_list.php
    │       ├── pemeriksaan_input.php
    │       ├── pemeriksaan_list.php
    │       ├── artikel_manage.php
    │       ├── artikel_form.php
    │       ├── artikel_view.php
    │       ├── profile.php
    │       ├── edit_profile.php
    │       └── ubah_password.php
    │
    │── includes/
    │   ├── auth.php
    │   ├── db.php
    │   ├── functions.php
    │   ├── layout_ortu_header.php
    │   ├── layout_ortu_footer.php
    │   ├── layout_nakes_header.php
    │   ├── layout_nakes_footer.php
    │   └── navbar_public.php
    │
    │── config/
    │   └── config.php
    │
    │── ml-api/
    │   ├── model.pkl
    │   ├── dataset.csv
    │   ├── api.py
    │   └── notebook.ipynb
    │
    └── README.md

## 🛠 **Teknologi yang Digunakan**

-   PHP Native
-   MySQL
-   Bootstrap 5
-   Chart.js
-   FastAPI / Flask
-   Jupyter Notebook

## 🚀 **Cara Menjalankan Projek**

1.  Import database
2.  Jalankan API ML:

```{=html}
<!-- -->
```
    uvicorn api:app --reload --port 8000

3.  Jalankan PHP:

```{=html}
<!-- -->
```
    php -S localhost:9000 -t public
