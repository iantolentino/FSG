let session;
const status = document.querySelector('#dashboard-status');
const loginPanel = document.querySelector('#login-panel');
const workspace = document.querySelector('#workspace');
const list = document.querySelector('#requests');
async function api(action, method = 'GET', body) {
  const response = await fetch('api.php?action=' + action, {
    method, headers: {'Content-Type':'application/json', 'X-CSRF-Token': session?.csrf || ''},
    body: body ? JSON.stringify(body) : undefined
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result.error || 'Request failed.');
  return result;
}
function element(tag, text, parent) {
  const el = document.createElement(tag);
  if (text !== undefined) el.textContent = text;
  if (parent) parent.append(el);
  return el;
}
function field(form, name, label, value, type = 'text') {
  const id = form.id + '-' + name;
  const labelEl = element('label', label, form); labelEl.htmlFor = id;
  const input = element(name === 'message' ? 'textarea' : 'input', undefined, form);
  input.id = id; input.name = name; input.value = value; input.required = true;
  if (name !== 'message') input.type = type;
  input.maxLength = name === 'message' ? 1000 : name === 'email' ? 254 : 80;
  return input;
}
function button(parent, text, fn, danger = false) {
  const el = element('button',text,parent); el.type = 'button'; el.className = 'button ' + (danger ? 'danger' : 'secondary');
  el.addEventListener('click', fn); return el;
}
async function refresh() {
  const {requests} = await api('list'); list.replaceChildren();
  if (!requests.length) element('p', 'No project requests yet. Submit one from the landing page.', list);
  requests.forEach((request) => {
    const card = element('article', undefined, list); card.className='request-card';
    element('h2', request.name, card); element('p', request.email + ' / ' + request.status, card).className='muted';
    element('p', request.message, card);
    const actions = element('div',undefined,card); actions.className='actions';
    button(actions, 'Edit request', () => edit(card,request));
    button(actions, 'Delete request', async () => {
      if (!confirm('Delete this project request?')) return;
      try {await api('delete','DELETE',{id:request.id}); await refresh(); status.textContent='Request deleted.';}
      catch(error){status.textContent=error.message;}
    },true);
  });
}
function edit(card, request) {
  card.replaceChildren(); const form=element('form',undefined,card); form.id='request-'+request.id;
  field(form,'name','Name',request.name); field(form,'email','Email',request.email,'email'); field(form,'message','Project details',request.message);
  const label=element('label','Status',form); label.htmlFor=form.id+'-status';
  const select=element('select',undefined,form); select.id=label.htmlFor; select.name='status';
  ['new','in-progress','done'].forEach(value=>{const option=element('option',value,select);option.value=value;});select.value=request.status;
  const actions=element('div',undefined,form);actions.className='actions';
  const save=element('button','Save changes',actions);save.className='button';save.type='submit';
  button(actions,'Cancel',()=>refresh().catch(e=>status.textContent=e.message));
  form.addEventListener('submit',async event=>{
    event.preventDefault();save.disabled=true;
    try {await api('update','PATCH',{id:request.id,...Object.fromEntries(new FormData(form))});await refresh();status.textContent='Request updated.';}
    catch(error){status.textContent=error.message;save.disabled=false;}
  });
}
async function showWorkspace() {
  loginPanel.hidden=session.authenticated;workspace.hidden=!session.authenticated;
  if(session.authenticated) await refresh();
}
document.querySelector('#login-form').addEventListener('submit',async event=>{
  event.preventDefault();const form=event.target;
  try{session=await api('login','POST',Object.fromEntries(new FormData(form)));form.reset();status.textContent='Signed in.';await showWorkspace();}
  catch(error){status.textContent=error.message;}
});
document.querySelector('#logout').addEventListener('click',async()=>{
  try{session=await api('logout','POST',{});list.replaceChildren();await showWorkspace();status.textContent='Signed out.';}catch(error){status.textContent=error.message;}
});
document.querySelector('#refresh').addEventListener('click',()=>refresh().catch(e=>status.textContent=e.message));
(async()=>{try{session=await api('session');await showWorkspace();}catch(error){status.textContent=error.message;}})();
