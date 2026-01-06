import re
import argparse
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.compose import ColumnTransformer
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
import pickle

URL_RE = re.compile(r"http[s]?://", re.IGNORECASE)
EMAIL_RE = re.compile(r"\b[\w\.-]+@[\w\.-]+\.\w+\b", re.IGNORECASE)
DIGIT_SEQ_RE = re.compile(r"\d{9,}")

def has_url(text): return 1 if URL_RE.search(str(text)) else 0
def has_email(text): return 1 if EMAIL_RE.search(str(text)) else 0
def has_phone(text):
    s = re.sub(r"[^\d]", "", str(text))
    return 1 if DIGIT_SEQ_RE.search(s) else 0

def main():
    p = argparse.ArgumentParser()
    p.add_argument("--csv", required=True, help="Path to dataset csv")
    p.add_argument("--out", required=True, help="Output .pkl path")
    p.add_argument("--text_col", default="message")
    p.add_argument("--label_col", default="label")
    args = p.parse_args()

    print(f"Loading {args.csv}...")
    try:
        df = pd.read_csv(args.csv, encoding='utf-8')
    except UnicodeDecodeError:
        print("UTF-8 failed, trying latin-1...")
        df = pd.read_csv(args.csv, encoding='latin-1')

    # Quick check for column existence
    if args.text_col not in df.columns:
        raise ValueError(f"Column '{args.text_col}' not found. Available: {list(df.columns)}")
    if args.label_col not in df.columns:
        raise ValueError(f"Column '{args.label_col}' not found. Available: {list(df.columns)}")

    # label mapping (adjust if your email dataset uses different names)
    label_map = {"ham":0, "legit":0, "normal":0, "sms":0,
                 "phishing":1, "spam":1, "smishing":1}

    print("Mapping labels...")
    if pd.api.types.is_numeric_dtype(df[args.label_col]):
        df[args.label_col] = df[args.label_col].astype(int)
    else:
        # Convert to string, strip whitespace, lower case, then map
        df[args.label_col] = (
            df[args.label_col].astype(str).str.strip().str.lower().map(label_map)
        )
        # Check for unmapped values
        if df[args.label_col].isna().any():
            bad = df.loc[df[args.label_col].isna(), args.label_col_original].unique() if 'label_col_original' in df else "Unknown" # can't easily get original vals after map, but that's fine
            print(f"Warning: Found unmapped labels. Dropping {df[args.label_col].isna().sum()} rows.")
            df = df.dropna(subset=[args.label_col])
        
        df[args.label_col] = df[args.label_col].astype(int)

    print("Extracting features (TF-IDF only)...")
    # REMOVED: Metadata features (has_url, etc) because they bias Email classification 
    # (Real emails always have links, SMS Ham usually don't)
    
    X = df[args.text_col]
    y = df[args.label_col]

    print("Splitting data...")
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )

    # Simplified Pipeline: Pure Text Analysis
    model = Pipeline([
        ("tfidf", TfidfVectorizer(stop_words="english", max_features=5000)),
        ("clf", LogisticRegression(max_iter=2000))
    ])

    print("Training model...")
    model.fit(X_train, y_train)

    print("Evaluating...")
    y_pred = model.predict(X_test)
    print("Accuracy:", accuracy_score(y_test, y_pred))
    print(confusion_matrix(y_test, y_pred))
    print(classification_report(y_test, y_pred))

    with open(args.out, 'wb') as f:
        pickle.dump(model, f)
    print(f"Saved -> {args.out}")

if __name__ == "__main__":
    main()
