<?php
// ─── Base de données ──────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'rag');
define('DB_USER',    'sanji');
define('DB_PASS',    'wynnrckr');
define('DB_CHARSET', 'utf8mb4');

// ─── Webhooks n8n ─────────────────────────────────────────────
define('WEBHOOK_INDEXATION_URL', 'https://n8n.srv859196.hstgr.cloud/webhook/f5953563-088a-4381-8445-1f95bc906bb1');
define('WEBHOOK_QA_URL',         'https://n8n.srv859196.hstgr.cloud/webhook/f4fc8034-45c7-4f4a-a117-af3f651e3e8d');

// ─── Application ──────────────────────────────────────────────
define('APP_NAME', 'RAG');
define('SESSION_NAME', 'rag_session');
