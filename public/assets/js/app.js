import { initThemeToggle } from './theme.js';
import { initSidebar } from './sidebar.js';
import { initMasks } from './masks.js';
import { initFinanceiroCharts } from './financeiro-charts.js';

const FLOWDESK_BASE = (window.FLOWDESK_BASE || '').replace(/\/$/, '');

function fdUrl(path) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${FLOWDESK_BASE}${normalizedPath}`;
}

document.addEventListener('DOMContentLoaded', () => {
  const safeInit = (label, fn) => {
    try {
      fn?.();
    } catch (error) {
      console.error(`FlowDesk init error: ${label}`, error);
    }
  };

  safeInit('theme', initThemeToggle);
  safeInit('sidebar', initSidebar);
  safeInit('masks', initMasks);
  safeInit('financeiro-charts', initFinanceiroCharts);

  const btnLogin = document.getElementById('btn-login');
  const btnCriar = document.getElementById('btn-criar');
  const formLogin = document.getElementById('form-login');
  const formCriar = document.getElementById('form-criar');
  const msgLogin = document.getElementById('msg-login');
  const msgCriar = document.getElementById('msg-criar-conta');
  const authTitle = document.getElementById('auth-title');
  const authSubtitle = document.getElementById('auth-subtitle');
  const authAnchors = document.querySelectorAll('[data-auth-target]');

  const stepIndicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
  const stepPanels = Array.from(document.querySelectorAll('[data-step-panel]'));
  const nextStepButtons = Array.from(document.querySelectorAll('[data-next-step]'));
  const prevStepButtons = Array.from(document.querySelectorAll('[data-prev-step]'));

  const signupFields = {
    nome: document.getElementById('register_nome'),
    email: document.getElementById('register_email'),
    senha: document.getElementById('register_senha'),
    confSenha: document.getElementById('register_conf_senha'),
    terms: document.getElementById('register_terms'),
  };

  const passwordStrength = {
    fill: document.getElementById('password-strength-fill'),
    label: document.getElementById('password-strength-label'),
    rules: {
      length: document.getElementById('password-rule-length'),
      number: document.getElementById('password-rule-number'),
      special: document.getElementById('password-rule-special'),
    },
  };

  function setFeedback(target, html) {
    if (target) {
      target.innerHTML = html;
    }
  }

  function clearFeedbacks() {
    setFeedback(msgLogin, '');
    setFeedback(msgCriar, '');
  }

  function getPasswordState(password) {
    const value = String(password || '');
    const checks = {
      length: value.length >= 8,
      number: /\d/.test(value),
      special: /[^a-zA-Z0-9]/.test(value),
    };

    const score = Object.values(checks).filter(Boolean).length;

    return { checks, score };
  }

  function updatePasswordStrength() {
    if (!signupFields.senha) return;

    const { checks, score } = getPasswordState(signupFields.senha.value);
    const level = Math.min(score, 3);
    const labels = {
      0: 'Senha fraca',
      1: 'Senha fraca',
      2: 'Senha media',
      3: 'Senha forte',
    };
    const widths = { 0: '8%', 1: '34%', 2: '68%', 3: '100%' };
    const colors = { 0: 'rgba(244, 63, 94, 0.4)', 1: '#f97316', 2: '#eab308', 3: '#22c55e' };

    if (passwordStrength.fill) {
      passwordStrength.fill.style.width = widths[level] || '8%';
      passwordStrength.fill.style.background = colors[level] || 'rgba(244, 63, 94, 0.4)';
    }

    if (passwordStrength.label) {
      passwordStrength.label.textContent = labels[level] || 'Senha fraca';
    }

    Object.entries(passwordStrength.rules).forEach(([key, item]) => {
      if (!item) return;

      const icon = item.querySelector('i');
      item.classList.remove('is-valid', 'is-invalid', 'is-pending');

      if (!signupFields.senha.value) {
        item.classList.add('is-pending');
        item.style.color = '';
        if (icon) icon.className = 'ri-checkbox-circle-line';
        return;
      }

      const isValid = Boolean(checks[key]);
      item.classList.add(isValid ? 'is-valid' : 'is-invalid');
      item.style.color = isValid ? '#22c55e' : '#f97316';
      if (icon) icon.className = isValid ? 'ri-checkbox-circle-fill' : 'ri-close-circle-line';
    });

    return { checks, score };
  }

  function activateLogin() {
    clearFeedbacks();

    btnLogin?.classList.add('is-active');
    btnCriar?.classList.remove('is-active');

    formLogin?.classList.remove('hidden');
    formCriar?.classList.add('hidden');

    if (authTitle) authTitle.textContent = 'Acesse sua conta';
    if (authSubtitle) authSubtitle.textContent = 'Entre para continuar sua operacao no FlowDesk.';
  }

  function activateSignup() {
    clearFeedbacks();

    btnCriar?.classList.add('is-active');
    btnLogin?.classList.remove('is-active');

    formCriar?.classList.remove('hidden');
    formLogin?.classList.add('hidden');

    if (authTitle) authTitle.textContent = 'Crie sua conta';
    if (authSubtitle) authSubtitle.textContent = 'Configure seu workspace em etapas curtas e entre no produto com mais clareza.';
  }

  function setSignupStep(step) {
    const normalized = Math.max(1, Math.min(3, Number(step) || 1));

    if (formCriar) {
      formCriar.dataset.step = String(normalized);
    }

    stepIndicators.forEach((button) => {
      const isActive = Number(button.dataset.stepIndicator) === normalized;
      button.classList.toggle('is-active', isActive);
    });

    stepPanels.forEach((panel) => {
      const isActive = Number(panel.dataset.stepPanel) === normalized;
      panel.classList.toggle('hidden', !isActive);
    });
  }

  function validateStep(step) {
    const failField = (field, message) => {
      if (!field) return false;
      field.setCustomValidity(message);
      field.reportValidity();
      field.focus();
      return false;
    };

    const clearField = (field) => {
      field?.setCustomValidity('');
    };

    if (step === 1) {
      clearField(signupFields.nome);

      if (!signupFields.nome || !signupFields.nome.value.trim()) {
        return failField(signupFields.nome, 'Informe seu nome para continuar.');
      }

      return true;
    }

    if (step === 2) {
      clearField(signupFields.email);

      if (!signupFields.email || !signupFields.email.value.trim()) {
        return failField(signupFields.email, 'Informe um e-mail valido para continuar.');
      }

      if (!signupFields.email.checkValidity()) {
        return failField(signupFields.email, 'Informe um e-mail valido para continuar.');
      }

      return true;
    }

    if (step === 3) {
      clearField(signupFields.senha);
      clearField(signupFields.confSenha);
      clearField(signupFields.terms);

      const passwordState = updatePasswordStrength();

      if (!signupFields.senha || !passwordState?.checks.length || !passwordState?.checks.number || !passwordState?.checks.special) {
        return failField(signupFields.senha, 'A senha precisa ter pelo menos 8 caracteres, 1 numero e 1 caractere especial.');
      }

      if (!signupFields.confSenha || !signupFields.confSenha.value) {
        return failField(signupFields.confSenha, 'Confirme sua senha para continuar.');
      }

      if (signupFields.senha.value !== signupFields.confSenha.value) {
        return failField(signupFields.confSenha, 'As senhas precisam ser iguais.');
      }

      if (!signupFields.terms?.checked) {
        return failField(signupFields.terms, 'Voce precisa aceitar os termos para criar a conta.');
      }

      return true;
    }

    return true;
  }

  btnLogin?.addEventListener('click', activateLogin);
  btnCriar?.addEventListener('click', () => {
    activateSignup();
    setSignupStep(1);
  });

  authAnchors.forEach((anchor) => {
    anchor.addEventListener('click', () => {
      if (anchor.dataset.authTarget === 'signup') {
        activateSignup();
        setSignupStep(1);
      } else {
        activateLogin();
      }

      document.getElementById('auth-shell')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });

  nextStepButtons.forEach((button) => {
    button.addEventListener('click', () => {
      if (!formCriar) return;
      const currentStep = Number(formCriar.dataset.step || '1');
      if (!validateStep(currentStep)) return;
      setSignupStep(Number(button.dataset.nextStep));
    });
  });

  stepIndicators.forEach((button) => {
    button.addEventListener('click', () => {
      if (!formCriar) return;

      const targetStep = Number(button.dataset.stepIndicator || '1');
      const currentStep = Number(formCriar.dataset.step || '1');

      if (targetStep > currentStep && !validateStep(currentStep)) {
        return;
      }

      setSignupStep(targetStep);
    });
  });

  prevStepButtons.forEach((button) => {
    button.addEventListener('click', () => {
      setSignupStep(Number(button.dataset.prevStep));
    });
  });

  if (btnLogin && btnCriar && formLogin && formCriar) {
    activateLogin();
    setSignupStep(1);
  }

  if (formCriar && stepPanels.length && (!btnLogin || !btnCriar)) {
    setSignupStep(Number(formCriar.dataset.step || '1'));
  }

  const revealElements = Array.from(document.querySelectorAll('[data-reveal]'));

  if (revealElements.length) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        revealObserver.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealElements.forEach((element, index) => {
      element.style.transitionDelay = `${Math.min(index * 45, 240)}ms`;
      revealObserver.observe(element);
    });
  }

  signupFields.senha?.addEventListener('input', updatePasswordStrength);
  updatePasswordStrength();

  if (formLogin) {
    formLogin.addEventListener('submit', () => {
      setFeedback(
        msgLogin,
        '<div class="fd-auth-flash fd-auth-flash-neutral">Validando acesso...</div>'
      );
    });
  }

  if (formCriar) {
    formCriar.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (!validateStep(3)) return;

      setFeedback(
        msgCriar,
        '<div class="fd-auth-flash fd-auth-flash-neutral">Criando sua conta...</div>'
      );

      try {
        const res = await fetch(fdUrl('/register'), {
          method: 'POST',
          body: new FormData(formCriar),
        });
        const data = await res.json();

        if (data.success) {
          setFeedback(
            msgCriar,
            `<div class="fd-auth-flash fd-auth-flash-success">${data.message}</div>`
          );
          formCriar.reset();
          setSignupStep(1);
          updatePasswordStrength();
          if (btnLogin && btnCriar && formLogin) {
            activateLogin();
          }
          return;
        }

        const erros = (data.errors || ['Erro desconhecido.'])
          .map((err) => `<li>${err}</li>`)
          .join('');

        setFeedback(
          msgCriar,
          `<div class="fd-auth-flash fd-auth-flash-danger"><ul>${erros}</ul></div>`
        );
      } catch {
        setFeedback(
          msgCriar,
          '<div class="fd-auth-flash fd-auth-flash-danger">Erro ao conectar ao servidor.</div>'
        );
      }
    });
  }
});
