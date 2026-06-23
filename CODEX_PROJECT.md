# おおくぼ内科クリニック プロジェクト引き継ぎ

## 現在の作業ルート

このプロジェクトの継続作業ルートは次です。

`/Users/banmako/dev/おおくぼ内科クリニック`

以前の作業ルートは次でした。

`/Users/banmako/dev/動画編集/okubo-renewal`

既存の古い中身は、移行前バックアップとして次に退避しています。

`/Users/banmako/dev/_codex_project_migration/おおくぼ内科クリニック-before-renewal-20260623-160724`

## GitHub

リモートリポジトリ:

`https://github.com/makoban/okubo-naika-renewal.git`

公開プレビュー:

- 通常案: `https://makoban.github.io/okubo-naika-renewal/`
- POP案: `https://makoban.github.io/okubo-naika-renewal/pop/`
- cute案: `https://makoban.github.io/okubo-naika-renewal/cute/`

## 構成

- `index.html` ほか直下のHTML: 通常案
- `pop/`: POPを強めた別案
- `cute/`: 丸フォントとカラフルな柄を強めた案
- `assets/`: gpt-image2生成イラスト、原本写真、現行ロゴ
- `topics-data.js`: TOPのトピックス編集用データ
- `TOPICS_EDITING.md`: トピックス編集方法

## 直近の注意点

- cute案では、ヒーローの柄が文字の上に乗らないよう、`.hero-inner` を `position: relative; z-index: 3;` にしてあります。
- 公開URLの反映確認済みです。
- `.claude/` は移行先ローカル設定として残していますが、GitHubには含めません。

## 確認コマンド

```bash
python3 /Users/banmako/.codex/skills/lp-design-director/scripts/check_lp_quality.py .
python3 -m http.server 4188
```

ローカル確認例:

`http://127.0.0.1:4188/cute/`
