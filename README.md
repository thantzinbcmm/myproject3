# README.md — Hotel Booking System

## 概要

**Hotel Booking System** は BMM Hotel 向けに開発されたホテル予約管理システムです。  
Laravel 11 をベースとした REST API バックエンドとして実装されており、多チャンネル予約管理、リアルタイム在庫管理、多言語対応（6言語）を提供します。

---

## 主要機能

### ゲスト向け機能
- 客室・プラン検索（空室カレンダー対応）
- オンライン予約作成・確認・変更・キャンセル
- 予約照会（予約番号 + メールアドレス認証）
- 会員登録・ログイン・予約履歴確認

### 管理者向け機能
- 予約管理（一覧・詳細・作成・キャンセル・チェックイン/アウト）
- グループ予約管理
- リアルタイム在庫管理（カレンダー表示・売止設定）
- プラン・料金管理（多言語対応・期間別料金設定）
- 施設・客室管理
- 会員管理・個人情報削除（GDPR対応）
- 管理者ユーザー・ロール管理
- 売上レポート・ダッシュボード

### システム機能
- 多チャンネル対応（DIRECT / PHONE / RAKUTEN / JALAN / AGENCY / CORPORATE）
- 多言語対応（日本語 / 英語 / 中国語簡体字・繁体字 / 韓国語 / ミャンマー語）
- JWT 認証（ゲスト会員・管理者で別ガード）
- 在庫楽観的ロック（同時予約競合防止）
- キャンセルポリシー自動計算
- 監査ログ自動記録
- 日次データベースバックアップ
- レート制限・セキュリティヘッダー適用

---

## 技術スタック

| カテゴリ | 技術 |
|---------|------|
| フレームワーク | Laravel 11 |
| 言語 | PHP 8.2 |
| データベース | MySQL 8.0 |
| キャッシュ/キュー | Redis 7 |
| 認証 | JWT（tymon/jwt-auth） |
| メール | Laravel Notification（ShouldQueue） |
| テスト | PestPHP / PHPUnit |
| コンテナ | Docker / Docker Compose |
| Webサーバー | Nginx |

---

## システム要件

- PHP 8.2+
- MySQL 8.0+
- Redis 7.0+
- Composer 2.x

詳細なセットアップ手順は `SETUP.md` を参照してください。

---

## ディレクトリ構成

    hotel-booking-system/
    ├── app/
    │   ├── Console/
    │   │   └── Commands/           # Artisanコマンド
    │   │       ├── BackupDatabase.php
    │   │       ├── CleanAuditLogs.php
    │   │       └── GenerateMonthlyInventory.php
    │   ├── Exceptions/             # 例外クラス
    │   │   ├── BusinessException.php
    │   │   ├── Handler.php
    │   │   └── InventoryConflictException.php
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── Api/            # ゲスト向けAPI
    │   │   │   └── Api/Admin/      # 管理者向けAPI
    │   │   ├── Middleware/         # ミドルウェア
    │   │   └── Requests/           # バリデーション
    │   ├── Models/                 # Eloquentモデル
    │   ├── Notifications/          # メール通知
    │   └── Services/               # ビジネスロジック
    ├── config/
    │   ├── auth.php
    │   ├── hotel.php               # ホテルシステム設定
    │   └── jwt.php
    ├── database/
    │   ├── migrations/             # DBマイグレーション
    │   └── seeders/                # 初期データ
    ├── docker/                     # Docker設定
    │   ├── Dockerfile
    │   ├── nginx/
    │   ├── php/
    │   └── mysql/
    ├── routes/
    │   ├── api.php                 # APIルート定義
    │   └── console.php             # スケジュールタスク
    └── tests/
        ├── Feature/                # 機能テスト
        └── Unit/                   # ユニットテスト

---

## API エンドポイント一覧

### 認証

    POST   /api/v1/auth/login              ゲストログイン
    POST   /api/v1/auth/admin/login        管理者ログイン
    POST   /api/v1/auth/refresh            トークン更新
    POST   /api/v1/auth/logout             ログアウト

### 施設・客室

    GET    /api/v1/facilities                               施設一覧
    GET    /api/v1/facilities/{id}                          施設詳細
    GET    /api/v1/facilities/{id}/rooms/search             空室検索
    GET    /api/v1/facilities/{id}/inventory/calendar       在庫カレンダー

### 予約（ゲスト）

    POST   /api/v1/reservations                             予約作成
    GET    /api/v1/reservations/{no}?email=xxx              予約照会
    PUT    /api/v1/reservations/{id}/cancel                 予約キャンセル
    PUT    /api/v1/reservations/{id}/change                 予約変更

### 会員

    POST   /api/v1/members/register                         会員登録
    GET    /api/v1/members/me                               会員情報
    GET    /api/v1/members/me/reservations                  予約履歴

### 管理者（要認証）

    GET    /api/v1/admin/reservations                       予約一覧
    POST   /api/v1/admin/reservations                       予約作成（管理者）
    GET    /api/v1/admin/reservations/{id}                  予約詳細
    PUT    /api/v1/admin/reservations/{id}/cancel           キャンセル
    PUT    /api/v1/admin/reservations/{id}/checkin          チェックイン
    PUT    /api/v1/admin/reservations/{id}/checkout         チェックアウト
    PUT    /api/v1/admin/inventory                          在庫更新
    GET    /api/v1/admin/inventory/calendar                 在庫カレンダー
    GET    /api/v1/admin/plans                              プラン一覧
    POST   /api/v1/admin/plans                              プラン作成
    PUT    /api/v1/admin/plans/{id}/prices                  料金設定
    GET    /api/v1/admin/facilities                         施設一覧（管理）
    POST   /api/v1/admin/facilities                         施設作成
    GET    /api/v1/admin/rooms                              客室一覧
    POST   /api/v1/admin/rooms                              客室作成
    GET    /api/v1/admin/members                            会員一覧
    DELETE /api/v1/admin/members/{id}/anonymize             個人情報削除
    POST   /api/v1/admin/group-reservations                 グループ予約作成
    GET    /api/v1/admin/reports/dashboard                  ダッシュボード
    GET    /api/v1/admin/reports/revenue                    売上レポート

---

## キャンセルポリシー

| 条件 | キャンセル料 |
|------|------------|
| チェックイン5日前以前 | 無料（0%） |
| チェックイン前日 | 宿泊料金の50% |
| チェックイン当日 | 宿泊料金の100% |
| ノーショー | 宿泊料金の100% |

---

## 管理者ロール権限

| リソース | SUPER_ADMIN | FACILITY_ADMIN | FRONT_STAFF | READONLY |
|---------|------------|----------------|-------------|---------|
| 施設管理 | CRUD | R | - | R |
| 客室管理 | CRUD | CRUD | R | R |
| プラン管理 | CRUD | CRUD | R | R |
| 予約管理 | CRUD | CRUD | CRUD | R |
| 在庫管理 | CRUD | CRUD | R | R |
| 会員管理 | CRUD | RU | R | R |
| 管理者管理 | CRUD | - | - | - |
| レポート | R | R | - | R |

---

## セキュリティ

- JWT 認証（ゲスト：2時間 / 管理者：8時間）
- bcrypt パスワードハッシュ（コスト係数12）
- ログイン試行回数ロック（会員：5回/30分 / 管理者：3回/60分）
- レート制限（予約：10回/分 / ログイン：5回/分）
- セキュリティヘッダー自動付与（X-Content-Type-Options 等）
- 個人情報匿名化（忘れられる権利）
- 監査ログ自動記録
- 楽観的ロックによる在庫競合防止

---

## ライセンス

This project is proprietary software for BMM Hotel.  
© 2025 BMM Hotel. All rights reserved.