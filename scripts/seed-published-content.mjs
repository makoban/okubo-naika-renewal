import fs from "node:fs";
import path from "node:path";
import vm from "node:vm";
import { fileURLToPath } from "node:url";
import { normalizeContent, publishedDocument } from "./apply-cms-payload.mjs";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const window = {};
vm.runInNewContext(fs.readFileSync(path.join(root, "review-data.js"), "utf8"), { window }, { timeout: 1000 });
const output = path.join(root, "published-content.json");
const previous = fs.existsSync(output) ? JSON.parse(fs.readFileSync(output, "utf8")) : null;
const document = publishedDocument(normalizeContent(window.OKUBO_REVIEW_DEFAULTS), previous);
fs.writeFileSync(output, JSON.stringify(document, null, 2) + "\n");
console.log(`Published document prepared: ${document.revision}`);
