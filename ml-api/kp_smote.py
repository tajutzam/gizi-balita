# kp_smote.py  (letakkan di folder: ml-api/)
import os
import pandas as pd
import numpy as np

from sklearn.impute import SimpleImputer
from sklearn.preprocessing import MinMaxScaler
from imblearn.over_sampling import SMOTE
from sklearn.model_selection import train_test_split
from sklearn.tree import DecisionTreeClassifier
from sklearn.metrics import classification_report

from flask import Flask, request, jsonify

# ==========================
# 1. KONFIGURASI LABEL
# ==========================

LABEL_MAP_REV = {
    0: "Gizi Buruk",
    1: "Gizi Kurang",
    2: "Gizi Baik",
    3: "Risiko Gizi Lebih",
    4: "Gizi Lebih",
    5: "Obesitas",
}

LABEL_MAP = {v: k for k, v in LABEL_MAP_REV.items()}

MODEL = None
TRAIN_INFO = {}


def load_and_train_model():
    """
    Melatih model sekali saat server start.
    Menggunakan fitur: JK, Umur, BB, TB, LILA
    sesuai dengan isi dataset yang kamu kirim.
    """
    global MODEL, TRAIN_INFO

    BASE_DIR = os.path.dirname(os.path.abspath(__file__))
    DATA_PATH = os.path.join(
        BASE_DIR,
        "Data Balita Puskesmas Depok 3 Sleman.xlsx - Application List.csv",
    )

    print("[INFO] Load dataset dari:", DATA_PATH)
    if not os.path.exists(DATA_PATH):
        raise FileNotFoundError(f"Dataset tidak ditemukan: {DATA_PATH}")

    # Baca CSV apa adanya
    df = pd.read_csv(DATA_PATH)

    # Pastikan kolom wajib ada
    required_cols = ["JK", "Umur", "BB", "TB", "LILA", "Status Gizi"]
    for col in required_cols:
        if col not in df.columns:
            raise ValueError(f"Kolom '{col}' tidak ditemukan di dataset")

    # ==============
    # 1) PREPROCESS
    # ==============

    df_proc = df.copy()

    # JK: P/L -> 0/1
    df_proc["JK"] = (
        df_proc["JK"]
        .astype(str)
        .str.strip()
        .map({"P": 0, "L": 1})
    )

    # Umur: "60 Bulan" -> 60
    df_proc["Umur"] = (
        df_proc["Umur"]
        .astype(str)
        .str.extract(r"(\d+)")[0]
        .astype(float)
    )

    # BB & TB & LILA: angka desimal pakai titik (kalau ada koma)
    for col in ["BB", "TB", "LILA"]:
        df_proc[col] = df_proc[col].astype(str).str.replace(",", ".", regex=False)
        df_proc[col] = pd.to_numeric(df_proc[col], errors="coerce")

    # Status Gizi: string -> label int
    df_proc["Status Gizi"] = (
        df_proc["Status Gizi"]
        .astype(str)
        .str.strip()
        .map(LABEL_MAP)
    )

    # Buang baris yang gagal dimapping
    df_proc = df_proc.dropna(subset=["JK", "Umur", "BB", "TB", "LILA", "Status Gizi"])

    df_proc["JK"] = df_proc["JK"].astype(int)
    df_proc["Umur"] = df_proc["Umur"].astype(float)
    df_proc["BB"] = df_proc["BB"].astype(float)
    df_proc["TB"] = df_proc["TB"].astype(float)
    df_proc["LILA"] = df_proc["LILA"].astype(float)
    df_proc["Status Gizi"] = df_proc["Status Gizi"].astype(int)

    # Fitur yang dipakai model
    feature_cols = ["JK", "Umur", "BB", "TB", "LILA"]

    X_raw = df_proc[feature_cols]
    y = df_proc["Status Gizi"]

    # Imputasi numeric (kalau ada NaN)
    imputer = SimpleImputer(strategy="median")
    X_imputed = imputer.fit_transform(X_raw)

    # Normalisasi
    scaler = MinMaxScaler()
    X_scaled = scaler.fit_transform(X_imputed)

    # Simpan mean dari X_scaled (untuk fallback nanti)
    feature_means = pd.DataFrame(X_scaled, columns=feature_cols).mean()

    # ==============
    # 2) SMOTE
    # ==============
    smote = SMOTE(random_state=42)
    X_res, y_res = smote.fit_resample(X_scaled, y)

    # ==============
    # 3) SPLIT
    # ==============
    X_train, X_test, y_train, y_test = train_test_split(
        X_res, y_res, test_size=0.3, random_state=42, stratify=y_res
    )

    # ==============
    # 4) MODEL
    # ==============
    model = DecisionTreeClassifier(
        random_state=42,
        class_weight="balanced",
        criterion="gini",
        max_depth=7,
        max_features="sqrt",
        min_samples_leaf=5,
        min_samples_split=10,
        ccp_alpha=0.01,
    )
    model.fit(X_train, y_train)

    # Evaluasi cepat di console (optional)
    y_pred_test = model.predict(X_test)
    print("[INFO] Classification report (TEST):")
    print(classification_report(y_test, y_pred_test, zero_division=0))

    TRAIN_INFO = {
        "imputer": imputer,
        "scaler": scaler,
        "feature_cols": feature_cols,
        "feature_means": feature_means.to_dict(),
    }

    return model, TRAIN_INFO


def predict_status_gizi(data_input: dict):
    """
    data_input yang diharapkan:
    {
      "JK": "L" / "P",
      "Umur": "60",       # atau "60 Bulan"
      "BB": "14.5",
      "TB": "102",
      "LILA": "15"
    }
    """
    global MODEL, TRAIN_INFO

    if MODEL is None or not TRAIN_INFO:
        raise RuntimeError("Model belum ter-load")

    feature_cols = TRAIN_INFO["feature_cols"]
    feature_means = TRAIN_INFO["feature_means"]

    # Buat DataFrame 1 baris
    df_in = pd.DataFrame([data_input])

    # --- Preprocess persis seperti training ---

    # JK 
    df_in["JK"] = (
        df_in["JK"]
        .astype(str)
        .str.strip()
        .map({"P": 0, "L": 1})
    )

    # Umur
    df_in["Umur"] = df_in["Umur"].astype(str).str.extract(r"(\d+)")[0]
    df_in["Umur"] = pd.to_numeric(df_in["Umur"], errors="coerce")

    # BB, TB, LILA (string -> float)
    for col in ["BB", "TB", "LILA"]:
        if col in df_in.columns:
            df_in[col] = df_in[col].astype(str).str.replace(",", ".", regex=False)
            df_in[col] = pd.to_numeric(df_in[col], errors="coerce")

    # Pastikan semua kolom fitur lengkap
    for col in feature_cols:
        if col not in df_in.columns:
            df_in[col] = np.nan

    # Urutkan kolom
    df_in = df_in[feature_cols]

    # Imputasi NaN dengan median training
    imputer = TRAIN_INFO["imputer"]
    X_imp = imputer.transform(df_in)

    # Scaling
    scaler = TRAIN_INFO["scaler"]
    X_scaled = scaler.transform(X_imp)

    # Fallback kalau masih ada NaN
    X_df = pd.DataFrame(X_scaled, columns=feature_cols)
    for col in feature_cols:
        X_df[col] = X_df[col].fillna(feature_means.get(col, 0.0))

    # Prediksi
    y_pred = MODEL.predict(X_df.values)[0]
    class_id = int(y_pred)
    class_label = LABEL_MAP_REV.get(class_id, "Unknown")

    return class_id, class_label



# ========================
# LOAD MODEL SAAT STARTUP
# ========================
print("[INFO] Melatih / memuat model ...")
MODEL, TRAIN_INFO = load_and_train_model()
print("[INFO] Model siap.")

# ========================
# 3. FLASK API
# ========================

app = Flask(__name__)


@app.route("/")
def index():
    return jsonify({"message": "API Gizi Balita aktif"}), 200


@app.route("/predict", methods=["POST"])
def predict():
    try:
        data = request.get_json()
        if not data:
            return jsonify({"success": False, "error": "Body JSON kosong"}), 400

        class_id, class_label = predict_status_gizi(data)

        return jsonify(
            {
                "success": True,
                "class_id": class_id,
                "class_label": class_label,
            }
        )
    except Exception as e:
        print("[ERROR] /predict:", str(e))
        return jsonify({"success": False, "error": str(e)}), 500


if __name__ == "__main__":
    # DEVELOPMENT ONLY
    app.run(host="0.0.0.0", port=5000)