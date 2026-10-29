<?php $title = 'Make an account - Verse Haven'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $title; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
  .gwrap{display:flex;justify-content:center;margin:18px 0 6px;min-height:44px}
  .gtext{color:#8a7560;font-style:italic;text-align:center;margin:14px 0 0}
  .verified{background:#efe3c8;border:1px solid #d3c29c;border-radius:3px;padding:6px 10px;margin:0 0 8px;font-size:16px}
  .verified b{color:#7a2a20}
  .verified a{color:#7a2b21;margin-left:6px;font-size:15px}
  .pane-r input[readonly]{background:#efe3c8;color:#7d6a58}
  #msg1{color:#a3271d;text-align:center;min-height:22px}
</style>
</head>
<body class="auth">
<div class="book" id="book">
  <div class="pane-l"><q>Come unto me, all ye that labour and are heavy laden, and I will give you rest.</q><cite>Matthew 11:28, KJV</cite></div>
  <div class="pane-r tight">
    <div class="brand"><div id="logo"></div><div class="name">Verse Haven</div><div class="tagrow">Read • Reflect • Remember</div></div>
    <h2>Create an account</h2>
    <div class="sub">A Google account is required to join Verse Haven.</div>

    <div id="step1">
      <p class="gtext">First, verify your Google account.</p>
      <div class="gwrap" id="gbtn"></div>
      <div id="msg1"></div>
    </div>

    <div id="step2" hidden>
      <div class="verified">✓ Verified with Google: <b id="gemail"></b> <a href="#" id="change">Use another</a></div>
      <label>Username <input id="user" autocomplete="username"></label>
      <label>Email <input id="email" type="email" readonly></label>
      <label>Password <input id="pass" type="password" autocomplete="new-password"></label>
      <label>Password again <input id="pass2" type="password" autocomplete="new-password"></label>
      <div id="msg"></div>
      <button class="go" id="go">Create account</button>
    </div>

    <p class="alt">Have an account? <a href="login.php">Log in</a></p>
  </div>
</div>
<script src="https://accounts.google.com/gsi/client" async defer onload="initGoogle()"></script>
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

// ---- Step 1: verify a Google account (the server checks it with Google) ----
let gUser = null;   // { email, name, sub } once verified

async function onCredential(resp) {
  $("msg1").textContent = "Checking your Google account…";
  try {
    const r = await fetch("google.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ credential: resp.credential })
    });
    const d = await r.json().catch(() => ({}));
    if (!r.ok || !d.ok) { $("msg1").textContent = d.error || "Could not check your Google account. Try again."; return; }
    gUser = { email: d.email, name: d.name, sub: d.sub };
    $("msg1").textContent = "";
    $("gemail").textContent = d.email;
    $("email").value = d.email;
    if (!$("user").value) $("user").value = d.email.split("@")[0].replace(/[^A-Za-z0-9_]/g, "").slice(0, 20);
    $("step1").hidden = true;
    $("step2").hidden = false;
    $("user").focus();
  } catch (e) {
    $("msg1").textContent = "Could not reach the server. Check your internet and try again.";
  }
}

function initGoogle() {
  window.google.accounts.id.initialize({ client_id: "<?php require __DIR__ . '/config.php'; echo GOOGLE_CLIENT_ID; ?>", callback: onCredential });
  window.google.accounts.id.renderButton($("gbtn"), { theme: "outline", size: "large", text: "signup_with", shape: "pill", width: 300 });
}

$("change").onclick = e => {
  e.preventDefault();
  gUser = null;
  $("step2").hidden = true;
  $("step1").hidden = false;
};

// ---- Step 2: choose a username and password ----
async function make() {
  if (!gUser) { $("msg").textContent = "Verify your Google account first."; return; }
  const username = $("user").value.trim(), key = username.toLowerCase();
  const email = gUser.email, pass = $("pass").value;
  const users = store.get("vh_users", {});
  let err = "";
  if (!/^[A-Za-z0-9_]{3,20}$/.test(username)) err = "Username: 3 to 20 letters, numbers, or _ .";
  else if (pass.length < 8) err = "Use a password with at least 8 letters or numbers.";
  else if (pass !== $("pass2").value) err = "The two passwords are not the same.";
  else if (users[key]) err = "This username is already taken.";
  else if (Object.values(users).some(u => (u.email || "").toLowerCase() === email || (gUser.sub && u.sub === gUser.sub))) err = "This Google account already has an account. Please log in.";
  if (err) { $("msg").textContent = err; return; }
  users[key] = { username, email, hash: await hash(key + ":" + pass), google: true, sub: gUser.sub };
  store.set("vh_users", users);
  store.set("vh_session", key);
  location.href = "index.php";
}
$("go").onclick = make;
$("pass2").onkeydown = e => { if (e.key === "Enter") make(); };
</script>
</body>
</html>
