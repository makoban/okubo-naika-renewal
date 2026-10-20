import fs from "node:fs";
import { setTimeout } from "node:timers/promises";

const expected = JSON.parse(fs.readFileSync(new URL("../published-content.json", import.meta.url), "utf8"));
const endpoint = "https://www.okubo-naika-cl.com/published-content.php?format=json";
const attempts = process.argv.includes("--once") ? 1 : 36;
for (let attempt = 1; attempt <= attempts; attempt += 1) {
  try {
    const response = await fetch(endpoint, { headers: { "Cache-Control": "no-cache" }, signal: AbortSignal.timeout(10000) });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const actual = await response.json();
    if (actual.revision === expected.revision && JSON.stringify(actual.content) === JSON.stringify(expected.content)) {
      console.log(`Production content verified: ${actual.revision}`);
      process.exit(0);
    }
    console.log(`本番反映待ち (${attempt}/${attempts})`);
  } catch (error) {
    console.log(`本番確認待ち (${attempt}/${attempts}): ${error.message}`);
  }
  if (attempt < attempts) await setTimeout(10000);
}
throw new Error("本番で公開データの一致を確認できませんでした。公開完了にはしていません。");
