# おおくぼ内科クリニック リニューアル案

現行サイト `http://www.okubo-naika-cl.com/` のトップページ内容を大きく変えず、デザインだけを最近の医院サイト寄りに整えた静的プロトタイプです。

## 確認ファイル

通常案はリポジトリ直下の `index.html` から確認できます。さらにPOP感を強めた別デザイン案は `pop/index.html` から確認できます。`pop/` は本文・写真イラスト・キャラクターを同じまま、枠、ボタン、カード、背景パターンの表現だけを変えた比較案です。

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
- `assets/original-logo.png`
- `assets/hero-clinic-pop-illustration.png`
- `assets/topic-board-frame.png`
- `assets/original/`
- `assets/photo-*.jpg`
- `assets/illust-*.png`
- `assets/explain-*.png`
- `assets/previous-illust/`

## 生成素材

現在のTOPは、原本外観写真の荒さを避けるため、写真の構図をもとにしたPOP寄りの生成イラスト `assets/hero-clinic-pop-illustration.png` を使用しています。トピックスはTOP直下に移動し、gpt-image2生成の案内板素材 `assets/topic-board-frame.png` を背景に、本文はHTML文字として載せています。

院内や設備は現行サイトの原本写真を保存した上で、画面表示用にはgpt-image2でTOPと同じ方向の明るい描き起こしイラスト `assets/illust-*.png` を使用しています。元写真は `assets/original/` と `assets/photo-*.jpg` に残し、以前の簡易加工版は `assets/previous-illust/` に退避しています。

内視鏡検査、ピロリ菌、院内緊急検査、診療報酬の難しい説明には、同じタッチのgpt-image2生成図解 `assets/explain-*.png` を追加しています。画像内の文字には頼らず、説明文はHTML文字として残しています。

`assets/original-logo.png` は現行サイトのロゴです。元ロゴのうさぎを削除しない前提で、ヘッダーと本文内にそのまま配置しています。

TOPの二人のうさぎは、原本ロゴの丸い低等身のタッチに寄せ、女の子はメガネなしで生成しています。各項目の主役はキャラクターではなく、院内や設備を明るく見せる生成イラストカードです。

## 素材生成プロンプト

### マスコット

`A cheerful original clinic mascot: a white rabbit girl wearing round glasses and a small nurse cape, friendly smile, colorful pop Japanese medical website style, teal peach yellow accents, full body, clean vector-like illustration, transparent background feel, no text`

### ヒーロー背景

`A bright pop-style Japanese neighborhood clinic exterior illustration, pastel peach building, teal accents, flowers, leaves, clean friendly medical website hero background, sunny morning, no text, no logos, wide composition`

### 現行採用TOPイラスト

`Model: gpt-image2. Create a wide hero illustration for an adult but slightly pop Japanese internal medicine clinic website. Recreate the clinic exterior from a reference description: a salmon-pink clinic building on the left, white entrance columns and canopy in the center, pale cream building wing on the right, large clean parking lot in front, blue sky, small green lawn. Style: polished flat Japanese clinic illustration, clean vector-like lines, warm pastel colors, tasteful POP accents, not childish, not fantasy, no photorealism, no blur. Add two small rabbit mascots on the right foreground in the same simple low-proportion touch as a clinic logo mascot: round head, small body, long ears, thin black outline, cream-white face. Boy rabbit wears small round glasses and a simple doctor coat. Girl rabbit wears a small nurse-like scarf or teal bow, no glasses. They stand calmly together, friendly but understated. Composition: 16:9, enough light negative space on the left for HTML text overlay. No text, no readable letters, no logos, no signage, no checkerboard, no transparent background.`

### トピックス案内板

`Model: gpt-image2. Create a blank cheerful but polished clinic notice board frame for a Japanese medical website. Style: slightly POP but adult, warm white paper center, mint-green rounded board frame, coral and yellow small tabs, subtle pin details, clean vector-like illustration, no text, no logos, no characters, no transparent checkerboard. Composition: wide 16:9 web section background, plenty of blank center area for HTML news text overlay, refined and modern.`

### ボタン枠

`A single cute glossy pastel pill-shaped web button asset, blank center, mint border, coral shadow, small hearts and stars around edges, Japanese pop clinic style, no text, clean PNG-like UI element`

### 本文カード枠

`A vertical pastel pop medical webpage card frame asset, blank white center, mint scalloped border, peach and yellow accents, tiny hearts, flowers, stars, friendly Japanese clinic style, no text`

### 絵文字風アイコン

`Cute medical emoji sticker sheet for a Japanese clinic website: phone, clock, map pin, stomach, endoscope, heart, calendar, check mark, sparkling stars, pastel mint coral yellow, no text, isolated icons on white`
