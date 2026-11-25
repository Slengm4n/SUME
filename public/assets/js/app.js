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

  /* Confirmações e retirada (painel do administrador)
     - marca/desmarca retirada sem recarregar a página
     - busca por nome ou RM; Enter marca quando sobra um único aluno pendente
     - no dia de hoje, atualiza sozinha a cada 10s */
  const board = $('[data-confirmations]');
  if (board) {
    const endpoint = board.dataset.live;
    const pickupUrl = board.dataset.pickupUrl;
    const canPickup = board.dataset.canPickup === '1';
    const rows = $('[data-rows]', board);
    const search = $('[data-pickup-search]', board);
    const hint = $('[data-search-hint]', board);
    const csrf = $('input[name="_token"]')?.value || '';
    let busy = false;

    const normalize = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

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

    const cell = (content, className, label) => {
      const td = document.createElement('td');
      if (className) td.className = className;
      if (label) td.dataset.label = label;
      if (content instanceof Node) td.append(content); else td.textContent = content;
      return td;
    };

    const hidden = (name, value) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      return input;
    };

    const checkIcon = () => {
      const ns = 'http://www.w3.org/2000/svg';
      const svg = document.createElementNS(ns, 'svg');
      svg.setAttribute('class', 'icon');
      svg.setAttribute('viewBox', '0 0 24 24');
      svg.setAttribute('fill', 'none');
      svg.setAttribute('stroke', 'currentColor');
      svg.setAttribute('stroke-width', '2');
      svg.setAttribute('aria-hidden', 'true');
      const path = document.createElementNS(ns, 'path');
      path.setAttribute('d', 'M20 6 9 17l-5-5');
      svg.append(path);
      return svg;
    };

    const pickupForm = (menuId, p) => {
      const form = document.createElement('form');
      form.method = 'post';
      form.action = pickupUrl;
      form.className = 'inline-form';
      form.dataset.pickupForm = '';
      form.append(hidden('_token', csrf), hidden('menu_id', menuId), hidden('user_id', p.id), hidden('picked_up', p.pickedUp ? '0' : '1'));
      const btn = document.createElement('button');
      btn.type = 'submit';
      if (p.pickedUp) {
        btn.className = 'btn btn--done btn--sm';
        btn.title = 'Desmarcar retirada';
        btn.setAttribute('aria-label', `${p.name} retirou às ${p.pickedUp}. Desmarcar`);
        btn.append(checkIcon(), ` Retirou ${p.pickedUp}`);
        form.append(btn);
      } else {
        btn.className = 'btn btn--secondary btn--sm';
        btn.setAttribute('aria-label', `Marcar retirada de ${p.name}`);
        btn.textContent = 'Marcar retirada';
        const box = document.createElement('span');
        box.className = 'checkbox-print';
        form.append(btn, box);
      }
      return form;
    };

    /* Busca: esconde quem não bate com o texto (ignora acentos) */
    const applyFilter = () => {
      if (!search) return [];
      const q = normalize(search.value);
      const visible = [];
      $$('tr[data-search]', rows).forEach((tr) => {
        const match = !q || normalize(tr.dataset.search).includes(q);
        tr.hidden = !match;
        if (match) visible.push(tr);
      });
      if (hint) {
        const pending = visible.filter((tr) => !tr.hasAttribute('data-picked'));
        hint.textContent = !q ? 'Digite o RM e aperte Enter para marcar a retirada.'
          : !visible.length ? 'Ninguém confirmado com esse nome ou RM.'
          : pending.length === 1 ? 'Aperte Enter para marcar a retirada.'
          : `${visible.length} aluno(s) encontrado(s).`;
      }
      return visible;
    };

    const render = (data) => {
      $('[data-dish]', board).textContent = data.menu ? data.menu.dish : 'Nenhuma refeição cadastrada para esta data.';
      $$('[data-total]', board).forEach((el) => { el.textContent = data.total; });
      const picked = $('span[data-picked]', board);
      if (picked) picked.textContent = data.pickedUp;
      const bar = $('[data-progress]', board);
      if (bar) bar.style.width = `${data.total ? Math.round((data.pickedUp / data.total) * 100) : 0}%`;
      const forecast = $('[data-forecast]', board);
      if (forecast && data.forecast !== null) forecast.textContent = data.forecast;

      const fragment = document.createDocumentFragment();
      data.people.forEach((p) => {
        const tr = document.createElement('tr');
        tr.dataset.search = `${p.name} ${p.rm || ''}`.toLowerCase();
        tr.dataset.user = p.id;
        if (p.pickedUp) tr.dataset.picked = '';
        const person = document.createElement('span');
        person.className = 'person';
        person.append(avatarEl(p), ' ', p.name);
        const action = canPickup && data.menu ? pickupForm(data.menu.id, p) : '';
        tr.append(cell(person, '', 'Aluno'), cell(p.rm || '—', '', 'RM'), cell(p.time, 'num', 'Confirmou às'), cell(action, 'table__actions'));
        fragment.append(tr);
      });
      if (!data.people.length) {
        const tr = document.createElement('tr');
        const td = cell('Nenhuma confirmação ainda.', 'table__empty');
        td.colSpan = 4;
        tr.append(td);
        fragment.append(tr);
      }
      rows.replaceChildren(fragment);
      applyFilter();
    };

    /* Marcar/desmarcar sem recarregar (sem JS, o formulário funciona normalmente) */
    const submitPickup = async (form) => {
      const btn = $('button', form);
      btn.setAttribute('aria-busy', 'true');
      busy = true;
      try {
        const res = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
        });
        const data = await res.json().catch(() => null);
        if (res.ok && data) {
          render(data);
          return true;
        }
        window.alert(data?.error || 'Não foi possível registrar. Recarregue a página e tente de novo.');
      } catch (_) {
        window.alert('Sem conexão. Tente de novo.');
      } finally {
        busy = false;
        btn.removeAttribute('aria-busy');
      }
      return false;
    };

    rows.addEventListener('submit', (event) => {
      const form = event.target.closest('[data-pickup-form]');
      if (!form) return;
      event.preventDefault();
      submitPickup(form);
    });

    if (search) {
      search.addEventListener('input', applyFilter);
      search.addEventListener('keydown', async (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const pending = applyFilter().filter((tr) => !tr.hasAttribute('data-picked'));
        if (pending.length !== 1) return;
        if (await submitPickup($('[data-pickup-form]', pending[0]))) {
          search.value = '';
          applyFilter();
          search.focus();
        }
      });
    }

    if (endpoint) {
      const refresh = async () => {
        if (document.hidden || busy) return;
        try {
          const res = await fetch(endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
          if (res.ok && !busy) render(await res.json());
        } catch (_) { /* sem rede: tenta de novo no próximo ciclo */ }
      };
      setInterval(refresh, 10000);
      document.addEventListener('visibilitychange', refresh);
    }
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
