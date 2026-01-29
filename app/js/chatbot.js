const fab = document.getElementById("chatFab");
const box = document.getElementById("chatBox");
const closeBtn = document.getElementById("chatClose");
const form = document.getElementById("chatForm");
const input = document.getElementById("chatInput");
const messages = document.getElementById("chatMessages");

function addMsg(text, who){
  const div = document.createElement("div");
  div.className = `msg ${who}`;
  div.textContent = text;
  messages.appendChild(div);
  messages.scrollTop = messages.scrollHeight;
}

fab.addEventListener("click", ()=>{
  box.classList.toggle("chat-hidden");
  if (!box.classList.contains("chat-hidden") && messages.childElementCount === 0){
    addMsg("Hola 👋 Soy el asistente de Red Teatros. Dime provincia, teatro, obra, horarios o ranking.", "bot");
  }
});

closeBtn.addEventListener("click", ()=> box.classList.add("chat-hidden"));

form.addEventListener("submit", async (e)=>{
  e.preventDefault();
  const text = input.value.trim();
  if (!text) return;

  addMsg(text, "user");
  input.value = "";

  try{
  const res = await fetch("/TFG-DAW-TEATROS/app/api/chat.php", {
    method: "POST",
    headers: { "Content-Type":"application/json" },
    body: JSON.stringify({ message: text })
  });

  const txt = await res.text();
  let data;
  try { data = JSON.parse(txt); }
  catch { data = { reply: "Error servidor (no JSON): " + txt.slice(0, 120) }; }

  addMsg(data.reply || "No he podido responder.", "bot");
}catch(err){
  addMsg("Error conectando con el asistente.", "bot");
}

});
