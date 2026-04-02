# mare-di-sakai-tanaoroshi-app

棚卸の実数を手入力で記録し、差異確認から確定まで行う PHP + MySQL アプリです。

## 方式
- `DDD` 寄りの構成で、エンティティと値オブジェクトにビジネスロジックを持たせています。
- ログインは `Cookie` による長期ログインに対応しています。
- UI はモバイルファーストで、`shadcn/ui` 風のカード中心デザインです。

## セットアップ
1. MySQL に `init.sql` を流し込みます。
2. `config/app.php` の DB 接続情報を環境に合わせて変更します。
3. ブラウザで `index.php` を開きます。

## 初期アカウント
- Email: `admin@example.com`
- Password: `admin1234`

## 主要画面
- Dashboard: 棚卸の全体状況
- Items: 商品マスタ管理
- Sessions: 棚卸セッション作成と数量入力
- Users: ログインユーザー管理

## `InfinityFree` への配置
- ルートに `index.php`, `src/`, `assets/`, `config/`, `init.sql` を配置します。
- `init.sql` は MySQL の初期化用です。
- `config/app.php` を本番の DB 接続先に合わせて更新します。
