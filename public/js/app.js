const state = {
  projects: [],
  activeProject: null,
  files: [],
  currentFile: null,
  selectedPath: null,
  menuOpen: true,
};

const el = {
  menuToggle: document.getElementById('menu-toggle'),
  menu: document.getElementById('menu'),
  themeToggle: document.getElementById('theme-toggle'),
  fileList: document.getElementById('file-list'),
  search: document.getElementById('search'),
  projectSelect: document.getElementById('project-select'),
  docViewer: document.getElementById('doc-viewer'),
  docMeta: document.getElementById('doc-meta'),
  docBody: document.getElementById('doc-body'),
  downloadBtn: document.getElementById('download-btn'),
  welcome: document.getElementById('welcome'),
  status: document.getElementById('status'),
  tokenDialog: document.getElementById('token-dialog'),
  tokenInput: document.getElementById('token-input'),
  tokenSubmit: document.getElementById('token-submit'),
  tokenCancel: document.getElementById('token-cancel'),
  tokenError: document.getElementById('token-error'),
  tokenTitle: document.getElementById('token-dialog-title'),
  tokenDesc: document.getElementById('token-dialog-desc'),
};

async function fetchJSON(url, opts) {
  const res = await fetch(url, opts);
  if (!res.ok) {
    if (res.status === 403) throw new Error('FORBIDDEN');
    throw new Error(`HTTP ${res.status}`);
  }
  return res.json();
}

function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? decodeURIComponent(match[2]) : null;
}

function setCookie(name, value, days) {
  const d = new Date();
  d.setTime(d.getTime() + days * 864e5);
  document.cookie = `${name}=${encodeURIComponent(value)};expires=${d.toUTCString()};path=/;SameSite=Lax`;
}

// ── Project management ──

async function loadProjects() {
  const list = await fetchJSON('/api/projects');
  state.projects = list;
  el.projectSelect.innerHTML = '';
  list.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = p.name + (p.locked ? ' 🔒' : '');
    el.projectSelect.appendChild(opt);
  });
  return list;
}

async function switchProject(projectId) {
  const project = state.projects.find(p => p.id === projectId);
  if (!project) return;

  if (project.locked) {
    const token = getCookie('token_' + projectId);
    if (!token) {
      await promptToken(project);
      return;
    }
    try {
      await fetchJSON(`/api/projects/${projectId}/files`, {
        headers: { 'Cookie': `token_${projectId}=${encodeURIComponent(token)}` },
      });
    } catch {
      await promptToken(project);
      return;
    }
  }

  state.activeProject = projectId;
  state.selectedPath = null;
  state.currentFile = null;
  await loadFiles(projectId);
  el.welcome.style.display = state.files.length === 0 ? 'flex' : 'none';
  el.docViewer.style.display = 'none';
  el.docBody.innerHTML = '';
}

async function loadFiles(projectId) {
  try {
    const token = getCookie('token_' + projectId);
    const opts = token
      ? { headers: { 'Cookie': `token_${projectId}=${encodeURIComponent(token)}` } }
      : {};
    const data = await fetchJSON(`/api/projects/${projectId}/files`, opts);
    state.files = data.files;
    renderFileList();
  } catch (e) {
    if (e.message === 'FORBIDDEN') {
      const project = state.projects.find(p => p.id === projectId);
      await promptToken(project);
    }
  }
}

async function loadFile(projectId, path) {
  try {
    state.selectedPath = path;
    const token = getCookie('token_' + projectId);
    const opts = token
      ? { headers: { 'Cookie': `token_${projectId}=${encodeURIComponent(token)}` } }
      : {};
    const encPath = path.split('/').map(encodeURIComponent).join('/');
    const data = await fetchJSON(`/api/projects/${projectId}/files/${encPath}`, opts);
    state.currentFile = data;
    renderFile(data);
  } catch (e) {
    if (e.message === 'FORBIDDEN') {
      const project = state.projects.find(p => p.id === projectId);
      await promptToken(project);
    }
  }
}

// ── Token dialog ──

function promptToken(project) {
  return new Promise((resolve) => {
    el.tokenTitle.textContent = `Acceso: ${project.name}`;
    el.tokenDesc.textContent = 'Introduce el token de acceso:';
    el.tokenInput.value = '';
    el.tokenError.style.display = 'none';
    el.tokenDialog.style.display = 'flex';
    el.tokenInput.focus();

    function cleanup() {
      el.tokenDialog.style.display = 'none';
      el.tokenSubmit.removeEventListener('click', onSubmit);
      el.tokenCancel.removeEventListener('click', onCancel);
      el.tokenInput.removeEventListener('keydown', onKey);
    }

    function onSubmit() {
      const token = el.tokenInput.value.trim();
      if (!token) return;
      fetchJSON(`/api/projects/${project.id}/unlock`, {
        method: 'POST',
        body: JSON.stringify({ token }),
      }).then(r => {
        if (r.ok) {
          setCookie('token_' + project.id, token, 30);
          cleanup();
          resolve(true);
          switchProject(project.id);
        } else {
          el.tokenError.textContent = 'Token incorrecto';
          el.tokenError.style.display = 'block';
        }
      }).catch(() => {
        el.tokenError.textContent = 'Error al validar token';
        el.tokenError.style.display = 'block';
      });
    }

    function onCancel() {
      cleanup();
      resolve(false);
      el.projectSelect.value = state.activeProject || state.projects[0]?.id || '';
    }

    function onKey(e) {
      if (e.key === 'Enter') onSubmit();
      if (e.key === 'Escape') onCancel();
    }

    el.tokenSubmit.addEventListener('click', onSubmit);
    el.tokenCancel.addEventListener('click', onCancel);
    el.tokenInput.addEventListener('keydown', onKey);
  });
}

// ── File list rendering ──

function groupFilesByDir(files) {
  const groups = {};
  const rootFiles = [];
  files.forEach(f => {
    const idx = f.path.indexOf('/');
    if (idx === -1) {
      rootFiles.push(f);
    } else {
      const dir = f.path.slice(0, idx);
      (groups[dir] ??= []).push(f);
    }
  });
  return { rootFiles, groups };
}

function renderFileList() {
  const query = (el.search.value || '').toLowerCase();
  let filtered = state.files;
  if (query) {
    filtered = state.files.filter(f =>
      f.name.toLowerCase().includes(query) || f.path.toLowerCase().includes(query)
    );
  }
  const { rootFiles, groups } = groupFilesByDir(filtered);
  el.fileList.innerHTML = '';

  rootFiles.forEach(f => el.fileList.appendChild(createFileItem(f)));
  Object.keys(groups).sort().forEach(dir => {
    const h = document.createElement('div');
    h.className = 'folder-header';
    h.textContent = dir;
    el.fileList.appendChild(h);
    groups[dir].forEach(f => el.fileList.appendChild(createFileItem(f)));
  });
}

function createFileItem(file) {
  const div = document.createElement('div');
  div.className = 'file-item' + (file.path === state.selectedPath ? ' active' : '');
  div.textContent = file.name;
  div.addEventListener('click', () => {
    loadFile(state.activeProject, file.path);
    history.pushState(
      { projectId: state.activeProject, filePath: file.path },
      '',
      buildURL(state.activeProject, file.path)
    );
  });
  return div;
}

// ── Download ──

let _currentFileForDownload = null;

function reconstructMarkdown(file) {
  let md = '';
  const meta = file.metadata || {};
  const keys = Object.keys(meta);
  if (keys.length) {
    md += '---\n';
    keys.forEach(k => {
      const v = meta[k];
      if (Array.isArray(v)) {
        md += k + ':\n';
        v.forEach(item => md += '  - ' + item + '\n');
      } else {
        md += k + ': ' + v + '\n';
      }
    });
    md += '---\n\n';
  }
  md += file.body || '';
  return md;
}

function downloadCurrentFile() {
  if (!_currentFileForDownload) return;
  const f = _currentFileForDownload;
  const content = reconstructMarkdown(f);
  const blob = new Blob([content], { type: 'text/markdown;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = f.path;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

el.downloadBtn.addEventListener('click', downloadCurrentFile);

// ── Document rendering ──

function renderFile(file) {
  el.welcome.style.display = 'none';
  el.docViewer.style.display = 'block';
  const isMd = file.path.endsWith('.md') || file.path.endsWith('.markdown');
  el.downloadBtn.style.display = isMd ? '' : 'none';
  _currentFileForDownload = isMd ? file : null;
  const meta = file.metadata || {};
  const tags = meta.tags || [];
  let html = '';
  if (meta.date) html += `<span class="meta-date">${formatDate(meta.date)}</span>`;
  if (meta.author) html += `<span class="meta-tag">${escapeHTML(meta.author)}</span>`;
  tags.forEach(t => { html += `<span class="meta-tag">${escapeHTML(t)}</span>`; });
  el.docMeta.innerHTML = html;
  el.docBody.innerHTML = `<div id="doc-header"><h1>${escapeHTML(meta.title || file.name)}</h1></div>${file.html || ''}`;
}

// ── Link navigation ──

function resolvePath(base, rel) {
  const dir = base.includes('/') ? base.slice(0, base.lastIndexOf('/') + 1) : '';
  const parts = (dir + rel).split('/');
  const out = [];
  for (const p of parts) {
    if (p === '..') out.pop();
    else if (p !== '.') out.push(p);
  }
  return out.join('/');
}

el.docBody.addEventListener('click', (e) => {
  let a = e.target.closest('a');
  if (!a) return;
  const href = a.getAttribute('href');
  if (!href) return;
  if (/^(https?:|mailto:|#|\/\/)/i.test(href)) return;
  e.preventDefault();
  const path = resolvePath(state.currentFile?.path || '', href);
  loadFile(state.activeProject, path);
  history.pushState(
    { projectId: state.activeProject, filePath: path },
    '',
    buildURL(state.activeProject, path)
  );
});

function escapeHTML(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

function formatDate(date) {
  if (!date) return '';
  try { return new Date(date).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' }); }
  catch { return date; }
}

// ── Menu toggle ──

function openMenu() {
  state.menuOpen = true;
  el.menu.classList.remove('closed');
  el.menuToggle.classList.remove('closed');
}

function closeMenu() {
  state.menuOpen = false;
  el.menu.classList.add('closed');
  el.menuToggle.classList.add('closed');
}

function toggleMenu() {
  state.menuOpen ? closeMenu() : openMenu();
}



// ── Theme toggle ──

function setTheme(light) {
  document.documentElement.classList.toggle('light', light);
  el.themeToggle.textContent = light ? '☀️' : '🌙';
  localStorage.setItem('theme', light ? 'light' : 'dark');
}

el.themeToggle.addEventListener('click', () => {
  setTheme(!document.documentElement.classList.contains('light'));
});

// ── URL routing ──

function buildURL(projectId, filePath) {
  let url = '/' + projectId;
  if (filePath) {
    url += '/' + filePath.split('/').map(encodeURIComponent).join('/');
  }
  return url;
}

function parseURLPath() {
  let path = decodeURIComponent(location.pathname);
  if (path.startsWith('/')) path = path.slice(1);
  if (!path) return null;
  const parts = path.split('/');
  const projectId = parts[0];
  const filePath = parts.slice(1).join('/') || null;
  return { projectId, filePath };
}

window.addEventListener('popstate', () => {
  const parsed = parseURLPath();
  if (!parsed || !parsed.projectId) return;
  const project = state.projects.find(p => p.id === parsed.projectId);
  if (!project) return;
  el.projectSelect.value = parsed.projectId;
  switchProject(parsed.projectId);
  if (parsed.filePath) {
    loadFile(parsed.projectId, parsed.filePath);
  }
});

// ── Initialization ──

el.menuToggle.addEventListener('click', toggleMenu);
el.search.addEventListener('input', renderFileList);
el.projectSelect.addEventListener('change', async () => {
  if (!el.projectSelect.value) return;
  const id = el.projectSelect.value;
  await switchProject(id);
  if (state.files.length > 0) {
    loadFile(id, state.files[0].path);
  }
  history.pushState({ projectId: id }, '', buildURL(id));
});

(async function init() {
  const saved = localStorage.getItem('theme');
  if (saved === 'light') setTheme(true);

  await loadProjects();
  const parsed = parseURLPath();
  if (parsed && state.projects.find(p => p.id === parsed.projectId)) {
    el.projectSelect.value = parsed.projectId;
    await switchProject(parsed.projectId);
    if (parsed.filePath) {
      await loadFile(parsed.projectId, parsed.filePath);
    }
    history.replaceState(
      { projectId: parsed.projectId, filePath: parsed.filePath },
      '',
      buildURL(parsed.projectId, parsed.filePath)
    );
  } else {
    const first = state.projects[0];
    if (first) {
      el.projectSelect.value = first.id;
      await switchProject(first.id);
      if (state.files.length > 0) {
        await loadFile(first.id, state.files[0].path);
      }
      history.replaceState({ projectId: first.id }, '', buildURL(first.id));
    }
  }
  el.status.textContent = '● Listo';
})();
