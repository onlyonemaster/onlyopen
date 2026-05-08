#!/usr/bin/env python3
"""
원챗(OneChat) RAG Pipeline v2.0 — 빅테크 엔터프라이즈 레벨
─────────────────────────────────────────────────────────────
선거 후보자 AI 아바타를 위한 RAG(Retrieval-Augmented Generation) 파이프라인.
5단계: Data Ingestion → Chunking & Embedding → Semantic Search → Evidence Response → Feedback Loop

Usage:
  python rag_pipeline.py --action ingest   --sms_idx 123
  python rag_pipeline.py --action search   --sms_idx 123 --query "출산 정책?"
  python rag_pipeline.py --action feedback --sms_idx 123 --chunk_id abc --score 0.95
  python rag_pipeline.py --action serve    --port 5100

Dependencies:
  pip install flask pymilvus openai pymysql rank-bm25 numpy scikit-learn PyPDF2 python-docx openpyxl
"""

import argparse
import hashlib
import json
import logging
import os
import re
import sys
import time
from datetime import datetime, timedelta
from pathlib import Path
from typing import Dict, List, Optional, Tuple, Any
from urllib.parse import urlparse

# ── Logging ────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(name)s: %(message)s',
    handlers=[logging.StreamHandler(sys.stdout)]
)
log = logging.getLogger('onechat-rag')

# ── Configuration ──────────────────────────────────────────────────
class RAGConfig:
    """중앙 설정 — 환경변수 우선, 없으면 기본값"""

    # Vector DB
    MILVUS_HOST = os.getenv('MILVUS_HOST', 'localhost')
    MILVUS_PORT = os.getenv('MILVUS_PORT', '19530')
    MILVUS_DB = os.getenv('MILVUS_DB', 'onechat_rag')

    # Embedding
    EMBEDDING_MODEL = os.getenv('EMBEDDING_MODEL', 'text-embedding-3-large')
    EMBEDDING_DIM = int(os.getenv('EMBEDDING_DIM', '3072'))
    EMBEDDING_BATCH = int(os.getenv('EMBEDDING_BATCH', '20'))

    # Chunking
    CHUNK_SIZE = int(os.getenv('CHUNK_SIZE', '800'))
    CHUNK_OVERLAP = int(os.getenv('CHUNK_OVERLAP', '80'))
    MAX_CHUNKS_PER_DOC = int(os.getenv('MAX_CHUNKS_PER_DOC', '500'))

    # Search
    TOP_K_DEFAULT = int(os.getenv('TOP_K_DEFAULT', '7'))
    HYBRID_ALPHA = float(os.getenv('HYBRID_ALPHA', '0.7'))  # 0=semantic only, 1=keyword only
    RERANK_ENABLED = os.getenv('RERANK_ENABLED', 'true').lower() == 'true'

    # DB
    DB_HOST = os.getenv('DB_HOST', 'localhost')
    DB_USER = os.getenv('DB_USER', 'root')
    DB_PASS = os.getenv('DB_PASS', '')
    DB_NAME = os.getenv('DB_NAME', 'kiam')

    # OpenAI
    OPENAI_API_KEY = os.getenv('OPENAI_API_KEY', '')
    OPENAI_BASE_URL = os.getenv('OPENAI_BASE_URL', 'https://api.openai.com/v1')

    # Server
    SERVER_HOST = '0.0.0.0'
    SERVER_PORT = int(os.getenv('RAG_PORT', '5100'))

    # Paths
    DATA_DIR = Path(os.getenv('RAG_DATA_DIR', '/tmp/onechat_rag_data'))
    UPLOAD_DIR = Path(os.getenv('RAG_UPLOAD_DIR', '/tmp/onechat_rag_uploads'))


cfg = RAGConfig()
cfg.DATA_DIR.mkdir(parents=True, exist_ok=True)
cfg.UPLOAD_DIR.mkdir(parents=True, exist_ok=True)


# ═══════════════════════════════════════════════════════════════════
# 1. EMBEDDING ENGINE
# ═══════════════════════════════════════════════════════════════════

class EmbeddingEngine:
    """OpenAI Embedding API wrapper — 배치 처리 + 재시도 (requests 기반, Python 3.6+ 호환)
    API 키가 없으면 시뮬레이션 모드(해시 기반 의사 벡터)로 자동 전환."""

    @staticmethod
    def _sim_vector(text: str, dim: int = 128) -> List[float]:
        """해시 기반 시뮬레이션 벡터 — API 없이도 유사도 계산 가능"""
        import hashlib, struct
        h = hashlib.sha256(text.encode('utf-8')).digest()
        vec = []
        for i in range(dim):
            # 32바이트 해시를 반복 사용하여 128차원 벡터 생성
            b = h[i % 32] / 255.0
            # 유니코드 자모(jamo) 패턴을 의사 난수로 확장
            seed = h[i % 32] ^ h[(i * 7 + 13) % 32]
            vec.append((b * 2.0 - 1.0) + (seed / 255.0 - 0.5) * 0.1)
        # 정규화
        norm = (sum(x*x for x in vec)) ** 0.5
        return [x/norm for x in vec] if norm > 0 else vec

    def embed(self, texts: List[str]) -> List[List[float]]:
        """텍스트 리스트 → 임베딩 벡터 리스트 (배치 처리)"""
        if not texts:
            return []

        # API 키가 없으면 시뮬레이션 모드
        if not cfg.OPENAI_API_KEY:
            log.info('Embedding: simulation mode (no API key)')
            return [self._sim_vector(t) for t in texts]

        import requests as _requests
        all_vectors = []
        url = f'{cfg.OPENAI_BASE_URL}/embeddings'
        headers = {
            'Authorization': f'Bearer {cfg.OPENAI_API_KEY}',
            'Content-Type': 'application/json'
        }

        for i in range(0, len(texts), cfg.EMBEDDING_BATCH):
            batch = texts[i:i + cfg.EMBEDDING_BATCH]
            batch = [t.replace('\n', ' ').strip() for t in batch]

            for attempt in range(3):
                try:
                    resp = _requests.post(url, json={
                        'model': cfg.EMBEDDING_MODEL,
                        'input': batch,
                        'encoding_format': 'float'
                    }, headers=headers, timeout=60)
                    resp.raise_for_status()
                    body = resp.json()
                    all_vectors.extend([d['embedding'] for d in body['data']])
                    break
                except Exception as e:
                    if attempt == 2:
                        raise
                    log.warning(f'Embedding retry {attempt+1}/3: {e}')
                    time.sleep(1.5 ** attempt)

        return all_vectors

    def embed_single(self, text: str) -> List[float]:
        return self.embed([text])[0]


# ═══════════════════════════════════════════════════════════════════
# 2. SEMANTIC CHUNKER
# ═══════════════════════════════════════════════════════════════════

class SemanticChunker:
    """
    의미 기반 청크 분할 엔진.
    - 문단/문장 경계 우선 분할
    - 청크 간 Overlap 유지
    - 메타데이터 태깅 (카테고리, 소스, 타임스탬프)
    """

    SENTENCE_BOUNDARY = re.compile(r'(?<=[.!?…])\s+(?=[A-Z가-힣])')
    PARAGRAPH_BOUNDARY = re.compile(r'\n\s*\n')

    @classmethod
    def chunk(cls, text: str, metadata: Optional[Dict] = None) -> List[Dict]:
        """텍스트 → 청크 리스트 (각 청크는 dict: text, metadata)"""
        meta = metadata or {}
        text = text.strip()
        if not text:
            return []

        # 1. 문단 단위로 1차 분할
        paragraphs = cls.PARAGRAPH_BOUNDARY.split(text)

        chunks = []
        current = ''
        current_len = 0

        for para in paragraphs:
            para = para.strip()
            if not para:
                continue

            para_tokens = len(para)

            # 문단이 너무 길면 문장 단위로 추가 분할
            if para_tokens > cfg.CHUNK_SIZE * 1.5:
                sentences = cls.SENTENCE_BOUNDARY.split(para)
                for sent in sentences:
                    sent = sent.strip()
                    if not sent:
                        continue
                    sent_tokens = len(sent)

                    if current_len + sent_tokens > cfg.CHUNK_SIZE and current:
                        chunks.append(cls._make_chunk(current.strip(), meta))
                        # Overlap: 이전 청크 마지막 부분 유지
                        overlap_text = current[-cfg.CHUNK_OVERLAP:] if len(current) > cfg.CHUNK_OVERLAP else current
                        current = overlap_text + ' ' + sent
                        current_len = len(current)
                    else:
                        current = (current + ' ' + sent).strip()
                        current_len = len(current)

                    if current_len >= cfg.CHUNK_SIZE:
                        chunks.append(cls._make_chunk(current.strip(), meta))
                        overlap_text = current[-cfg.CHUNK_OVERLAP:] if len(current) > cfg.CHUNK_OVERLAP else current
                        current = overlap_text
                        current_len = len(current)
            else:
                if current_len + para_tokens > cfg.CHUNK_SIZE and current:
                    chunks.append(cls._make_chunk(current.strip(), meta))
                    overlap_text = current[-cfg.CHUNK_OVERLAP:] if len(current) > cfg.CHUNK_OVERLAP else current
                    current = overlap_text + '\n\n' + para
                    current_len = len(current)
                else:
                    current = (current + '\n\n' + para).strip() if current else para
                    current_len = len(current)

                if current_len >= cfg.CHUNK_SIZE:
                    chunks.append(cls._make_chunk(current.strip(), meta))
                    overlap_text = current[-cfg.CHUNK_OVERLAP:] if len(current) > cfg.CHUNK_OVERLAP else current
                    current = overlap_text
                    current_len = len(current)

        if current.strip():
            chunks.append(cls._make_chunk(current.strip(), meta))

        # 최대 개수 제한
        return chunks[:cfg.MAX_CHUNKS_PER_DOC]

    @staticmethod
    def _make_chunk(text: str, meta: Dict) -> Dict:
        chunk_id = hashlib.sha256(
            f"{meta.get('sms_idx','')}|{meta.get('source','')}|{text[:200]}".encode()
        ).hexdigest()[:16]
        return {
            'chunk_id': chunk_id,
            'text': text,
            'metadata': {
                **meta,
                'char_count': len(text),
                'token_estimate': len(text) // 2,
                'created_at': datetime.now().isoformat()
            }
        }


# ═══════════════════════════════════════════════════════════════════
# 3. VECTOR STORE (Milvus)
# ═══════════════════════════════════════════════════════════════════

class VectorStore:
    """Milvus Vector DB 래퍼 — 컬렉션 관리 + upsert + search"""

    COLLECTION_PREFIX = 'onechat_candidate_'

    def __init__(self):
        self._connected = False

    def _connect(self):
        if self._connected:
            return
        try:
            from pymilvus import connections, utility
            connections.connect(
                alias='default',
                host=cfg.MILVUS_HOST,
                port=cfg.MILVUS_PORT
            )
            self._connected = True
            log.info(f'Milvus connected: {cfg.MILVUS_HOST}:{cfg.MILVUS_PORT}')
        except Exception as e:
            log.warning(f'Milvus connection failed, using fallback: {e}')
            self._connected = False

    def _collection_name(self, sms_idx: int) -> str:
        return f'{self.COLLECTION_PREFIX}{sms_idx}'

    def _ensure_collection(self, sms_idx: int):
        from pymilvus import Collection, CollectionSchema, DataType, FieldSchema, utility
        name = self._collection_name(sms_idx)

        if utility.has_collection(name):
            return

        fields = [
            FieldSchema(name='chunk_id', dtype=DataType.VARCHAR, max_length=64, is_primary=True),
            FieldSchema(name='sms_idx', dtype=DataType.INT64),
            FieldSchema(name='text', dtype=DataType.VARCHAR, max_length=65535),
            FieldSchema(name='embedding', dtype=DataType.FLOAT_VECTOR, dim=cfg.EMBEDDING_DIM),
            FieldSchema(name='category', dtype=DataType.VARCHAR, max_length=128),
            FieldSchema(name='source', dtype=DataType.VARCHAR, max_length=512),
            FieldSchema(name='metadata_json', dtype=DataType.VARCHAR, max_length=8192),
        ]
        schema = CollectionSchema(fields, description=f'OneChat RAG — candidate {sms_idx}')
        collection = Collection(name, schema)

        # 인덱스 생성
        index_params = {
            'metric_type': 'COSINE',
            'index_type': 'IVF_FLAT',
            'params': {'nlist': 128}
        }
        collection.create_index('embedding', index_params)
        collection.load()
        log.info(f'Collection created: {name}')

    def upsert_chunks(self, sms_idx: int, chunks: List[Dict]) -> int:
        """청크 리스트를 Vector DB에 upsert. 반환: 저장된 개수"""
        if not chunks:
            return 0

        self._connect()
        if not self._connected:
            return self._fallback_upsert(sms_idx, chunks)

        self._ensure_collection(sms_idx)

        from pymilvus import Collection
        collection = Collection(self._collection_name(sms_idx))

        rows = []
        for c in chunks:
            rows.append([
                c['chunk_id'],
                sms_idx,
                c['text'][:65535],
                [],  # placeholder for embedding
                c.get('metadata', {}).get('category', ''),
                c.get('metadata', {}).get('source', ''),
                json.dumps(c.get('metadata', {}), ensure_ascii=False)
            ])

        # 임베딩 생성
        texts_for_embed = [c['text'] for c in chunks]
        embeddings = EmbeddingEngine().embed(texts_for_embed)

        for i, emb in enumerate(embeddings):
            rows[i][3] = emb

        mr = collection.upsert(rows)
        collection.flush()
        log.info(f'Upserted {mr.insert_count} chunks to {self._collection_name(sms_idx)}')
        return mr.insert_count

    def search(self, sms_idx: int, query: str, top_k: int = None,
               filters: Optional[Dict] = None) -> List[Dict]:
        """의미 기반 검색. filters: {category: '정책', source: '...'}"""
        if top_k is None:
            top_k = cfg.TOP_K_DEFAULT

        self._connect()
        if not self._connected:
            return self._fallback_search(sms_idx, query, top_k, filters)

        query_vec = EmbeddingEngine().embed_single(query)

        from pymilvus import Collection
        try:
            collection = Collection(self._collection_name(sms_idx))
            collection.load()
        except Exception:
            return []

        # 필터 표현식 구축
        expr_parts = []
        if filters:
            for k, v in filters.items():
                if k in ('category', 'source'):
                    expr_parts.append(f'{k} == "{v}"')
        expr = ' && '.join(expr_parts) if expr_parts else None

        search_params = {'metric_type': 'COSINE', 'params': {'nprobe': 16}}
        results = collection.search(
            data=[query_vec],
            anns_field='embedding',
            param=search_params,
            limit=top_k,
            expr=expr,
            output_fields=['chunk_id', 'text', 'category', 'source', 'metadata_json']
        )

        hits = []
        for hit in results[0]:
            hits.append({
                'chunk_id': hit.entity.get('chunk_id'),
                'text': hit.entity.get('text'),
                'category': hit.entity.get('category', ''),
                'source': hit.entity.get('source', ''),
                'metadata': json.loads(hit.entity.get('metadata_json', '{}')),
                'score': float(hit.distance)
            })
        return hits

    def delete_collection(self, sms_idx: int):
        """후보자 데이터 전체 삭제"""
        self._connect()
        if self._connected:
            from pymilvus import utility
            name = self._collection_name(sms_idx)
            if utility.has_collection(name):
                utility.drop_collection(name)
                log.info(f'Collection dropped: {name}')

    # ── Fallback: Milvus 없을 때 JSON 파일 기반 검색 ──────────────
    def _fallback_path(self, sms_idx: int) -> Path:
        return cfg.DATA_DIR / f'chunks_{sms_idx}.json'

    def _fallback_upsert(self, sms_idx: int, chunks: List[Dict]) -> int:
        path = self._fallback_path(sms_idx)

        existing = {}
        if path.exists():
            try:
                existing_data = json.loads(path.read_text(encoding='utf-8'))
                for c in existing_data:
                    existing[c['chunk_id']] = c
            except Exception:
                pass

        for c in chunks:
            existing[c['chunk_id']] = c

        path.write_text(
            json.dumps(list(existing.values()), ensure_ascii=False, indent=2),
            encoding='utf-8'
        )
        log.info(f'Fallback upsert: {len(chunks)} chunks to {path}')
        return len(chunks)

    def _fallback_search(self, sms_idx: int, query: str, top_k: int,
                         filters: Optional[Dict]) -> List[Dict]:
        path = self._fallback_path(sms_idx)
        if not path.exists():
            return []

        try:
            all_chunks = json.loads(path.read_text(encoding='utf-8'))
        except Exception:
            return []

        # 필터링
        if filters:
            filtered = []
            for c in all_chunks:
                match = True
                for k, v in filters.items():
                    if c.get('metadata', {}).get(k) != v:
                        match = False
                        break
                if match:
                    filtered.append(c)
            all_chunks = filtered

        # BM25 + 의미 유사도 하이브리드
        from rank_bm25 import BM25Okapi
        query_vec = EmbeddingEngine().embed_single(query)
        texts = [c['text'] for c in all_chunks]
        tokenized = [t.split() for t in texts]
        bm25 = BM25Okapi(tokenized)
        bm25_scores = bm25.get_scores(query.split())

        results = []
        for i, c in enumerate(all_chunks):
            # 실시간 chunk embedding 생성 + 캐싱
            chunk_vec = self._load_embedding(c)
            if not chunk_vec:
                chunk_vec = EmbeddingEngine().embed_single(c['text'])
                c['_cached_embedding'] = chunk_vec  # 다음 검색을 위해 캐싱
            sem_score = self._cosine_sim(query_vec, chunk_vec)
            bm25_norm = bm25_scores[i] / max(bm25_scores) if max(bm25_scores) > 0 else 0
            hybrid = cfg.HYBRID_ALPHA * sem_score + (1 - cfg.HYBRID_ALPHA) * bm25_norm
            results.append({
                'chunk_id': c['chunk_id'],
                'text': c['text'],
                'category': c.get('metadata', {}).get('category', ''),
                'source': c.get('metadata', {}).get('source', ''),
                'metadata': c.get('metadata', {}),
                'score': round(hybrid, 4)
            })

        results.sort(key=lambda x: x['score'], reverse=True)
        return results[:top_k]

    @staticmethod
    def _cosine_sim(a, b):
        if not a or not b:
            return 0.0
        dot = sum(x * y for x, y in zip(a, b))
        norm_a = sum(x * x for x in a) ** 0.5
        norm_b = sum(x * x for x in b) ** 0.5
        return dot / (norm_a * norm_b) if norm_a * norm_b > 0 else 0.0

    @staticmethod
    def _load_embedding(chunk: Dict) -> List[float]:
        return chunk.get('_cached_embedding', [])


# ═══════════════════════════════════════════════════════════════════
# 4. DOCUMENT PARSER (Multi-format)
# ═══════════════════════════════════════════════════════════════════

class DocumentParser:
    """멀티 포맷 문서 파서: PDF, DOCX, PPTX, XLSX, TXT, CSV, Markdown"""

    @classmethod
    def parse(cls, file_path: Path) -> str:
        ext = file_path.suffix.lower()
        parsers = {
            '.txt': cls._parse_txt,
            '.md': cls._parse_txt,
            '.csv': cls._parse_csv,
            '.pdf': cls._parse_pdf,
            '.docx': cls._parse_docx,
            '.pptx': cls._parse_pptx,
            '.xlsx': cls._parse_xlsx,
        }
        parser = parsers.get(ext)
        if parser is None:
            raise ValueError(f'Unsupported format: {ext}')
        return parser(file_path)

    @staticmethod
    def _parse_txt(path: Path) -> str:
        return path.read_text(encoding='utf-8', errors='replace')

    @staticmethod
    def _parse_csv(path: Path) -> str:
        import csv
        rows = []
        with open(path, 'r', encoding='utf-8', errors='replace') as f:
            reader = csv.DictReader(f)
            for row in reader:
                rows.append(' | '.join(f'{k}: {v}' for k, v in row.items()))
        return '\n'.join(rows)

    @staticmethod
    def _parse_pdf(path: Path) -> str:
        try:
            from PyPDF2 import PdfReader
            reader = PdfReader(str(path))
            return '\n\n'.join(page.extract_text() or '' for page in reader.pages)
        except ImportError:
            raise ImportError('PyPDF2 not installed. pip install PyPDF2')

    @staticmethod
    def _parse_docx(path: Path) -> str:
        try:
            from docx import Document
            doc = Document(str(path))
            return '\n\n'.join(p.text for p in doc.paragraphs)
        except ImportError:
            raise ImportError('python-docx not installed. pip install python-docx')

    @staticmethod
    def _parse_pptx(path: Path) -> str:
        try:
            from pptx import Presentation
            prs = Presentation(str(path))
            texts = []
            for slide in prs.slides:
                slide_texts = []
                for shape in slide.shapes:
                    if shape.has_text_frame:
                        slide_texts.append(shape.text_frame.text)
                texts.append('\n'.join(slide_texts))
            return '\n\n---\n\n'.join(texts)
        except ImportError:
            raise ImportError('python-pptx not installed. pip install python-pptx')

    @staticmethod
    def _parse_xlsx(path: Path) -> str:
        try:
            from openpyxl import load_workbook
            wb = load_workbook(path, read_only=True, data_only=True)
            all_text = []
            for sheet_name in wb.sheetnames:
                ws = wb[sheet_name]
                rows_text = []
                for row in ws.iter_rows(values_only=True):
                    rows_text.append(' | '.join(str(c) if c is not None else '' for c in row))
                all_text.append(f'--- Sheet: {sheet_name} ---\n' + '\n'.join(rows_text))
            return '\n\n'.join(all_text)
        except ImportError:
            raise ImportError('openpyxl not installed. pip install openpyxl')


# ═══════════════════════════════════════════════════════════════════
# 5. RAG PIPELINE (Main orchestrator)
# ═══════════════════════════════════════════════════════════════════

class RAGPipeline:

    def __init__(self):
        self.embedder = EmbeddingEngine()
        self.chunker = SemanticChunker()
        self.vector_store = VectorStore()
        self.parser = DocumentParser()

    # ── INGEST ────────────────────────────────────────────────────

    def ingest_text(self, sms_idx: int, text: str, metadata: Dict) -> int:
        """텍스트를 청크로 분할 후 Vector DB에 저장"""
        chunks = self.chunker.chunk(text, metadata={
            **metadata,
            'sms_idx': sms_idx,
            'ingested_at': datetime.now().isoformat()
        })

        for c in chunks:
            c['metadata']['sms_idx'] = sms_idx

        return self.vector_store.upsert_chunks(sms_idx, chunks)

    def ingest_file(self, sms_idx: int, file_path: str, category: str,
                    source: str = '') -> int:
        """파일 경로 → 파싱 → 청크 → Vector DB"""
        path = Path(file_path)
        if not path.exists():
            raise FileNotFoundError(f'File not found: {file_path}')

        text = self.parser.parse(path)
        meta = {
            'category': category,
            'source': source or path.name,
            'file_name': path.name,
            'file_type': path.suffix.lower(),
            'sms_idx': sms_idx
        }
        return self.ingest_text(sms_idx, text, meta)

    def ingest_url(self, sms_idx: int, url: str, category: str) -> int:
        """URL → 크롤링 → 텍스트 추출 → 청크 → Vector DB"""
        import requests
        from bs4 import BeautifulSoup

        headers = {
            'User-Agent': 'OneChat-RAG/2.0 (Candidate Research Bot; +https://onechat.ai)'
        }

        try:
            resp = requests.get(url, headers=headers, timeout=30)
            resp.raise_for_status()
        except Exception as e:
            raise RuntimeError(f'Failed to fetch URL {url}: {e}')

        soup = BeautifulSoup(resp.text, 'html.parser')

        # 불필요 요소 제거
        for tag in soup(['script', 'style', 'nav', 'footer', 'header', 'aside']):
            tag.decompose()

        text = soup.get_text(separator='\n', strip=True)
        parsed = urlparse(url)
        source = f'{parsed.netloc}{parsed.path}'

        meta = {
            'category': category,
            'source': source,
            'url': url,
            'file_type': 'url',
            'sms_idx': sms_idx
        }
        return self.ingest_text(sms_idx, text, meta)

    def ingest_youtube(self, sms_idx: int, url: str, category: str) -> int:
        """YouTube URL → 자막 추출 → 텍스트 → 청크 → Vector DB"""
        try:
            from youtube_transcript_api import YouTubeTranscriptApi
        except ImportError:
            raise ImportError('youtube-transcript-api not installed. pip install youtube-transcript-api')

        # URL에서 video_id 추출
        video_id = None
        patterns = [
            r'(?:v=|/v/|youtu\.be/)([a-zA-Z0-9_-]{11})',
            r'(?:embed/)([a-zA-Z0-9_-]{11})'
        ]
        for pat in patterns:
            m = re.search(pat, url)
            if m:
                video_id = m.group(1)
                break
        if not video_id:
            raise ValueError(f'Cannot extract video ID from URL: {url}')

        transcript = YouTubeTranscriptApi.get_transcript(video_id, languages=['ko', 'en'])
        text = '\n'.join(
            f'[{entry["start"]:.0f}s] {entry["text"]}'
            for entry in transcript
        )

        meta = {
            'category': category,
            'source': f'youtube:{video_id}',
            'url': url,
            'file_type': 'youtube',
            'sms_idx': sms_idx
        }
        return self.ingest_text(sms_idx, text, meta)

    # ── SEARCH ────────────────────────────────────────────────────

    def search(self, sms_idx: int, query: str, top_k: int = None,
               category: str = None) -> List[Dict]:
        """RAG 검색: 유권자 질문 → Context 검색"""
        filters = {}
        if category:
            filters['category'] = category
        return self.vector_store.search(sms_idx, query, top_k, filters)

    # ── RESPONSE GENERATION ───────────────────────────────────────

    def generate_response(self, sms_idx: int, query: str,
                          system_prompt: str = '',
                          model: str = 'gpt-4o') -> Dict:
        """
        RAG 기반 응답 생성: Context 검색 → System Prompt + Context → LLM 응답
        Returns: {answer, citations, context_used}
        """
        # 1. 검색
        context_chunks = self.search(sms_idx, query, top_k=cfg.TOP_K_DEFAULT)

        if not context_chunks:
            return {
                'answer': '',
                'citations': [],
                'context_used': [],
                'fallback': True
            }

        # 2. Reranking (선택적)
        if cfg.RERANK_ENABLED and len(context_chunks) > 3:
            context_chunks = self._rerank(query, context_chunks)

        # 3. Context 문자열 구성
        context_texts = []
        for i, c in enumerate(context_chunks):
            source_info = c.get('source', '') or c.get('category', '')
            context_texts.append(f'[Source-{i+1} | {source_info}]\n{c["text"]}')

        context_block = '\n\n---\n\n'.join(context_texts)

        # 4. LLM 호출
        prompt = f"""{system_prompt}

## 검색된 후보자 데이터 (Evidence)
아래는 후보자의 실제 발언, 공약, 정책 문서에서 검색된 내용입니다.
답변 시 반드시 이 데이터에 기반하여 답변하고, 출처를 명시하세요.

{context_block}

## 유권자 질문
{query}

## 응답 규칙
1. 반드시 위 Evidence에 기반하여 답변할 것
2. 각 답변에 출처 번호 [Source-N]을 명시할 것
3. Evidence에 없는 내용은 "확인된 바 없습니다"라고 답변할 것
4. 정치적 중립을 유지하되 후보의 입장을 사실대로 전달할 것
5. 200~400자 내외로 간결하게 답변할 것"""

        try:
            import requests as _requests
            resp = _requests.post(
                f'{cfg.OPENAI_BASE_URL}/chat/completions',
                json={
                    'model': model,
                    'messages': [{'role': 'user', 'content': prompt}],
                    'temperature': 0.5,
                    'max_tokens': 800
                },
                headers={
                    'Authorization': f'Bearer {cfg.OPENAI_API_KEY}',
                    'Content-Type': 'application/json'
                },
                timeout=90
            )
            resp.raise_for_status()
            answer = resp.json()['choices'][0]['message']['content']
        except Exception as e:
            log.error(f'LLM call failed: {e}')
            answer = '죄송합니다. 현재 답변을 생성할 수 없습니다. 잠시 후 다시 시도해 주세요.'

        # 5. 인용 정보 구성
        citations = []
        for c in context_chunks:
            citations.append({
                'chunk_id': c['chunk_id'],
                'source': c.get('source', ''),
                'category': c.get('category', ''),
                'text_preview': c['text'][:200]
            })

        return {
            'answer': answer,
            'citations': citations,
            'context_used': context_chunks,
            'fallback': False
        }

    def _rerank(self, query: str, chunks: List[Dict]) -> List[Dict]:
        """Cross-encoder reranking (간단한 키워드 기반 구현)"""
        # 실제 운영시 Cohere Rerank API 또는 SentenceTransformers CrossEncoder 사용
        query_words = set(query.lower().split())
        for c in chunks:
            text_words = set(c['text'].lower().split())
            overlap = len(query_words & text_words)
            c['_bm25_boost'] = overlap / max(len(query_words), 1)
            c['score'] = c['score'] * 0.7 + c['_bm25_boost'] * 0.3

        chunks.sort(key=lambda x: x['score'], reverse=True)
        return chunks[:5]

    # ── FEEDBACK ──────────────────────────────────────────────────

    def record_feedback(self, sms_idx: int, query: str, chunk_ids: List[str],
                        user_rating: float, feedback_type: str = 'quality'):
        """유저 피드백 기록 → 향후 검색 품질 개선에 활용"""
        path = cfg.DATA_DIR / f'feedback_{sms_idx}.jsonl'
        entry = {
            'timestamp': datetime.now().isoformat(),
            'sms_idx': sms_idx,
            'query': query,
            'chunk_ids': chunk_ids,
            'user_rating': user_rating,
            'feedback_type': feedback_type
        }
        with open(path, 'a', encoding='utf-8') as f:
            f.write(json.dumps(entry, ensure_ascii=False) + '\n')

    # ── ANALYTICS ─────────────────────────────────────────────────

    def get_stats(self, sms_idx: int) -> Dict:
        """후보자별 RAG 통계"""
        chunks_path = self.vector_store._fallback_path(sms_idx)
        feedback_path = cfg.DATA_DIR / f'feedback_{sms_idx}.jsonl'

        total_chunks = 0
        categories = {}
        if chunks_path.exists():
            try:
                data = json.loads(chunks_path.read_text(encoding='utf-8'))
                total_chunks = len(data)
                for c in data:
                    cat = c.get('metadata', {}).get('category', 'unknown')
                    categories[cat] = categories.get(cat, 0) + 1
            except Exception:
                pass

        feedback_count = 0
        avg_rating = 0.0
        if feedback_path.exists():
            ratings = []
            with open(feedback_path, 'r', encoding='utf-8') as f:
                for line in f:
                    try:
                        entry = json.loads(line)
                        ratings.append(entry.get('user_rating', 0))
                    except Exception:
                        pass
            feedback_count = len(ratings)
            avg_rating = sum(ratings) / len(ratings) if ratings else 0.0

        return {
            'sms_idx': sms_idx,
            'total_chunks': total_chunks,
            'categories': categories,
            'total_feedback': feedback_count,
            'avg_rating': round(avg_rating, 3),
            'collection_name': self.vector_store._collection_name(sms_idx),
            'timestamp': datetime.now().isoformat()
        }


# ═══════════════════════════════════════════════════════════════════
# 6. FLASK API SERVER
# ═══════════════════════════════════════════════════════════════════

def create_flask_app():
    from flask import Flask, request, jsonify

    app = Flask(__name__)
    pipeline = RAGPipeline()

    @app.route('/health', methods=['GET'])
    def health():
        return jsonify({'status': 'ok', 'service': 'onechat-rag', 'version': '2.0.0'})

    @app.route('/ingest/text', methods=['POST'])
    def api_ingest_text():
        data = request.get_json(force=True)
        sms_idx = int(data['sms_idx'])
        text = data['text']
        metadata = data.get('metadata', {})
        count = pipeline.ingest_text(sms_idx, text, metadata)
        return jsonify({'status': 'ok', 'chunks_created': count})

    @app.route('/ingest/file', methods=['POST'])
    def api_ingest_file():
        data = request.get_json(force=True)
        sms_idx = int(data['sms_idx'])
        file_path = data['file_path']
        category = data.get('category', 'general')
        source = data.get('source', '')
        count = pipeline.ingest_file(sms_idx, file_path, category, source)
        return jsonify({'status': 'ok', 'chunks_created': count})

    @app.route('/ingest/url', methods=['POST'])
    def api_ingest_url():
        data = request.get_json(force=True)
        sms_idx = int(data['sms_idx'])
        url = data['url']
        category = data.get('category', 'web')
        count = pipeline.ingest_url(sms_idx, url, category)
        return jsonify({'status': 'ok', 'chunks_created': count})

    @app.route('/ingest/youtube', methods=['POST'])
    def api_ingest_youtube():
        data = request.get_json(force=True)
        sms_idx = int(data['sms_idx'])
        url = data['url']
        category = data.get('category', 'speech')
        count = pipeline.ingest_youtube(sms_idx, url, category)
        return jsonify({'status': 'ok', 'chunks_created': count})

    @app.route('/search', methods=['POST'])
    def api_search():
        data = request.get_json(force=True)
        sms_idx = int(data['sms_idx'])
        query = data['query']
        top_k = data.get('top_k')
        category = data.get('category')
        results = pipeline.search(sms_idx, query, top_k, category)
        return jsonify({'status': 'ok', 'results': results, 'count': len(results)})

    @app.route('/generate', methods=['POST'])
    def api_generate():
        data = request.get_json(force=True)
        sms_idx = int(data['sms_idx'])
        query = data['query']
        system_prompt = data.get('system_prompt', '')
        model = data.get('model', 'gpt-4o')
        result = pipeline.generate_response(sms_idx, query, system_prompt, model)
        return jsonify({'status': 'ok', **result})

    @app.route('/feedback', methods=['POST'])
    def api_feedback():
        data = request.get_json(force=True)
        sms_idx = int(data['sms_idx'])
        query = data['query']
        chunk_ids = data.get('chunk_ids', [])
        rating = float(data.get('rating', 0))
        ftype = data.get('type', 'quality')
        pipeline.record_feedback(sms_idx, query, chunk_ids, rating, ftype)
        return jsonify({'status': 'ok'})

    @app.route('/stats/<int:sms_idx>', methods=['GET'])
    def api_stats(sms_idx):
        stats = pipeline.get_stats(sms_idx)
        return jsonify({'status': 'ok', 'stats': stats})

    @app.route('/delete/<int:sms_idx>', methods=['DELETE'])
    def api_delete(sms_idx):
        pipeline.vector_store.delete_collection(sms_idx)
        # Remove fallback files
        for p in [
            cfg.DATA_DIR / f'chunks_{sms_idx}.json',
            cfg.DATA_DIR / f'feedback_{sms_idx}.jsonl'
        ]:
            if p.exists():
                p.unlink()
        return jsonify({'status': 'ok', 'message': f'Candidate {sms_idx} data deleted'})

    return app


# ═══════════════════════════════════════════════════════════════════
# CLI
# ═══════════════════════════════════════════════════════════════════

def main():
    parser = argparse.ArgumentParser(description='OneChat RAG Pipeline v2.0')
    sub = parser.add_subparsers(dest='action')

    # ingest text
    p = sub.add_parser('ingest')
    p.add_argument('--sms_idx', type=int, required=True)
    p.add_argument('--text', type=str)
    p.add_argument('--file', type=str)
    p.add_argument('--url', type=str)
    p.add_argument('--youtube', type=str)
    p.add_argument('--category', type=str, default='general')
    p.add_argument('--source', type=str, default='')

    # search
    p = sub.add_parser('search')
    p.add_argument('--sms_idx', type=int, required=True)
    p.add_argument('--query', type=str, required=True)
    p.add_argument('--top_k', type=int, default=7)
    p.add_argument('--category', type=str)

    # generate
    p = sub.add_parser('generate')
    p.add_argument('--sms_idx', type=int, required=True)
    p.add_argument('--query', type=str, required=True)
    p.add_argument('--system', type=str, default='')

    # stats
    p = sub.add_parser('stats')
    p.add_argument('--sms_idx', type=int, required=True)

    # serve
    p = sub.add_parser('serve')
    p.add_argument('--port', type=int, default=cfg.SERVER_PORT)
    p.add_argument('--debug', action='store_true')

    args = parser.parse_args()
    pipeline = RAGPipeline()

    if args.action == 'ingest':
        if args.text:
            count = pipeline.ingest_text(args.sms_idx, args.text,
                                         {'category': args.category, 'source': args.source})
        elif args.file:
            count = pipeline.ingest_file(args.sms_idx, args.file, args.category, args.source)
        elif args.url:
            count = pipeline.ingest_url(args.sms_idx, args.url, args.category)
        elif args.youtube:
            count = pipeline.ingest_youtube(args.sms_idx, args.youtube, args.category)
        else:
            print('ERROR: must specify --text, --file, --url, or --youtube')
            sys.exit(1)
        print(json.dumps({'status': 'ok', 'chunks_created': count}))

    elif args.action == 'search':
        results = pipeline.search(args.sms_idx, args.query, args.top_k, args.category)
        print(json.dumps({'status': 'ok', 'results': results}, ensure_ascii=False, indent=2))

    elif args.action == 'generate':
        result = pipeline.generate_response(args.sms_idx, args.query, args.system)
        print(json.dumps({'status': 'ok', **result}, ensure_ascii=False, indent=2))

    elif args.action == 'stats':
        stats = pipeline.get_stats(args.sms_idx)
        print(json.dumps({'status': 'ok', 'stats': stats}, ensure_ascii=False, indent=2))

    elif args.action == 'serve':
        port = args.port or cfg.SERVER_PORT
        app = create_flask_app()
        log.info(f'Starting OneChat RAG server on port {port}')
        app.run(host=cfg.SERVER_HOST, port=port, debug=args.debug)

    else:
        parser.print_help()


if __name__ == '__main__':
    main()