<?php $title = 'Log in - Verse Haven'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $title; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body class="auth">
<div class="book" id="book">
  <div class="pane-l"><q>Thy word is a lamp unto my feet, and a light unto my path.</q><cite>Psalm 119:105, KJV</cite></div>
  <div class="pane-r">
    <div class="brand"><div id="logo"></div><div class="name">Verse Haven</div><div class="tagrow">Read • Reflect • Remember</div></div>
    <h2>Log in</h2>
    <div class="sub">Welcome back to Verse Haven.</div>
    <label>Username or email <input id="id" autocomplete="username"></label>
    <label>Password <input id="pass" type="password" autocomplete="current-password"></label>
    <div id="msg"></div>
    <button class="go" id="go">Log in</button>
    <p class="alt">New here? <a href="register.php">Create an account</a></p>
  </div>
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

// Your gold frame (frame.svg) and the line in the middle of the book
function addOrnaments(book) {
  book.insertAdjacentHTML("beforeend", '<div class="frame"></div><div class="seam"></div>');
}

$("logo").innerHTML = logoSvg(40, true);
addOrnaments($("book"));
if (store.get("vh_session", null)) location.replace("index.php");
async function login() {
  const id = $("id").value.trim().toLowerCase(), pass = $("pass").value;
  const users = store.get("vh_users", {});
  const key = users[id] ? id : Object.keys(users).find(k => (users[k].email || "").toLowerCase() === id);
  if (!key || users[key].hash !== await hash(key + ":" + pass)) { $("msg").textContent = "Wrong username, email, or password."; return; }
  store.set("vh_session", key);
  location.href = "index.php";
}
$("go").onclick = login;
$("pass").onkeydown = e => { if (e.key === "Enter") login(); };
</script>
</body>
</html>
