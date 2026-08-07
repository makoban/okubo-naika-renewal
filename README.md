# おおくぼ内科クリニック リニューアル案

現行サイト `http://www.okubo-naika-cl.com/` のトップページ内容を大きく変えず、デザインだけを最近の医院サイト寄りに整えた静的プロトタイプです。

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
