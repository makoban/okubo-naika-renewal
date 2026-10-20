import assert from "node:assert/strict";
import { spawnSync } from "node:child_process";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import test from "node:test";
import vm from "node:vm";
import { fileURLToPath } from "node:url";
import { decodeIssueBody, normalizeContent, publishedDocument } from "../scripts/apply-cms-payload.mjs";

const repositoryRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");

function plain(value) {
  return JSON.parse(JSON.stringify(value));
}

function loadPublicStore(relativePath) {
  const published = {
    topics: [{ id: "published-topic", status: "show", title: "公開中のお知らせ" }],
    fees: [{ id: "published-fee", status: "show", title: "公開中の案内" }],
  };
  const localDraft = {
    topics: [{ id: "draft-topic", status: "show", title: "ブラウザ下書き" }],
    fees: [{ id: "draft-fee", status: "show", title: "ブラウザ下書き" }],
  };
  const storageCalls = [];
  const localStorage = {
    getItem(key) {
      storageCalls.push(["getItem", key]);
      return JSON.stringify(localDraft);
    },
    setItem(key, value) {
      storageCalls.push(["setItem", key, value]);
    },
    removeItem(key) {
      storageCalls.push(["removeItem", key]);
    },
  };
  const window = {
    OKUBO_REVIEW_DEFAULTS: plain(published),
    localStorage,
    crypto: { randomUUID: () => "test-uuid" },
  };
  const context = vm.createContext({
    console,
    document: { querySelector: () => null },
    window,
  });
  const filename = path.join(repositoryRoot, relativePath);
  vm.runInContext(fs.readFileSync(filename, "utf8"), context, { filename });

  return { localDraft, published, storageCalls, window };
}

for (const relativePath of ["review-store.js", "review/review-store.js"]) {
  test(`${relativePath} ignores same-origin localStorage drafts`, () => {
    const { localDraft, published, storageCalls, window } = loadPublicStore(relativePath);
    const store = window.OKUBO_REVIEW_STORE;

    assert.deepEqual(plain(window.OKUBO_TOPICS), published.topics);
    assert.notDeepEqual(plain(window.OKUBO_TOPICS), localDraft.topics);
    assert.equal(store.readonly, true);
    assert.equal(store.key, null);
    assert.deepEqual(plain(store.get()), published);
    assert.deepEqual(plain(store.save(localDraft)), published);
    assert.deepEqual(plain(store.reset()), published);
    assert.deepEqual(storageCalls, []);
  });
}

test("CMS payload generation preserves the published source marker", (t) => {
  const temporaryRoot = fs.mkdtempSync(path.join(os.tmpdir(), "okubo-cms-regression-"));
  t.after(() => fs.rmSync(temporaryRoot, { recursive: true, force: true }));

  fs.mkdirSync(path.join(temporaryRoot, "scripts"), { recursive: true });
  fs.mkdirSync(path.join(temporaryRoot, "review"), { recursive: true });
  fs.copyFileSync(
    path.join(repositoryRoot, "scripts", "apply-cms-payload.mjs"),
    path.join(temporaryRoot, "scripts", "apply-cms-payload.mjs")
  );
  const previous = JSON.parse(fs.readFileSync(path.join(repositoryRoot, "published-content.json"), "utf8"));
  fs.writeFileSync(path.join(temporaryRoot, "published-content.json"), JSON.stringify(previous));

  const payload = {
    version: 2,
    baseRevision: previous.revision,
    topics: [
      {
        id: "topic-test",
        status: "show",
        date: "2026.08.06",
        label: "お知らせ",
        title: "公開テスト",
        body: "公開データ生成の回帰テストです。",
        link: "",
        linkText: "",
        tone: "normal",
        font: "gothic",
      },
    ],
    fees: [{ id: "fee-test", status: "show", title: "テスト項目", body: "テスト本文" }],
  };
  const encoded = Buffer.from(JSON.stringify(payload), "utf8").toString("base64");
  const issueBodyPath = path.join(temporaryRoot, "issue-body.txt");
  fs.writeFileSync(
    issueBodyPath,
    `<!-- OKUBO_CMS_DATA_BEGIN -->\n${encoded}\n<!-- OKUBO_CMS_DATA_END -->`,
    "utf8"
  );

  const result = spawnSync(
    process.execPath,
    [path.join(temporaryRoot, "scripts", "apply-cms-payload.mjs"), issueBodyPath],
    { cwd: temporaryRoot, encoding: "utf8" }
  );
  assert.ifError(result.error);
  assert.equal(result.status, 0, result.stderr || result.stdout);

  const rootOutput = fs.readFileSync(path.join(temporaryRoot, "review-data.js"), "utf8");
  const reviewOutput = fs.readFileSync(path.join(temporaryRoot, "review", "review-data.js"), "utf8");
  assert.equal(reviewOutput, rootOutput);
  assert.match(rootOutput, /window\.OKUBO_REVIEW_SOURCE\s*=\s*["']published["'];/);

  const generatedWindow = {};
  vm.runInNewContext(rootOutput, { window: generatedWindow });
  assert.equal(generatedWindow.OKUBO_REVIEW_SOURCE, "published");
  assert.deepEqual(plain(generatedWindow.OKUBO_REVIEW_DEFAULTS), {
    topics: payload.topics,
    fees: payload.fees,
  });
  const published = JSON.parse(fs.readFileSync(path.join(temporaryRoot, "published-content.json"), "utf8"));
  assert.deepEqual(published.content, plain(generatedWindow.OKUBO_REVIEW_DEFAULTS));
  assert.match(published.revision, /^[a-f0-9]{64}$/);
});

function issueBody(payload) {
  return `<!-- OKUBO_CMS_DATA_BEGIN -->${Buffer.from(JSON.stringify(payload)).toString("base64")}<!-- OKUBO_CMS_DATA_END -->`;
}

test("an older editing session cannot replace a newer publication", () => {
  const current = "a".repeat(64);
  const stale = { version: 2, baseRevision: "b".repeat(64), topics: [], fees: [] };
  assert.throws(() => decodeIssueBody(issueBody(stale), current), /編集中に公開内容が更新/);
  assert.throws(() => decodeIssueBody(issueBody({ version: 1, topics: [], fees: [] }), current), /編集画面が古い/);
  assert.deepEqual(decodeIssueBody(issueBody({ ...stale, baseRevision: current }), current), { topics: [], fees: [] });
});

test("the same content keeps its publication revision and time", () => {
  const initial = publishedDocument({ topics: [], fees: [] });
  initial.publishedAt = "2026-10-01T00:00:00.000Z";
  assert.deepEqual(publishedDocument(initial.content, initial), initial);
});

test("unsafe links are removed and oversized content is rejected", () => {
  const content = normalizeContent({ topics: [{ id: "x", title: "Text", link: "javascript:alert(1)" }], fees: [] });
  assert.equal(content.topics[0].link, "");
  assert.throws(() => normalizeContent({ topics: [{ title: "x".repeat(161) }], fees: [] }), /文字数/);
  assert.throws(() => normalizeContent({ topics: Array(21).fill({}), fees: [] }), /項目数/);
});
