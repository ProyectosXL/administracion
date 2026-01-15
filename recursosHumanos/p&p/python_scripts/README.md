---
title: DocuGest Tags & Glossary API
emoji: 📚
colorFrom: blue
colorTo: green
sdk: docker
pinned: false
---

# DocuGest Tags & Glossary API

Automatic tag generation and glossary term detection for PDF documents using advanced NLP techniques.

## 🎯 Features

- **Intelligent Tag Generation**: Hybrid approach using KeyBERT + spaCy
- **Glossary Term Detection**: 3-layer detection (YAKE + spaCy NER + Wikipedia validation)
- **Spanish Language Support**: Optimized for Spanish documents
- **Smart Filtering**: Removes generic terms, stopwords, and form fields
- **Priority System**: Proper nouns → Technical terms → Common concepts

## 🚀 Endpoints

### Health Check

```bash
GET /
```

Returns API status and loaded models.

### Generate Tags

```bash
POST /generate-tags
```

**Request Body:**
```json
{
  "pdf_base64": "JVBERi0xLjQKJeLjz9MKMSAwIG9i...",
  "top_n": 10
}
```

**Response:**
```json
{
  "tags": ["Proveedor", "COMPRA", "Autorización", "estructura costos"],
  "total_tags": 4
}
```

### Generate Glossary Terms

```bash
POST /generate-glossary-terms
```

**Request Body:**
```json
{
  "pdf_base64": "JVBERi0xLjQKJeLjz9MKMSAwIG9i...",
  "existing_terms": ["Trello", "Firefox"]
}
```

**Response:**
```json
{
  "terminos": [
    {
      "termino": "SAP",
      "definicion": "SAP SE es una empresa alemana dedicada al diseño de productos informáticos...",
      "url": "https://es.wikipedia.org/wiki/SAP"
    }
  ],
  "total_terminos": 1
}
```

## 📋 Usage Example

### Python

```python
import requests
import base64

# Read PDF file
with open("document.pdf", "rb") as f:
    pdf_base64 = base64.b64encode(f.read()).decode()

# Generate tags
response = requests.post(
    "https://[USUARIO]-docugest-tags.hf.space/generate-tags",
    json={"pdf_base64": pdf_base64, "top_n": 10}
)

tags = response.json()["tags"]
print(tags)
```

### PHP

```php
<?php
// Read PDF file
$pdf_content = file_get_contents("document.pdf");
$pdf_base64 = base64_encode($pdf_content);

// Generate tags
$ch = curl_init("https://[USUARIO]-docugest-tags.hf.space/generate-tags");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "pdf_base64" => $pdf_base64,
    "top_n" => 10
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

$response = curl_exec($ch);
$data = json_decode($response, true);

print_r($data["tags"]);
?>
```

## ⚙️ Models Used

- **KeyBERT**: all-MiniLM-L6-v2 (semantic keyword extraction)
- **spaCy**: es_core_news_sm (Spanish NLP pipeline)
- **YAKE**: Keyword extraction algorithm
- **Wikipedia API**: Term validation and definitions

## 🔧 Configuration

The API runs on port **7860** (Hugging Face Spaces standard).

**Timeout Recommendations:**
- First request: 120 seconds (Space may be sleeping)
- Subsequent requests: 60 seconds

**Retry Logic:**
- If request fails, wait 30 seconds and retry
- Maximum 2 retry attempts

## 📝 Notes

- Optimized for Spanish documents
- PDF files should be text-based (not scanned images)
- Maximum text processing: 100,000 characters
- Comprehensive blacklist of 70+ generic terms
- Duplicate detection (case-insensitive)

## 🏗️ Architecture

```
┌─────────────┐
│   FastAPI   │
│   (Port     │
│    7860)    │
└──────┬──────┘
       │
       ├─────► KeyBERT (Semantic extraction)
       ├─────► spaCy (NLP analysis)
       ├─────► YAKE (Keyword extraction)
       └─────► Wikipedia (Term validation)
```

## 📄 License

MIT License - DocuGest Team 2026
