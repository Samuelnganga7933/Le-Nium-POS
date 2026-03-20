-- MySQL dump from SQLite

CREATE TABLE `migrations` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `migration` varchar not null, `batch` integer not null);

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
('1', '0001_01_01_000000_create_users_table', '1'),
('2', '0001_01_01_000001_create_cache_table', '1'),
('3', '0001_01_01_000002_create_jobs_table', '1'),
('4', '2025_12_22_222027_create_employees_table', '2');

CREATE TABLE `users` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar not null, `email` varchar not null, `email_verified_at` datetime, `password` varchar not null, `remember_token` varchar, `created_at` datetime, `updated_at` datetime);

CREATE TABLE `password_reset_tokens` (`email` varchar not null, `token` varchar not null, `created_at` datetime, PRIMARY KEY (`email`));

CREATE TABLE `sessions` (`id` varchar not null, `user_id` integer, `ip_address` varchar, `user_agent` text, `payload` text not null, `last_activity` integer not null, PRIMARY KEY (`id`));

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('tAtOvjNBkf6F6CdqZFusj19ak0NFVX4k0V5rH32D', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaDU2d0h3aXRia3huTUNtb21EbHZIWWUweThiVkdIZVlIRUY5bG5QVSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1766439199');

CREATE TABLE `cache` (`key` varchar not null, `value` text not null, `expiration` integer not null, PRIMARY KEY (`key`));

CREATE TABLE `cache_locks` (`key` varchar not null, `owner` varchar not null, `expiration` integer not null, PRIMARY KEY (`key`));

CREATE TABLE `jobs` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `queue` varchar not null, `payload` text not null, `attempts` integer not null, `reserved_at` integer, `available_at` integer not null, `created_at` integer not null);

CREATE TABLE `job_batches` (`id` varchar not null, `name` varchar not null, `total_jobs` integer not null, `pending_jobs` integer not null, `failed_jobs` integer not null, `failed_job_ids` text not null, `options` text, `cancelled_at` integer, `created_at` integer not null, `finished_at` integer, PRIMARY KEY (`id`));

CREATE TABLE `failed_jobs` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `uuid` varchar not null, `connection` text not null, `queue` text not null, `payload` text not null, `exception` text not null, `failed_at` datetime not null default CURRENT_TIMESTAMP);

CREATE TABLE `employees` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` integer not null, `salary` numeric not null default '0', `payment_method` varchar check (`payment_method` in ('mpesa', 'bank', 'cash')) not null default 'mpesa', `bank_name` varchar, `bank_account` varchar, `mpesa_phone` varchar, `employment_status` varchar check (`employment_status` in ('active', 'suspended', 'terminated', 'on_leave')) not null default 'active', `hire_date` date, `last_raise_date` date, `notes` text, `created_at` datetime, `updated_at` datetime, foreign key(`user_id`) references `users`(`id`) on delete cascade);

