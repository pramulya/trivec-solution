import sys
import os

# FORCE PATH: Ensure user site-packages are visible to PHP process
sys.path.append(r"C:\Users\denni\AppData\Roaming\Python\Python313\site-packages")

import json
import pickle
import pandas as pd
import re
import argparse

# Re-define feature functions since they might be needed if not pickle-able safely
# (Though in our pipeline we used functions, joblib usually handles them if in same namespace,
#  but safest to define them here exactly as in training)
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
    p.add_argument("--model", required=True, help="Path to .pkl model")
    p.add_argument("--input-file", help="Path to JSON input file (optional, defaults to stdin)")
    args = p.parse_args()

    # 1. Read JSON
    try:
        raw_input = ""
        if args.input_file:
            # Try multiple encodings
            for enc in ['utf-8', 'utf-16', 'latin-1']:
                try:
                    with open(args.input_file, 'r', encoding=enc) as f:
                        raw_input = f.read()
                    break
                except UnicodeError:
                    continue
        else:
            # Read from STDIN
            # sys.stdin defaults to utf-8 in modern python but might vary
            import io
            sys.stdin = io.TextIOWrapper(sys.stdin.buffer, encoding='utf-8')
            raw_input = sys.stdin.read()

        if not raw_input:
            # No input, just exit
            print("[]")
            return
        
        data = json.loads(raw_input)
    except Exception as e:
        print(json.dumps({"error": "Invalid JSON input", "details": str(e)}))
        sys.exit(1)

    if not isinstance(data, list):
        print(json.dumps({"error": "Input must be a JSON list of objects"}))
        sys.exit(1)

    if len(data) == 0:
        print("[]")
        return

    # 2. Convert to DataFrame
    df = pd.DataFrame(data)
    
    # Needs 'text' column logic used in training, let's assume input keys are 'id' and 'text'
    # We rename 'text' to whatever the model expects? 
    # Actually, in training we used:
    # pre = ColumnTransformer([("text", TfidfVectorizer(...), args.text_col), ...])
    # The pipeline expects a DataFrame with specific columns: [args.text_col, "has_url", "has_email", "has_phone"]
    # So we need to reconstruct those.

    # We assume the input JSON has a 'text' field.
    # The model pkl has the exact column name baked into the ColumnTransformer.
    # We need to know what 'args.text_col' was during training.
    # For email model it was 'TEXT', for sms it was 'v2'.
    # This is tricky. simpler way: rename input col to match model expectation OR
    # use a generic wrapper.
    
    # HACK: check model transformer columns? or just default to 'text' and user must ensure training used 'text'?
    # User trained:
    # SMS: text_col="v2"
    # Email: text_col="TEXT"
    
    # We will try to map the known input 'body' to both likely columns to be safe
    # or rely on caller to pass it.
    # Let's Standardize: Input JSON will have 'body'. We will dup it to 'v2' and 'TEXT'.
    
    if 'body' not in df.columns:
         print(json.dumps({"error": "Input JSON objects must have 'body' field"}))
         sys.exit(1)

    df['v2'] = df['body']   # For SMS model
    df['TEXT'] = df['body'] # For Email model
    df['message'] = df['body'] # Fallback

    # 3. Feature Extraction (must match training exactly)
    # We run it on 'body' since that's the source
    df["has_url"] = df['body'].apply(has_url)
    df["has_email"] = df['body'].apply(has_email)
    df["has_phone"] = df['body'].apply(has_phone)

    # 4. Load Model
    try:
        with open(args.model, 'rb') as f:
            model = pickle.load(f)
    except Exception as e:
        print(json.dumps({"error": "Failed to load model", "details": str(e)}))
        sys.exit(1)

    # 3. Predict
    try:
        # Model is now a simple Pipeline(tfidf -> clf)
        # It expects a list of strings or a pandas Series
        texts = df['body'].fillna("")
        
        # predict_proba for score
        probs = model.predict_proba(texts)
        # probs is [[prob_0, prob_1], ...]
        scores = probs[:, 1]
        
        preds = model.predict(texts)
    except Exception as e:
        print(json.dumps({"error": "Prediction failed", "details": str(e)}))
        sys.exit(1)

    # 6. Format Output
    results = []
    for idx, row in df.iterrows():
        score_percent = round(scores[idx] * 100, 2)
        label = "phishing" if preds[idx] == 1 else "safe"
        
        # Add 'suspicious' logic if score is high but not 1? 
        # For simple logic: 
        if 50 < score_percent < 80:
             label = "suspicious" # Custom logic layer on top of raw prediction
        if score_percent >= 80:
             label = "phishing"

        results.append({
            "id": row.get('id'), # Pass through ID
            "score": score_percent,
            "label": label
        })

    print(json.dumps(results))

if __name__ == "__main__":
    main()
