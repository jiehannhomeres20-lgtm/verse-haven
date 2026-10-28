<?php $title = 'Verse Haven'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $title; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body class="app">
<header class="top">
  <a class="logo" href="index.php"><span id="logo"></span><span class="name">Verse Haven</span></a>
  <form class="gofm" id="goForm"><input id="q" placeholder="Go to a passage, e.g. John 3:16 or Psalm 23"><button>Go</button></form>
  <select id="trans" title="Bible version">
    <option value="web">English (World English Bible)</option>
    <option value="kjv">English (King James Version)</option>
    <option value="webbe">English (World English Bible, British)</option>
    <option value="bbe">English (Bible in Basic English)</option>
    <option value="oeb-us">English (Open English Bible)</option>
    <option value="almeida">Portuguese (Almeida)</option>
    <option value="clementine">Latin (Clementine Vulgate)</option>
    <option value="mt:war">Waray-Waray (machine translated)</option>
  </select>
  <button class="tbtn" id="rand">Random verse</button>
  <button class="tbtn" id="dark">Dark</button>
  <span class="who" id="who"></span>
  <button class="tbtn" id="logout">Log out</button>
</header>
<div class="layout">
  <aside class="side">
    <div class="tabs"><button id="tabBooks" class="on">Books</button><button id="tabSaved">Saved 0</button></div>
    <div id="list"></div>
  </aside>
  <main class="main" id="main">
    <article class="page">
      <div class="head">
        <div><h1 id="title"></h1><div class="sub" id="sub"></div><div class="trnote" id="trnote"></div></div>
        <div class="tools"><button id="fm" title="Smaller text">A−</button><button id="fp" title="Bigger text">A+</button><button id="copy">Copy</button></div>
      </div>
      <div class="chaps" id="chaps"></div>
      <div class="text" id="text"></div>
      <div class="pn"><button id="prev">← Previous</button><button id="next">Next →</button></div>
    </article>
  </main>
</div>
<script>
const $ = id => document.getElementById(id);
// Accounts and settings are saved in this browser (no database)
const store = {
  get(k, d) { try { const v = JSON.parse(localStorage.getItem(k)); return v === null || v === undefined ? d : v; } catch (e) { return d; } },
  set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
};
async function hash(text) {
  const b = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(text));
  return [...new Uint8Array(b)].map(x => x.toString(16).padStart(2, "0")).join("");
}

// The small book logo
function logoSvg(size, round) {
  return `<svg width="${size}" height="${size}" viewBox="0 0 40 40" aria-hidden="true">
    <defs><linearGradient id="lg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#b9743a"/><stop offset="1" stop-color="#7a2b21"/></linearGradient></defs>
    ${round ? '<circle cx="20" cy="20" r="18.5" fill="url(#lg)" stroke="#d9b45a" stroke-width="1.5"/>' : '<rect x="1" y="1" width="38" height="38" rx="9" fill="url(#lg)" stroke="#d9b45a" stroke-width="1.5"/>'}
    <path d="M9 12c4-1.5 8-.5 11 2v14c-3-2.5-7-3.5-11-2z" fill="#f6ecd8"/>
    <path d="M31 12c-4-1.5-8-.5-11 2v14c3-2.5 7-3.5 11-2z" fill="#f6ecd8"/></svg>`;
}

const BOOKS = "Genesis:50,Exodus:40,Leviticus:27,Numbers:36,Deuteronomy:34,Joshua:24,Judges:21,Ruth:4,1 Samuel:31,2 Samuel:24,1 Kings:22,2 Kings:25,1 Chronicles:29,2 Chronicles:36,Ezra:10,Nehemiah:13,Esther:10,Job:42,Psalms:150,Proverbs:31,Ecclesiastes:12,Song of Solomon:8,Isaiah:66,Jeremiah:52,Lamentations:5,Ezekiel:48,Daniel:12,Hosea:14,Joel:3,Amos:9,Obadiah:1,Jonah:4,Micah:7,Nahum:3,Habakkuk:3,Zephaniah:3,Haggai:2,Zechariah:14,Malachi:4,Matthew:28,Mark:16,Luke:24,John:21,Acts:28,Romans:16,1 Corinthians:16,2 Corinthians:13,Galatians:6,Ephesians:6,Philippians:4,Colossians:4,1 Thessalonians:5,2 Thessalonians:3,1 Timothy:6,2 Timothy:4,Titus:3,Philemon:1,Hebrews:13,James:5,1 Peter:5,2 Peter:3,1 John:5,2 John:1,3 John:1,Jude:1,Revelation:22"
  .split(",").map(s => { const i = s.lastIndexOf(":"); return [s.slice(0, i), +s.slice(i + 1)]; });
// Book codes for the free Bible site (same order as the book list above)
const CODES = "GEN,EXO,LEV,NUM,DEU,JOS,JDG,RUT,1SA,2SA,1KI,2KI,1CH,2CH,EZR,NEH,EST,JOB,PSA,PRO,ECC,SNG,ISA,JER,LAM,EZK,DAN,HOS,JOL,AMO,OBA,JON,MIC,NAM,HAB,ZEP,HAG,ZEC,MAL,MAT,MRK,LUK,JHN,ACT,ROM,1CO,2CO,GAL,EPH,PHP,COL,1TH,2TH,1TI,2TI,TIT,PHM,HEB,JAS,1PE,2PE,1JN,2JN,3JN,JUD,REV".split(",");
const RANDOM = ["John 3:16","Psalm 23:1-3","Romans 8:28","Proverbs 3:5-6","Isaiah 41:10","Philippians 4:13","Jeremiah 29:11","Matthew 11:28","Joshua 1:9","Psalm 46:1","1 Corinthians 13:4-7","Hebrews 11:1","Genesis 1:1","Galatians 5:22-23","2 Timothy 1:7","Psalm 119:105"];

// Must be logged in
const me = store.get("vh_session", null);
if (!me) location.replace("login.php");
const user = store.get("vh_users", {})[me] || {};
$("who").textContent = user.username || user.name || me;
$("logo").innerHTML = logoSvg(34, false);

let cur = store.get("vh_last", { book: "John", ch: 1 });
let saved = store.get("vh_saved_" + me, []);
let trans = store.get("vh_trans", "web");
let fsize = store.get("vh_fsize3", 14);
let verses = [], tab = "books", seq = 0;

$("trans").value = trans;
$("text").style.fontSize = fsize + "px";
function setDark(on) { document.body.classList.toggle("dark", on); $("dark").textContent = on ? "Light" : "Dark"; store.set("vh_dark", on); }
setDark(store.get("vh_dark", false));

function parseRef(s) {
  const m = s.trim().match(/^((?:[1-3]\s*)?[A-Za-z][A-Za-z ]*?)\s*(\d+)?(?::(\d+)(?:\s*-\s*(\d+))?)?$/);
  if (!m) return null;
  const name = m[1].replace(/\s+/g, " ").trim().toLowerCase().replace(/^([1-3])\s*/, "$1 ");
  const b = BOOKS.find(x => x[0].toLowerCase() === name) || BOOKS.find(x => x[0].toLowerCase().startsWith(name));
  if (!b) return null;
  const v1 = m[3] ? +m[3] : 0;
  return { book: b[0], ch: Math.min(Math.max(+(m[2] || 1), 1), b[1]), v1, v2: m[4] ? +m[4] : v1 };
}

function renderList() {
  const box = $("list"); box.innerHTML = "";
  $("tabBooks").classList.toggle("on", tab === "books");
  $("tabSaved").classList.toggle("on", tab === "saved");
  $("tabSaved").textContent = "Saved " + saved.length;
  if (tab === "books") {
    [["Old Testament", 0, 39], ["New Testament", 39, 66]].forEach(([t, a, b]) => {
      const h = document.createElement("h3"); h.textContent = t; box.append(h);
      BOOKS.slice(a, b).forEach(([n]) => {
        const d = document.createElement("button"); d.className = "bk" + (n === cur.book ? " on" : "");
        d.textContent = n; d.onclick = () => load(n, 1); box.append(d);
      });
    });
  } else if (!saved.length) {
    box.innerHTML = '<p class="empty">Click a verse in the text to save it here.</p>';
  } else {
    saved.forEach((s, i) => {
      const d = document.createElement("div"); d.className = "sv";
      const a = document.createElement("button"); a.className = "svt"; a.innerHTML = "<b></b><span></span>";
      a.children[0].textContent = s.book + " " + s.ch + ":" + s.v; a.children[1].textContent = s.text;
      a.onclick = () => load(s.book, s.ch, s.v, s.v);
      const x = document.createElement("button"); x.className = "x"; x.textContent = "×"; x.title = "Remove";
      x.onclick = () => { saved.splice(i, 1); store.set("vh_saved_" + me, saved); renderList(); markSaved(); };
      d.append(a, x); box.append(d);
    });
  }
}
$("tabBooks").onclick = () => { tab = "books"; renderList(); };
$("tabSaved").onclick = () => { tab = "saved"; renderList(); };

function renderChaps() {
  const n = BOOKS.find(b => b[0] === cur.book)[1], box = $("chaps"); box.innerHTML = "";
  for (let i = 1; i <= n; i++) {
    const b = document.createElement("button"); b.textContent = i; if (i === cur.ch) b.className = "on";
    b.onclick = () => load(cur.book, i); box.append(b);
  }
}

// Get Bible text. First ask our PHP file (bible.php); if that does not work, ask the Bible site directly.
async function fetchBible(ref, v1 = 0, v2 = 0) {
  if (trans.startsWith("ho:")) return fetchFree(ref);
  if (trans.startsWith("mt:")) return fetchMachine(ref, trans.slice(3), v1, v2);
  try {
    const r = await fetch("bible.php?ref=" + encodeURIComponent(ref) + "&t=" + trans);
    if (r.ok) return await r.json();
  } catch (e) {}
  const r = await fetch("https://bible-api.com/" + encodeURIComponent(ref) + "?translation=" + trans);
  return await r.json();
}

// Tagalog and Waray-Waray come from bible.helloao.org (free Bible API)
async function fetchFree(ref) {
  const i = ref.lastIndexOf(" "), book = ref.slice(0, i), ch = ref.slice(i + 1);
  const code = CODES[BOOKS.findIndex(b => b[0] === book)];
  const r = await fetch("https://bible.helloao.org/api/" + trans.slice(3) + "/" + code + "/" + ch + ".simple.json");
  if (!r.ok) return { error: "missing" };
  const d = await r.json();
  const list = ((d.chapter && d.chapter.content) || []).filter(c => c.type === "verse").map(c => ({ verse: c.number, text: c.text || "" }));
  if (!list.length) return { error: "missing" };
  const t = d.translation || {};
  return { translation_name: t.name || t.englishName || "", verses: list };
}

// Ask the free Bible site which Tagalog and Waray-Waray versions it has, and add them to the list
async function addLocalVersions() {
  try {
    const r = await fetch("https://bible.helloao.org/api/available_translations.json");
    const all = (await r.json()).translations || [];
    [["Tagalog", ["tgl", "fil"]], ["Waray-Waray", ["war"]]].forEach(([label, codes]) => {
      const list = all.filter(t => codes.includes(t.language));
      const g = document.createElement("optgroup"); g.label = label;
      if (!list.length) return;
      list.forEach(t => g.append(new Option(label + " - " + (t.englishName || t.name), "ho:" + t.id)));
      $("trans").append(g);
    });
    $("trans").value = trans;
    if ($("trans").value !== trans) $("trans").value = "web";
  } catch (e) {}
}

// ---------- Translator (based on your translate.php, uses the free MyMemory service) ----------
let trCache = store.get("vh_tr", {});     // translations already done are kept, so they do not use the free daily limit again
function trSave() {
  const keys = Object.keys(trCache);
  if (keys.length > 1500) keys.slice(0, 500).forEach(k => delete trCache[k]);
  store.set("vh_tr", trCache);
}
function decode(s) { const e = document.createElement("textarea"); e.innerHTML = s; return e.value; }
function splitText(text, max = 450) {
  if (text.length <= max) return [text];
  const chunks = []; let cur = "";
  text.split(/(?<=[.!?;:])\s+/).forEach(p => {
    if (cur && cur.length + p.length + 1 > max) { chunks.push(cur); cur = p; } else cur = cur ? cur + " " + p : p;
  });
  if (cur) chunks.push(cur);
  return chunks.map(c => c.slice(0, max));
}
// Ask MyMemory directly from the browser
async function myMemory(text, from, to) {
  const out = [];
  for (const q of splitText(text)) {
    const r = await fetch("https://api.mymemory.translated.net/get?q=" + encodeURIComponent(q) + "&langpair=" + from + "|" + to);
    const j = await r.json();
    const t = (j.responseData && j.responseData.translatedText) || "";
    if (/MYMEMORY WARNING/i.test(t) || +j.responseStatus === 429) throw new Error("The free translation limit for today has been reached. Try again tomorrow.");
    if (+j.responseStatus !== 200) throw new Error("The free translation service could not translate into this language.");
    out.push(decode(t));
  }
  return out.join(" ");
}
// Waray-Waray goes through Google Translate (MyMemory does not support it)
async function googleTranslate(text, from, to) {
  const out = [];
  for (const q of splitText(text)) {
    const r = await fetch("https://translate.googleapis.com/translate_a/single?client=gtx&sl=" + from + "&tl=" + to + "&dt=t&q=" + encodeURIComponent(q), { signal: AbortSignal.timeout(15000) });
    const j = await r.json();
    out.push((j[0] || []).map(s => s[0]).join(""));
  }
  return out.join(" ");
}
// Translate up to 10 texts: first with translate.php, and if that fails, directly from the browser
async function translateBatch(texts, from, to) {
  let serverError = "";
  try {
    const r = await fetch("translate.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ texts, from, to }), signal: AbortSignal.timeout(25000) });
    const d = await r.json().catch(() => ({}));
    if (r.ok && d.translations) return d.translations;
    serverError = d.error || "";
  } catch (e) {}
  try {
    const out = [];
    for (const t of texts) out.push(to === "war" ? await googleTranslate(t, from, to) : await myMemory(t, from, to));
    return out;
  } catch (e) {
    throw new Error(serverError || (e instanceof TypeError || e.name === "TimeoutError" ? "Could not reach the translator. Check your internet connection and try again." : e.message) || "Could not translate. Try again.");
  }
}

// Put a translated verse on the page (and keep it for Copy and Save)
function applyText(v, text) {
  v.text = text;
  const s = document.querySelector('#text .v[data-v="' + v.verse + '"]');
  if (s && s.childNodes[1]) s.childNodes[1].nodeValue = " " + text + " ";
}
// Translate a whole chapter little by little; verses change from English to the translation as they arrive
async function translateChapter(list, ref, to, my, label) {
  const ck = v => ["web", ref, v.verse, to].join("|");
  list.forEach(v => { if (trCache[ck(v)]) applyText(v, trCache[ck(v)]); });
  const todo = list.filter(v => !trCache[ck(v)]);
  let done = list.length - todo.length, failed = "";
  const note = () => {
    if (my !== seq) return;
    $("trnote").textContent = done < list.length && !failed
      ? "Translating to " + label + "… " + done + " of " + list.length
      : "Machine translation to " + label + ". It can have mistakes.";
  };
  note();
  const batches = [];
  for (let i = 0; i < todo.length; i += 5) batches.push(todo.slice(i, i + 5));
  let next = 0;
  const worker = async () => {
    while (next < batches.length && !failed) {
      const part = batches[next++];
      try {
        const res = await translateBatch(part.map(v => v.text.trim()), "en", to);
        if (my !== seq) return;                       // you changed the page: stop
        part.forEach((v, k) => { trCache[ck(v)] = res[k]; applyText(v, res[k]); });
        done += part.length; trSave(); note();
      } catch (e) { failed = e.message || "Could not translate."; }
    }
  };
  await Promise.all([worker(), worker(), worker()]);
  if (my === seq && failed) $("trnote").textContent = failed + " The rest is shown in English.";
}

// Machine-translated version (Waray-Waray): translates only the verse(s) you ask for, so it stays fast
async function fetchMachine(ref, to, v1, v2) {
  const my = seq;
  let d = null;
  try {
    const r = await fetch("bible.php?ref=" + encodeURIComponent(ref) + "&t=web");
    if (r.ok) d = await r.json();
  } catch (e) {}
  if (!d) { const r = await fetch("https://bible-api.com/" + encodeURIComponent(ref) + "?translation=web"); d = await r.json(); }
  if (d.error || !d.verses) return { error: "missing" };
  const label = to === "war" ? "Waray-Waray" : "Tagalog";

  // Whole chapter: show the English right away, then replace each verse with its translation as it arrives
  if (!v1) {
    return { translation_name: "World English Bible", verses: d.verses, after: () => translateChapter(d.verses, ref, to, my, label) };
  }

  const want = d.verses.filter(v => v.verse >= v1 && v.verse <= v2).slice(0, 5);
  const ck = v => ["web", ref, v.verse, to].join("|");
  const todo = want.filter(v => !trCache[ck(v)]);
  if (todo.length) {
    $("trnote").textContent = "Translating to " + label + "…";
    try {
      const res = await translateBatch(todo.map(v => v.text.trim()), "en", to);
      if (my !== seq) return { verses: [] };      // you changed the page: stop
      todo.forEach((v, k) => { trCache[ck(v)] = res[k]; });
      trSave();
    } catch (e) { e.tr = true; throw e; }
  }
  $("trnote").textContent = "Machine translation to " + label + ". It can have mistakes.";
  return { translation_name: "World English Bible", verses: want.map(v => ({ verse: v.verse, text: trCache[ck(v)] || v.text })) };
}

async function load(book, ch, v1 = 0, v2 = 0) {
  const my = ++seq;
  if (v1 && v2 < v1) v2 = v1;
  cur = { book, ch }; store.set("vh_last", cur);
  $("trnote").textContent = "";
  $("title").textContent = book + " " + ch + (v1 ? ":" + v1 + (v2 > v1 ? "-" + v2 : "") : "");
  $("sub").textContent = "";
  $("text").innerHTML = '<p class="msg">Loading...</p>';
  $("chaps").style.display = v1 ? "none" : "";
  document.querySelector(".pn").style.display = v1 ? "none" : "";
  if (!v1) renderChaps();
  document.querySelectorAll(".bk").forEach(b => b.classList.toggle("on", b.textContent === book));
  try {
    const d = await fetchBible(book + " " + ch, v1, v2);
    if (my !== seq) return;
    if (d.error) throw new Error(d.error);
    verses = d.verses; $("sub").textContent = d.translation_name || "";
    drawText(v1, v2);
    if (d.after) d.after();
  } catch (e) {
    if (my === seq) $("text").innerHTML = '<p class="msg">' + (e.message === "missing" ? "This chapter is not in this version. Try another book or version." : e.tr ? e.message : "Could not load this. Check your internet and try again.") + '</p>';
  }
}

let shown = [];
function drawText(v1, v2) {
  const box = $("text"); box.innerHTML = "";
  shown = v1 ? verses.filter(v => v.verse >= v1 && v.verse <= v2) : verses;
  if (!shown.length) { box.innerHTML = '<p class="msg">That verse was not found.</p>'; }
  shown.forEach(v => {
    const s = document.createElement("span"); s.className = "v"; s.dataset.v = v.verse;
    const n = document.createElement("sup"); n.textContent = v.verse;
    s.append(n, document.createTextNode(" " + v.text.trim() + " "));
    s.onclick = () => toggleSave(v);
    box.append(s);
  });
  if (v1) {
    // Only the verse(s): show a link to read the whole chapter
    const p = document.createElement("p"); p.className = "note";
    p.append("Click a verse to save or unsave it. ");
    const a = document.createElement("a"); a.textContent = "Read all of " + cur.book + " " + cur.ch;
    a.onclick = () => load(cur.book, cur.ch);
    p.append(a); box.append(p);
  }
  markSaved();
  $("main").scrollTop = 0;
}

const isSaved = v => saved.findIndex(s => s.book === cur.book && s.ch === cur.ch && s.v === v);
function markSaved() {
  document.querySelectorAll(".v").forEach(s => s.classList.toggle("sv2", isSaved(+s.dataset.v) >= 0));
}
function toggleSave(v) {
  const i = isSaved(v.verse);
  if (i >= 0) saved.splice(i, 1);
  else saved.push({ book: cur.book, ch: cur.ch, v: v.verse, text: v.text.trim(), t: trans });
  store.set("vh_saved_" + me, saved);
  markSaved();
  if (tab === "saved") renderList(); else $("tabSaved").textContent = "Saved " + saved.length;
}

function step(dir) {
  const bi = BOOKS.findIndex(b => b[0] === cur.book);
  if (dir > 0) { if (cur.ch < BOOKS[bi][1]) load(cur.book, cur.ch + 1); else if (bi < BOOKS.length - 1) load(BOOKS[bi + 1][0], 1); }
  else { if (cur.ch > 1) load(cur.book, cur.ch - 1); else if (bi > 0) load(BOOKS[bi - 1][0], BOOKS[bi - 1][1]); }
}
$("next").onclick = () => step(1);
$("prev").onclick = () => step(-1);

$("goForm").onsubmit = e => {
  e.preventDefault();
  const r = parseRef($("q").value);
  if (r) load(r.book, r.ch, r.v1, r.v2);
  else { $("title").textContent = "Not found"; $("sub").textContent = ""; $("chaps").innerHTML = ""; $("text").innerHTML = '<p class="msg">I could not find that. Try John 3:16 or Psalm 23.</p>'; }
};
$("rand").onclick = () => { const r = parseRef(RANDOM[Math.floor(Math.random() * RANDOM.length)]); load(r.book, r.ch, r.v1, r.v2); };
$("trans").onchange = () => { trans = $("trans").value; store.set("vh_trans", trans); load(cur.book, cur.ch); };
$("dark").onclick = () => setDark(!document.body.classList.contains("dark"));
function size(d) { fsize = Math.min(28, Math.max(12, fsize + d)); store.set("vh_fsize3", fsize); $("text").style.fontSize = fsize + "px"; }
$("fm").onclick = () => size(-1);
$("fp").onclick = () => size(1);
$("copy").onclick = async () => {
  const t = $("title").textContent + "\n" + shown.map(v => v.verse + " " + v.text.trim()).join("\n");
  try { await navigator.clipboard.writeText(t); $("copy").textContent = "Copied"; } catch (e) { $("copy").textContent = "Cannot copy"; }
  setTimeout(() => $("copy").textContent = "Copy", 1400);
};
$("logout").onclick = () => { store.set("vh_session", null); location.href = "login.php"; };

renderList();
load(cur.book, cur.ch);
addLocalVersions();
</script>
</body>
</html>