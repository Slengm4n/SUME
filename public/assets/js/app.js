/* SUME — comportamentos da interface. Sem dependências. */
(() => {
  'use strict';

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  /* Avisos (toasts): fecham no X ou sozinhos após alguns segundos */
  $$('[data-toast]').forEach((toast) => {
    const close = () => {
      toast.classList.add('is-leaving');
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };
    $('[data-toast-close]', toast)?.addEventListener('click', close);
    if (!toast.classList.contains('toast--error')) setTimeout(close, 6000);
  });

  /* Confirmação antes de ações destrutivas: <button data-confirm="Tem certeza?"> */
  document.addEventListener('submit', (event) => {
    const button = event.submitter;
    const message = button?.dataset.confirm || event.target.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
  });

  /* Mostrar/ocultar senha */
  $$('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.togglePassword);
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Ocultar' : 'Mostrar';
      btn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
    });
  });

  /* Menus suspensos (<details data-dropdown>): fecham ao clicar fora ou com Esc */
  const dropdowns = $$('[data-dropdown]');
  document.addEventListener('click', (event) => {
    dropdowns.forEach((d) => { if (d.open && !d.contains(event.target)) d.open = false; });
  });
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    dropdowns.forEach((d) => {
      if (d.open) { d.open = false; $('summary', d)?.focus(); }
    });
  });

  /* Foto de perfil: envia assim que o arquivo é escolhido */
  const avatarInput = $('[data-avatar-input]');
  avatarInput?.addEventListener('change', () => {
    if (!avatarInput.files.length) return;
    if (avatarInput.files[0].size > 2 * 1024 * 1024) {
      window.alert('A imagem deve ter no máximo 2 MB.');
      avatarInput.value = '';
      return;
    }
    avatarInput.closest('form').submit();
  });

  /* Legenda das estrelas na avaliação */
  const caption = $('[data-rating-caption]');
  if (caption) {
    const labels = { 1: 'Muito ruim', 2: 'Ruim', 3: 'Ok', 4: 'Boa', 5: 'Excelente' };
    const update = () => {
      const checked = $('.rating input:checked');
      caption.textContent = checked ? labels[checked.value] : 'Toque nas estrelas';
    };
    $$('.rating input').forEach((r) => r.addEventListener('change', update));
    update();
  }

  /* Confirmações ao vivo (painel do administrador) */
  const live = $('[data-live-confirmations]');
  const endpoint = live?.dataset.liveConfirmations;
  if (live && endpoint) {
    const rows = $('[data-rows]', live);
    const total = $('[data-total]', live);
    const dish = $('[data-dish]', live);

    const avatarEl = (person) => {
      if (person.avatar) {
        const img = document.createElement('img');
        img.className = 'avatar avatar--sm';
        img.src = person.avatar;
        img.alt = '';
        return img;
      }
      const span = document.createElement('span');
      span.className = 'avatar avatar--sm';
      span.setAttribute('aria-hidden', 'true');
      span.textContent = person.initials;
      return span;
    };

    const cell = (content, className) => {
      const td = document.createElement('td');
      if (className) td.className = className;
      if (content instanceof Node) td.append(content); else td.textContent = content;
      return td;
    };

    const render = (data) => {
      dish.textContent = data.menu ? data.menu.dish : 'Nenhuma refeição cadastrada para esta data.';
      total.textContent = data.total;
      const forecast = $('[data-forecast]', live);
      if (forecast && data.forecast !== null) forecast.textContent = data.forecast;

      const fragment = document.createDocumentFragment();
      data.people.forEach((p) => {
        const tr = document.createElement('tr');
        const person = document.createElement('span');
        person.className = 'person';
        person.append(avatarEl(p), ' ', p.name);
        const check = document.createElement('span');
        check.className = 'checkbox-print';
        tr.append(cell(person), cell(p.rm || '—'), cell(p.time, 'num'), cell(check, 'print-only'));
        fragment.append(tr);
      });
      if (!data.people.length) {
        const tr = document.createElement('tr');
        const td = cell('Nenhuma confirmação ainda.', 'table__empty');
        td.colSpan = 3;
        tr.append(td);
        fragment.append(tr);
      }
      rows.replaceChildren(fragment);
    };

    const refresh = async () => {
      if (document.hidden) return;
      try {
        const res = await fetch(endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (res.ok) render(await res.json());
      } catch (_) { /* sem rede: tenta de novo no próximo ciclo */ }
    };

    setInterval(refresh, 10000);
    document.addEventListener('visibilitychange', refresh);
  }

  /* Imprimir / salvar como PDF */
  $$('[data-print]').forEach((btn) => btn.addEventListener('click', () => window.print()));

  /* Tooltip das barras do gráfico: data-tip="título|valor|detalhe" */
  const bars = $$('[data-tip]');
  if (bars.length) {
    const tip = document.createElement('div');
    tip.className = 'chart-tip';
    tip.setAttribute('role', 'tooltip');
    tip.hidden = true;
    document.body.append(tip);

    const show = (bar) => {
      const [title, value, detail] = bar.dataset.tip.split('|');
      tip.replaceChildren();
      [['chart-tip__title', title], ['chart-tip__value', value], ['chart-tip__detail', detail]].forEach(([cls, text]) => {
        if (!text) return;
        const el = document.createElement('span');
        el.className = cls;
        el.textContent = text;
        tip.append(el);
      });
      tip.hidden = false;
      const r = bar.getBoundingClientRect();
      const t = tip.getBoundingClientRect();
      const left = Math.min(window.innerWidth - t.width - 8, Math.max(8, r.left + r.width / 2 - t.width / 2));
      tip.style.left = `${left + window.scrollX}px`;
      tip.style.top = `${r.top + window.scrollY - t.height - 10}px`;
      bar.classList.add('is-hover');
    };
    const hide = (bar) => { tip.hidden = true; bar.classList.remove('is-hover'); };

    bars.forEach((bar) => {
      bar.addEventListener('mouseenter', () => show(bar));
      bar.addEventListener('focus', () => show(bar));
      bar.addEventListener('mouseleave', () => hide(bar));
      bar.addEventListener('blur', () => hide(bar));
    });
  }

  /* Prévia do % de desperdício ao registrar sobras */
  const wasteForm = $('[data-waste-form]');
  if (wasteForm) {
    const prepared = $('#prepared_portions', wasteForm);
    const leftover = $('#leftover_portions', wasteForm);
    const preview = $('[data-waste-preview]', wasteForm);
    const update = () => {
      const p = parseInt(prepared.value, 10);
      const l = parseInt(leftover.value, 10);
      if (!(p > 0) || Number.isNaN(l) || l < 0) { preview.textContent = ''; preview.className = 'waste-preview'; return; }
      if (l > p) { preview.textContent = 'As sobras estão maiores que o total preparado.'; preview.className = 'waste-preview is-bad'; return; }
      const rate = (l / p) * 100;
      preview.textContent = `${p - l} porções servidas · ${rate.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}% de desperdício`;
      preview.className = `waste-preview ${rate <= 10 ? 'is-good' : 'is-warn'}`;
    };
    [prepared, leftover].forEach((el) => el.addEventListener('input', update));
    update();
  }
})();
