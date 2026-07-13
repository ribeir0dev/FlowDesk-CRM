const root = document.querySelector('[data-proposal-builder]');

if (root) {
  const form = root.querySelector('[data-proposal-form]');
  const itemsContainer = root.querySelector('[data-proposal-items]');
  const itemTemplate = document.getElementById('proposalItemTemplate');
  const serviceInput = root.querySelector('[data-service-input]');
  const paymentInput = root.querySelector('[data-payment-input]');
  const installmentsPanel = root.querySelector('[data-installments-panel]');
  const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
  const services = {
    landing_page: 'Landing Page',
    configuracao: 'Configuração',
    stream_overlay: 'Stream Overlay',
    criativos: 'Criativos',
    identidade_visual: 'Identidade Visual',
    ecommerce: 'E-Commerce',
    manutencao: 'Manutenção',
  };

  const formatDate = (value) => {
    if (!value) return '-';
    const [year, month, day] = value.split('-');
    return `${day}/${month}/${year}`;
  };

  const reindexItems = () => {
    itemsContainer.querySelectorAll('[data-proposal-item]').forEach((row, index) => {
      row.querySelector('[data-item-description]').name = `itens[${index}][descricao]`;
      row.querySelector('[data-item-quantity]').name = `itens[${index}][quantidade]`;
      row.querySelector('[data-item-price]').name = `itens[${index}][valor_unitario]`;
      row.querySelector('[data-item-discount]').name = `itens[${index}][desconto_percentual]`;
    });
  };

  const updatePreview = () => {
    let gross = 0;
    let total = 0;
    const previewItems = root.querySelector('[data-preview-items]');
    previewItems.innerHTML = '';

    itemsContainer.querySelectorAll('[data-proposal-item]').forEach((row) => {
      const description = row.querySelector('[data-item-description]').value.trim() || 'Novo item';
      const quantity = Math.max(0, Number(row.querySelector('[data-item-quantity]').value) || 0);
      const price = Math.max(0, Number(row.querySelector('[data-item-price]').value) || 0);
      const discount = Math.min(100, Math.max(0, Number(row.querySelector('[data-item-discount]').value) || 0));
      const rawSubtotal = quantity * price;
      const subtotal = rawSubtotal * (1 - discount / 100);
      gross += rawSubtotal;
      total += subtotal;
      row.querySelector('[data-item-subtotal]').textContent = money.format(subtotal);

      const line = document.createElement('div');
      const label = document.createElement('span');
      const value = document.createElement('strong');
      label.textContent = `${quantity}x ${description}`;
      value.textContent = money.format(subtotal);
      line.append(label, value);
      previewItems.append(line);
    });

    const discountTotal = Math.max(0, gross - total);
    root.querySelector('[data-items-total]').textContent = money.format(total);
    root.querySelector('[data-preview-subtotal]').textContent = money.format(gross);
    root.querySelector('[data-preview-discount]').textContent = `- ${money.format(discountTotal)}`;
    root.querySelector('[data-preview-total]').textContent = money.format(total);

    const clientSelect = root.querySelector('[data-proposal-client]');
    root.querySelector('[data-preview-client]').textContent = clientSelect.selectedOptions[0]?.textContent.trim() || 'Selecione um cliente';
    root.querySelector('[data-preview-category]').textContent = services[serviceInput.value] || serviceInput.value;
    root.querySelector('[data-preview-validity]').textContent = formatDate(root.querySelector('[data-summary-validity]').value);
    root.querySelector('[data-preview-status]').textContent = root.querySelector('[data-summary-status]').value;
    root.querySelector('[data-preview-deadline]').textContent = `${root.querySelector('[data-summary-deadline]').value || 0} dias úteis`;
    root.querySelector('[data-preview-extra-total]').textContent = money.format(total);

    let paymentLabel = paymentInput.value;
    if (paymentLabel === '50/50') paymentLabel = '50% de Entrada + 50% na Entrega';
    if (paymentLabel === 'Parcelado') {
      paymentLabel = `${root.querySelector('[data-summary-installments]').value}x parcelado`;
    }
    root.querySelector('[data-preview-payment]').textContent = paymentLabel;
    root.querySelector('[data-preview-installment-label]').textContent = paymentInput.value === 'Parcelado'
      ? `${root.querySelector('[data-summary-installments]').value}x`
      : '1x';
  };

  const updateClientContext = () => {
    const select = root.querySelector('[data-proposal-client]');
    const selected = select.selectedOptions[0];
    const context = root.querySelector('[data-proposal-client-context]');
    const email = selected?.dataset.email || '';
    const phone = selected?.dataset.phone || '';
    const photo = selected?.dataset.photo || '';
    const clientName = selected?.textContent.trim() || '';
    context.hidden = !email && !phone;
    root.querySelector('[data-client-contact-name]').value = clientName;
    root.querySelector('[data-client-email-field]').value = email;
    root.querySelector('[data-client-phone-field]').value = phone;
    root.querySelector('[data-preview-client-email]').textContent = email || 'E-mail não cadastrado';
    const avatar = root.querySelector('[data-preview-avatar]');
    avatar.innerHTML = photo ? `<img src="${photo}" alt="">` : '<i class="ri-user-line"></i>';
    context.querySelector('[data-client-email]').textContent = email || 'E-mail não cadastrado';
    context.querySelector('[data-client-phone]').textContent = phone || 'Telefone não cadastrado';
    updatePreview();
  };

  root.querySelector('[data-add-proposal-item]').addEventListener('click', () => {
    itemsContainer.append(itemTemplate.content.cloneNode(true));
    reindexItems();
    updatePreview();
  });

  itemsContainer.addEventListener('click', (event) => {
    const remove = event.target.closest('[data-remove-proposal-item]');
    if (!remove) return;
    if (itemsContainer.querySelectorAll('[data-proposal-item]').length <= 1) return;
    remove.closest('[data-proposal-item]').remove();
    reindexItems();
    updatePreview();
  });

  root.querySelectorAll('[data-service-options] button').forEach((button) => {
    button.addEventListener('click', () => {
      root.querySelectorAll('[data-service-options] button').forEach((item) => item.classList.remove('is-active'));
      button.classList.add('is-active');
      serviceInput.value = button.dataset.value;
      updatePreview();
    });
  });

  root.querySelectorAll('[data-payment-options] button').forEach((button) => {
    button.addEventListener('click', () => {
      root.querySelectorAll('[data-payment-options] button').forEach((item) => item.classList.remove('is-active'));
      button.classList.add('is-active');
      paymentInput.value = button.dataset.value;
      installmentsPanel.hidden = button.dataset.value !== 'Parcelado';
      updatePreview();
    });
  });

  form.addEventListener('input', updatePreview);
  form.addEventListener('change', (event) => {
    if (event.target.matches('[data-proposal-client]')) updateClientContext();
    updatePreview();
  });

  reindexItems();
  updateClientContext();
  updatePreview();
}
