#pragma once

#include <Arduino.h>

static const char UI_STYLE[] PROGMEM = R"HTML(
<style>
:root{--ink:#eefcff;--muted:#9bb8c2;--panel:rgba(8,24,34,.88);--line:rgba(94,234,212,.18);--cyan:#2dd4bf;--blue:#38bdf8;--good:#4ade80;--warn:#fbbf24;--bad:#fb7185;--deep:#031017}
*{box-sizing:border-box}
html{color-scheme:dark}
body{margin:0;min-height:100vh;padding:18px;color:var(--ink);font-family:"Trebuchet MS","Gill Sans",sans-serif;background:radial-gradient(circle at 12% -5%,#0e7490 0,transparent 34%),radial-gradient(circle at 92% 10%,#164e63 0,transparent 28%),linear-gradient(145deg,#02080d,#071a22 58%,#031017);background-attachment:fixed}
body:before{content:"";position:fixed;inset:0;pointer-events:none;opacity:.18;background-image:linear-gradient(rgba(45,212,191,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(45,212,191,.08) 1px,transparent 1px);background-size:32px 32px;mask-image:linear-gradient(to bottom,#000,transparent 76%)}
a{color:inherit;text-decoration:none}
.shell{position:relative;max-width:980px;margin:auto}
.nav{position:sticky;top:8px;z-index:20;display:flex;gap:7px;overflow:auto;padding:8px;margin-bottom:18px;border:1px solid var(--line);border-radius:17px;background:rgba(2,12,18,.78);backdrop-filter:blur(14px);box-shadow:0 16px 50px rgba(0,0,0,.3)}
.nav a{flex:0 0 auto;padding:9px 11px;border-radius:11px;color:#b8dce5;font-size:11px;font-weight:900;letter-spacing:.06em}
.nav a:hover{color:#021416;background:linear-gradient(135deg,var(--cyan),#7dd3fc)}
.page-head{display:flex;align-items:end;justify-content:space-between;gap:15px;margin:20px 2px}
.eyebrow{color:#5eead4;font-size:10px;font-weight:900;letter-spacing:.24em;text-transform:uppercase}
h1{margin:4px 0 0;font-family:Georgia,"Times New Roman",serif;font-size:clamp(27px,5vw,43px);line-height:1.05}
h2,h3{font-family:Georgia,"Times New Roman",serif}
h2{margin:0 0 14px;font-size:22px;color:#d9fbff}
h3{margin:0 0 10px;color:#a5f3fc}
.version{color:var(--muted);font-size:11px;white-space:nowrap}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:14px}
.span-12{grid-column:span 12}.span-8{grid-column:span 8}.span-7{grid-column:span 7}.span-6{grid-column:span 6}.span-5{grid-column:span 5}.span-4{grid-column:span 4}
.card{position:relative;overflow:hidden;padding:18px;border:1px solid var(--line);border-radius:20px;background:linear-gradient(145deg,rgba(13,39,51,.9),rgba(5,18,27,.9));box-shadow:0 18px 48px rgba(0,0,0,.24),inset 0 1px rgba(255,255,255,.035)}
.card:before{content:"";position:absolute;left:8%;right:8%;top:0;height:1px;background:linear-gradient(90deg,transparent,var(--cyan),transparent)}
.hero{min-height:210px;background:radial-gradient(circle at 85% 10%,rgba(56,189,248,.22),transparent 35%),linear-gradient(145deg,rgba(8,83,101,.72),rgba(5,18,27,.94))}
.clock{font:900 clamp(34px,7vw,58px)/1 Georgia,"Times New Roman",serif;letter-spacing:.02em}
.date{margin-top:8px;color:#bdeaf2;font-weight:800}
.stage{margin-top:20px;padding:12px 14px;border:1px solid rgba(94,234,212,.19);border-radius:14px;background:rgba(1,10,15,.38);color:#e7fbff;line-height:1.45}
.quick{display:grid;gap:9px}
.quick-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 12px;border-radius:13px;background:rgba(1,10,15,.36);color:#b9d1d8;font-size:12px;font-weight:850}
.chip{display:inline-flex;align-items:center;justify-content:center;min-width:72px;padding:6px 9px;border:1px solid rgba(74,222,128,.42);border-radius:999px;color:#bbf7d0;background:rgba(22,163,74,.15);font-size:10px;font-weight:950;letter-spacing:.05em}
.chip.off{color:#fecdd3;border-color:rgba(251,113,133,.4);background:rgba(190,24,93,.14)}
.chip.warn{color:#fef3c7;border-color:rgba(251,191,36,.5);background:rgba(180,83,9,.18)}
.controls{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:15px}
button,.button{appearance:none;border:1px solid rgba(94,234,212,.24);border-radius:13px;padding:11px 14px;color:#effcff;background:linear-gradient(135deg,#0e7490,#0369a1);font:900 12px/1.2 "Trebuchet MS",sans-serif;letter-spacing:.035em;cursor:pointer;transition:.16s}
button:hover,.button:hover{transform:translateY(-1px);filter:brightness(1.12)}
button.secondary,.button.secondary{background:#102a35;color:#cce8ee}
button.danger,.button.danger{background:linear-gradient(135deg,#be123c,#7f1d1d);border-color:rgba(251,113,133,.5)}
button.on{background:linear-gradient(135deg,#15803d,#22c55e);color:#03140a}
.tank-zone{display:flex;align-items:center;justify-content:center;min-height:360px}
.tank{position:relative;width:190px;height:300px;overflow:hidden;border:7px solid #d8f8ff;border-radius:24px;background:linear-gradient(90deg,#031017,#183541 18%,#254957 52%,#112c38 84%,#031017);box-shadow:0 0 42px rgba(45,212,191,.22),inset 14px 0 20px rgba(255,255,255,.1)}
.tank:after{content:"";position:absolute;z-index:4;inset:18px;background:repeating-linear-gradient(to bottom,transparent 0 32px,rgba(165,243,252,.22) 33px,transparent 35px);pointer-events:none}
.water{position:absolute;left:9px;right:9px;bottom:9px;height:0;border-radius:0 0 11px 11px;background:linear-gradient(#22d3ee,#0891b2 55%,#155e75);box-shadow:0 -4px 22px rgba(34,211,238,.55);transition:height .65s}
.water:before{content:"";position:absolute;left:-5%;top:-6px;width:110%;height:13px;border-radius:50%;background:#67e8f9;opacity:.82}
.tank-copy{position:absolute;z-index:6;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;text-shadow:0 2px 8px #001018}
.tank-copy strong{font:900 34px/1 Georgia,"Times New Roman",serif}.tank-copy span{margin-top:7px;color:#d7f8ff;font-size:12px}
.pump-drop{position:absolute;z-index:8;top:7px;left:50%;display:none;width:14px;height:76px;transform:translateX(-50%);border-radius:99px;background:linear-gradient(#a5f3fc,#06b6d4);box-shadow:0 0 20px #22d3ee;animation:flow .8s linear infinite}.pump-drop.on{display:block}
@keyframes flow{from{opacity:.2;transform:translate(-50%,-30px) scaleY(.45)}to{opacity:1;transform:translate(-50%,70px) scaleY(1.3)}}
.metric{padding:13px;border-radius:14px;background:rgba(1,10,15,.38);border:1px solid rgba(94,234,212,.12)}
.metric small{display:block;color:var(--muted);font-size:10px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.metric strong{display:block;margin-top:5px;color:#e8fcff;font:900 19px Georgia,serif}
.metric-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:9px}
.status-list{display:grid;gap:9px}.status-line{padding-bottom:9px;border-bottom:1px solid rgba(148,163,184,.12);color:#b8d0d7;font-size:13px;line-height:1.45}.status-line:last-child{border:0;padding-bottom:0}.status-line b{color:#eafcff}
.output-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.output-grid button{min-height:55px;background:#102a35}.output-grid button.on{background:linear-gradient(135deg,#15803d,#22c55e);color:#03140a}
form{display:grid;gap:13px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.form-grid.three{grid-template-columns:repeat(3,minmax(0,1fr))}
label{display:grid;gap:6px;color:#b9d1d8;font-size:12px;font-weight:800}
input,select{width:100%;min-width:0;padding:10px 11px;border:1px solid #284957;border-radius:11px;outline:0;background:#06151e;color:#f0fdff;font:700 13px "Trebuchet MS",sans-serif}
input:focus,select:focus{border-color:#2dd4bf;box-shadow:0 0 0 3px rgba(45,212,191,.1)}
label.check{display:flex;align-items:center;gap:9px;padding:10px 11px;border:1px solid rgba(94,234,212,.13);border-radius:11px;background:rgba(1,10,15,.3)}label.check input{width:17px;height:17px}
.actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:4px}.actions button{min-width:140px}
.notice{padding:13px 15px;border-left:3px solid var(--cyan);border-radius:10px;background:rgba(8,145,178,.12);color:#c9edf3;font-size:12px;line-height:1.55}.notice.warn{border-color:var(--warn);background:rgba(180,83,9,.14);color:#fef3c7}.notice.bad{border-color:var(--bad);background:rgba(190,24,93,.14);color:#ffe4e6}
.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:12px}th,td{padding:10px 8px;border-bottom:1px solid rgba(148,163,184,.13);text-align:left;white-space:nowrap}th{color:#67e8f9;font-size:10px;letter-spacing:.08em;text-transform:uppercase}td{color:#c3d8de}td.wrap{white-space:normal;min-width:220px}
.history{display:flex;align-items:end;gap:6px;min-height:110px;padding:12px;border-radius:14px;background:#06151e}.history i{flex:1;min-width:6px;border-radius:5px 5px 2px 2px;background:linear-gradient(#67e8f9,#0e7490)}
.wifi-list{display:grid;gap:8px;margin-top:10px}.wifi-item{display:flex;justify-content:space-between;gap:12px;padding:11px 12px;border:1px solid rgba(94,234,212,.14);border-radius:11px;background:#06151e;color:#d8f7fb}
.footer{padding:23px 4px 8px;color:#668b96;text-align:center;font-size:10px;letter-spacing:.06em}
.toast{position:fixed;z-index:50;top:18px;left:50%;max-width:calc(100% - 28px);padding:12px 16px;transform:translate(-50%,-20px);opacity:0;pointer-events:none;border:1px solid #34d399;border-radius:12px;background:#064e3b;color:#ecfdf5;box-shadow:0 16px 45px rgba(0,0,0,.45);transition:.22s}.toast.show{opacity:1;transform:translate(-50%,0)}.toast.bad{border-color:#fb7185;background:#881337}
.login{max-width:430px;margin:10vh auto}.login .card{padding:27px}
@media(max-width:760px){body{padding:9px}.span-8,.span-7,.span-6,.span-5,.span-4{grid-column:span 12}.form-grid,.form-grid.three,.output-grid{grid-template-columns:1fr}.page-head{align-items:start;flex-direction:column}.tank-zone{min-height:330px}.nav{top:4px}.version{white-space:normal}.controls{grid-template-columns:1fr 1fr}}
</style>
)HTML";

static const char UI_NAV[] PROGMEM = R"HTML(
<nav class="nav">
<a href="/">HOME</a><a href="/settings">SETTINGS</a><a href="/wifi">WIFI</a>
<a href="/pins">PINS</a><a href="/calibrate">CALIBRATE</a><a href="/logs">LOGS</a>
<a href="/security">SECURITY</a><a href="/update">UPDATE</a><a href="/logout">LOGOUT</a>
</nav>
)HTML";

static const char UI_DASH_SCRIPT[] PROGMEM = R"HTML(
<script>
const $=id=>document.getElementById(id), text=(id,v)=>{const e=$(id);if(e)e.textContent=v};
let pollBusy=false,toastTimer;
function toast(message,bad=false){const e=$("toast");e.textContent=message;e.className="toast show"+(bad?" bad":"");clearTimeout(toastTimer);toastTimer=setTimeout(()=>e.className="toast",5000)}
function chip(id,on,onText,offText,warn=false){const e=$(id);if(!e)return;e.textContent=on?onText:offText;e.className="chip"+(on?"":warn?" warn":" off")}
async function command(action){try{const r=await fetch("/api/action",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action="+encodeURIComponent(action)});if(r.status===401){location="/login";return}const d=await r.json();toast(d.message||"Command complete",!d.ok);poll()}catch(e){toast("Controller did not respond",true)}}
async function poll(){if(!$("clock")||pollBusy)return;pollBusy=true;try{const r=await fetch("/data",{cache:"no-store"});if(r.status===401){location="/login";return}if(!r.ok)throw 0;const d=await r.json();
text("clock",d.time);text("date",d.date);text("stage",d.stage);text("level",d.sensor_valid?d.level.toFixed(1)+"%":d.sensor_status);text("distance",d.sensor_valid?d.distance.toFixed(1)+" cm":"Waiting for echo");
const w=$("water");if(w)w.style.height=(d.sensor_valid?Math.max(0,Math.min(100,d.level)):0)+"%";$("drop").classList.toggle("on",d.pump);
chip("wifiChip",d.wifi,"ONLINE","OFFLINE");chip("pumpChip",d.pump,"RUNNING","STOPPED");chip("timeChip",d.time_synced,"SYNCED","WAITING",true);
text("ip",d.ip);text("uptime",d.uptime);text("heap",d.heap+" KB");text("rssi",d.wifi?d.rssi+" dBm":"--");text("mode",d.mode);text("lastAction",d.last_source+": "+d.last_action);
text("guards",(d.grid_ok?"Grid OK":"Grid blocked")+" / "+(d.reserve_ok?"Reserve OK":"Reserve blocked"));text("autoStatus",d.auto_status);text("energy",d.energy_kwh.toFixed(3)+" kWh");text("cost",d.energy_cost.toFixed(2));
text("auxState",d.aux?"ON":"OFF");text("light1State",d.light1?"ON":"OFF");text("light2State",d.light2?"ON":"OFF");
$("auxButton").classList.toggle("on",d.aux);$("light1Button").classList.toggle("on",d.light1);$("light2Button").classList.toggle("on",d.light2);
$("normalButton").classList.toggle("on",d.normal_demand);$("forceButton").classList.toggle("on",d.force_demand);
}catch(e){chip("wifiChip",false,"ONLINE","NO DATA");}finally{pollBusy=false}}
if($("clock")){poll();setInterval(poll,1200)}
</script>
)HTML";

static const char UI_SCAN_SCRIPT[] PROGMEM = R"HTML(
<script>
async function scanWifi(){const box=document.getElementById("scanList"),btn=document.getElementById("scanButton");btn.disabled=true;box.innerHTML='<div class="notice">Scanning...</div>';try{let r=await fetch("/api/scan",{cache:"no-store"});if(r.status===202){setTimeout(scanWifi,1200);return}if(!r.ok)throw 0;let rows=await r.json();box.innerHTML=rows.length?"":"<div class='notice'>No network found.</div>";rows.forEach(n=>{let e=document.createElement("button");e.type="button";e.className="wifi-item";let a=document.createElement("span"),b=document.createElement("span");a.textContent=n.ssid||"(hidden)";b.textContent=n.rssi+" dBm "+(n.open?"OPEN":"LOCKED");e.append(a,b);e.onclick=()=>document.querySelector("[name=sta_ssid]").value=n.ssid;box.appendChild(e)});btn.disabled=false}catch(e){box.innerHTML="<div class='notice bad'>Scan failed.</div>";btn.disabled=false}}
</script>
)HTML";
