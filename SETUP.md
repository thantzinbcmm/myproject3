# SETUP.md — Hotel Booking System セットアップ手順

## 目次

1. 必要環境
2. ローカル開発環境（Docker）
3. 手動セットアップ（Dockerなし）
4. 環境変数の設定
5. データベースのセットアップ
6. JWT シークレットの生成
7. 初期データの投入
8. キュー・スケジューラの起動
9. テストの実行
10. 本番環境へのデプロイ

---

## 1. 必要環境

| 環境 | バージョン |
|------|-----------|
| PHP  | 8.2 以上  |
| MySQL | 8.0 以上 |
| Redis | 7.0 以上 |
| Composer | 2.x |
| Node.js | 18.x 以上（フロントエンドのみ） |
| Docker | 24.x 以上（Docker使用時） |
| Docker Compose | 2.x（Docker使用時） |

---

## 2. ローカル開発環境（Docker推奨）

### 2-1. リポジトリのクローン

    git clone https://github.com/your-org/hotel-booking-system.git
    cd hotel-booking-system

### 2-2. 環境変数ファイルの作成

    cp .env.example .env

### 2-3. Docker コンテナの起動

    docker-compose up -d

起動するサービス:

- `app` — PHP 8.2-FPM アプリケーションサーバー
- `webserver` — Nginx（ポート 8000）
- `db` — MySQL 8.0（ポート 3306）
- `redis` — Redis 7（ポート 6379）
- `mailpit` — メール確認ツール（ポート 8025）
- `queue_worker` — キューワーカー
- `scheduler` — Laravelスケジューラー

### 2-4. Composer 依存関係のインストール

    docker-compose exec app composer install

### 2-5. アプリケーションキーの生成

    docker-compose exec app php artisan key:generate

### 2-6. JWT シークレットの生成

    docker-compose exec app php artisan jwt:secret

### 2-7. マイグレーションの実行

    docker-compose exec app php artisan migrate

### 2-8. 初期データの投入

    docker-compose exec app php artisan db:seed

### 2-9. 在庫データの初期生成（3ヶ月分）

    docker-compose exec app php artisan inventory:generate --months=3

### 2-10. 動作確認

ブラウザまたは curl で以下のURLにアクセス:

    curl http://localhost:8000/up
    # → {"status": "ok"} が返ればOK

    curl http://localhost:8000/api/v1/facilities
    # → 施設一覧が返ればOK

メールの確認: http://localhost:8025 (Mailpit UI)

---

## 3. 手動セットアップ（Dockerなし）

### 3-1. Composer 依存関係のインストール

    composer install --no-interaction --prefer-dist --optimize-autoloader

### 3-2. 環境変数ファイルの作成

    cp .env.example .env

### 3-3. アプリケーションキーの生成

    php artisan key:generate

### 3-4. JWT シークレットの生成

    php artisan jwt:secret

### 3-5. マイグレーションの実行

    php artisan migrate

### 3-6. 初期データの投入

    php artisan db:seed

### 3-7. ストレージのリンク作成

    php artisan storage:link

### 3-8. キャッシュの最適化（本番環境向け）

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

### 3-9. 開発サーバーの起動

    php artisan serve --port=8000

---

## 4. 環境変数の設定

`.env` ファイルを編集して以下の値を設定します。

### 4-1. アプリケーション基本設定

    APP_NAME="BMM Hotel Booking System"
    APP_ENV=local          # production / staging / local
    APP_DEBUG=true         # 本番では false
    APP_TIMEZONE=Asia/Tokyo
    APP_URL=http://localhost:8000

### 4-2. データベース設定

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=hotel_booking
    DB_USERNAME=your_db_user
    DB_PASSWORD=your_db_password

### 4-3. Redis 設定

    REDIS_HOST=127.0.0.1
    REDIS_PASSWORD=null
    REDIS_PORT=6379

### 4-4. JWT 設定

    JWT_SECRET=（php artisan jwt:secret で自動生成）
    JWT_TTL=120              # アクセストークン有効期限（分）
    JWT_REFRESH_TTL=10080    # リフレッシュトークン有効期限（分）

### 4-5. メール設定（SendGrid例）

    MAIL_MAILER=smtp
    MAIL_HOST=smtp.sendgrid.net
    MAIL_PORT=587
    MAIL_USERNAME=apikey
    MAIL_PASSWORD=your_sendgrid_api_key
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS=noreply@bmm-hotel.com
    MAIL_FROM_NAME="BMM Hotel"

### 4-6. AWS S3 設定（バックアップ用）

    AWS_ACCESS_KEY_ID=your_access_key
    AWS_SECRET_ACCESS_KEY=your_secret_key
    AWS_DEFAULT_REGION=ap-northeast-1
    AWS_BUCKET=your-bucket-name

### 4-7. CORS 設定

    CORS_ALLOWED_ORIGINS="https://booking.bmm-hotel.com,https://admin.bmm-hotel.com"

---

## 5. データベースのセットアップ

### 5-1. MySQL でデータベースを作成

    CREATE DATABASE hotel_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER 'hotel_user'@'localhost' IDENTIFIED BY 'your_password';
    GRANT ALL PRIVILEGES ON hotel_booking.* TO 'hotel_user'@'localhost';
    FLUSH PRIVILEGES;

### 5-2. マイグレーションの実行

    php artisan migrate

### 5-3. マイグレーションのロールバック（必要な場合）

    php artisan migrate:rollback
    php artisan migrate:fresh --seed

---

## 6. JWT シークレットの生成

    php artisan jwt:secret

このコマンドで `.env` の `JWT_SECRET` が自動設定されます。

---

## 7. 初期データの投入

### 7-1. 全シーダーの実行

    php artisan db:seed

投入されるデータ:

- 管理者ロール（SUPER_ADMIN / FACILITY_ADMIN / FRONT_STAFF / READONLY）
- テスト施設（BMM ホテル 本館）
- 管理者ユーザー（superadmin / facility_admin / front_staff）
- 客室タイプ（SINGLE / DOUBLE / TWIN / SUITE）
- 客室（各フロアに複数室）
- キャンセルポリシー
- 宿泊プラン（スタンダード / 朝食付き）
- 在庫レコード（本日〜1年分）

### 7-2. 初期管理者アカウント情報

| ロール | ユーザー名 | パスワード |
|--------|-----------|-----------|
| SUPER_ADMIN | superadmin | Admin@12345! |
| FACILITY_ADMIN | facility_admin | Facility@12345! |
| FRONT_STAFF | front_staff | Front@12345! |

**本番環境では必ずパスワードを変更してください。**

### 7-3. 在庫の追加生成

    php artisan inventory:generate --months=6

---

## 8. キュー・スケジューラの起動

### 8-1. キューワーカー（手動）

    php artisan queue:work redis --sleep=3 --tries=3 --timeout=90

### 8-2. スケジューラー（cron設定）

サーバーの crontab に以下を追加:

    * * * * * cd /path/to/hotel-booking-system && php artisan schedule:run >> /dev/null 2>&1

### 8-3. スケジュール済みコマンド一覧

| コマンド | 実行タイミング | 説明 |
|---------|--------------|------|
| `backup:database` | 毎日 03:00 JST | DBバックアップ |
| `inventory:generate --months=3` | 毎日 02:00 JST | 在庫自動生成 |
| `audit:clean --days=365` | 毎月1日 04:00 | 監査ログクリーンアップ |

---

## 9. テストの実行

### 9-1. テスト用データベースの設定

`.env.testing` を作成:

    APP_ENV=testing
    DB_DATABASE=hotel_booking_test
    CACHE_STORE=array
    QUEUE_CONNECTION=sync
    MAIL_MAILER=array

### 9-2. 全テストの実行

    php artisan test

### 9-3. 特定テストの実行

    php artisan test --filter=CancelFeeServiceTest
    php artisan test --filter=ReservationTest
    php artisan test tests/Feature/Api/AuthTest.php

### 9-4. カバレッジレポートの生成

    php artisan test --coverage --min=70

---

## 10. 本番環境へのデプロイ

### 10-1. 最適化コマンドの実行

    composer install --no-dev --optimize-autoloader
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

### 10-2. マイグレーションの実行

    php artisan migrate --force

### 10-3. キュー・スケジューラの確認

Supervisor などで `queue:work` を常時起動することを推奨します。

    [program:hotel-queue]
    command=php /var/www/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
    autostart=true
    autorestart=true
    user=www-data
    numprocs=2
    redirect_stderr=true
    stdout_logfile=/var/log/hotel-queue.log

### 10-4. セキュリティチェックリスト

- [ ] `APP_DEBUG=false` に設定
- [ ] `APP_ENV=production` に設定
- [ ] JWT シークレットが本番用の値に設定されている
- [ ] データベースのパスワードが強固である
- [ ] HTTPS が有効になっている
- [ ] CORS の許可オリジンが本番ドメインのみに制限されている
- [ ] 初期管理者パスワードが変更されている
- [ ] バックアップの設定が完了している

---

## トラブルシューティング

### Permission エラーが発生する場合

    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache

### キャッシュをクリアする場合

    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

### JWT シークレットが見つからない場合

    php artisan jwt:secret --force