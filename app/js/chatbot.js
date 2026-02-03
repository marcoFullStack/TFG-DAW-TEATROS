
const fab = document.getElementById("chatFab");
const box = document.getElementById("chatBox");
const closeBtn = document.getElementById("chatClose");
const form = document.getElementById("chatForm");
const input = document.getElementById("chatInput");
const messages = document.getElementById("chatMessages");

/**
 * The function `addMsg` creates a new `div` element with specified text and class, appends it to the
 * `messages` container, and scrolls to the bottom of the container.
 * @param text - The `text` parameter is the message content that you want to display in the chat
 * interface.
 * @param who - The `who` parameter in the `addMsg` function is used to specify the sender of the
 * message. It is a class name that will be added to the created `div` element to differentiate between
 * messages sent by different users or entities.
 */
function addMsg(text, who){
  const div = document.createElement("div");
  div.className = `msg ${who}`;
  div.textContent = text;
  messages.appendChild(div);
  messages.scrollTop = messages.scrollHeight;
}

/* The `fab.addEventListener("click", ()=>{ ... })` code block is adding a click event listener to the
element with the id "chatFab" (referred to as `fab`). When this element is clicked, the following
actions are performed: */
fab.addEventListener("click", ()=>{
  box.classList.toggle("chat-hidden");
  if (!box.classList.contains("chat-hidden") && messages.childElementCount === 0){
    addMsg("Hola 👋 Soy el asistente de Red Teatros. Dime provincia, teatro, obra, horarios o ranking.", "bot");
  }
});

closeBtn.addEventListener("click", ()=> box.classList.add("chat-hidden"));

/* The `form.addEventListener("submit", async (e) => { ... })` code block is adding an event listener
to the form element with the id "chatForm" (referred to as `form`). This event listener is triggered
when the form is submitted, typically by pressing the Enter key or clicking a submit button within
the form. */
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
