CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "company_id" integer,
  "is_admin" tinyint(1) not null default '0',
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "users_company_id_is_admin_index" on "users"(
  "company_id",
  "is_admin"
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "company_mcp_configs"(
  "id" integer primary key autoincrement not null,
  "company_id" integer not null,
  "ai_model" varchar not null default 'llama3',
  "max_context_length" integer not null default '4000',
  "allowed_tools" text,
  "custom_instructions" text,
  "security_rules" text,
  "active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("company_id") references "companies"("id")
);
CREATE UNIQUE INDEX "company_mcp_configs_company_id_unique" on "company_mcp_configs"(
  "company_id"
);
CREATE TABLE IF NOT EXISTS "mcp_interactions"(
  "id" integer primary key autoincrement not null,
  "company_id" integer not null,
  "user_id" integer,
  "message" text not null,
  "response" text not null,
  "context" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("company_id") references "companies"("id"),
  foreign key("user_id") references "users"("id")
);
CREATE INDEX "mcp_interactions_company_id_created_at_index" on "mcp_interactions"(
  "company_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "companies"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "cnpj" varchar,
  "phone" varchar,
  "address" text,
  "active" tinyint(1) not null default '1',
  "settings" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "companies_active_created_at_index" on "companies"(
  "active",
  "created_at"
);
CREATE UNIQUE INDEX "companies_cnpj_unique" on "companies"("cnpj");

INSERT INTO migrations VALUES(6,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(7,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(8,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(9,'2025_07_02_184746_create_mcp_tables',1);
INSERT INTO migrations VALUES(11,'2025_07_02_194024_create_companies_table',2);
