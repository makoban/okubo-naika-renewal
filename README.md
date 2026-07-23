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
- `assets/hero-clinic-official-characters.webp`
- `assets/topic-board-frame.webp`
- `assets/original/`
- `assets/photo-*.jpg`
- `assets/illust-*.png`
- `assets/explain-*.png`
- `assets/previous-illust/`

## 生成素材

現在のTOPは、原本外観写真の構図をもとにした生成イラスト `assets/hero-clinic-official-characters.webp` を使用しています。ヒーロー内のキャラクターは、支給された公式キャラクター3体（医師・女性医師・看護師）のデザインに統一します。今後、新しいページや画像を作る場合も、別デザインのうさぎを追加せず、この3体を基準にしてください。トピックスはTOP直下に移動し、gpt-image2生成の案内板素材 `assets/topic-board-frame.webp` を背景に、本文はHTML文字として載せています。

院内や設備は現行サイトの原本写真を保存した上で、画面表示用には軽量化したWebP画像 `assets/illust-*.webp` を使用しています。PNG・JPEGは編集原本として残し、公開ページからは参照しません。追加素材の外観は `assets/illust-clinic-exterior-client.webp`、大久保先生の診察室イラストは `assets/illust-doctor-okubo.webp`、医師紹介の肖像は輪郭線と淡彩表現を明確にした `assets/illust-doctor-okubo-portrait-drawn.webp` を使用します。元写真は `assets/original/`、`assets/photo-*.jpg`、`assets/client-202607/` に残し、以前の簡易加工版は `assets/previous-illust/` に退避しています。

内視鏡検査、ピロリ菌、院内緊急検査、診療報酬の難しい説明には、同じタッチのgpt-image2生成図解 `assets/explain-*.webp` を使用しています。画像内の文字には頼らず、説明文はHTML文字として残しています。ファーストビュー以外の画像には遅延読み込みを設定し、スマートフォンの初期通信量を抑えています。

`assets/original-logo.png` は現行サイトのロゴです。元ロゴのうさぎを削除しない前提で、ヘッダーと本文内にそのまま配置しています。

TOPのうさぎは支給された公式キャラクター3体に統一しています。各項目の主役はキャラクターではなく、院内や設備を明るく見せる生成イラストカードです。

## 素材生成プロンプト

### マスコット

今後のマスコット生成では、必ず `assets/client-202607/official-characters.png` を参照画像として使用し、医師・女性医師・看護師の3体の顔、眼鏡、リボン、帽子、服装、配色を維持します。新規デザインのうさぎは作りません。

### ヒーロー背景

`Using the supplied official three-character rabbit reference without redesigning their faces, glasses, bows, nurse cap, clothing, or colors, create a bright pastel Japanese neighborhood clinic exterior illustration. Keep all three official characters together in the right foreground, small enough not to obscure the clinic. Salmon-pink clinic building, white entrance columns and canopy, cream wing, clean parking lot, blue sky, warm morning light, refined hand-drawn clinic illustration, wide 16:9 composition, clear negative space on the left for HTML text, no added text or logos.`

### 現行採用TOPイラスト

`Model: gpt-image2. Use the supplied clinic exterior and official character sheet as references. Preserve the official three characters exactly: the male doctor with large square glasses and orange stethoscope, the female doctor with the pink polka-dot bow, and the nurse with the blue checked bow and blue cap. Place all three together in the right foreground. Recreate the salmon-pink clinic building, white entrance columns and canopy, pale cream wing, parking lot, blue sky, and small green lawn in the same warm pastel hand-drawn style used across the site. Composition: wide 16:9 with light negative space on the left for HTML text. Do not add, remove, or redesign characters. No added text, logos, checkerboard, or transparent background.`

### トピックス案内板

`Model: gpt-image2. Create a blank cheerful but polished clinic notice board frame for a Japanese medical website. Style: slightly POP but adult, warm white paper center, mint-green rounded board frame, coral and yellow small tabs, subtle pin details, clean vector-like illustration, no text, no logos, no characters, no transparent checkerboard. Composition: wide 16:9 web section background, plenty of blank center area for HTML news text overlay, refined and modern.`

### ボタン枠

`A single cute glossy pastel pill-shaped web button asset, blank center, mint border, coral shadow, small hearts and stars around edges, Japanese pop clinic style, no text, clean PNG-like UI element`

### 本文カード枠

`A vertical pastel pop medical webpage card frame asset, blank white center, mint scalloped border, peach and yellow accents, tiny hearts, flowers, stars, friendly Japanese clinic style, no text`

### 絵文字風アイコン

`Cute medical emoji sticker sheet for a Japanese clinic website: phone, clock, map pin, stomach, endoscope, heart, calendar, check mark, sparkling stars, pastel mint coral yellow, no text, isolated icons on white`
