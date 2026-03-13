<?php
if (session_status() !== PHP_SESSION_ACTIVE)
  session_start();
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['user_id'])) {
  header('Location: /index.php');
  exit;
}



$user_id = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT id, nome, email, foto_perfil
  FROM usuarios
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header('Location: /app/Controllers/AuthController.php?acao=logout');
  exit;
}

$avatar = $user['foto_perfil'] ?: '/assets/img/avatar.png';
?>

<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card  mb-3 py-5">
      <div class="card-body text-center">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="rounded-circle mb-3" width="96" height="96"
          style="object-fit:cover;">
        <h6 class="mb-0"><?= htmlspecialchars($user['nome']) ?></h6>
        <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
      </div>
    </div>
  </div>

  <div class="col-md-8 mx-auto">
    <div class="card ">
      <div class="card-body">
        <h6 class="mb-3">Configurações da conta</h6>

        <form action="/app/Controllers/AuthController.php?acao=updateProfile" method="post"
          enctype="multipart/form-data">
          <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">

          <div class="mb-3">
            <label class="form-label label-config small">Nome</label>
            <input type="text" name="nome" class="form-control card" value="<?= htmlspecialchars($user['nome']) ?>"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label label-config small">E-mail</label>
            <input type="email" name="email" class="form-control card" value="<?= htmlspecialchars($user['email']) ?>"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label label-config small">Nova senha</label>
            <input type="password" name="senha" class="form-control card" placeholder="Deixe em branco para manter a atual">
          </div>

          <div class="mb-3">
            <label class="form-label label-config small">Foto de perfil</label>
            <input type="file" name="foto_perfil" class="form-control card form-control-sm" accept="image/*">
            <small class="text-muted small">Opcional. JPG/PNG, até 2MB.</small>
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm">
              Salvar alterações
            </button>
          </div>
        </form>
        <div class="theme-picker d-flex gap-3">
          <label class="form-label label-config small">Temas:</label>
          <button type="button" class="theme-option" data-theme="dark" aria-label="Tema escuro">
          </button>

          <button type="button" class="theme-option" data-theme="light" aria-label="Tema claro">
          </button>

          <button type="button" class="theme-option" data-theme="modern">

          <!-- futuro: mais temas
  <button type="button"
          class="theme-option"
          data-theme="solar"
          aria-label="Tema solar">
  </button>
  -->
        </div>


      </div>
    </div>
  </div>
</div>