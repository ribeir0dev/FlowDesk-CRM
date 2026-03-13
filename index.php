<?php
    $pageTitle = "Login | FlowDesk";
    include __DIR__.'/inc/headers/login.php';
    require_once __DIR__.'/config/csrf.php';
    require_once __DIR__.'/config/db.php';


session_start();

// Se já estiver logado, manda direto para o painel
if (isset($_SESSION['user_id'])) {
    header('Location: /modules/painel.php');
    exit;
}

$pageTitle = "Login | FlowDesk";
include __DIR__ . '/inc/headers/login.php';
?>
   <div class="page-wrapper">
        <div class="row g-0">

            <!-- ESQUERDA -->
            <div class="col-lg-9 d-flex hero-section">
                <div class="position-relative vh-100 vw-100">
                </div>
            </div>
            <!-- DIREITA -->
            <div class="col-lg-3 d-flex align-items-center justify-content-center form-section min-vh-100">
                <div class="login-container">
                    <div class="form-side">
                        <div class="mb-4 text-center">
                            <a><img src="assets/img/icon.png" width="64" alt="FlowDesk Logo" class="mb-3" /> <span
                                    class="fs-3 text-light">FlowDesk</span></a>
                            <h5 class="mb-1">Bem-vindo(a) de volta, criativo!</h5>
                            <p class="text-secondary small mb-4">Estamos felizes em ver você novamente.</p>
                        </div>
                        <div class="super-tabs mb-3">
                            <button class="super-tab active" type="button" id="btn-login">Logar</button>
                            <button class="super-tab" type="button" id="btn-criar" disabled>Criar Conta</button>
                        </div>
                        <form id="form-login" method="POST" action="app/Controllers/AuthController.php?acao=login" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                            <div class="mb-3 input-group">
                                <input type="text" class="form-control" placeholder="Seu usuário ou email"
                                    name="user_or_email" required>
                                <span class="input-group-text bg-form"><i class="ri-user-line"></i></span>
                            </div>
                            <div class="mb-2 input-group">
                                <input type="password" class="form-control" placeholder="Senha" name="password"
                                    required>
                                <span class="input-group-text bg-form"><i class="ri-shield-keyhole-line"></i></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember"
                                        checked>
                                    <label class="form-check-label small text-light" for="rememberMe">Lembrar este
                                        dispositivo.</label>
                                </div>
                                <a href="#" class="small text-esqueci text-decoration-none">Esqueci minha senha</a>
                            </div>
                            <button type="submit" class="btn btn-entrar btn-lg w-100 rounded-pill mb-3">Entrar</button>
                        </form>

                        <div id="msg-login"></div>

                        <form id="form-criar" autocomplete="off" style="display:none">
                            <div class="mb-3 input-group">
                                <input type="text" class="form-control" placeholder="Usuário" name="nome" required>
                                <span class="input-group-text bg-form"><i class="ri-user-line"></i></span>
                            </div>
                            <div class="mb-3 input-group">
                                <input type="email" class="form-control" placeholder="Email" name="email" required>
                                <span class="input-group-text bg-form"><i class="ri-mail-fill"></i></span>
                            </div>
                            <div class="mb-2 input-group">
                                <input type="password" class="form-control" placeholder="Senha" name="senha" required>
                                <span class="input-group-text bg-form"><i class="ri-shield-keyhole-line"></i></span>
                            </div>
                            <div class="mb-2 input-group">
                                <input type="password" class="form-control" placeholder="Confirmar senha"
                                    name="conf_senha" required>
                                <span class="input-group-text bg-form"><i class="ri-shield-keyhole-line"></i></span>
                            </div>
                            <button type="submit" class="btn btn-entrar btn-lg w-100 rounded-pill mb-3">Criar
                                Conta</button>
                        </form>
                        <div id="msg-criar-conta"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
        include __DIR__.'/inc/footers/footer.php';
?>