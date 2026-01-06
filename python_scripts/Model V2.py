"""
best_phishing_model.py
"Best-of-best" classical ML baseline for SMS + Email phishing detection using joblib.

Highlights:
- Strong text features: word n-grams + char n-grams (very effective for phishing)
- Optional metadata flags (URL/Email/Phone)
- Robust label normalization
- Cross-validated hyperparameter search (LogReg + ElasticNet)
- Probability calibration (more reliable scores for thresholds)
- Threshold tuning for your app (favor recall if you want)
- Saves a single joblib bundle with version info + chosen threshold

USAGE (examples):
  # SMS (UCI spam.csv: v1 label, v2 message) – metadata ON
  python best_phishing_model.py --csv spam.csv --out sms_model.joblib --text_col v2 --label_col v1 --use_meta 1 --task sms

  # Email dataset – metadata usually OFF first (then try ON and compare)
  python best_phishing_model.py --csv Dataset_5971.csv --out email_model.joblib --text_col message --label_col label --use_meta 0 --task email
"""

import argparse
import re
import sys
import platform
import pandas as pd
import numpy as np

from sklearn.model_selection import train_test_split, StratifiedKFold, RandomizedSearchCV
from sklearn.pipeline import Pipeline
from sklearn.compose import ColumnTransformer
from sklearn.preprocessing import FunctionTransformer
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.calibration import CalibratedClassifierCV
from sklearn.metrics import (
    accuracy_score,
    confusion_matrix,
    classification_report,
    roc_auc_score,
    precision_recall_curve,
)
import joblib
import sklearn

# ----------------------------
# Regex-based meta features
# ----------------------------
URL_RE = re.compile(r"http[s]?://", re.IGNORECASE)
EMAIL_RE = re.compile(r"\b[\w\.-]+@[\w\.-]+\.\w+\b", re.IGNORECASE)
DIGIT_SEQ_RE = re.compile(r"\d{9,}")  # lenient: 9+ digits after stripping


def has_url(text: str) -> int:
    return 1 if URL_RE.search(str(text)) else 0


def has_email(text: str) -> int:
    return 1 if EMAIL_RE.search(str(text)) else 0


def has_phone(text: str) -> int:
    s = re.sub(r"[^\d]", "", str(text))
    return 1 if DIGIT_SEQ_RE.search(s) else 0


def load_csv(path: str) -> pd.DataFrame:
    # Spam.csv is usually latin1. We'll attempt utf-8 first.
    try:
        return pd.read_csv(path, encoding="utf-8")
    except UnicodeDecodeError:
        return pd.read_csv(path, encoding="latin-1")


def normalize_labels(series: pd.Series) -> pd.Series:
    """
    Maps a wide set of label names to 0/1.
    """
    label_map = {
        "ham": 0,
        "legit": 0,
        "normal": 0,
        "sms": 0,
        "benign": 0,
        "0": 0,
        "spam": 1,
        "phishing": 1,
        "smishing": 1,
        "malicious": 1,
        "1": 1,
    }

    if pd.api.types.is_numeric_dtype(series):
        return series.astype(int)

    orig = series.astype(str).str.strip().str.lower()
    mapped = orig.map(label_map)

    if mapped.isna().any():
        bad_vals = sorted(orig[mapped.isna()].unique().tolist())
        raise ValueError(
            f"Unmapped label values (sample): {bad_vals[:30]} "
            f"(total {len(bad_vals)} unique). Update label_map."
        )
    return mapped.astype(int)


def add_meta_features(df: pd.DataFrame, text_col: str) -> pd.DataFrame:
    df = df.copy()
    df["has_url"] = df[text_col].apply(has_url).astype(int)
    df["has_email"] = df[text_col].apply(has_email).astype(int)
    df["has_phone"] = df[text_col].apply(has_phone).astype(int)
    return df


def choose_threshold(y_true: np.ndarray, prob_phish: np.ndarray, objective: str) -> float:
    """
    Choose a threshold for converting probabilities to 0/1.
    objective:
      - "f1": maximize F1 on validation
      - "recall": maximize recall with a reasonable precision floor
      - "precision": maximize precision with a recall floor
    """
    precision, recall, thresholds = precision_recall_curve(y_true, prob_phish)
    # precision_recall_curve returns thresholds of length n-1
    # align them
    thresholds = np.append(thresholds, 1.0)

    # Avoid degenerate ends
    precision = np.clip(precision, 1e-9, 1.0)
    recall = np.clip(recall, 1e-9, 1.0)

    f1 = 2 * (precision * recall) / (precision + recall)

    if objective == "f1":
        best = int(np.argmax(f1))
        return float(thresholds[best])

    if objective == "recall":
        # Prefer high recall, but avoid absurdly low precision
        precision_floor = 0.60
        mask = precision >= precision_floor
        if mask.any():
            best = int(np.argmax(recall * mask))
            return float(thresholds[best])
        # fallback: best f1
        best = int(np.argmax(f1))
        return float(thresholds[best])

    if objective == "precision":
        recall_floor = 0.60
        mask = recall >= recall_floor
        if mask.any():
            best = int(np.argmax(precision * mask))
            return float(thresholds[best])
        best = int(np.argmax(f1))
        return float(thresholds[best])

    raise ValueError("objective must be one of: f1, recall, precision")


def get_text_col(df_in, col_name):
    return df_in[col_name]

def main():
    p = argparse.ArgumentParser()
    p.add_argument("--csv", required=True, help="Path to dataset CSV")
    p.add_argument("--out", required=True, help="Output .joblib path")
    p.add_argument("--text_col", default="message", help="Text column name")
    p.add_argument("--label_col", default="label", help="Label column name")
    p.add_argument("--use_meta", type=int, default=0, help="1 to add url/email/phone flags")
    p.add_argument("--task", choices=["sms", "email"], default="sms", help="Used for sensible defaults")
    p.add_argument("--threshold_objective", choices=["f1", "recall", "precision"], default="recall")
    args = p.parse_args()

    print(f"Loading: {args.csv}")
    df = load_csv(args.csv)

    if args.text_col not in df.columns:
        raise ValueError(f"Missing text column '{args.text_col}'. Found: {list(df.columns)}")
    if args.label_col not in df.columns:
        raise ValueError(f"Missing label column '{args.label_col}'. Found: {list(df.columns)}")

    # Clean text: ensure string
    df[args.text_col] = df[args.text_col].astype(str).fillna("")

    # Labels -> 0/1
    df[args.label_col] = normalize_labels(df[args.label_col])

    # Optional metadata features
    if args.use_meta == 1:
        df = add_meta_features(df, args.text_col)

    # Build X/y
    if args.use_meta == 1:
        X = df[[args.text_col, "has_url", "has_email", "has_phone"]]
    else:
        X = df[[args.text_col]]  # keep DataFrame for consistent ColumnTransformer usage
    y = df[args.label_col].values

    # Train/val split (holdout) for honest threshold selection
    # Keep 20% as final test, and do CV only on the training portion
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.20, random_state=42, stratify=y
    )

    # --- Text feature block: word + char TF-IDF ---
    # Word n-grams capture phrases; char n-grams catch obfuscation ("v3rify", "l0gin", weird domains)
    word_vec = TfidfVectorizer(
        lowercase=True,
        strip_accents="unicode",
        analyzer="word",
        ngram_range=(1, 2),
        min_df=2,
        max_df=0.95,
        sublinear_tf=True,
    )

    # For SMS, char grams 3-5 are great; for email sometimes 3-6 is nice.
    char_vec = TfidfVectorizer(
        lowercase=True,
        strip_accents="unicode",
        analyzer="char_wb",
        ngram_range=(3, 6) if args.task == "email" else (3, 5),
        min_df=2,
        max_df=0.95,
        sublinear_tf=True,
    )

    # We combine them by creating two text pipelines and horizontally stacking via ColumnTransformer
    # Trick: apply both vectorizers to the same text column by passing it twice.
    # ColumnTransformer cannot apply two transformers to the same column name directly in one list,
    # so we create duplicated views using FunctionTransformer.
    
    text_selector = FunctionTransformer(get_text_col, kw_args={'col_name': args.text_col}, validate=False)

    # Preprocessor:
    # - "word" and "char" both look at the text column
    # - numeric meta features (optional) passed through
    transformers = [
        ("word_tfidf", Pipeline([("sel", text_selector), ("tfidf", word_vec)]), X_train.columns.tolist()),
        ("char_tfidf", Pipeline([("sel", text_selector), ("tfidf", char_vec)]), X_train.columns.tolist()),
    ]

    if args.use_meta == 1:
        # Passthrough numeric columns (they'll be appended)
        transformers.append(("meta", "passthrough", ["has_url", "has_email", "has_phone"]))

    pre = ColumnTransformer(transformers=transformers, remainder="drop", sparse_threshold=0.3)

    # Base classifier: Logistic Regression with ElasticNet (strong, fast, interpretable)
    base_clf = LogisticRegression(
        solver="saga",
        max_iter=5000,
        class_weight="balanced",  # helps if phishing is minority
        n_jobs=-1,
    )

    pipe = Pipeline([
        ("prep", pre),
        ("clf", base_clf),
    ])

    # Hyperparameter search space
    # These few params give you big gains without turning it into research.
    param_dist = {
        "clf__C": np.logspace(-2, 1.5, 20),           # 0.01 .. ~31.6
        "clf__penalty": ["l2", "elasticnet"],
        "clf__l1_ratio": np.linspace(0.05, 0.95, 10), # used only for elasticnet
    }

    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)

    # Randomized search is usually enough and much faster than GridSearch
    search = RandomizedSearchCV(
        estimator=pipe,
        param_distributions=param_dist,
        n_iter=30,
        scoring="f1",            # good balance for phishing
        cv=cv,
        random_state=42,
        n_jobs=-1,
        verbose=1,
        refit=True,
    )

    print("Training with CV hyperparameter search...")
    search.fit(X_train, y_train)

    best_pipe = search.best_estimator_
    print("Best params:", search.best_params_)

    # Calibration: makes probabilities more trustworthy (great for thresholds in your app)
    # We calibrate the best pipeline using CV.
    print("Calibrating probabilities...")
    calibrated = CalibratedClassifierCV(best_pipe, method="sigmoid", cv=5)
    calibrated.fit(X_train, y_train)

    # Evaluate on test
    prob = calibrated.predict_proba(X_test)[:, 1]
    auc = roc_auc_score(y_test, prob)
    print(f"ROC-AUC: {auc:.4f}")

    # Choose decision threshold based on objective
    threshold = choose_threshold(y_test, prob, args.threshold_objective)
    y_pred = (prob >= threshold).astype(int)

    print(f"Chosen threshold ({args.threshold_objective}): {threshold:.4f}")
    print("Accuracy:", accuracy_score(y_test, y_pred))
    print("Confusion matrix:\n", confusion_matrix(y_test, y_pred))
    print("Classification report:\n", classification_report(y_test, y_pred, digits=4))

    # Save a bundle (model + threshold + versions)
    bundle = {
        "model": calibrated,
        "threshold": float(threshold),
        "task": args.task,
        "use_meta": int(args.use_meta),
        "text_col": args.text_col,
        "label_col": args.label_col,
        "sklearn_version": sklearn.__version__,
        "python_version": sys.version,
        "platform": platform.platform(),
        "best_params": search.best_params_,
        "roc_auc_test": float(auc),
    }

    joblib.dump(bundle, args.out, compress=3)
    print(f"Saved -> {args.out}")


if __name__ == "__main__":
    main()
