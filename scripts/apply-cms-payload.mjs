import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const markerPattern = /<!-- OKUBO_CMS_DATA_BEGIN -->([\s\S]*?)<!-- OKUBO_CMS_DATA_END -->/;
const allowedLinks = new Set([
  "",
  "./index.html",
  "./guide.html#hours",
  "./access.html",
  "./doctor.html",
  "./guide.html",
  "./emergency.html",
  "./endoscopy.html",
  "./pylori.html",
]);
const allowedStatuses = new Set(["show", "hide"]);
const allowedTones = new Set(["normal", "important", "soft"]);
const allowedFonts = new Set(["gothic", "mincho"]);

function readText(value, maxLength) {
  const text = String(value ?? "").replaceAll("\u0000", "");
  if (text.length > maxLength) throw new Error(`文字数が上限（${maxLength}文字）を超えています。`);
  return text;
}

function readChoice(value, choices, fallback) {
  const text = String(value ?? fallback);
  return choices.has(text) ? text : fallback;
}

function normalizeId(value, prefix, index) {
  const id = String(value ?? "").replace(/[^A-Za-z0-9_-]/g, "").slice(0, 100);
  return id || `${prefix}-${index + 1}`;
}

function decodeIssueBody(body) {
  const match = body.match(markerPattern);
  if (!match) throw new Error("本番反映用データが見つかりません。");

  const encoded = match[1].replace(/\s+/g, "");
  if (!encoded || encoded.length > 200000 || !/^[A-Za-z0-9+/=]+$/.test(encoded)) {
    throw new Error("本番反映用データの形式が正しくありません。");
  }

  const payload = JSON.parse(Buffer.from(encoded, "base64").toString("utf8"));
  if (payload.version !== 1 || !Array.isArray(payload.topics) || !Array.isArray(payload.fees)) {
    throw new Error("本番反映用データのバージョンまたは項目構成が正しくありません。");
  }
  if (payload.topics.length > 20 || payload.fees.length > 30) {
    throw new Error("登録できる項目数を超えています。");
  }

  return {
    topics: payload.topics.map((topic, index) => ({
      id: normalizeId(topic.id, "topic", index),
      status: readChoice(topic.status, allowedStatuses, "show"),
      date: readText(topic.date, 40),
      label: readText(topic.label, 60),
      title: readText(topic.title, 160),
      body: readText(topic.body, 4000),
      link: readChoice(topic.link, allowedLinks, ""),
      linkText: readText(topic.linkText, 100),
      tone: readChoice(topic.tone, allowedTones, "normal"),
      font: readChoice(topic.font, allowedFonts, "gothic"),
    })),
    fees: payload.fees.map((fee, index) => ({
      id: normalizeId(fee.id, "fee", index),
      status: readChoice(fee.status, allowedStatuses, "show"),
      title: readText(fee.title, 200),
      body: readText(fee.body, 12000),
    })),
  };
}

const issueBodyPath = process.argv[2];
const checkOnly = process.argv.includes("--check");
if (!issueBodyPath) throw new Error("Issue本文ファイルを指定してください。");

const normalized = decodeIssueBody(fs.readFileSync(issueBodyPath, "utf8"));
if (checkOnly) {
  console.log(`CMS payload OK: topics=${normalized.topics.length}, fees=${normalized.fees.length}`);
  process.exit(0);
}

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const repositoryRoot = path.resolve(scriptDirectory, "..");
const output = `window.OKUBO_REVIEW_DEFAULTS = ${JSON.stringify(normalized, null, 2)};\n\nwindow.OKUBO_TOPICS = window.OKUBO_REVIEW_DEFAULTS.topics;\n`;

fs.writeFileSync(path.join(repositoryRoot, "review-data.js"), output, "utf8");
fs.writeFileSync(path.join(repositoryRoot, "review", "review-data.js"), output, "utf8");
console.log(`CMS content updated: topics=${normalized.topics.length}, fees=${normalized.fees.length}`);
