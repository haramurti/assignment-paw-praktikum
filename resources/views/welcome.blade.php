<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Todo App</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: system-ui, sans-serif; background: #f5f5f5; color: #222; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
  .card { background: #fff; border-radius: 8px; padding: 2rem; width: 100%; max-width: 480px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; }
  .form-group { display: flex; flex-direction: column; gap: .25rem; margin-bottom: .75rem; }
  label { font-size: .8rem; color: #666; }
  input { border: 1px solid #ddd; border-radius: 4px; padding: .5rem .75rem; font-size: .9rem; width: 100%; outline: none; }
  input:focus { border-color: #555; }
  button { cursor: pointer; border: none; border-radius: 4px; padding: .5rem 1rem; font-size: .9rem; }
  .btn-primary { background: #222; color: #fff; width: 100%; margin-top: .25rem; }
  .btn-primary:hover { background: #444; }
  .link { background: none; color: #555; font-size: .8rem; text-decoration: underline; margin-top: .75rem; display: block; text-align: center; }
  .link:hover { color: #222; }
  .error { color: #c0392b; font-size: .8rem; margin-top: .5rem; }

  .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
  .btn-logout { background: none; border: 1px solid #ddd; color: #666; font-size: .8rem; padding: .3rem .75rem; border-radius: 4px; }
  .btn-logout:hover { border-color: #999; color: #222; }
  .add-form { display: flex; gap: .5rem; margin-bottom: 1.25rem; }
  .add-form input { flex: 1; }
  .btn-add { background: #222; color: #fff; white-space: nowrap; border-radius: 4px; padding: .5rem 1rem; flex-shrink: 0; }
  .btn-add:hover { background: #444; }
  .todo-list { list-style: none; display: flex; flex-direction: column; gap: .5rem; }
  .todo-item { display: flex; align-items: center; gap: .75rem; padding: .6rem .75rem; border: 1px solid #eee; border-radius: 4px; }
  .todo-item.done .todo-title { text-decoration: line-through; color: #aaa; }
  .todo-check { cursor: pointer; width: 16px; height: 16px; flex-shrink: 0; }
  .todo-title { flex: 1; font-size: .9rem; word-break: break-word; }
  .btn-del { background: none; color: #ccc; font-size: 1.1rem; padding: 0 .2rem; line-height: 1; flex-shrink: 0; }
  .btn-del:hover { color: #c0392b; }
  .empty { text-align: center; color: #aaa; font-size: .9rem; padding: 1.5rem 0; }

  [hidden] { display: none !important; }
</style>
</head>
<body>

<!-- Auth view -->
<div class="card" id="auth-view">
  <h1 id="auth-title">Masuk</h1>
  <div id="register-name" class="form-group" hidden>
    <label>Nama</label>
    <input type="text" id="name" placeholder="Nama lengkap">
  </div>
  <div class="form-group">
    <label>Email</label>
    <input type="email" id="email" placeholder="email@contoh.com">
  </div>
  <div class="form-group">
    <label>Password</label>
    <input type="password" id="password" placeholder="••••••••">
  </div>
  <div id="auth-error" class="error" hidden></div>
  <button class="btn-primary" id="auth-btn">Masuk</button>
  <button class="link" id="auth-toggle">Belum punya akun? Daftar</button>
</div>

<!-- Todo view -->
<div class="card" id="todo-view" hidden>
  <div class="top-bar">
    <h1>Todo</h1>
    <button class="btn-logout" id="logout-btn">Keluar</button>
  </div>
  <div class="add-form">
    <input type="text" id="new-todo" placeholder="Tambah todo baru...">
    <button class="btn-add" id="add-btn">Tambah</button>
  </div>
  <ul class="todo-list" id="todo-list">
    <li class="empty">Belum ada todo.</li>
  </ul>
</div>

<script>
const API = '/api';
let token = localStorage.getItem('token') || null;
let isLogin = true;

const authView  = document.getElementById('auth-view');
const todoView  = document.getElementById('todo-view');
const authTitle = document.getElementById('auth-title');
const authBtn   = document.getElementById('auth-btn');
const authToggle= document.getElementById('auth-toggle');
const regName   = document.getElementById('register-name');
const authError = document.getElementById('auth-error');
const todoList  = document.getElementById('todo-list');
const newTodo   = document.getElementById('new-todo');

function showError(msg) {
  authError.textContent = msg;
  authError.hidden = false;
}

authToggle.addEventListener('click', () => {
  isLogin = !isLogin;
  authError.hidden = true;
  authTitle.textContent = isLogin ? 'Masuk' : 'Daftar';
  authBtn.textContent   = isLogin ? 'Masuk' : 'Daftar';
  authToggle.textContent= isLogin ? 'Belum punya akun? Daftar' : 'Sudah punya akun? Masuk';
  regName.hidden = isLogin;
});

authBtn.addEventListener('click', async () => {
  authError.hidden = true;
  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const name     = document.getElementById('name').value.trim();
  const endpoint = isLogin ? '/login' : '/register';
  const body     = isLogin ? { email, password } : { name, email, password };

  try {
    const res  = await fetch(API + endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok) {
      const msg = data.message || (data.errors && Object.values(data.errors).flat().join(' ')) || 'Gagal.';
      showError(msg);
      return;
    }
    token = data.token;
    localStorage.setItem('token', token);
    showTodos();
  } catch {
    showError('Tidak dapat terhubung ke server.');
  }
});

document.getElementById('logout-btn').addEventListener('click', async () => {
  await fetch(API + '/logout', {
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
  }).catch(() => {});
  token = null;
  localStorage.removeItem('token');
  authView.hidden = false;
  todoView.hidden = true;
});

document.getElementById('add-btn').addEventListener('click', addTodo);
newTodo.addEventListener('keydown', e => { if (e.key === 'Enter') addTodo(); });

async function addTodo() {
  const title = newTodo.value.trim();
  if (!title) return;
  const res = await fetch(API + '/todos', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
    body: JSON.stringify({ title }),
  });
  if (res.ok) {
    newTodo.value = '';
    await loadTodos();
  }
}

async function loadTodos() {
  const res   = await fetch(API + '/todos', { headers: { 'Accept': 'application/json' } });
  const todos = await res.json();
  renderTodos(todos);
}

function renderTodos(todos) {
  if (!todos.length) {
    todoList.innerHTML = '<li class="empty">Belum ada todo.</li>';
    return;
  }
  todoList.innerHTML = todos.map(t => `
    <li class="todo-item${t.is_done ? ' done' : ''}" data-id="${t.id}">
      <input type="checkbox" class="todo-check" ${t.is_done ? 'checked' : ''}>
      <span class="todo-title">${escHtml(t.title)}</span>
      <button class="btn-del" title="Hapus">×</button>
    </li>`).join('');

  todoList.querySelectorAll('.todo-check').forEach(cb => {
    cb.addEventListener('change', async () => {
      const id = cb.closest('.todo-item').dataset.id;
      await fetch(API + '/todos/' + id, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ is_done: cb.checked }),
      });
      await loadTodos();
    });
  });

  todoList.querySelectorAll('.btn-del').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.closest('.todo-item').dataset.id;
      await fetch(API + '/todos/' + id, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
      });
      await loadTodos();
    });
  });
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function showTodos() {
  authView.hidden = true;
  todoView.hidden = false;
  await loadTodos();
}

if (token) showTodos();
</script>
</body>
</html>
