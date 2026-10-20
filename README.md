# おおくぼ内科クリニック

医院サイトの通常版とデザイン比較用プレビューを管理します。通常版の正式公開先は `https://www.okubo-naika-cl.com/` です。

## さくらへの正式公開（2026-10-16）

- 通常版7ページを、さくらのレンタルサーバ スタンダードへ配置。
- 初期ドメイン: `bantex-okubo.sakura.ne.jp`、サーバー: `www2227.sakura.ne.jp`、公開フォルダー: `/home/bantex-okubo/www/`。
- DNS: `ns1.dns.ne.jp` / `ns2.dns.ne.jp`、A: `182.48.49.167`、wwwはapexのCNAME。
- 無料Let's Encrypt証明書を導入。HTTP・wwwなし・index.htmlはHTTPSのwww付きルートへ301転送。
- 旧URLは `original.html`→`access.html`、`original2.html`→`doctor.html`、`original3.html`→`guide.html`、`original7.html`→`emergency.html`、`original8.html`→`endoscopy.html`、`original9.html`→`pylori.html` へ301転送。
- 初期ドメインは `X-Robots-Tag: noindex, nofollow`。GitHub Pagesは引き続き確認用で、ソースHTMLのnoindexを保持。
- 医院ドメインのメール利用なし。旧サービスの停止日・最終請求は未確認。

2026-10-16の切替直後は、この端末のDNSに旧IPが残っていたため最終確認を保留しました。2026-10-20、通常回線の正式URL `https://www.okubo-naika-cl.com/` で新HPのブラウザ表示とHTTPSを確認し、DNS反映待ちが解消したことを確認しました。IPの強制指定なしで全42公開ファイルのSHA-256一致（.htaccessの403も別途確認）、全7ページと10転送条件を確認済みです。HP公開の技術的な移転は完了しています。旧サービスの停止・最終請求の契約確認は別途必要です。

本番用パッケージを次で作成します。原本画像、別案、確認ページ、メール資料は含めません。

```bash
python3 scripts/build-sakura-package.py --production --output output/sakura-production-20261016
```

生成ZIPをさくらのファイルマネージャーで公開フォルダーへアップロードし、その場で解凍します。パッケージにはcanonical・robots.txt・sitemap.xml・転送用.htaccessを含み、公開フォルダーに置いたZIPへのHTTPアクセスは拒否します。新しいドメインで同様に移転する場合は、HTTPS証明書が有効になる前に接続先を切り替えないでください。パスワード等をこのリポジトリへ保存しません。

## 医院用の更新画面（2026-10-20）

更新URLは https://www.okubo-naika-cl.com/admin/ です。医院用ID・パスワードでログインし、編集 → プレビュー →「本番HPに公開する」→ 確認でOK、で直接公開できます。GitHubの操作・手動アップロード・PCの常時起動は不要です。以前のGitHub Pages編集URLも、この画面への案内へ変更済みです。編集範囲はTOPのトピックスと診療報酬のお知らせです。

公開データの正本は、さくらの公開フォルダー外 `/home/bantex-okubo/.okubo-cms-state/published.json` です。認証済み・CSRF検証済みのPOSTだけを受け付け、件数・文字数・リンク先・項目形式を検証して、ロックと原子的な置き換えで保存します。古い公開revisionからの上書きは拒否します。更新前の公開データを同じ非公開領域のbackupsへ保存し、audit.jsonlには時刻・ユーザー名・revisionだけを記録します。GitHub JSONの自動取得とIssue公開ワークフローは廃止しました。再配置時も非公開の公開データは上書きしません。

ログインはbcrypt照合、Secure/HttpOnly/SameSite=StrictのセッションCookie、30分の無操作期限・8時間の最長期限、IP別の連続失敗制限を使用します。Referrer-Policyはsame-originとし、フォームPOSTのOrigin検証と両立させます。認証の初期値はローカル生成し、秘密庫 `projects/okubo-cms` の `OKUBO_CMS_USER` / `OKUBO_CMS_PASSWORD` で保管します。サーバーには照合用ハッシュのみを保存し、初期配置用PHPは初期化後に削除します。認証設定・データ・バックアップはWebから取得できません。

下書きは `okuboClinicCustomerDraftV1` として医院側の同じブラウザ内だけに保存します。プレビューは本番と同じHTML・画像・スタイルを使い、公開ページは下書きを読みません。API保存後には公開データのrevisionを読み直し、一致した場合だけ画面に公開完了と表示します。使い方はログイン後の「使い方」で確認できます。

CMSのNodeテストは `node --test tests/*.test.mjs`、PHPの検証・保存・バックアップ・競合テストは `php tests/published-content-test.php`。GitHub Actionsの `Test CMS publishing` ではPHPのHTTPサーバーも起動し、ログイン・Cookie・未認証/CSRF/外部Origin/古いrevisionの拒否・直接公開・ログアウト後の拒否を検証します。

2026-10-20、本番のChromeでログイン・下書きだけのプレビュー・公開ボタンからの直接反映・元データへの復元を確認しました。公開データは試験前と全項目一致しています。ログイン、2編集タブ、使い方、2プレビューを320・360・375・390・393・412・430・1440pxの48条件で確認し、横はみ出し・主要要素の親枠からのはみ出しはありません。46静的配信ファイルのSHA-256一致、未認証の編集302/公開401、初期設定・テンプレート・設置ZIPの403も確認済みです。

## 確認ファイル

通常案はリポジトリ直下の `index.html` から確認できます。さらにPOP感を強めた別デザイン案は `pop/index.html` から確認できます。3つ目の可愛い案は `cute/index.html` から確認できます。`pop/` と `cute/` は本文・写真イラスト・キャラクターを同じまま、枠、ボタン、カード、背景パターン、フォントの表現だけを変えた比較案です。

- `index.html`
- `access.html`
- `doctor.html`
- `guide.html`
- `emergency.html`
- `endoscopy.html`
- `pylori.html`
- `styles.css`
- `script.js`
- `topics-data.js`
- `TOPICS_EDITING.md`
- `pop/`
- `cute/`
- `assets/original-logo.png`
- `assets/hero-clinic-staff-pair.webp`
- `assets/staff-rabbit-pair.webp`
- `assets/topic-board-frame.webp`
- `assets/original/`
- `assets/photo-*.jpg`
- `assets/illust-*.png`
- `assets/explain-*.png`
- `assets/previous-illust/`

## 生成素材

現在のTOPは、原本外観写真の構図をもとにした生成イラスト `assets/hero-clinic-staff-pair.webp` を使用しています。ヒーロー内のキャラクターは、眼鏡の医師うさぎとピンクの看護師服を着た女性うさぎの2体に統一しています。青い看護師うさぎは公開ページで使用しません。トピックスはTOP直下に移動し、gpt-image2生成の案内板素材 `assets/topic-board-frame.webp` を背景に、本文はHTML文字として載せています。

院内や設備は現行サイトの原本写真を保存した上で、画面表示用には軽量化したWebP画像 `assets/illust-*.webp` を使用しています。PNG・JPEGは編集原本として残し、公開ページからは参照しません。追加素材の外観は `assets/illust-clinic-exterior-client.webp`、TOPの「医師の紹介」カードと医師紹介ページには白衣姿の大久保先生イラスト `assets/illust-doctor-okubo.webp` を使用します。元写真は `assets/original/`、`assets/photo-*.jpg`、`assets/client-202607/` に残し、以前の簡易加工版は `assets/previous-illust/` に退避しています。

院内設備の追加修正版は、実機の形を保ちながら他設備と同じ明るいパステル調に合わせた洗浄機 `assets/illust-washer-pastel.webp`、左側の扉を3枚にした廊下 `assets/illust-corridor-three-doors.webp`、階段や段差をなくした入口 `assets/illust-entrance-level.webp` を使用します。「処置室」の表示名は設備内容に合わせて「エコー」に変更しています。

内視鏡検査、ピロリ菌、院内緊急検査、診療報酬の難しい説明には、同じタッチのgpt-image2生成図解 `assets/explain-*.webp` を使用しています。画像内の文字には頼らず、説明文はHTML文字として残しています。ファーストビュー以外の画像には遅延読み込みを設定し、スマートフォンの初期通信量を抑えています。

`assets/original-logo.png` は現行サイトのロゴです。元ロゴのうさぎを削除しない前提で、ヘッダーと本文内にそのまま配置しています。

TOPと医師紹介のうさぎは、眼鏡の医師うさぎとピンクの看護師服を着た女性うさぎの2体に統一しています。各項目の主役はキャラクターではなく、院内や設備を明るく見せる生成イラストカードです。

## 素材生成プロンプト

### マスコット

今後のマスコット生成では、必ず `assets/client-202607/official-characters.png` を参照画像として使用します。採用するのは眼鏡の医師うさぎと、ピンクの水玉リボンを残したまま看護師服・帽子を淡いピンクに変更した女性うさぎの2体です。青い看護師うさぎと新規デザインのうさぎは公開ページで使用しません。

### ヒーロー背景

`Using the supplied official rabbit reference, create a bright pastel Japanese neighborhood clinic exterior illustration. Keep exactly two characters together in the right foreground: the male doctor rabbit with square glasses and the pink-bow female rabbit wearing a soft pink nurse dress and cap. Do not include the blue nurse rabbit. Salmon-pink clinic building, white entrance columns and canopy, cream wing, clean parking lot, blue sky, warm morning light, refined hand-drawn clinic illustration, wide 16:9 composition, clear negative space on the left for HTML text, no added text or logos.`

### 現行採用TOPイラスト

`Model: gpt-image2. Use the supplied clinic exterior and official character sheet as references. Preserve the male doctor with large square glasses and orange stethoscope. Use the pink-bow female rabbit as the second character, changing her clothing to a soft pink nurse dress and cap while keeping the bow. Do not include the blue nurse rabbit. Place the two characters together in the right foreground. Recreate the salmon-pink clinic building, white entrance columns and canopy, pale cream wing, parking lot, blue sky, and small green lawn in the same warm pastel hand-drawn style used across the site. Composition: wide 16:9 with light negative space on the left for HTML text. No added text, logos, checkerboard, or transparent background.`

### トピックス案内板

`Model: gpt-image2. Create a blank cheerful but polished clinic notice board frame for a Japanese medical website. Style: slightly POP but adult, warm white paper center, mint-green rounded board frame, coral and yellow small tabs, subtle pin details, clean vector-like illustration, no text, no logos, no characters, no transparent checkerboard. Composition: wide 16:9 web section background, plenty of blank center area for HTML news text overlay, refined and modern.`

### ボタン枠

`A single cute glossy pastel pill-shaped web button asset, blank center, mint border, coral shadow, small hearts and stars around edges, Japanese pop clinic style, no text, clean PNG-like UI element`

### 本文カード枠

`A vertical pastel pop medical webpage card frame asset, blank white center, mint scalloped border, peach and yellow accents, tiny hearts, flowers, stars, friendly Japanese clinic style, no text`

### 絵文字風アイコン

`Cute medical emoji sticker sheet for a Japanese clinic website: phone, clock, map pin, stomach, endoscope, heart, calendar, check mark, sparkling stars, pastel mint coral yellow, no text, isolated icons on white`

## CMS回帰テスト

追加パッケージは不要です。Node.jsの標準テストランナーで実行します。

```bash
node --test tests/*.test.mjs
```

公開ページがブラウザ下書きを読み込まないことと、CMS公開データに公開済みマーカーが生成されることを検査します。
